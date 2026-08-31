<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransfers\StockTransferResource\Pages;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';

    protected static ?string $navigationLabel = 'Transfer Stok';

    protected static ?string $modelLabel = 'Transfer Stok';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transfer Antar Gudang')
                ->description('Alur: simpan (Menunggu) → Kirim (stok keluar gudang asal) → Terima (stok masuk gudang tujuan). Total stok tidak berubah selama transfer.')
                ->schema([
                    Select::make('from_warehouse_id')->label('Dari Gudang')
                        ->options(fn () => Warehouse::where('is_active', true)->pluck('name', 'id'))
                        ->required()->searchable()->native(false),
                    Select::make('to_warehouse_id')->label('Ke Gudang')
                        ->options(fn () => Warehouse::where('is_active', true)->pluck('name', 'id'))
                        ->required()->searchable()->native(false)
                        ->different('from_warehouse_id'),
                    Textarea::make('note')->label('Catatan')->maxLength(500)->columnSpanFull(),
                    Repeater::make('items')
                        ->label('Item')
                        ->relationship()
                        ->schema([
                            Select::make('product_id')->label('Produk')
                                ->options(fn () => Product::active()->orderBy('name')->limit(300)->pluck('name', 'id'))
                                ->searchable()->required()->live(),
                            Select::make('variant_id')->label('Varian')
                                ->options(fn ($get) => $get('product_id')
                                    ? \App\Models\ProductVariant::where('product_id', $get('product_id'))->pluck('sku', 'id')
                                    : [])
                                ->searchable()->native(false),
                            TextInput::make('quantity')->label('Jumlah')->numeric()->minValue(1)->required(),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_number')->label('Nomor')->searchable()->weight('semibold')->copyable(),
                TextColumn::make('fromWarehouse.name')->label('Dari'),
                TextColumn::make('toWarehouse.name')->label('Ke'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => StockTransfer::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        StockTransfer::STATUS_RECEIVED => 'success',
                        StockTransfer::STATUS_CANCELLED => 'danger',
                        StockTransfer::STATUS_IN_TRANSIT => 'info',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')->label('Dibuat')->since(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'view' => Pages\ViewStockTransfer::route('/{record}'),
        ];
    }
}
