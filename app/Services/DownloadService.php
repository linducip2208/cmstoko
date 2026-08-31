<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\ProductDownload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Secure download delivery for DOWNLOADABLE products.
 *
 * Rules:
 *  - order must be PAID
 *  - requestor must own the order (members only — guests are prompted to register)
 *  - within expiry window (paid_at + product.download_expiry_days, null = never)
 *  - within download limit (product.download_limit, null = unlimited), counted
 *    from product_download_logs per order item
 */
class DownloadService
{
    public function authorize(OrderItem $item, int $userId): void
    {
        $order = $item->order;

        if ($order->user_id !== $userId) {
            abort(404); // don't leak existence
        }

        if (! $order->isPaid()) {
            abort(403, 'Pesanan belum dibayar.');
        }

        $product = $item->product;

        if (! $product || ! $product->isDownloadable()) {
            abort(404);
        }

        if ($product->download_expiry_days !== null) {
            $expiresAt = ($order->paid_at ?? $order->created_at)->copy()->addDays((int) $product->download_expiry_days);

            if (now()->gt($expiresAt)) {
                abort(403, 'Masa unduh sudah berakhir.');
            }
        }

        if ($product->download_limit !== null) {
            $used = DB::table('product_download_logs')->where('order_item_id', $item->id)->count();

            if ($used >= (int) $product->download_limit) {
                abort(403, 'Batas unduh sudah tercapai.');
            }
        }
    }

    public function log(OrderItem $item, int $userId, ?string $ip): void
    {
        DB::table('product_download_logs')->insert([
            'order_item_id' => $item->id,
            'order_id' => $item->order_id,
            'product_id' => $item->product_id,
            'user_id' => $userId,
            'ip' => $ip,
            'downloaded_at' => now(),
        ]);
    }

    /**
     * @return array{0: StreamedResponse, 1: ProductDownload}
     */
    public function stream(OrderItem $item): array
    {
        $download = ProductDownload::where('product_id', $item->product_id)
            ->orderBy('sort_order')
            ->firstOrFail();

        $disk = Storage::disk('downloads'); // private — never web-accessible

        if (! $disk->exists($download->file_path)) {
            abort(404, 'Berkas tidak ditemukan.');
        }

        $response = response()->streamDownload(function () use ($disk, $download) {
            echo $disk->get($download->file_path);
        }, $download->file_name, [
            'Content-Type' => 'application/octet-stream',
        ]);

        return [$response, $download];
    }
}
