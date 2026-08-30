<?php

namespace App\Filament\Resources\Products\ProductResource\RelationManagers;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isConfigurable();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true)
                    ->maxLength(60),
                TextInput::make('barcode')
                    ->label('Barcode')
                    ->maxLength(60),
                TextInput::make('price')
                    ->label('Harga')
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0)
                    ->helperText('Kosongkan untuk memakai harga produk induk.'),
                TextInput::make('sale_price')
                    ->label('Harga Diskon')
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0),
                TextInput::make('cost')
                    ->label('Harga Pokok')
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0),
                TextInput::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('weight')
                    ->label('Berat (gram)')
                    ->numeric()
                    ->minValue(1),
                FileUpload::make('image')
                    ->label('Gambar Varian')
                    ->image()
                    ->disk('public')
                    ->directory('products/variants')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                TextInput::make('position')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->hidden(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('attribute_label')
                    ->label('Varian')
                    ->state(fn (ProductVariant $record) => $record->label() ?: '—')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('-'),
                TextColumn::make('price')
                    ->label('Harga')
                    ->formatStateUsing(fn (ProductVariant $record) => 'Rp '.number_format($record->effectivePrice(), 0, ',', '.')),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn ($state) => $state < 5 ? 'danger' : ($state < 20 ? 'warning' : 'success')),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->disk('public')
                    ->circular(),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Buat Varian Otomatis')
                    ->icon('heroicon-m-sparkles')
                    ->color('primary')
                    ->form(fn (Model $record) => $this->generationForm($record))
                    ->action(fn (array $data, Model $record) => $this->generateVariants($record, $data)),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('position');
    }

    protected function generationForm(Model $record): array
    {
        $attributes = Attribute::where('is_variant', true)
            ->with('options')
            ->orderBy('position')
            ->get();

        $fields = [];

        foreach ($attributes as $attribute) {
            $usedIds = ProductVariantAttributeValue::query()
                ->whereIn('variant_id', $record->variants()->pluck('id'))
                ->where('attribute_id', $attribute->id)
                ->pluck('attribute_option_id')
                ->unique()
                ->all();

            $fields[] = Select::make("attribute_{$attribute->id}")
                ->label($attribute->name)
                ->options($attribute->options->pluck('label', 'id'))
                ->multiple()
                ->native(false)
                ->default($usedIds)
                ->helperText('Opsi baru yang dipilih akan menghasilkan kombinasi varian tambahan.');
        }

        return $fields;
    }

    protected function generateVariants(Model $record, array $data): void
    {
        $attributes = Attribute::where('is_variant', true)
            ->with('options')
            ->orderBy('position')
            ->get();

        $selected = [];
        foreach ($attributes as $attribute) {
            $optionIds = array_map('intval', (array) ($data["attribute_{$attribute->id}"] ?? []));

            if ($optionIds !== []) {
                $selected[$attribute->id] = $optionIds;
            }
        }

        if ($selected === []) {
            return;
        }

        DB::transaction(function () use ($record, $selected) {
            $product = $record->fresh();
            $position = (int) ($product->variants()->max('position') ?? 0);

            // Existing combos keyed by option set.
            $existing = $product->variants()->with('attributeValues')->get()
                ->mapWithKeys(fn (ProductVariant $v) => [
                    $v->attributeValues->pluck('attribute_option_id')->sort()->implode('-') => true,
                ]);

            $combinations = $this->cartesian($selected);
            $generated = 0;

            foreach ($combinations as $combination) {
                $key = collect($combination)->sort()->implode('-');

                if (isset($existing[$key])) {
                    continue;
                }

                $variant = $product->variants()->create([
                    'sku' => $this->variantSku($product, $combination),
                    'stock' => 0,
                    'weight' => $product->weight,
                    'is_active' => true,
                    'position' => ++$position,
                ]);

                $options = AttributeOption::whereIn('id', $combination)->get();
                foreach ($options as $option) {
                    $variant->attributeValues()->create([
                        'attribute_id' => $option->attribute_id,
                        'attribute_option_id' => $option->id,
                    ]);
                }

                $generated++;
            }
        });
    }

    /**
     * Cartesian product of [attribute_id => [option_id, ...]].
     *
     * @return list<list<int>>
     */
    protected function cartesian(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $options) {
            $append = [];

            foreach ($result as $combination) {
                foreach ($options as $option) {
                    $append[] = array_merge($combination, [$option]);
                }
            }

            $result = $append;
        }

        return $result;
    }

    protected function variantSku(Product $product, array $optionIds): string
    {
        $base = $product->sku ?: strtoupper(Str::slug($product->name, ''));

        return mb_substr($base, 0, 20).'-'.collect($optionIds)->implode('-');
    }
}
