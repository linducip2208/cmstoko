<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_name', 'action', 'subject_type', 'subject_id',
        'before', 'after', 'ip', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            $log->created_at ??= now();
        });
    }

    public function subject()
    {
        return $this->subject_type ? $this->morphTo('subject') : null;
    }
}
