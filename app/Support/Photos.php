<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/** Reads the generated photo manifest (public/images/photos/manifest.json) once per request. */
final class Photos
{
    private static ?array $manifest = null;

    public static function get(string $slug): ?array
    {
        if (self::$manifest === null) {
            $path = public_path('images/photos/manifest.json');
            self::$manifest = is_file($path) ? (json_decode(File::get($path), true)['photos'] ?? []) : [];
        }

        return self::$manifest[$slug] ?? null;
    }

    public static function reset(): void
    {
        self::$manifest = null;
    }
}
