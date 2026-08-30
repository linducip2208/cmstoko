<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Products\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
                Section::make('Informasi Produk')
                    ->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->options(Category::orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->native(false),
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->unique(ignoreRecord: true)
                            ->maxLength(60),
                        RichEditor::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(['lg' => 2]),
                Section::make('Harga & Stok')
                    ->schema([
                        TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->required(),
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
                            ->required(),
                        TextInput::make('weight')
                            ->label('Berat (gram)')
                            ->numeric()
                            ->minValue(1)
                            ->default(1000)
                            ->required(),
                    ]),
                Section::make('Gambar & Visibilitas')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Gambar Produk')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('products')
                            ->multiple()
                            ->maxFiles(6)
                            ->maxSize(3072)
                            ->reorderable()
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Unggulan')
                            ->default(false),
                    ])
                    ->columnSpan(['lg' => 2]),
            ])
            ->columns(3);
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
                    ->limit(40),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
