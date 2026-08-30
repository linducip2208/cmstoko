<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Returns\ReturnRequestResource\Pages;
use App\Models\ReturnRequest;
use App\Models\StockMovement;
use App\Services\InventoryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Pengembalian';

    protected static ?string $modelLabel = 'Pengembalian';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengajuan')
                    ->schema([
                        TextEntry::make('return_number')->label('Nomor')->badge()->copyable(),
                        TextEntry::make('order.order_number')->label('Pesanan'),
                        TextEntry::make('user.name')->label('Pelanggan')->placeholder('—'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => ReturnRequest::STATUSES[$state] ?? $state)
                            ->color(fn (string $state) => match ($state) {
                                ReturnRequest::STATUS_REFUNDED, ReturnRequest::STATUS_RECEIVED => 'success',
                                ReturnRequest::STATUS_REJECTED, ReturnRequest::STATUS_CANCELLED => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('reason')->label('Alasan')->columnSpanFull(),
                    ])
                    ->columns(4),
                Section::make('Item')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('orderItem.product_name')->label('Produk'),
                                TextEntry::make('quantity')->label('Qty'),
                                TextEntry::make('orderItem.price')
                                    ->label('Harga')
                                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.')),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order.order_number')
                    ->label('Pesanan')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Pelanggan')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ReturnRequest::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        ReturnRequest::STATUS_REFUNDED, ReturnRequest::STATUS_RECEIVED => 'success',
                        ReturnRequest::STATUS_REJECTED, ReturnRequest::STATUS_CANCELLED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRecordActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui')
                ->color('primary')
                ->requiresConfirmation()
                ->action(fn (ReturnRequest $record) => tap($record)->update(['status' => ReturnRequest::STATUS_APPROVED]))
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_REQUESTED),

            Action::make('mark_received')
                ->label('Barang Diterima (restock)')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Stok item pengembalian akan dikembalikan ke inventaris.')
                ->action(function (ReturnRequest $record) {
                    $inventory = app(InventoryService::class);

                    foreach ($record->items()->with('orderItem')->get() as $item) {
                        if ($item->orderItem->product_id) {
                            $inventory->increase(
                                (int) $item->orderItem->product_id,
                                $item->orderItem->variant_id !== null ? (int) $item->orderItem->variant_id : null,
                                (int) $item->quantity,
                                StockMovement::TYPE_RETURN,
                                $record,
                                'Retur '.$record->return_number,
                            );
                        }
                    }

                    $record->update(['status' => ReturnRequest::STATUS_RECEIVED]);
                    Notification::make()->title('Barang diterima & stok dikembalikan')->success()->send();
                })
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_APPROVED),

            Action::make('mark_refunded')
                ->label('Dana Dikembalikan')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn (ReturnRequest $record) => $record->update(['status' => ReturnRequest::STATUS_REFUNDED]))
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_RECEIVED),

            Action::make('reject')
                ->label('Tolak')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn (ReturnRequest $record) => $record->update(['status' => ReturnRequest::STATUS_REJECTED]))
                ->visible(fn (ReturnRequest $record) => in_array($record->status, [ReturnRequest::STATUS_REQUESTED, ReturnRequest::STATUS_APPROVED], true)),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRequests::route('/'),
            'view' => Pages\ViewReturnRequest::route('/{record}'),
        ];
    }
}
