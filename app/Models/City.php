<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    public $timestamps = false;

    protected $fillable = ['id', 'province_id', 'name', 'type', 'postal_code'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
