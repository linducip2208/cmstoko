<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * On-demand responsive image derivatives.
 *
 * Originals are NEVER modified. Variants are generated lazily on first
 * request and cached on the public disk, keyed by width (WebP when GD
 * supports it, original format otherwise). Missing/corrupt sources
 * degrade gracefully to the original URL.
 */
class ImagePipeline
{
    /** @var list<int> responsive widths, ascending */
    public const WIDTHS = [320, 480, 640, 960, 1280];

    public const THUMB = 160;

    /**
     * Generate (if missing) and return the variant URL for a width.
     * Fallback: original URL on any failure.
     */
    public function variant(Media $media, int $width): string
    {
        try {
            $disk = Storage::disk($media->disk);

            if (! $disk->exists($media->path)) {
                return $media->url();
            }

            // Raster formats only; SVG served as-is (already sanitized at upload).
            if ($media->mime === 'image/svg+xml' || $media->mime === 'image/gif') {
                return $media->url();
            }

            $useWebp = function_exists('imagewebp') && $media->mime !== 'image/png' || $media->mime === 'image/png';

            $ext = $useWebp ? 'webp' : $media->extension;
            $dir = dirname($media->path);
            $name = pathinfo($media->file_name, PATHINFO_FILENAME);

            $variantPath = "{$dir}/{$name}-{$width}w.{$ext}";

            if ($disk->exists($variantPath)) {
                return $disk->url($variantPath);
            }

            $binary = $disk->get($media->path);
            $image = @imagecreatefromstring($binary);

            if ($image === false) {
                return $media->url();
            }

            $srcW = imagesx($image);
            $srcH = imagesy($image);

            // Never upscale.
            if ($width >= $srcW) {
                imagedestroy($image);

                return $media->url();
            }

            $dstW = $width;
            $dstH = (int) round($srcH * $width / $srcW);

            $canvas = imagecreatetruecolor($dstW, $dstH);

            // Preserve alpha for PNG/WebP.
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);

            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

            $stream = fopen('php://temp', 'r+');

            if ($useWebp) {
                imagewebp($canvas, $stream, 82);
            } elseif ($media->mime === 'image/png') {
                imagesavealpha($canvas, true);
                imagepng($canvas, $stream, 6);
            } else {
                imagejpeg($canvas, $stream, 82);
            }

            rewind($stream);
            $disk->put($variantPath, $stream);
            fclose($stream);

            imagedestroy($image);
            imagedestroy($canvas);

            return $disk->url($variantPath);
        } catch (\Throwable) {
            return $media->url();
        }
    }

    /**
     * Thumbnail URL (fixed width).
     */
    public function thumb(Media $media): string
    {
        return $this->variant($media, self::THUMB);
    }

    /**
     * Full srcset string across configured widths (skips upscaled variants
     * by checking the source dimensions when known).
     *
     * @return string e.g. ".../img-320w.webp 320w, .../img-480w.webp 480w"
     */
    public function srcset(Media $media): string
    {
        $parts = [];

        $maxWidth = $media->width;

        foreach (self::WIDTHS as $width) {
            if ($maxWidth !== null && $width >= $maxWidth) {
                $parts[] = $this->variant($media, (int) $maxWidth)." {$maxWidth}w";
                break;
            }

            $parts[] = $this->variant($media, $width)." {$width}w";
        }

        // Always include the original as the largest candidate.
        $parts[] = $media->url()." {$maxWidth}w";

        return implode(', ', array_unique($parts));
    }
}
