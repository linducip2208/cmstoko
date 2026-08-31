<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Support\Csv;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Contracts\HasSchemas;
use UnitEnum;

class InventoryReport extends Page implements HasSchemas
{
    public string $view = 'filament.pages.inventory-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Laporan Persediaan';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('reports.view') ?? false;
    }

    public int $lowStockThreshold = 5;

    public function lowStock()
    {
        return Product::query()
            ->active()
            ->where('stock', '<=', $this->lowStockThreshold)
            ->orderBy('stock')
            ->limit(50)
            ->get(['id', 'name', 'sku', 'stock', 'price', 'sale_price']);
    }

    public function outOfStockCount(): int
    {
        return (int) Product::query()->active()->where('stock', '=', 0)->count();
    }

    public function stockValue(): int
    {
        return (int) Product::query()
            ->active()
            ->selectRaw('SUM(stock * COALESCE(sale_price, price)) as value')
            ->value('value');
    }

    public function totalSkus(): int
    {
        return (int) Product::query()->active()->count();
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Csv::streamDownload(
                    'laporan-persediaan-'.now()->format('Ymd-His').'.csv',
                    ['Produk', 'SKU', 'Stok', 'Harga', 'Nilai Stok'],
                    Product::query()
                        ->active()
                        ->orderBy('stock')
                        ->cursor()
                        ->map(fn (Product $product) => [
                            $product->name,
                            $product->sku,
                            $product->stock,
                            $product->effectivePrice(),
                            $product->stock * $product->effectivePrice(),
                        ]),
                )),
        ];
    }

    public function title(): string
    {
        return 'Laporan Persediaan';
    }

    public function getHeading(): string
    {
        return 'Laporan Persediaan';
    }

    public function getSubheading(): ?string
    {
        return 'Nilai & kesehatan stok';
    }
}
