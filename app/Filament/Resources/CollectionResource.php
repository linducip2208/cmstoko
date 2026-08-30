<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Collections\CollectionResource\Pages;
use App\Models\Collection;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Koleksi';

    protected static ?string $modelLabel = 'Koleksi';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Koleksi')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true),
                        Select::make('type')
                            ->label('Tipe')
                            ->options([
                                Collection::TYPE_MANUAL => 'Manual (pilih produk)',
                                Collection::TYPE_RULES => 'Otomatis (berdasarkan aturan)',
                            ])
                            ->default(Collection::TYPE_MANUAL)
                            ->native(false)
                            ->live(),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Aturan Otomatis')
                    ->description('Produk masuk otomatis bila memenuhi salah satu aturan.')
                    ->schema([
                        Repeater::make('rules')
                            ->hiddenLabel()
                            ->reorderable(false)
                            ->schema([
                                Select::make('field')
                                    ->label('Kondisi')
                                    ->options([
                                        'category' => 'Kategori',
                                        'brand' => 'Merek',
                                        'price_min' => 'Harga minimal',
                                        'price_max' => 'Harga maksimal',
                                        'featured' => 'Produk unggulan',
                                        'discount' => 'Sedang diskon',
                                        'new' => 'Produk baru (hari terakhir)',
                                    ])
                                    ->required()
                                    ->native(false),
                                TextInput::make('value')
                                    ->label('Nilai')
                                    ->numeric()
                                    ->visible(fn ($get) => in_array($get('field'), ['price_min', 'price_max', 'new', 'category', 'brand'])),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->columnSpanFull()
                            ->visible(fn ($get) => $get('type') === Collection::TYPE_RULES),
                    ])
                    ->columnSpanFull(),
                Section::make('Produk Manual')
                    ->schema([
                        Select::make('manual_products')
                            ->label('Pilih Produk')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn ($record) => Product::query()
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                ->orderBy('name')
                                ->limit(500)
                                ->pluck('name', 'id'))
                            ->visible(fn ($get) => $get('type') === Collection::TYPE_MANUAL)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state($record->products()->pluck('products.id')->all());
                                }
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Tampilan')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Gambar')
                            ->image()
                            ->disk('public')
                            ->directory('collections')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Unggulan'),
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->disk('public')
                    ->defaultImageUrl('/images/placeholder.svg'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('products_count')
                    ->label('Produk')
                    ->counts('products'),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'edit' => Pages\EditCollection::route('/{record}/edit'),
        ];
    }
}
