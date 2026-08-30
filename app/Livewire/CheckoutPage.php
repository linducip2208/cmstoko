<?php

namespace App\Livewire;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\PaymentService;
use App\Services\ShippingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

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

    public Collection $addresses;

    public ?int $addressId = null;

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
            $this->addresses = $user->addresses()->orderByDesc('is_default')->orderBy('id')->get();

            // Prefill from the default saved address.
            if ($default = $this->addresses->firstWhere('is_default', true) ?? $this->addresses->first()) {
                $this->applyAddress($default->id);
            }
        } else {
            $this->addresses = collect();
        }

        $this->useApiShipping = $shipping->hasApi();
        $this->provinces = $this->useApiShipping ? $shipping->provinces() : collect();
        $this->cities = collect();

        // Re-run option loading after potential saved-address prefill.
        if ($this->useApiShipping && $this->city_id) {
            $this->loadShippingOptions($shipping, $cart);
        } elseif (! $this->useApiShipping) {
            $this->loadShippingOptions($shipping, $cart);
        }
    }

    /**
     * Fill the checkout form from a saved address (ownership enforced).
     */
    public function applyAddress(int $id): void
    {
        $address = auth()->user()?->addresses()->whereKey($id)->first();

        if (! $address) {
            return;
        }

        $this->addressId = $address->id;
        $this->customer_name = $address->name;
        $this->customer_phone = (string) $address->phone;
        $this->province_id = $address->province_id !== null ? (int) $address->province_id : null;
        $this->city_id = $address->city_id !== null ? (int) $address->city_id : null;
        $this->province_name_manual = (string) $address->province_name;
        $this->city_name_manual = (string) $address->city_name;
        $this->postal_code = (string) $address->postal_code;
        $this->address = (string) $address->address;

        if ($this->useApiShipping) {
            $shipping = app(ShippingService::class);
            $this->cities = $this->province_id ? $shipping->cities($this->province_id) : collect();
        }

        $this->loadShippingOptions(app(ShippingService::class), app(CartService::class));
    }

    /**
     * Switch to a fresh manual address.
     */
    public function useNewAddress(): void
    {
        $this->addressId = null;
        $this->province_id = null;
        $this->city_id = null;
        $this->province_name_manual = '';
        $this->city_name_manual = '';
        $this->postal_code = '';
        $this->address = '';
        $this->cities = collect();
        $this->shippingOptions = [];
        $this->service = '';

        if (! $this->useApiShipping) {
            $this->loadShippingOptions(app(ShippingService::class), app(CartService::class));
        }
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
        if (RateLimiter::tooManyAttempts('coupon:'.session()->getId(), 8)) {
            $this->couponSuccess = false;
            $this->couponMessage = 'Terlalu banyak percobaan kupon. Coba lagi nanti.';

            return;
        }

        RateLimiter::hit('coupon:'.session()->getId(), 120);

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
        if (RateLimiter::tooManyAttempts('checkout:'.session()->getId(), 12)) {
            $this->addError('order', 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.');

            return;
        }

        RateLimiter::hit('checkout:'.session()->getId(), 120);

        if ($cart->count() === 0) {
            return $this->redirect(route('cart'), navigate: true);
        }

        $validated = $this->validate();

        $selected = collect($this->shippingOptions)->firstWhere('service', $this->service);

        if ($this->useApiShipping && ! $selected) {
            $this->addError('service', 'Pilih layanan pengiriman terlebih dahulu.');

            return;
        }

        try {
            $order = DB::transaction(function () use ($cart, $validated, $selected, $payment) {
                $inventory = app(InventoryService::class);

                $items = $cart->items();

                if ($items->isEmpty()) {
                    throw new RuntimeException('Keranjang kosong.');
                }

                // Lock product + variant rows to serialize concurrent purchases.
                $variantIds = $items->pluck('variant.id')->filter();

                $variantLocks = $variantIds->isNotEmpty()
                    ? ProductVariant::whereIn('id', $variantIds)->lockForUpdate()->get()->keyBy('id')
                    : collect();

                $locked = Product::whereIn('id', $items->pluck('product.id'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Re-validate stock against locked rows (variants take priority).
                foreach ($items as $item) {
                    $variant = $item['variant'] ? $variantLocks->get($item['variant']->id) : null;
                    $available = $variant ? $variant->stock : $locked[$item['product']->id]->stock;

                    if ($available < $item['qty']) {
                        throw new RuntimeException("Stok {$item['product']->name} tinggal {$available}.");
                    }
                }

                $subtotal = $cart->subtotal();
                $discount = $cart->discount();
                $coupon = $cart->coupon();

                // Cart rules: server-authoritative promotions (group targeting, free shipping, stacking capped at subtotal).
                $ruleResult = \App\Models\CartRule::evaluate($items, $subtotal, auth()->user());
                $totalDiscount = $discount + $ruleResult['discount'];

                $shippingCost = $selected ? (int) $selected['cost'] : (int) config('shop.flat_shipping_cost');
                $effectiveShipping = $ruleResult['free_shipping'] ? 0 : $shippingCost;

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
                    'rule_discount' => $ruleResult['discount'],
                    'applied_rules' => $ruleResult['rules'],
                    'shipping_cost' => $effectiveShipping,
                    'total' => max(0, $subtotal - $totalDiscount + $effectiveShipping),
                    'coupon_code' => $coupon?->code,
                    'weight' => $cart->weight(),
                    'shipping_courier' => $selected['description'] ?? null,
                    'shipping_service' => $selected['service'] ?? config('shop.flat_shipping_service'),
                    'shipping_etd' => $selected['etd'] ?? config('shop.flat_shipping_etd'),
                    'status' => Order::STATUS_PENDING,
                    'payment_method' => $payment->configured() ? 'midtrans' : 'manual_transfer',
                ]);

                foreach ($items as $item) {
                    $variant = $item['variant'];

                    $order->items()->create([
                        'product_id' => $item['product']->id,
                        'variant_id' => $variant?->id,
                        'variant_label' => $variant?->attributeValues->map(fn ($v) => $v->option->label)->implode(' / '),
                        'product_name' => $item['product']->name,
                        'product_image' => $item['product']->coverImage(),
                        'price' => $item['price'],
                        'quantity' => $item['qty'],
                        'subtotal' => $item['price'] * $item['qty'],
                    ]);

                    try {
                        $inventory->deduct(
                            $item['product']->id,
                            $variant?->id,
                            $item['qty'],
                            StockMovement::TYPE_SALE,
                            $order,
                            'Pesanan '.$order->order_number,
                        );
                    } catch (InvalidArgumentException $e) {
                        throw new RuntimeException($e->getMessage());
                    }
                }

                if ($coupon) {
                    // Atomic usage increment capped at max_uses.
                    $affected = Coupon::whereKey($coupon->id)
                        ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
                        ->increment('used_count');

                    if ($affected === 0) {
                        throw new RuntimeException('Kuota kupon sudah habis.');
                    }
                }

                \App\Models\CartRule::consume($ruleResult['rule_ids']);

                $order->histories()->create([
                    'from' => null,
                    'to' => Order::STATUS_PENDING,
                    'note' => 'Pesanan dibuat',
                ]);

                return $order;
            });
        } catch (RuntimeException $e) {
            $this->addError('stock', $e->getMessage());

            return;
        }

        if (! $order) {
            return;
        }

        $cart->clear();

        session()->push('shop.orders', $order->order_number);

        \App\Events\OrderPlaced::dispatch($order);

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
        $ruleResult = \App\Models\CartRule::evaluate($cart->items(), $cart->subtotal(), auth()->user());

        return view('livewire.checkout-page', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'discount' => $cart->discount(),
            'coupon' => $cart->coupon(),
            'weight' => $cart->weight(),
            'ruleDiscount' => $ruleResult['discount'],
            'freeShipping' => $ruleResult['free_shipping'],
            'ruleNames' => collect($ruleResult['rules'])->pluck('name'),
            'savedAddresses' => auth()->check()
                ? auth()->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get()
                : collect(),
        ])->layout('components.layouts.app');
    }
}
