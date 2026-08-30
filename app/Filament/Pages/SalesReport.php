<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\OrderItem;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use UnitEnum;

class SalesReport extends Page
{
    public string $view = 'filament.pages.sales-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?int $navigationSort = 3;

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
        return Order::whereIn('status', [
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
        ])->where('created_at', '>=', $this->from());
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
            ->whereIn('orders.status', [Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_COMPLETED])
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
