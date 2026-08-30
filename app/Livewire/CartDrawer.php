<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public string $couponCode = '';

    public string $couponMessage = '';

    public bool $couponSuccess = false;

    #[On('cart-updated')]
    public function refreshCart(): void {}

    #[On('cart-added')]
    public function openAfterAdd(): void
    {
        $this->dispatch('open-cart-drawer');
    }

    public function increment(string $key, CartService $cart): void
    {
        $row = collect($cart->items())->first(fn ($item) => $item['key'] === $key);

        if ($row) {
            [$productId, $variantId] = $this->splitKey($key);
            $cart->setQty($productId, $variantId, $row['qty'] + 1);
            $this->refreshCouponAfterChange($cart);
            $this->dispatch('cart-updated');
        }
    }

    public function decrement(string $key, CartService $cart): void
    {
        $row = collect($cart->items())->first(fn ($item) => $item['key'] === $key);

        if ($row) {
            [$productId, $variantId] = $this->splitKey($key);
            $cart->setQty($productId, $variantId, $row['qty'] - 1);
            $this->refreshCouponAfterChange($cart);
            $this->dispatch('cart-updated');
        }
    }

    public function removeItem(string $key, CartService $cart): void
    {
        [$productId, $variantId] = $this->splitKey($key);
        $cart->remove($productId, $variantId);
        $this->refreshCouponAfterChange($cart);
        $this->dispatch('cart-updated');
    }

    public function applyCoupon(CartService $cart): void
    {
        if (trim($this->couponCode) === '') {
            return;
        }

        $this->couponSuccess = $cart->setCoupon($this->couponCode);
        $this->couponMessage = $this->couponSuccess
            ? 'Kupon berhasil dipakai.'
            : 'Kupon tidak valid atau minimum belanja belum terpenuhi.';
    }

    public function removeCoupon(CartService $cart): void
    {
        $cart->removeCoupon();
        $this->couponCode = '';
        $this->couponMessage = '';
    }

    protected function refreshCouponAfterChange(CartService $cart): void
    {
        if ($cart->coupon()) {
            $cart->setCoupon($cart->coupon()->code);
        }
    }

    protected function splitKey(string $key): array
    {
        [$productId, $variantId] = array_pad(explode(':', $key), 2, null);

        return [(int) $productId, $variantId !== null && $variantId !== '' ? (int) $variantId : null];
    }

    public function render(CartService $cart)
    {
        return view('livewire.cart-drawer', [
            'items' => $cart->items(),
            'count' => $cart->count(),
            'subtotal' => $cart->subtotal(),
            'discount' => $cart->discount(),
            'coupon' => $cart->coupon(),
        ]);
    }
}
