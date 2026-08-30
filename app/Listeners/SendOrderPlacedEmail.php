<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Notifications\OrderStatusMail;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderPlacedEmail implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        $event->order->notify(OrderStatusMail::forPlaced($event->order));
    }
}
