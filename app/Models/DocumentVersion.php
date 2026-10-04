<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['uploaded_at' => 'datetime', 'encrypted' => 'boolean', 'purged_at' => 'datetime'];

    /** Download name built from the record, never from the uploader's filename. */
    public function safeFilename(): string
    {
        $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'][$this->mime] ?? 'bin';
        $code = strtolower((string) ($this->document?->code ?? 'document'));

        return "{$code}-v{$this->version}.{$ext}";
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
