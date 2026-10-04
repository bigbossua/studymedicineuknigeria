<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\Applications\DocumentCatalogue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

/**
 * Secure upload pipeline — docs/architecture/14.4.
 * Validates by content (not extension), re-encodes images (strips metadata), rejects active PDFs,
 * scans with ClamAV when available, encrypts sensitive types, stores under a UUID name on the private disk.
 */
class DocumentStore
{
    public const ENCRYPTED_TYPES = ['PASSPORT', 'FINANCIAL'];

    public function store(Document $doc, UploadedFile $file, int $userId): DocumentVersion
    {
        $allowed = DocumentCatalogue::allowedMimes($doc->code);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) ?: 'application/octet-stream';
        if (! in_array($mime, $allowed, true)) {
            throw ValidationException::withMessages(['file' => 'This file type is not accepted for this document. Allowed: '.implode(', ', DocumentCatalogue::TYPES[$doc->code]['formats'] ?? ['pdf']).'.']);
        }
        $ext = array_search([$mime], DocumentCatalogue::MIME, true) ?: ($mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/png' ? 'png' : ($mime === 'application/pdf' ? 'pdf' : 'docx')));
        $max = DocumentCatalogue::MAX_BYTES[$ext] ?? 10 * 1024 * 1024;
        if ($file->getSize() > $max) {
            throw ValidationException::withMessages(['file' => 'The file is larger than '.round($max / 1048576).' MB. Please compress it or upload a smaller scan.']);
        }

        $bytes = file_get_contents($file->getRealPath());
        if ($ext === 'docx') {
            $this->assertNoMacros($file->getRealPath());
        }
        if ($mime === 'application/pdf') {
            $this->assertSafePdf($bytes);
        }
        if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
            $bytes = $this->reencodeImage($bytes, $mime);
        }

        $scan = $this->scan($file->getRealPath());
        if ($scan === 'infected') {
            throw ValidationException::withMessages(['file' => 'This file failed our security scan and was not stored. Please upload a fresh scan or photograph of the document.']);
        }

        $sha = hash('sha256', $bytes);
        $encrypt = in_array($doc->code, self::ENCRYPTED_TYPES, true);
        $payload = $encrypt ? Crypt::encrypt($bytes, false) : $bytes;
        $version = ($doc->versions()->max('version') ?? 0) + 1;
        $path = sprintf('applications/%s/%s/v%d-%s.%s%s', $doc->application->application_number, $doc->code, $version, Str::uuid(), $ext, $encrypt ? '.enc' : '');
        Storage::disk('private')->put($path, $payload);

        $v = $doc->versions()->create([
            'version' => $version, 'disk' => 'private', 'path' => $path, 'original_filename' => Str::limit($file->getClientOriginalName(), 180, ''),
            'mime' => $mime, 'size_bytes' => strlen($bytes), 'sha256' => $sha, 'scan_status' => $scan, 'encrypted' => $encrypt,
            'key_id' => $encrypt ? 'app-key-v1' : null, 'uploaded_by' => $userId,
        ]);
        $doc->forceFill(['current_version_id' => $v->id])->save();
        $doc->transition(DocumentStatus::UNDER_REVIEW, $userId, null);

