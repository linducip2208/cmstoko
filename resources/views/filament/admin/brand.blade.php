<?php

use Illuminate\Support\Str;

$storeName = config('shop.name', 'TokoKita');
?>
<span class="fi-logo-mark flex items-center gap-2.5">
    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#9a4a2b] font-display text-lg font-bold text-white shadow-sm" aria-hidden="true">
        {{ mb_substr($storeName, 0, 1) }}
    </span>
    <span class="text-base font-bold tracking-tight text-gray-900 dark:text-gray-50">
        {{ $storeName }}
    </span>
</span>
