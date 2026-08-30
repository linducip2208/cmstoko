<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Order success/detail page. Guests may only view orders they placed
     * in the same session; logged-in customers only their own orders.
     */
    public function success(string $orderNumber, PaymentService $payment)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        if (! $this->canViewOrder($order)) {
            abort(403);
        }

        return view('pages.order-success', [
            'order' => $order,
            'midtransConfigured' => $payment->configured(),
        ]);
    }

    /**
     * Midtrans redirect endpoint. The source of truth is the webhook;
     * this only forwards the shopper to their order page.
     */
    public function finish(Request $request)
    {
        $orderNumber = $request->query('order_id');

        if ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();

            if ($order && $this->canViewOrder($order)) {
                return redirect()->route('order.success', $order->order_number);
            }
        }

        return redirect()->route('track-order');
    }

    public function webhook(Request $request, PaymentService $payment)
    {
        $payment->handleNotification((array) $request->json()->all());

        return response()->json(['status' => 'ok']);
    }

    protected function canViewOrder(Order $order): bool
    {
        if (auth()->check()) {
            return $order->user_id === auth()->id() || auth()->user()->isStaff();
        }

        $placed = (array) session('shop.orders', []);

        return in_array($order->order_number, $placed, true);
    }
}
