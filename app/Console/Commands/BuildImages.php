<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Builds responsive, compressed derivatives for the photographs listed in brand/photos/manifest.json:
 * WebP + JPEG at three widths each, plus a tiny blurred placeholder, into public/images/photos/.
 * The manifest (slug, alt, credit, source_url, licence, focal point) is copied for <x-photo> to read.
 * Originals never ship: public/ only receives the derivatives.
 */
class BuildImages extends Command
{
    protected $signature = 'smukn:images {--source=brand/photos : folder with manifest.json and originals} {--dest=public/images/photos : output folder} {--force : rebuild even if derivatives exist}';

    protected $description = 'Generate responsive WebP/JPEG derivatives for site photographs from brand/photos';

    public const WIDTHS = [480, 960, 1440];

    public function handle(): int
    {
        $src = base_path($this->option('source'));
        $dest = base_path($this->option('dest'));
        $manifestPath = "$src/manifest.json";
        if (! is_file($manifestPath)) {
            $this->error("No manifest at $manifestPath");

            return self::FAILURE;
        }
        $manifest = json_decode(File::get($manifestPath), true);
        $photos = $manifest['photos'] ?? [];
        File::ensureDirectoryExists($dest);
        $built = [];
        foreach ($photos as $p) {
            foreach (['slug', 'alt', 'licence', 'source_url'] as $k) {
                if (empty($p[$k])) {
                    $this->warn("skipping entry without {$k}: ".json_encode($p));

                    continue 2;
                }
            }
            $original = collect(['jpg', 'jpeg', 'png'])->map(fn ($e) => "$src/{$p['slug']}.$e")->first(fn ($f) => is_file($f));
            if (! $original) {
                $this->warn("no original for {$p['slug']} (expected {$p['slug']}.jpg or .png in $src)");

                continue;
            }
            $img = str_ends_with(strtolower($original), '.png') ? imagecreatefrompng($original) : imagecreatefromjpeg($original);
            if (! $img) {
                $this->warn("could not read {$original}");

                continue;
            }
            $w = imagesx($img);
            $h = imagesy($img);
            $entry = ['slug' => $p['slug'], 'alt' => $p['alt'], 'credit' => $p['credit'] ?? null, 'source_url' => $p['source_url'], 'licence' => $p['licence'], 'page' => $p['page'] ?? null, 'intended_use' => $p['intended_use'] ?? null, 'width' => $w, 'height' => $h, 'focal' => $p['focal'] ?? 'center', 'sizes' => []];
            foreach (self::WIDTHS as $tw) {
                if ($tw > $w) {
                    continue;
                }
                $th = (int) round($h * $tw / $w);
                $jpgPath = "$dest/{$p['slug']}-{$tw}.jpg";
                $webpPath = "$dest/{$p['slug']}-{$tw}.webp";
                if (! $this->option('force') && is_file($jpgPath) && is_file($webpPath)) {
                    $entry['sizes'][] = $tw;

                    continue;
                }
                $resized = imagecreatetruecolor($tw, $th);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
                imageinterlace($resized, true);
                imagejpeg($resized, $jpgPath, 78);
                imagewebp($resized, $webpPath, 74);
                imagedestroy($resized);
                $entry['sizes'][] = $tw;
            }
            // 24px blurred placeholder as an inline data URI so the layout never jumps or flashes white
            $pw = 24;
            $ph = max(1, (int) round($h * $pw / $w));
            $tiny = imagecreatetruecolor($pw, $ph);
            imagecopyresampled($tiny, $img, 0, 0, 0, 0, $pw, $ph, $w, $h);
            ob_start();
            imagejpeg($tiny, null, 40);
            $entry['placeholder'] = 'data:image/jpeg;base64,'.base64_encode(ob_get_clean());
            imagedestroy($tiny);
            imagedestroy($img);
            $built[$p['slug']] = $entry;
            $this->line("built {$p['slug']}: ".implode(', ', $entry['sizes']).' px');
        }
        File::put("$dest/manifest.json", json_encode(['generated_at' => now()->toIso8601String(), 'photos' => $built], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info(count($built).' photograph(s) ready in '.$this->option('dest'));

        return self::SUCCESS;
    }
}
