<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Component;

class AddToCart extends Component
{
    public int $productId;

    public int $stock = 99;

    public int $qty = 1;

    public bool $compact = false;

    public function mount(int $productId, int $stock = 99, bool $compact = false): void
    {
        $this->productId = $productId;
        $this->stock = $stock;
        $this->compact = $compact;
    }

    public function addToCart(CartService $cart): void
    {
        if ($this->stock < 1) {
            $this->dispatch('cart-notify', message: 'Stok produk habis.', type: 'error');

            return;
        }

        $cart->add($this->productId, $this->qty);
        $this->dispatch('cart-updated');
        $this->dispatch('cart-notify', message: 'Produk ditambahkan ke keranjang.', type: 'success');
    }

    public function render()
    {
        return view('livewire.add-to-cart');
    }
}
