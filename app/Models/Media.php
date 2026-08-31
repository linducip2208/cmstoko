<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $file_name
 * @property string $original_name
 * @property string $path
 * @property string $disk
 * @property string $mime
 * @property string $extension
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $title
 * @property string|null $alt
 * @property string|null $caption
 * @property int|null $uploaded_by
 * @property \Carbon\CarbonInterface $created_at
 * @property \Carbon\CarbonInterface $updated_at
 */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'file_name', 'original_name', 'path', 'disk', 'mime', 'extension',
        'size', 'width', 'height', 'title', 'alt', 'caption', 'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Safe, collision-proof storage name. Originals never touch the disk.
     */
    public static function safeFileName(string $original): string
    {
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $base = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'file';

        return $base.'-'.Str::lower(Str::random(10)).'.'.Str::limit($extension, 10, '');
    }

    /**
     * Whitelisted raster/vector image mimes (checked against REAL file content).
     */
    public const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];

    public const MAX_SIZE = 5 * 1024 * 1024; // 5 MB
}
