<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccountOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('user_id', Auth::id())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->withCount('items')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('account.orders', ['orders' => $orders, 'statuses' => Order::STATUSES]);
    }

    public function show(string $orderNumber, PaymentService $payment)
    {
        $order = Order::with(['items', 'histories', 'shipments.items'])
            ->where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $reviewableItems = Auth::user()->reviews()
            ->pluck('order_item_id')
            ->filter()
            ->all();

        return view('account.order-detail', [
            'order' => $order,
            'statuses' => Order::STATUSES,
            'reviewedItemIds' => $reviewableItems,
            'returnable' => $order->isPaid() && $order->created_at->gt(now()->subDays((int) config('shop.return_window_days', 7))),
        ]);
    }

    public function storeReview(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->where('status', '!=', Order::STATUS_PENDING)
            ->firstOrFail();

        $validated = $request->validate([
            'order_item_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $item = $order->items()->whereKey($validated['order_item_id'])->firstOrFail();

        $already = Review::where('user_id', Auth::id())->where('order_item_id', $item->id)->exists();

        if ($already) {
            throw ValidationException::withMessages([
                'rating' => 'Kamu sudah membuat ulasan untuk item ini.',
            ]);
        }

        Review::create([
            'product_id' => $item->product_id,
            'user_id' => Auth::id(),
            'order_item_id' => $item->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'is_verified' => true,
            'status' => Review::STATUS_PENDING,
        ]);

        return back()->with('status', 'Ulasan terkirim. Menunggu moderasi toko.');
    }
}
