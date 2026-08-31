<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Csv;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Contracts\HasSchemas;
use UnitEnum;

class SalesReport extends Page implements HasSchemas
{
    public string $view = 'filament.pages.sales-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('reports.view') ?? false;
    }

    public string $range = '30';

    public function updatedRange(): void
    {
        $this->resetStatsCache();
    }

    protected function from(): CarbonInterface
    {
        return now()->subDays((int) $this->range - 1)->startOfDay();
    }

    protected function paidOrders()
    {
        return Order::whereIn('status', Order::PAID_STATUSES)
            ->where('created_at', '>=', $this->from());
    }

    public function revenue(): int
    {
        return (int) $this->paidOrders()->sum('total');
    }

    public function orderCount(): int
    {
        return (int) $this->paidOrders()->count();
    }

    public function averageOrderValue(): int
    {
        return $this->orderCount() > 0 ? (int) round($this->revenue() / $this->orderCount()) : 0;
    }

    public function topProducts()
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', Order::PAID_STATUSES)
            ->where('orders.created_at', '>=', $this->from())
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as total_qty, SUM(order_items.subtotal) as total_revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
    }

    public function recentPaidOrders()
    {
        return $this->paidOrders()->latest()->limit(10)->get();
    }

    protected function resetStatsCache(): void
    {
        $this->dispatch('$refresh');
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $from = $this->from();

                    return Csv::streamDownload(
                        'laporan-penjualan-'.now()->format('Ymd-His').'.csv',
                        ['Nomor Pesanan', 'Tanggal', 'Pelanggan', 'Email', 'Status', 'Subtotal', 'Diskon', 'Ongkir', 'Total'],
                        Order::whereIn('status', Order::PAID_STATUSES)
                            ->where('created_at', '>=', $from)
                            ->orderBy('created_at')
                            ->cursor()
                            ->map(fn (Order $order) => [
                                $order->order_number,
                                $order->created_at->format('Y-m-d H:i'),
                                $order->customer_name,
                                $order->customer_email,
                                $order->status,
                                $order->subtotal,
                                $order->discount,
                                $order->shipping_cost,
                                $order->total,
                            ]),
                    );
                }),
        ];
    }

    public function title(): string
    {
        return 'Laporan Penjualan';
    }

    public function getHeading(): string
    {
        return 'Laporan Penjualan';
    }

    public function getSubheading(): ?string
    {
        return $this->range.' hari terakhir';
    }
}
