<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Livewire\Component;

class AddToCart extends Component
{
    public Product $product;

    public int $qty = 1;

    /** @var array<int, int> attribute_id => option_id */
    public array $selected = [];

    public bool $added = false;

    public function mount(Product $product): void
    {
        // Preselect the first attribute option of the first available variant.
        if ($product->isConfigurable()) {
            $variant = $product->variants
                ->first(fn (ProductVariant $v) => $v->is_active && $v->stock > 0)
                ?? $product->variants->first();

            if ($variant) {
                foreach ($variant->attributeValues as $value) {
                    $this->selected[$value->attribute_id] = $value->attribute_option_id;
                }
            }
        }
    }

    public function updatedSelected(): void
    {
        $this->added = false;
    }

    public function getVariantProperty(): ?ProductVariant
    {
        if (! $this->product->isConfigurable() || count($this->selected) < count($this->product->variantAttributes())) {
            return null;
        }

        $wanted = collect($this->selected)->values()->sort()->implode('-');

        return $this->product->variants
            ->filter(fn (ProductVariant $v) => $v->is_active)
            ->first(fn (ProductVariant $v) => $v->attributeValues->pluck('attribute_option_id')->sort()->implode('-') === $wanted);
    }

    public function getStockProperty(): int
    {
        $variant = $this->variant;

        return $variant ? $variant->stock : $this->product->stock;
    }

    public function getPriceProperty(): int
    {
        $variant = $this->variant;

        return $variant ? $variant->effectivePrice() : $this->product->effectivePrice();
    }

    public function getHasDiscountProperty(): bool
    {
        $variant = $this->variant;

        return $variant ? $variant->hasDiscount() : $this->product->hasDiscount();
    }

    public function getRegularPriceProperty(): int
    {
        $variant = $this->variant;

        return (int) ($variant->price ?? $this->product->price);
    }

    public function addToCart(CartService $cart): void
    {
        $this->qty = max(1, min(999, $this->qty));
        $this->added = false;

        $variant = $this->variant;

        if ($this->product->isConfigurable() && ! $variant) {
            $this->addError('variant', 'Pilih varian terlebih dahulu.');

            return;
        }

        $available = $variant ? $variant->stock : $this->product->stock;

        if ($available < $this->qty) {
            $this->addError('qty', $available > 0 ? "Stok tinggal {$available}." : 'Stok sedang habis.');

            return;
        }

        $cart->add($this->product->id, $variant?->id, $this->qty);

        $this->added = true;

        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $attributes = collect();

        if ($this->product->isConfigurable()) {
            $attributes = collect($this->product->variantAttributes());
        }

        return view('livewire.add-to-cart', [
            'attributes' => $attributes,
            'variant' => $this->variant,
        ]);
    }
}
