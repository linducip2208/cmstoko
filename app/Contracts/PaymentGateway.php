<?php

namespace App\Contracts;

use App\Models\Order;

/**
 * Payment gateway contract. Every driver must be side-effect safe:
 * webhooks are the only source of truth for payment state changes.
 */
interface PaymentGateway
{
    /**
     * Unique driver id used in order.payment_method.
     */
    public function id(): string;

    /**
     * Human label for shoppers and admin.
     */
    public function label(): string;

    /**
     * Whether the driver is ready to process payments.
     */
    public function isConfigured(): bool;

    /**
     * Initialize a payment for the order.
     *
     * @return array{type: string, token?: ?string, redirect_url?: ?string, instructions?: ?string}
     */
    public function initiate(Order $order): array;

    /**
     * Process a gateway callback/webhook payload.
     * Must be idempotent and return the affected order (if any).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): ?Order;

    /**
     * Public configuration for the storefront (never includes secrets).
     *
     * @return array<string, mixed>
     */
    public function publicConfig(): array;
}
