<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Resizes phone photos (often 4-8 MB) to a small square JPEG so lists, ID cards
 * and the scanner stay fast. Uses plain GD - no extra package, no version issues.
 */
class ImageUpload
{
    public static function square(UploadedFile $file, string $dir, int $size = 400): string
    {
        $bytes = @file_get_contents($file->getRealPath());
        $src   = $bytes ? @imagecreatefromstring($bytes) : false;

        if (!$src) {
            throw new \RuntimeException('That file is not a valid image.');
        }

        // centre-crop to a square and shrink in one step (keeps memory low)
        $w    = imagesx($src);
        $h    = imagesy($src);
        $side = min($w, $h);
        $dst  = imagecreatetruecolor($size, $size);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), $size, $size, $side, $side);
        imagedestroy($src);

        // phones store rotation in EXIF - fix it on the small image
        if (function_exists('exif_read_data') && in_array(strtolower((string) $file->getMimeType()), ['image/jpeg', 'image/jpg'], true)) {
            $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0 && ($rotated = imagerotate($dst, $angle, 0))) {
                imagedestroy($dst);
                $dst = $rotated;
            }
        }

        $abs = public_path($dir);
        if (!is_dir($abs)) {
            mkdir($abs, 0755, true);
        }

        $name = Str::random(20) . '.jpg';
        imagejpeg($dst, $abs . DIRECTORY_SEPARATOR . $name, 82);
        imagedestroy($dst);

        return trim($dir, '/') . '/' . $name;
    }

    public static function delete(?string $path): void
    {
        if ($path && is_file(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}
