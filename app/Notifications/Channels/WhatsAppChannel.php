<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp notification channel — architecture stub.
 *
 * Messages are logged (and optionally POSTed to a provider webhook) so the
 * commerce flow can already dispatch WhatsApp notifications without external
 * credentials. To activate a real provider, implement the payload mapping for
 * your gateway (Fonnte, Wablas, WhatsApp Cloud API, …) and set:
 *   WHATSAPP_ENABLED=true
 *   WHATSAPP_ENDPOINT=https://provider.example/api/send
 *   WHATSAPP_TOKEN=secret
 */
class WhatsAppChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (! config('services.whatsapp.enabled')) {
            return;
        }

        $message = method_exists($notification, 'toWhatsApp')
            ? $notification->toWhatsApp($notifiable)
            : null;

        if (! $message) {
            return;
        }

        $endpoint = config('services.whatsapp.endpoint');

        if (! $endpoint) {
            Log::info('WhatsApp notification (stub, no endpoint configured)', [
                'to' => method_exists($notifiable, 'routeNotificationForWhatsApp')
                    ? $notifiable->routeNotificationForWhatsApp($notification)
                    : null,
                'message' => $message,
            ]);

            return;
        }

        try {
            Http::withToken((string) config('services.whatsapp.token'))
                ->acceptJson()
                ->timeout(10)
                ->post($endpoint, [
                    'to' => $notifiable->routeNotificationForWhatsApp($notification),
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp send failed', ['error' => $e->getMessage()]);
        }
    }
}
