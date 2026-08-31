<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Cart rule = promotion applied server-side at checkout (never client-authored).
 *
 * Conditions (JSON, all optional, AND-combined):
 *  - min_subtotal / max_subtotal: int IDR
 *  - product_ids:   line must contain any of these products
 *  - category_ids:  any line's product category (or descendants) in list
 *  - brand_ids:     any line's product brand in list
 *  - quantity_min:  total cart quantity >= value
 *
 * Actions: percent (action_value 1-100) | fixed (IDR) | free_shipping.
 * All matching rules stack; total discount is capped at subtotal.
 */
class CartRule extends Model
{
    public const ACTION_PERCENT = 'percent';

    public const ACTION_FIXED = 'fixed';

    public const ACTION_FREE_SHIPPING = 'free_shipping';

    protected $fillable = [
        'name', 'description', 'action_type', 'action_value', 'customer_group_id',
        'conditions', 'priority', 'usage_limit', 'used_count', 'is_active',
        'starts_at', 'ends_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->orderByDesc('priority');
    }

    /**
     * Evaluate all active rules against the cart. Returns the aggregated effect.
     *
     * @return array{discount: int, free_shipping: bool, rules: array<int, array{name: string, discount: int, free_shipping: bool}>}
     */
    public static function evaluate(iterable $items, int $subtotal, ?User $user): array
    {
        $result = ['discount' => 0, 'free_shipping' => false, 'rule_ids' => [], 'rules' => []];

        $rules = static::query()->active()->with('customerGroup')->get();

        foreach ($rules as $rule) {
            if (! $rule->matches($items, $subtotal, $user)) {
                continue;
            }

            $discount = match ($rule->action_type) {
                self::ACTION_PERCENT => (int) round($subtotal * min(100, max(0, $rule->action_value)) / 100),
                self::ACTION_FIXED => min((int) $rule->action_value, $subtotal),
                self::ACTION_FREE_SHIPPING => 0,
                default => 0,
            };

            $isFreeShipping = $rule->action_type === self::ACTION_FREE_SHIPPING;

            if ($discount > 0 || $isFreeShipping) {
                $result['discount'] += $discount;
                $result['free_shipping'] = $result['free_shipping'] || $isFreeShipping;
                $result['rule_ids'][] = $rule->id;
                $result['rules'][] = [
                    'name' => $rule->name,
                    'discount' => $discount,
                    'free_shipping' => $isFreeShipping,
                ];
            }
        }

        $result['discount'] = min($result['discount'], $subtotal);

        return $result;
    }

    /**
     * Atomic usage increment; returns false when the quota ran out.
     */
    public static function consume(array $ruleIds): void
    {
        if ($ruleIds === []) {
            return;
        }

        CartRule::query()
            ->whereIn('id', $ruleIds)
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->increment('used_count');
    }

    /**
     * @param  iterable<array{product: Product, qty: int, price: int}>  $items
     */
    public function matches(iterable $items, int $subtotal, ?User $user): bool
    {
        $conditions = $this->conditions ?? [];

        // Customer group targeting: null group = guests; guests have no user.
        if ($this->customer_group_id) {
            $groupSlug = $user?->customer_group_id
                ? CustomerGroup::find($user->customer_group_id)?->slug
                : CustomerGroup::SLUG_GUEST;

            if ($this->customerGroup?->slug !== $groupSlug) {
                return false;
            }
        }

        if (isset($conditions['min_subtotal']) && $subtotal < (int) $conditions['min_subtotal']) {
            return false;
        }

        if (isset($conditions['max_subtotal']) && $subtotal > (int) $conditions['max_subtotal']) {
            return false;
        }

        $totalQty = 0;

        foreach ($items as $item) {
            $totalQty += (int) $item['qty'];
        }

        if (isset($conditions['quantity_min']) && $totalQty < (int) $conditions['quantity_min']) {
            return false;
        }

        if (! empty($conditions['product_ids']) || ! empty($conditions['category_ids']) || ! empty($conditions['brand_ids'])) {
            $productIds = array_map('intval', (array) ($conditions['product_ids'] ?? []));
            $categoryIds = array_map('intval', (array) ($conditions['category_ids'] ?? []));
            $brandIds = array_map('intval', (array) ($conditions['brand_ids'] ?? []));

            $matched = false;

            foreach ($items as $item) {
                $product = $item['product'];

                if ($productIds !== [] && in_array((int) $product->id, $productIds, true)) {
                    $matched = true;
                    break;
                }

                if ($brandIds !== [] && $product->brand_id && in_array((int) $product->brand_id, $brandIds, true)) {
                    $matched = true;
                    break;
                }

                if ($categoryIds !== [] && $product->category_id) {
                    // Walk the ancestor chain: a rule on "Parent" must match
                    // products inside "Parent > Child".
                    $categoryTree = [];
                    $category = Category::find((int) $product->category_id);

                    while ($category) {
                        $categoryTree[] = (int) $category->id;
                        $category = $category->parent_id ? Category::find((int) $category->parent_id) : null;
                    }

                    if (array_intersect($categoryIds, $categoryTree) !== []) {
                        $matched = true;
                        break;
                    }
                }
            }

            if (! $matched) {
                return false;
            }
        }

        return true;
    }

    protected static function booted(): void
    {
        //
    }
}
