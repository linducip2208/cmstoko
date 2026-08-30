<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'payment_method' => $this->payment_method,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'variant' => $item->variant_label,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ])),
            'shipments' => $this->whenLoaded('shipments', fn () => $this->shipments->map(fn ($shipment) => [
                'number' => $shipment->shipment_number,
                'courier' => $shipment->courier,
                'service' => $shipment->service,
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status,
                'shipped_at' => $shipment->shipped_at?->toIso8601String(),
            ])),
            'history' => $this->whenLoaded('histories', fn () => $this->histories->map(fn ($h) => [
                'from' => $h->from,
                'to' => $h->to,
                'note' => $h->note,
                'at' => $h->created_at?->toIso8601String(),
            ])),
            // Internal-only fields (admin notes, refunds detail) deliberately excluded.
            'summary' => [
                'subtotal' => $this->subtotal,
                'discount' => $this->discount,
                'shipping_cost' => $this->shipping_cost,
                'total' => $this->total,
            ],
            'shipping' => [
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
                'city' => $this->city_name,
                'province' => $this->province_name,
                'address' => $this->address,
                'postal_code' => $this->postal_code,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
