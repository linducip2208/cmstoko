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

    public function updatedQty(int $itemId, int $qty, CartService $cart): void
    {
        $cart->setQty($itemId, $qty);
        $this->refreshCouponAfterChange($cart);
    }

    public function increment(int $productId, CartService $cart): void
    {
        $cart->add($productId, 1);
        $this->refreshCouponAfterChange($cart);
        $this->dispatch('cart-updated');
    }

    public function decrement(int $productId, CartService $cart): void
    {
        $items = collect($cart->items());
        $row = $items->first(fn ($item) => $item['product']->id === $productId);

        if ($row) {
            $cart->setQty($productId, $row['qty'] - 1);
            $this->refreshCouponAfterChange($cart);
            $this->dispatch('cart-updated');
        }
    }

    public function removeItem(int $productId, CartService $cart): void
    {
        $cart->remove($productId);
        $this->refreshCouponAfterChange($cart);
        $this->dispatch('cart-updated');
        $this->dispatch('cart-notify', message: 'Produk dihapus dari keranjang.', type: 'success');
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
