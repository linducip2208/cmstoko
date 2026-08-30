<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Notifications\OrderStatusMail;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusEmail implements ShouldQueue
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        $notification = match ($event->to) {
            Order::STATUS_PAID => OrderStatusMail::forPaid($order),
            Order::STATUS_SHIPPED => OrderStatusMail::forShipped($order),
            Order::STATUS_COMPLETED => OrderStatusMail::forCompleted($order),
            Order::STATUS_CANCELLED => OrderStatusMail::forCancelled($order),
            default => null,
        };

        if ($notification) {
            $order->notify($notification);
        }
    }
}
