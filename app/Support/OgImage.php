<?php

namespace App\Support;

/**
 * 1200×630 Open Graph card in the brand system: navy field, the symbol tile, a serif title wrapped to
 * three lines, a kicker and the domain. Pure GD with the self-hosted fonts (brand/fonts/ttf), so it
 * runs on shared hosting. Returns PNG bytes.
 */
final class OgImage
{
    public const W = 1200;

    public const H = 630;

    public static function render(string $title, string $kicker = 'Nigeria to the UK · Medicine', string $footer = 'studymedicineuknigeria.com · independent, evidence-led guidance'): string
    {
        $serif = base_path('brand/fonts/ttf/SourceSerif4-latin.ttf');
        $sans = base_path('brand/fonts/ttf/Inter-latin.ttf');
        $im = imagecreatetruecolor(self::W, self::H);
        imagealphablending($im, true);
        $navy = imagecolorallocate($im, 0x0B, 0x3D, 0x5C);
        $navyDeep = imagecolorallocate($im, 0x08, 0x2E, 0x46);
        $white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
        $mist = imagecolorallocate($im, 0xC9, 0xD6, 0xE2);
        $red = imagecolorallocate($im, 0xB4, 0x23, 0x1F);
        imagefilledrectangle($im, 0, 0, self::W, self::H, $navy);
        // quiet diagonal band (the brand's saltire reference) in a slightly deeper navy
        imagefilledpolygon($im, [self::W - 420, 0, self::W, 0, self::W, 300, self::W - 720, self::H, self::W - 1020, self::H], $navyDeep);

        // symbol tile
        $symbolPath = base_path('brand/logo/smukn-symbol-512.png');
        if (is_file($symbolPath) && ($symbol = imagecreatefrompng($symbolPath))) {
            imagecopyresampled($im, $symbol, 72, 64, 0, 0, 96, 96, imagesx($symbol), imagesy($symbol));
            imagedestroy($symbol);
        }
        imagettftext($im, 22, 0, 190, 102, $mist, $sans, 'STUDY MEDICINE UK NIGERIA');
        imagettftext($im, 20, 0, 190, 134, $mist, $sans, $kicker);

        // title: largest size whose wrapped text fits three lines inside the safe width
        $maxWidth = 1000;
        $lines = [];
        $size = 60;
        foreach ([60, 54, 48, 42, 38] as $size) {
            $lines = self::wrap($title, $serif, $size, $maxWidth);
            if (count($lines) <= 3) {
                break;
            }
        }
        $y = 290;
        foreach (array_slice($lines, 0, 3) as $i => $line) {
            if ($i === 2 && count($lines) > 3) {
                $line = rtrim($line, ' ,;:').'…';
            }
            imagettftext($im, $size, 0, 72, $y, $white, $serif, $line);
            $y += (int) round($size * 1.3);
        }

        // red point + footer
        imagefilledellipse($im, 80, self::H - 72, 14, 14, $red);
        imagettftext($im, 22, 0, 104, self::H - 64, $mist, $sans, $footer);

        ob_start();
        imagepng($im, null, 8);
        imagedestroy($im);

        return (string) ob_get_clean();
    }

    /** @return list<string> */
    private static function wrap(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $w) {
            $try = $current === '' ? $w : "$current $w";
            $box = imagettfbbox($size, 0, $font, $try);
            if (($box[2] - $box[0]) > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $w;
            } else {
                $current = $try;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }
}
