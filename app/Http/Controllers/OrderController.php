<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function success(string $orderNumber, PaymentService $payment)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        return view('pages.order-success', [
            'order' => $order,
            'midtransConfigured' => $payment->configured(),
        ]);
    }

    public function finish(Request $request)
    {
        $orderNumber = $request->query('order_id');

        if ($orderNumber) {
            Order::where('order_number', $orderNumber)
                ->where('status', Order::STATUS_PENDING)
                ->first()?->forceFill(['status' => Order::STATUS_PENDING])->save();

            return redirect()->route('order.success', $orderNumber);
        }

        return redirect()->route('track-order');
    }

    public function webhook(Request $request, PaymentService $payment)
    {
        $payment->handleNotification((array) $request->json()->all());

        return response()->json(['status' => 'ok']);
    }
}
