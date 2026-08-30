<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        return \Illuminate\Support\Facades\Storage::disk($this->disk)->url($this->path);
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