        return $v;
    }

    public function contents(DocumentVersion $v): string
    {
        abort_if($v->purged_at !== null, 410, 'This file was deleted under the retention policy.');
        $raw = Storage::disk($v->disk)->get($v->path);

        return $v->encrypted ? Crypt::decrypt($raw, false) : $raw;
    }

    /** A .docm renamed to .docx carries the same MIME type; refuse any Office package with VBA or macro-enabled content types. */
    private function assertNoMacros(string $path): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'This Word file could not be read. Please save it again as .docx or export it as PDF.']);
        }
        $types = (string) $zip->getFromName('[Content_Types].xml');
        $hasVba = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_contains(strtolower((string) $zip->getNameIndex($i)), 'vbaproject')) {
                $hasVba = true;
            }
        }
        $zip->close();
        if ($hasVba || stripos($types, 'macroEnabled') !== false) {
            throw ValidationException::withMessages(['file' => 'Word files containing macros are not accepted. Please export the document as PDF.']);
        }
    }

    /**
     * Refuses PDFs that carry scripts, launch actions, embedded files or encryption. Looks at the raw
     * bytes, at every stream the file can inflate (object streams hide dictionaries from a plain scan)
     * and at name-escaped spellings such as /J#61vaScript.
     */
    private function assertSafePdf(string $bytes): void
    {
        if (! str_starts_with($bytes, '%PDF')) {
            throw ValidationException::withMessages(['file' => 'This does not appear to be a valid PDF.']);
        }
        $haystacks = [$bytes];
        // Inflate incrementally with caps so a small upload cannot expand to gigabytes (security audit 2026-10-04):
        // 16 MB per stream, 64 MB in total. Object streams hide dictionaries, so one that does not fit is refused.
        $budget = 64 * 1024 * 1024;
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $bytes, $m, PREG_OFFSET_CAPTURE)) {
            foreach (array_slice($m[1], 0, 2000) as [$stream, $offset]) {
                $isObjectStream = str_contains(substr($bytes, max(0, $offset - 600), min(600, $offset)), '/ObjStm');
                [$inflated, $complete] = self::inflateCapped($stream, min(16 * 1024 * 1024, $budget));
                if ($inflated !== '') {
                    $budget -= strlen($inflated);
                    $haystacks[] = $inflated;
                }
                if ($isObjectStream && ! $complete) {
                    throw ValidationException::withMessages(['file' => 'This PDF is too complex to check. Please export a plain PDF (for example, "Print to PDF").']);
                }
                if ($budget <= 0) {
                    break;
                }
            }
        }
        $needles = ['/JavaScript', '/JS', '/Launch', '/EmbeddedFile', '/OpenAction', '/AA', '/Encrypt', '/RichMedia', '/XFA'];
        foreach ($haystacks as $h) {
            // decode #xx name escapes so /J#61vaScript reads as /JavaScript
            $decoded = preg_replace_callback('/#([0-9A-Fa-f]{2})/', fn ($x) => chr(hexdec($x[1])), $h) ?? $h;
            foreach ($needles as $needle) {
                if (preg_match('#'.preg_quote($needle, '#').'(?![A-Za-z])#', $decoded)) {
                    throw ValidationException::withMessages(['file' => 'PDFs with scripts, embedded files, forms or encryption are not accepted. Please export a plain PDF (for example, "Print to PDF").']);
                }
            }
        }
    }

    /**
     * Inflate zlib or raw-deflate data up to $cap bytes. Returns [output, complete]; output is '' when the data is not
     * compressed at all (images, plain streams). Never allocates more than $cap.
     */
    private static function inflateCapped(string $data, int $cap): array
    {
        foreach ([[ZLIB_ENCODING_DEFLATE, $data], [ZLIB_ENCODING_RAW, $data], [ZLIB_ENCODING_RAW, substr($data, 2)]] as [$encoding, $input]) {
            $ctx = @inflate_init($encoding);
            if ($ctx === false || $input === '') {
                continue;
            }
            $out = '';
            $ok = true;
            foreach (str_split($input, 8192) as $chunk) {
                $piece = @inflate_add($ctx, $chunk, ZLIB_SYNC_FLUSH);
                if ($piece === false) {
                    $ok = false;
                    break;
                }
                $out .= $piece;
                if (strlen($out) >= $cap) {
                    return [substr($out, 0, $cap), false];
                }
            }
            if ($ok && $out !== '') {
                $status = @inflate_get_status($ctx);

                return [$out, $status === ZLIB_STREAM_END];
            }
        }

        return ['', true];
    }

    private function reencodeImage(string $bytes, string $mime): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $bytes;
        } // GD unavailable: store as-is (flagged in DPIA)
        // Read the declared size before decoding: a small PNG can declare 20,000 × 20,000 pixels and exhaust memory.
        $size = @getimagesizefromstring($bytes);
        if (! $size || $size[0] * $size[1] > 40_000_000) {
            throw ValidationException::withMessages(['file' => $size ? 'The image is too large. Please upload a photo or scan under 40 megapixels.' : 'The image could not be read. Please upload a JPG or PNG.']);
        }
        $img = @imagecreatefromstring($bytes);
        if (! $img) {
            throw ValidationException::withMessages(['file' => 'The image could not be read. Please upload a JPG or PNG.']);
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 600 || $h < 600) {
            imagedestroy($img);
            throw ValidationException::withMessages(['file' => 'The image is too small to read. Please upload a clearer scan or photo (at least 600 pixels on each side).']);
        }
        $max = 3000;
        if ($w > $max || $h > $max) {
            $scale = $max / max($w, $h);
            $nw = (int) ($w * $scale);
            $nh = (int) ($h * $scale);
            $dst = imagecreatetruecolor($nw, $nh);
            if ($mime === 'image/png') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $dst;
        }
        ob_start();
        $mime === 'image/png' ? imagepng($img, null, 6) : imagejpeg($img, null, 88);
        imagedestroy($img);

        return ob_get_clean();
    }

    private function scan(string $path): string
    {
        $bin = trim((string) shell_exec('command -v clamdscan 2>/dev/null')) ?: trim((string) shell_exec('command -v clamscan 2>/dev/null'));
        if (! $bin) {
            return 'unavailable';
        }
        $p = new Process([$bin, '--no-summary', $path]);
        $p->setTimeout(60);
        try {
            $p->run();
        } catch (\Throwable) {
            return 'unavailable';
        }

        return match ($p->getExitCode()) {
            0 => 'clean', 1 => 'infected', default => 'unavailable'
        };
    }
}
