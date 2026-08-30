<?php

namespace App\Livewire;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\PaymentService;
use App\Services\ShippingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Checkout - TokoKita')]
class CheckoutPage extends Component
{
    public string $customer_name = '';

    public string $customer_email = '';

    public string $customer_phone = '';

    public ?int $province_id = null;

    public ?int $city_id = null;

    public string $city_name_manual = '';

    public string $province_name_manual = '';

    public string $address = '';

    public string $postal_code = '';

    public string $notes = '';

    public string $courier = 'jne';

    public string $service = '';

    public string $couponCode = '';

    public string $couponMessage = '';

    public bool $couponSuccess = false;

    public array $shippingOptions = [];

    public Collection $provinces;

    public Collection $cities;

    public bool $useApiShipping = false;

    public function mount(CartService $cart, ShippingService $shipping): void
    {
        if ($cart->count() === 0) {
            $this->redirect(route('cart'), navigate: true);

            return;
        }

        if (auth()->check()) {
            $user = auth()->user();
            $this->customer_name = $user->name;
            $this->customer_email = $user->email;
            $this->customer_phone = (string) $user->phone;
        }

        $this->useApiShipping = $shipping->hasApi();
        $this->provinces = $this->useApiShipping ? $shipping->provinces() : collect();
        $this->cities = collect();
    }

    public function updatedProvinceId(ShippingService $shipping): void
    {
        $this->cities = $this->province_id ? $shipping->cities($this->province_id) : collect();
        $this->city_id = null;
        $this->shippingOptions = [];
        $this->service = '';
    }

    public function updatedCityId(ShippingService $shipping, CartService $cart): void
    {
        $this->loadShippingOptions($shipping, $cart);
    }

    public function updatedCourier(ShippingService $shipping, CartService $cart): void
    {
        $this->loadShippingOptions($shipping, $cart);
    }

    protected function loadShippingOptions(ShippingService $shipping, CartService $cart): void
    {
        $this->shippingOptions = [];
        $this->service = '';

        if (! $this->useApiShipping) {
            $this->shippingOptions = $shipping->fallback();
            $this->service = $this->shippingOptions[0]['service'];

            return;
        }

        if ($this->city_id) {
            $this->shippingOptions = $shipping->cost($this->city_id, $cart->weight(), $this->courier);

            if ($this->shippingOptions !== []) {
                $this->service = $this->shippingOptions[0]['service'];
            }
        }
    }

    public function applyCoupon(CartService $cart): void
    {
        $this->couponSuccess = $cart->setCoupon($this->couponCode);
        $this->couponMessage = $this->couponSuccess
            ? 'Kupon berhasil dipakai.'
            : 'Kupon tidak valid atau minimum belanja belum terpenuhi.';
    }

    public function removeCoupon(CartService $cart): void
    {
        $cart->removeCoupon();
        $this->couponCode = '';
        $this->couponMessage = '';
    }

    protected function rules(): array
    {
        $rules = [
            'customer_name' => 'required|string|max:120',
            'customer_email' => 'required|email|max:150',
            'customer_phone' => 'required|string|max:25',
            'address' => 'required|string|max:500',
            'postal_code' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:500',
        ];

        if ($this->useApiShipping) {
            $rules += [
                'province_id' => 'required|integer',
                'city_id' => 'required|integer',
            ];
        } else {
            $rules += [
                'city_name_manual' => 'required|string|max:120',
                'province_name_manual' => 'required|string|max:120',
            ];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'customer_name.required' => 'Nama wajib diisi.',
            'customer_email.required' => 'Email wajib diisi.',
            'customer_email.email' => 'Format email tidak valid.',
            'customer_phone.required' => 'Nomor telepon wajib diisi.',
            'address.required' => 'Alamat lengkap wajib diisi.',
            'province_id.required' => 'Provinsi wajib dipilih.',
            'city_id.required' => 'Kota/Kabupaten wajib dipilih.',
            'city_name_manual.required' => 'Kota/Kabupaten wajib diisi.',
            'province_name_manual.required' => 'Provinsi wajib diisi.',
        ];
    }

    public function placeOrder(CartService $cart, PaymentService $payment, ShippingService $shipping)
    {
        if ($cart->count() === 0) {
            return $this->redirect(route('cart'), navigate: true);
        }

        $validated = $this->validate();

        $selected = collect($this->shippingOptions)->firstWhere('service', $this->service);

        if ($this->useApiShipping && ! $selected) {
            $this->addError('service', 'Pilih layanan pengiriman terlebih dahulu.');

            return;
        }

        $order = DB::transaction(function () use ($cart, $validated, $selected) {
            $items = $cart->items();
            $subtotal = $cart->subtotal();
            $discount = $cart->discount();
            $coupon = $cart->coupon();
            $shippingCost = $selected ? (int) $selected['cost'] : (int) config('shop.flat_shipping_cost');

            foreach ($items as $item) {
                $product = $item['product'];

                if ($product->stock < $item['qty']) {
                    $this->addError('stock', "Stok {$product->name} tinggal {$product->stock}.");

                    return null;
                }
            }

            $order = Order::create([
                'user_id' => auth()->id(),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'province_id' => $validated['province_id'] ?? null,
                'city_id' => $validated['city_id'] ?? null,
                'province_name' => $validated['province_name_manual'] ?? optional($this->provinces->firstWhere('id', $this->province_id))->name,
                'city_name' => $validated['city_name_manual'] ?? optional($this->cities->firstWhere('id', $this->city_id))->name,
                'address' => $validated['address'],
                'postal_code' => $validated['postal_code'],
                'notes' => $validated['notes'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_cost' => $shippingCost,
                'total' => max(0, $subtotal - $discount + $shippingCost),
                'coupon_code' => $coupon?->code,
                'weight' => $cart->weight(),
                'shipping_courier' => $selected['description'] ?? null,
                'shipping_service' => $selected['service'] ?? config('shop.flat_shipping_service'),
                'shipping_etd' => $selected['etd'] ?? config('shop.flat_shipping_etd'),
                'status' => Order::STATUS_PENDING,
                'payment_method' => $payment->configured() ? 'midtrans' : 'manual_transfer',
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_image' => $item['product']->coverImage(),
                    'price' => $item['price'],
                    'quantity' => $item['qty'],
                    'subtotal' => $item['subtotal'],
                ]);

                Product::whereKey($item['product']->id)->decrement('stock', $item['qty']);
            }

            if ($coupon) {
                Coupon::whereKey($coupon->id)->increment('used_count');
            }

            return $order;
        });

        if (! $order) {
            return;
        }

        $cart->clear();

        if ($payment->configured()) {
            $token = $payment->snapToken($order->load('items'));

            if ($token) {
                $this->dispatch('open-snap', token: $token, orderNumber: $order->order_number);

                return;
            }
        }

        $this->redirect(route('order.success', $order->order_number), navigate: true);
    }

    public function render(CartService $cart)
    {
        return view('livewire.checkout-page', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'discount' => $cart->discount(),
            'coupon' => $cart->coupon(),
            'weight' => $cart->weight(),
        ])->layout('components.layouts.app');
    }
}
