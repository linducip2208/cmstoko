<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartBadge extends Component
{
    public int $count = 0;

    #[On('cart-updated')]
    public function refreshCount(CartService $cart): void
    {
        $this->count = $cart->count();
    }

    public function mount(CartService $cart): void
    {
        $this->count = $cart->count();
    }

    public function render()
    {
        return view('livewire.cart-badge');
    }
}
