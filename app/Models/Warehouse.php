<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name', 'code', 'is_default', 'is_active'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        // Data integrity: only ONE default warehouse.
        static::saving(function (Warehouse $warehouse) {
            if ($warehouse->is_default) {
                static::query()->where('is_default', true)
                    ->when($warehouse->exists, fn ($q) => $q->whereKeyNot($warehouse->id))
                    ->update(['is_default' => false]);
            }
        });
    }

    public function levels()
    {
        return $this->hasMany(InventoryLevel::class);
    }
}
