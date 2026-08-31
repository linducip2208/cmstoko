<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class MediaService
{
    /**
     * Store an uploaded image safely:
     * - real MIME inspection (not client-supplied), blocklist executables/scripts
     * - SVGs sanitized (script/on* stripped) before storage
     * - randomized safe file names, size + dimension capture
     */
    public function store(UploadedFile $file, int $userId): Media
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('Berkas tidak valid.');
        }

        if ($file->getSize() > Media::MAX_SIZE) {
            throw new InvalidArgumentException('Ukuran berkas maksimal 5 MB.');
        }

        $realMime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());

        if (! array_key_exists($realMime, Media::ALLOWED_MIMES)) {
            throw new InvalidArgumentException('Tipe berkas tidak diizinkan.');
        }

        // Defence in depth: block disguised executables by extension.
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'phps', 'exe', 'sh', 'bat', 'js', 'html', 'htm'], true)) {
            throw new InvalidArgumentException('Ekstensi berkas tidak diizinkan.');
        }

        $fileName = Media::safeFileName($file->getClientOriginalName());
        $directory = 'media/'.now()->format('Y/m');

        $binary = $file->getContent();

        if ($realMime === 'image/svg+xml') {
            $binary = $this->sanitizeSvg($binary);
        }

        Storage::disk('public')->put($directory.'/'.$fileName, $binary);

        [$width, $height] = $realMime === 'image/svg+xml'
            ? [null, null]
            : (@getimagesizefromstring($binary) ?: [null, null]);

        return Media::create([
            'file_name' => $fileName,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'path' => $directory.'/'.$fileName,
            'disk' => 'public',
            'mime' => $realMime,
            'extension' => $extension ?: Media::ALLOWED_MIMES[$realMime],
            'size' => strlen($binary),
            'width' => $width,
            'height' => $height,
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Delete only if the file is not referenced by catalog entities.
     */
    public function delete(Media $media): void
    {
        if ($this->isInUse($media)) {
            throw new InvalidArgumentException('Berkas masih dipakai oleh konten katalog. Lepaskan referensinya dulu.');
        }

        // Remove all pipeline derivatives along with the original.
        $pipeline = new ImagePipeline;
        $disk = Storage::disk($media->disk);
        $dir = dirname($media->path);
        $name = pathinfo($media->file_name, PATHINFO_FILENAME);

        $candidates = array_merge(
            [ImagePipeline::THUMB],
            ImagePipeline::WIDTHS,
            [$media->width ?? 0],
        );

        foreach ($candidates as $width) {
            if ($width <= 0) {
                continue;
            }

            foreach (['webp', $media->extension] as $ext) {
                $variantPath = "{$dir}/{$name}-{$width}w.{$ext}";

                if ($disk->exists($variantPath)) {
                    $disk->delete($variantPath);
                }
            }
        }

        $disk->delete($media->path);
        $media->delete();
    }

    /**
     * Usage awareness: cheap substring scan across image-bearing columns.
     * Matches on the unique stored file name (JSON encoding escapes slashes,
     * so full paths are unreliable for LIKE queries).
     */
    public function isInUse(Media $media): bool
    {
        $needle = $media->file_name;

        foreach (['images', 'seo', 'attribute_values'] as $column) {
            if (Product::query()->where($column, 'like', "%{$needle}%")->exists()) {
                return true;
            }
        }

        foreach (
            [
                [new Category, 'cover_image'],
                [new Category, 'icon'],
                [new Brand, 'logo'],
                [new Brand, 'cover'],
                [new BlogPost, 'cover'],
                [new CmsPage, 'featured_image'],
            ] as [$model, $column]
        ) {
            if ($model->newQuery()->where($column, 'like', "%{$needle}%")->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function sanitizeSvg(string $svg): string
    {
        $svg = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $svg);
        $svg = preg_replace('/\son\w+="[^"]*"/i', '', $svg);
        $svg = preg_replace("/\son\w+='[^']*'/i", '', $svg);
        $svg = preg_replace('/\son\w+=\S+/i', '', $svg);
        // Block external references that can leak user IPs or execute.
        $svg = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', '', $svg);

        return $svg;
    }
}
