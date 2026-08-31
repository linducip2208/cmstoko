<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Order $order,
        protected string $heading,
        protected string $message,
        protected array $lines = [],
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $storeName = Settings::get('store.name', config('shop.name', 'TokoKita'));

        $mail = (new MailMessage)
            ->subject("[{$storeName}] {$this->heading} — {$this->order->order_number}")
            ->greeting("Halo {$this->order->customer_name},")
            ->line($this->message);

        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        if ($this->order->isPending() && $this->order->payment_method === 'manual_transfer') {
            $mail->line('Silakan transfer tepat sejumlah '.rupiah($this->order->total).' ke:');

            foreach (Settings::get('payments.bank_accounts', config('shop.bank_accounts')) as $account) {
                $mail->line("{$account['bank']} {$account['number']} — a.n. {$account['holder']}");
            }
        }

        $mail->action('Lihat Pesanan', route('order.success', $this->order->order_number));

        if ($this->order->status === Order::STATUS_SHIPPED) {
            $shipment = $this->order->shipments()->latest('id')->first();

            if ($shipment?->tracking_number) {
                $mail->line("Nomor resi {$shipment->tracking_number} ({$shipment->courier} {$shipment->service}).");
            }
        }

        $mail->salutation("Terima kasih,\n{$storeName}");

        return $mail;
    }

    /**
     * Rendered subject/heading helpers keep callers terse.
     */
    public static function forPlaced(Order $order): static
    {
        return new static(
            $order,
            'Pesanan diterima',
            'Pesananmu sudah kami terima dan menunggu pembayaran.'
        );
    }

    public static function forPaid(Order $order): static
    {
        return new static(
            $order,
            'Pembayaran terkonfirmasi',
            'Pembayaranmu sudah kami terima. Pesananmu masuk antrean pengemasan.'
        );
    }

    public static function forShipped(Order $order): static
    {
        return new static(
            $order,
            'Pesanan dikirim',
            'Pesananmu sudah dikirim ke alamat pengiriman. Pantau paketmu dengan nomor resi berikut.'
        );
    }

    public static function forCompleted(Order $order): static
    {
        return new static(
            $order,
            'Pesanan selesai',
            'Pesananmu telah selesai. Terima kasih sudah berbelanja!'
        );
    }

    public static function forCancelled(Order $order): static
    {
        return new static(
            $order,
            'Pesanan dibatalkan',
            'Pesananmu dibatalkan. Jika kamu sudah melakukan pembayaran, dana akan dikembalikan.'
        );
    }
}
