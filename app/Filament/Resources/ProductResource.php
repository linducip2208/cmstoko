<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Products\ProductResource\Pages;
use App\Filament\Resources\Products\ProductResource\RelationManagers\VariantsRelationManager;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Produk')
                    ->tabs([
                        Tabs\Tab::make('Informasi')
                            ->schema([
                                Select::make('category_id')
                                    ->label('Kategori')
                                    ->options(Category::orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required(),
                                Select::make('brand_id')
                                    ->label('Merek')
                                    ->options(Brand::orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('type')
                                    ->label('Tipe Produk')
                                    ->options(Product::TYPES)
                                    ->default(Product::TYPE_SIMPLE)
                                    ->native(false)
                                    ->live()
                                    ->helperText('Produk dengan varian (mis. ukuran/warna) memakai tipe "Dengan Varian".'),
                                TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->required()
                                    ->maxLength(180)
                                    ->columnSpanFull(),
                                TextInput::make('sku')
                                    ->label('SKU Induk')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(60)
                                    ->helperText('Untuk produk varian, SKU diisi pada tiap varian.'),
                                Select::make('tax_class_id')
                                    ->label('Kelas Pajak')
                                    ->options(fn () => \App\Models\TaxClass::orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->helperText('Kosong = kelas default (mis. PPN). Pilih kelas tanpa tarif agar produk tidak kena pajak.'),
                                TextInput::make('short_description')
                                    ->label('Deskripsi Singkat')
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                RichEditor::make('description')
                                    ->label('Deskripsi Lengkap')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Tabs\Tab::make('Harga & Stok')
                            ->schema([
                                TextInput::make('price')
                                    ->label('Harga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->required()
                                    ->helperText(fn (Get $get) => $get('type') === Product::TYPE_CONFIGURABLE
                                        ? 'Dipakai sebagai harga dasar varian; varian boleh override.'
                                        : null),
                                TextInput::make('sale_price')
                                    ->label('Harga Diskon')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0),
                                TextInput::make('stock')
                                    ->label('Stok')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->helperText(fn (Get $get) => $get('type') === Product::TYPE_CONFIGURABLE
                                        ? 'Stok per varian dikelola di tab Varian.'
                                        : null),
                                TextInput::make('weight')
                                    ->label('Berat (gram)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1000)
                                    ->required(),
                            ])
                            ->columns(2),
                        Tabs\Tab::make('Atribut')
                            ->schema([
                                KeyValue::make('attribute_values')
                                    ->label('Atribut Tambahan')
                                    ->keyLabel('Atribut')
                                    ->valueLabel('Nilai')
                                    ->helperText('Misal: Material = Stainless, Garansi = 1 tahun.')
                                    ->columnSpanFull(),
                            ]),
                        Tabs\Tab::make('Media & Visibilitas')
                            ->schema([
                                FileUpload::make('images')
                                    ->label('Gambar Produk')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('products')
                                    ->multiple()
                                    ->maxFiles(8)
                                    ->maxSize(3072)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                                    ->reorderable()
                                    ->columnSpanFull(),
                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                                Toggle::make('is_featured')
                                    ->label('Unggulan')
                                    ->default(false),
                            ])
                            ->columns(2),
                        Tabs\Tab::make('SEO')
                            ->schema([
                                TextInput::make('seo.meta_title')
                                    ->label('Meta Title')
                                    ->maxLength(180)
                                    ->columnSpanFull(),
                                Textarea::make('seo.meta_description')
                                    ->label('Meta Description')
                                    ->maxLength(320)
                                    ->columnSpanFull()
                                    ->rows(3),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images')
                    ->disk('public')
                    ->circular()
                    ->state(fn (Product $record) => [$record->coverImage()]),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->description(fn (Product $record) => $record->isConfigurable()
                        ? $record->variants()->count().' varian'
                        : null),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge(),
                TextColumn::make('brand.name')
                    ->label('Merek')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Product::TYPES[$state] ?? $state),
                TextColumn::make('price')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state < 5 ? 'danger' : ($state < 20 ? 'warning' : 'success')),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                ToggleColumn::make('is_featured')
                    ->label('Unggulan'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                SelectFilter::make('brand')
                    ->label('Merek')
                    ->relationship('brand', 'name'),
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(Product::TYPES),
                SelectFilter::make('stock_status')
                    ->label('Status Stok')
                    ->options([
                        'in' => 'Tersedia',
                        'low' => 'Stok Menipis',
                        'out' => 'Habis',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'in' => $query->where('stock', '>', 0),
                        'low' => $query->whereBetween('stock', [1, 4]),
                        'out' => $query->where('stock', 0),
                        default => $query,
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
