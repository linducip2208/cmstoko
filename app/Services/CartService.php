<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    public const SESSION_CART = 'shop.cart';

    public const SESSION_COUPON = 'shop.coupon';

    /**
     * Add a line. Key = product_id + optional variant, qty capped at 999.
     */
    public function add(int $productId, ?int $variantId = null, int $qty = 1): void
    {
        $cart = $this->raw();
        $key = $this->key($productId, $variantId);
        $cart[$key] = min(($cart[$key] ?? 0) + max(1, $qty), 999);
        Session::put(self::SESSION_CART, $cart);
    }

    public function setQty(int $productId, ?int $variantId, int $qty): void
    {
        $cart = $this->raw();

        if ($qty <= 0) {
            $this->remove($productId, $variantId);

            return;
        }

        $cart[$this->key($productId, $variantId)] = min($qty, 999);
        Session::put(self::SESSION_CART, $cart);
    }

    public function remove(int $productId, ?int $variantId = null): void
    {
        $cart = $this->raw();
        unset($cart[$this->key($productId, $variantId)]);
        Session::put(self::SESSION_CART, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_CART);
        $this->removeCoupon();
    }

    /**
     * @return Collection<int, array{key: string, product: Product, variant: ?ProductVariant, qty: int, price: int, subtotal: int}>
     */
    public function items(): Collection
    {
        $cart = $this->raw();

        if ($cart === []) {
            return collect();
        }

        $productIds = collect($cart)->keys()->map(fn (string $key) => (int) explode(':', $key)[0])->unique()->all();

        $products = Product::active()->with(['brand', 'category', 'variants.attributeValues.option'])->whereIn('id', $productIds)->get()->keyBy('id');

        $lines = collect();

        foreach ($cart as $key => $qty) {
            [$productId, $variantId] = array_pad(explode(':', $key), 2, null);
            $product = $products->get((int) $productId);

            if (! $product) {
                continue; // deleted product drops out of the cart silently
            }

            $variant = null;

            if ($variantId !== null && $variantId !== '' && $variantId !== '0') {
                $variant = $product->variants->firstWhere('id', (int) $variantId);
                $variant = $variant && $variant->is_active ? $variant : null;

                if (! $variant) {
                    continue; // variant no longer purchasable
                }
            }

            $price = $variant ? $variant->effectivePrice() : $product->effectivePrice();

            $lines->push([
                // Force string: PHP casts numeric-string array keys to int,
                // which breaks strict key comparisons in cart UIs.
                'key' => (string) $key,
                'product' => $product,
                'variant' => $variant,
                'qty' => (int) $qty,
                'price' => $price,
                'subtotal' => $price * (int) $qty,
            ]);
        }

        return $lines->values();
    }

    public function count(): int
    {
        return (int) array_sum($this->raw());
    }

    public function subtotal(): int
    {
        return (int) $this->items()->sum('subtotal');
    }

    public function setCoupon(string $code): bool
    {
        $coupon = Coupon::where('code', strtoupper(trim($code)))->first();

        if (! $coupon || ! $coupon->isUsable() || $coupon->discountFor($this->subtotal()) <= 0) {
            return false;
        }

        Session::put(self::SESSION_COUPON, $coupon->code);

        return true;
    }

    public function removeCoupon(): void
    {
        Session::forget(self::SESSION_COUPON);
    }

    public function coupon(): ?Coupon
    {
        $code = Session::get(self::SESSION_COUPON);

        if (! $code) {
            return null;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isUsable() || $coupon->discountFor($this->subtotal()) <= 0) {
            $this->removeCoupon();

            return null;
        }

        return $coupon;
    }

    public function discount(): int
    {
        return $this->coupon()?->discountFor($this->subtotal()) ?? 0;
    }

    public function weight(): int
    {
        return (int) $this->items()->sum(
            fn (array $item) => ($item['product']->requiresShipping() ? ($item['variant']->weight ?? $item['product']->weight) : 0) * $item['qty']
        );
    }

    /**
     * True when at least one line needs physical shipping. A fully digital
     * cart skips shipping selection entirely (cost 0).
     */
    public function requiresShipping(): bool
    {
        return $this->items()->contains(fn (array $item) => $item['product']->requiresShipping());
    }

    /**
     * @return array<string, int>
     */
    public function raw(): array
    {
        $cart = (array) Session::get(self::SESSION_CART, []);
        $out = [];

        foreach ($cart as $key => $qty) {
            $out[(string) $key] = max(1, (int) $qty);
        }

        return $out;
    }

    protected function key(int $productId, ?int $variantId): string
    {
        return $variantId ? "{$productId}:{$variantId}" : (string) $productId;
    }
}
