<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->withCount('items')
            ->when($request->filled('status'), function ($q) use ($request) {
                $valid = implode(',', array_keys(Order::STATUSES));
                $request->validate(['status' => ['required', 'in:'.$valid]]);

                return $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(min((int) $request->integer('per_page', 10), 50))
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    public function show(Request $request, string $orderNumber): OrderResource
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id) // ownership enforced — no IDOR
            ->with(['items', 'shipments.items', 'histories'])
            ->firstOrFail();

        return new OrderResource($order);
    }
}
