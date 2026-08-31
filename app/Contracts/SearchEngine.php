<?php

namespace App\Contracts;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Search abstraction. The default driver is SQL LIKE (zero infra); swap the
 * binding in AppServiceProvider for Meilisearch/Typesense/Algolia later.
 */
interface SearchEngine
{
    /**
     * Product search for catalog pages.
     *
     * @return Builder<Product>
     */
    public function products(string $term);

    /**
     * Lightweight predictive suggestions for the header search box.
     *
     * @return array{products: Collection, categories: Collection, brands: Collection}
     */
    public function suggest(string $term, int $limit = 6): array;
}
