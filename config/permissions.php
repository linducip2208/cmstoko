<?php

// Permission registry: group => [resource => [actions]]
// Every resource gets view/create/update/delete; extras are appended.
// Super Admin bypasses all gates. Roles are seeded from this map (see RbacSeeder).

use App\Models\Role;

return [
    'roles' => [
        Role::SUPER_ADMIN => 'Super Admin',
        Role::OWNER => 'Owner',
        Role::STORE_MANAGER => 'Store Manager',
        Role::CATALOG_MANAGER => 'Catalog Manager',
        Role::INVENTORY_STAFF => 'Inventory Staff',
        Role::ORDER_STAFF => 'Order Staff',
        Role::CONTENT_EDITOR => 'Content Editor',
        Role::MARKETING => 'Marketing',
        Role::FINANCE => 'Finance',
        Role::CUSTOMER_SUPPORT => 'Customer Support',
        Role::CUSTOMER => 'Customer',
    ],

    'resources' => [
        'dashboard' => ['view'],
        'products' => ['view', 'create', 'update', 'delete'],
        'categories' => ['view', 'create', 'update', 'delete'],
        'brands' => ['view', 'create', 'update', 'delete'],
        'attributes' => ['view', 'create', 'update', 'delete'],
        'collections' => ['view', 'create', 'update', 'delete'],
        'inventory' => ['view', 'update', 'adjust', 'transfer'],
        'orders' => ['view', 'update', 'cancel', 'refund'],
        'invoices' => ['view', 'create'],
        'shipments' => ['view', 'create'],
        'refunds' => ['view', 'create'],
        'returns' => ['view', 'update'],
        'customers' => ['view', 'update', 'delete'],
        'reviews' => ['view', 'update', 'delete'],
        'promotions' => ['view', 'create', 'update', 'delete'],
        'coupons' => ['view', 'create', 'update', 'delete'],
        'flash-sales' => ['view', 'create', 'update', 'delete'],
        'newsletter' => ['view'],
        'pages' => ['view', 'create', 'update', 'delete'],
        'blog' => ['view', 'create', 'update', 'delete'],
        'faqs' => ['view', 'create', 'update', 'delete'],
        'testimonials' => ['view', 'create', 'update', 'delete'],
        'banners' => ['view', 'create', 'update', 'delete'],
        'media' => ['view', 'create', 'update', 'delete'],
        'menus' => ['view', 'create', 'update', 'delete'],
        'appearance' => ['view', 'update'],
        'seo' => ['view', 'update'],
        'redirects' => ['view', 'create', 'update', 'delete'],
        'reports' => ['view'],
        'settings' => ['view', 'update'],
        'users' => ['view', 'create', 'update', 'delete'],
        'roles' => ['view', 'create', 'update', 'delete'],
        'audit-logs' => ['view'],
    ],

    // Role => list of resources granted (null = everything except users/roles/audit-logs + settings gated below)
    'role_grants' => [
        Role::SUPER_ADMIN => '*',
        Role::OWNER => '*',
        Role::STORE_MANAGER => [
            'dashboard', 'products', 'categories', 'brands', 'attributes', 'collections',
            'inventory', 'orders', 'invoices', 'shipments', 'refunds', 'returns', 'customers',
            'reviews', 'promotions', 'coupons', 'flash-sales', 'pages', 'blog', 'faqs',
            'testimonials', 'banners', 'media', 'menus', 'reports', 'redirects',
        ],
        Role::CATALOG_MANAGER => [
            'dashboard', 'products', 'categories', 'brands', 'attributes', 'collections', 'media', 'reports' => ['view'],
        ],
        Role::INVENTORY_STAFF => ['dashboard', 'products' => ['view', 'update'], 'inventory', 'reports' => ['view']],
        Role::ORDER_STAFF => ['dashboard', 'orders', 'invoices', 'shipments', 'returns' => ['view', 'update'], 'customers' => ['view', 'update'], 'reports' => ['view']],
        Role::CONTENT_EDITOR => ['dashboard', 'pages', 'blog', 'faqs', 'testimonials', 'banners', 'media', 'menus', 'seo' => ['view', 'update'], 'redirects', 'reports' => ['view']],
        Role::MARKETING => ['dashboard', 'promotions', 'coupons', 'flash-sales', 'newsletter', 'banners', 'collections' => ['view', 'update'], 'products' => ['view'], 'reports' => ['view']],
        Role::FINANCE => ['dashboard', 'orders' => ['view'], 'invoices' => ['view', 'create'], 'refunds', 'reports' => ['view']],
        Role::CUSTOMER_SUPPORT => ['dashboard', 'orders' => ['view', 'update'], 'returns' => ['view', 'update'], 'customers' => ['view', 'update'], 'reviews' => ['view', 'update', 'delete']],
    ],
];
