<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Orders\OrderResource\Pages;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Pesanan';

    protected static ?string $modelLabel = 'Pesanan';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Status Pesanan')
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('Nomor Pesanan')
                            ->badge()
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (Order $record) => match ($record->status) {
                                Order::STATUS_COMPLETED => 'success',
                                Order::STATUS_SHIPPED, Order::STATUS_PROCESSING => 'info',
                                Order::STATUS_PAID => 'primary',
                                Order::STATUS_CANCELLED => 'danger',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (Order $record) => $record->statusLabel()),
                        TextEntry::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->formatStateUsing(fn (?string $state) => $state === 'midtrans' ? 'Midtrans (Payment Gateway)' : ($state ? 'Transfer Manual' : '-')),
                        TextEntry::make('transaction_id')
                            ->label('ID Transaksi')
                            ->placeholder('-'),
                        TextEntry::make('paid_at')
                            ->label('Dibayar Pada')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(5),
                Section::make('Informasi Pelanggan')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Nama'),
                        TextEntry::make('customer_email')
                            ->label('Email'),
                        TextEntry::make('customer_phone')
                            ->label('Telepon'),
                    ])
                    ->columns(3),
                Section::make('Alamat Pengiriman')
                    ->schema([
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->columnSpanFull(),
                        TextEntry::make('city_name')
                            ->label('Kota/Kabupaten'),
                        TextEntry::make('province_name')
                            ->label('Provinsi'),
                        TextEntry::make('postal_code')
                            ->label('Kode Pos')
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-'),
                    ])
                    ->columns(4),
                Section::make('Barang Pesanan')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label('Produk')
                                    ->columnSpan(2),
                                TextEntry::make('price')
                                    ->label('Harga')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                                TextEntry::make('quantity')
                                    ->label('Qty'),
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                            ])
                            ->columns(5),
                    ]),
                Section::make('Pembayaran & Pengiriman')
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                        TextEntry::make('coupon_code')
                            ->label('Kode Kupon')
                            ->placeholder('-')
                            ->badge(),
                        TextEntry::make('discount')
                            ->label('Diskon')
                            ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                        TextEntry::make('shipping_courier')
                            ->label('Kurir')
                            ->formatStateUsing(fn (Order $record) => $record->shipping_service
                                ? strtoupper($record->shipping_courier ?? '').' - '.$record->shipping_service.' ('.$record->shipping_etd.' hari)'
                                : '-'),
                        TextEntry::make('shipping_cost')
                            ->label('Ongkos Kirim')
                            ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                        TextEntry::make('total')
                            ->label('Total')
                            ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                            ->weight('bold')
                            ->size(TextEntry\TextEntrySize::Large),
                    ])
                    ->columns(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items'),
                TextColumn::make('total')
                    ->label('Total')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Order::STATUS_COMPLETED => 'success',
                        Order::STATUS_SHIPPED, Order::STATUS_PROCESSING => 'info',
                        Order::STATUS_PAID => 'primary',
                        Order::STATUS_CANCELLED => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (Order $record) => $record->statusLabel()),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Order::STATUSES),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function statusAction(string $status): Action
    {
        $labels = [
            Order::STATUS_PAID => 'Tandai Dibayar',
            Order::STATUS_PROCESSING => 'Proses Pesanan',
            Order::STATUS_SHIPPED => 'Kirim Pesanan',
            Order::STATUS_COMPLETED => 'Selesaikan',
            Order::STATUS_CANCELLED => 'Batalkan',
        ];

        $icons = [
            Order::STATUS_PAID => 'heroicon-m-banknotes',
            Order::STATUS_PROCESSING => 'heroicon-m-cog-6-tooth',
            Order::STATUS_SHIPPED => 'heroicon-m-truck',
            Order::STATUS_COMPLETED => 'heroicon-m-check-circle',
            Order::STATUS_CANCELLED => 'heroicon-m-x-circle',
        ];

        return Action::make("mark_{$status}")
            ->label($labels[$status])
            ->icon($icons[$status])
            ->color(match ($status) {
                Order::STATUS_CANCELLED => 'danger',
                Order::STATUS_COMPLETED => 'success',
                default => 'primary',
            })
            ->requiresConfirmation()
            ->visible(fn (Order $record) => $record->status !== $status && ! in_array($record->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED]))
            ->action(fn (Order $record) => $record->forceFill([
                'status' => $status,
                'paid_at' => $status === Order::STATUS_PAID ? now() : $record->paid_at,
            ])->save());
    }
}
