<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Lacak Pesanan - TokoKita')]
class TrackOrder extends Component
{
    public string $number = '';

    public ?Order $order = null;

    public bool $searched = false;

    public function search(): void
    {
        $this->validate(['number' => 'required|string|max:40'], [
            'number.required' => 'Masukkan nomor pesanan.',
        ]);

        $this->order = Order::with('items')
            ->where('order_number', strtoupper(trim($this->number)))
            ->first();
        $this->searched = true;
    }

    public function render()
    {
        return view('livewire.track-order')->layout('components.layouts.app');
    }
}
