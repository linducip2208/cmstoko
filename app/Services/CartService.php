<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    public const SESSION_CART = 'shop.cart';

    public const SESSION_COUPON = 'shop.coupon';

    public function add(int $productId, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$productId] = min(($cart[$productId] ?? 0) + max(1, $qty), 999);
        Session::put(self::SESSION_CART, $cart);
    }

    public function setQty(int $productId, int $qty): void
    {
        $cart = $this->raw();

        if ($qty <= 0) {
            $this->remove($productId);

            return;
        }

        $cart[$productId] = min($qty, 999);
        Session::put(self::SESSION_CART, $cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        Session::put(self::SESSION_CART, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_CART);
        $this->removeCoupon();
    }

    public function items(): Collection
    {
        $cart = $this->raw();

        if ($cart === []) {
            return collect();
        }

        $products = Product::active()->whereIn('id', array_keys($cart))->get();

        return $products
            ->map(fn (Product $product) => [
                'product' => $product,
                'qty' => (int) $cart[$product->id],
                'price' => $product->effectivePrice(),
                'subtotal' => $product->effectivePrice() * (int) $cart[$product->id],
            ])
            ->values();
    }

    public function count(): int
    {
        return array_sum($this->raw());
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
        return (int) $this->items()->sum(fn (array $item) => $item['product']->weight * $item['qty']);
    }

    /**
     * @return array<int, int>
     */
    public function raw(): array
    {
        return array_map('intval', (array) Session::get(self::SESSION_CART, []));
    }
}
