<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartBadge extends Component
{
    public int $count = 0;

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $this->count = app(CartService::class)->count();
    }

    public function render()
    {
        $this->count ??= 0;

        return view('livewire.cart-badge', ['count' => $this->count ?: app(CartService::class)->count()]);
    }
}
