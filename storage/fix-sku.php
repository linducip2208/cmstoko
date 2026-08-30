<?php

$p = 'resources/views/livewire/add-to-cart.blade.php';
$c = file_get_contents($p);

// Replace the corrupted fallback string inside sku ?? '...' with a clean em dash.
$c = preg_replace("/sku \?\? '[^']*'/u", "sku ?? '\u{2014}'", $c);

file_put_contents($p, $c);
echo "sku fallback fixed\n";
