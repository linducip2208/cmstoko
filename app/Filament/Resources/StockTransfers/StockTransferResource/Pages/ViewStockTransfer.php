<?php

namespace App\Filament\Resources\StockTransfers\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use App\Models\StockTransfer;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransfer extends ViewRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ship')
                ->label('Kirim')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Stok akan dikeluarkan dari gudang asal sekarang.')
                ->visible(fn (StockTransfer $record) => $record->status === StockTransfer::STATUS_PENDING)
                ->action(function (StockTransfer $record) {
                    try {
                        app(InventoryService::class)->shipTransfer($record, auth()->id());
                        Notification::make()->title('Transfer dikirim')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('receive')
                ->label('Terima')
                ->icon('heroicon-o-inbox-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Stok akan masuk ke gudang tujuan.')
                ->visible(fn (StockTransfer $record) => $record->status === StockTransfer::STATUS_IN_TRANSIT)
                ->action(function (StockTransfer $record) {
                    try {
                        app(InventoryService::class)->receiveTransfer($record, auth()->id());
                        Notification::make()->title('Transfer diterima, stok gudang tujuan bertambah')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (StockTransfer $record) => $record->status === StockTransfer::STATUS_PENDING)
                ->action(function (StockTransfer $record) {
                    try {
                        app(InventoryService::class)->cancelTransfer($record, null, auth()->id());
                        Notification::make()->title('Transfer dibatalkan')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
