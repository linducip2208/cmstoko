<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Lacak Pesanan - TokoKita')]
class TrackOrder extends Component
{
    public string $number = '';

    public string $email = '';

    public ?Order $order = null;

    public bool $searched = false;

    protected $messages = [
        'email.required' => 'Masukkan email yang dipakai saat memesan.',
        'email.email' => 'Format email tidak valid.',
    ];

    public function search(): void
    {
        $rules = ['number' => 'required|string|max:40'];

        // Guests must verify ownership with the email used at checkout.
        if (! auth()->check()) {
            $rules['email'] = 'required|email|max:150';
        }

        $this->validate($rules, ['number.required' => 'Masukkan nomor pesanan.']);

        $order = Order::with(['items', 'shipments', 'histories'])
            ->where('order_number', strtoupper(trim($this->number)))
            ->first();

        $this->order = $this->canView($order) ? $order : null;
        $this->searched = true;
    }

    protected function canView(?Order $order): bool
    {
        if (! $order) {
            return false;
        }

        if (auth()->check()) {
            return $order->user_id === auth()->id() || auth()->user()->isStaff();
        }

        return strcasecmp(trim($this->email), (string) $order->customer_email) === 0;
    }

    public function render()
    {
        return view('livewire.track-order')->layout('components.layouts.app');
    }
}
