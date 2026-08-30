<?php

namespace App\Filament\Resources\StockMovements\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Support\Csv;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Csv::streamDownload(
                    'kartu-stok-'.now()->format('Ymd-His').'.csv',
                    ['Waktu', 'Produk', 'Varian', 'Jenis', 'Perubahan', 'Stok Awal', 'Stok Akhir', 'Catatan', 'Oleh'],
                    StockMovement::query()
                        ->with(['product', 'variant'])
                        ->orderBy('created_at')
                        ->cursor()
                        ->map(fn (StockMovement $movement) => [
                            $movement->created_at->format('Y-m-d H:i'),
                            $movement->product?->name,
                            $movement->variant?->sku,
                            StockMovement::TYPES[$movement->type] ?? $movement->type,
                            $movement->quantity,
                            $movement->stock_before,
                            $movement->stock_after,
                            $movement->note,
                            $movement->user_id,
                        ]),
                )),
        ];
    }
}
