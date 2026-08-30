<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index()
    {
        $returns = ReturnRequest::where('user_id', Auth::id())
            ->with('order')
            ->latest()
            ->get();

        return view('account.returns', [
            'returns' => $returns,
            'statuses' => ReturnRequest::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $order = Order::where('order_number', $validated['order_number'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        abort_unless($order->isPaid(), 400, 'Pesanan ini belum dapat diajukan pengembalian.');
        abort_unless(
            $order->created_at->gt(now()->subDays((int) config('shop.return_window_days', 7))),
            400,
            'Masa pengajuan pengembalian sudah berakhir.'
        );

        DB::transaction(function () use ($order, $validated) {
            $return = $order->returnRequests()->create([
                'user_id' => Auth::id(),
                'status' => ReturnRequest::STATUS_REQUESTED,
                'reason' => $validated['reason'],
            ]);

            $orderedIds = $order->items()->pluck('id', 'id');

            // Quantities already requested (active) — the customer may never
            // request to return more than they bought, across all attempts.
            $alreadyRequested = ReturnItem::whereIn('order_item_id', $orderedIds->keys())
                ->whereHas('returnRequest', fn ($q) => $q->whereNotIn('status', [ReturnRequest::STATUS_REJECTED, ReturnRequest::STATUS_CANCELLED]))
                ->groupBy('order_item_id')
                ->selectRaw('order_item_id, SUM(quantity) as total')
                ->pluck('total', 'order_item_id');

            foreach ($validated['items'] as $line) {
                if (! isset($orderedIds[$line['order_item_id']])) {
                    continue;
                }

                $item = $order->items()->whereKey($line['order_item_id'])->first();

                $remaining = $item->quantity - (int) ($alreadyRequested[$item->id] ?? 0);

                $quantity = min((int) $line['quantity'], max(0, $remaining));

                if ($quantity > 0) {
                    ReturnItem::create([
                        'return_request_id' => $return->id,
                        'order_item_id' => $item->id,
                        'quantity' => $quantity,
                        'reason' => $validated['reason'],
                    ]);
                }
            }

            if ($return->items()->doesntExist()) {
                $return->delete();

                abort(400, 'Item pengembalian tidak valid.');
            }
        });

        return back()->with('status', 'Pengajuan pengembalian terkirim. Kami akan meninjau dalam 1-2 hari kerja.');
    }
}
