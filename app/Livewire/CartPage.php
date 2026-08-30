<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Keranjang - TokoKita')]
class CartPage extends Component
{
    public string $couponCode = '';

    public string $couponMessage = '';

    public bool $couponSuccess = false;

    #[On('cart-updated')]
    public function refreshCart(): void {}

    public function setQty(string $key, int $qty, CartService $cart): void
    {
        [$productId, $variantId] = $this->splitKey($key);
        $cart->setQty($productId, $variantId, $qty);
        $this->refreshCouponAfterChange($cart);
        $this->dispatch('cart-updated');
    }

    public function increment(string $key, CartService $cart): void
    {
        $items = collect($cart->items());
        $row = $items->first(fn ($item) => $item['key'] === $key);

        if ($row) {
            [$productId, $variantId] = $this->splitKey($key);
            $cart->setQty($productId, $variantId, $row['qty'] + 1);
            $this->refreshCouponAfterChange($cart);
            $this->dispatch('cart-updated');
        }
    }

    public function decrement(string $key, CartService $cart): void
    {
        $items = collect($cart->items());
        $row = $items->first(fn ($item) => $item['key'] === $key);

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
        return view('livewire.cart-page', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'discount' => $cart->discount(),
            'coupon' => $cart->coupon(),
            'weight' => $cart->weight(),
        ])->layout('components.layouts.app');
    }
}
