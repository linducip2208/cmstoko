<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const SUPER_ADMIN = 'super-admin';

    public const OWNER = 'owner';

    public const STORE_MANAGER = 'store-manager';

    public const CATALOG_MANAGER = 'catalog-manager';

    public const INVENTORY_STAFF = 'inventory-staff';

    public const ORDER_STAFF = 'order-staff';

    public const CONTENT_EDITOR = 'content-editor';

    public const MARKETING = 'marketing';

    public const FINANCE = 'finance';

    public const CUSTOMER_SUPPORT = 'customer-support';

    public const CUSTOMER = 'customer';

    public const STAFF_ROLES = [
        self::SUPER_ADMIN,
        self::OWNER,
        self::STORE_MANAGER,
        self::CATALOG_MANAGER,
        self::INVENTORY_STAFF,
        self::ORDER_STAFF,
        self::CONTENT_EDITOR,
        self::MARKETING,
        self::FINANCE,
        self::CUSTOMER_SUPPORT,
    ];

    protected $fillable = ['slug', 'name', 'description', 'is_system', 'is_staff', 'sort_order'];

    protected $casts = ['is_system' => 'boolean', 'is_staff' => 'boolean'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions->contains('slug', $permission);
    }
}
