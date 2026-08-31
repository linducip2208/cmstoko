<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Support\Csv;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Collection;
use UnitEnum;

class TaxReport extends Page implements HasSchemas
{
    public string $view = 'filament.pages.tax-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Laporan Pajak';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('reports.view') ?? false;
    }

    public string $range = '30';

    protected function from(): CarbonInterface
    {
        return now()->subDays((int) $this->range - 1)->startOfDay();
    }

    protected function taxedOrders()
    {
        return Order::whereIn('status', Order::PAID_STATUSES)
            ->where('created_at', '>=', $this->from())
            ->where('tax_amount', '>', 0);
    }

    public function totalTax(): int
    {
        return (int) $this->taxedOrders()->sum('tax_amount');
    }

    public function taxedOrderCount(): int
    {
        return (int) $this->taxedOrders()->count();
    }

    /**
     * Aggregate per rate name from the immutable order snapshots.
     *
     * @return Collection<int, object{name: string, amount: int}>
     */
    public function breakdown()
    {
        $totals = [];

        $this->taxedOrders()
            ->get(['id', 'tax_snapshot'])
            ->each(function (Order $order) use (&$totals) {
                foreach ((array) ($order->tax_snapshot ?? []) as $entry) {
                    $name = $entry['name'] ?? 'Pajak';
                    $totals[$name] = ($totals[$name] ?? 0) + (int) ($entry['amount'] ?? 0);
                }
            });

        return collect($totals)
            ->map(fn (int $amount, string $name) => (object) ['name' => $name, 'amount' => $amount])
            ->sortByDesc('amount')
            ->values();
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Csv::streamDownload(
                    'laporan-pajak-'.now()->format('Ymd-His').'.csv',
                    ['Nomor Pesanan', 'Tanggal', 'Status', 'DPP (Subtotal-Diskon)', 'Pajak'],
                    $this->taxedOrders()
                        ->orderBy('created_at')
                        ->with('items')
                        ->cursor()
                        ->map(fn (Order $order) => [
                            $order->order_number,
                            $order->created_at->format('Y-m-d H:i'),
                            $order->status,
                            $order->subtotal - $order->discount - $order->rule_discount,
                            $order->tax_amount,
                        ]),
                )),
        ];
    }

    public function title(): string
    {
        return 'Laporan Pajak';
    }

    public function getHeading(): string
    {
        return 'Laporan Pajak';
    }

    public function getSubheading(): ?string
    {
        return $this->range.' hari terakhir — dari snapshot order (kebal perubahan tarif)';
    }
}
