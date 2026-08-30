<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenue = Order::whereIn('status', [
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
        ])->sum('total');

        $revenueToday = Order::whereDate('paid_at', today())
            ->orWhereDate('created_at', today())
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_COMPLETED])
            ->sum('total');

        return [
            Stat::make('Total Pendapatan', 'Rp '.number_format((int) $revenue, 0, ',', '.'))
                ->description('Semua pesanan terkonfirmasi')
                ->color('success')
                ->icon('heroicon-m-banknotes'),
            Stat::make('Pendapatan Hari Ini', 'Rp '.number_format((int) $revenueToday, 0, ',', '.'))
                ->color('primary')
                ->icon('heroicon-m-calendar-days'),
            Stat::make('Pesanan Baru', (string) Order::status(Order::STATUS_PENDING)->count())
                ->description('Menunggu pembayaran')
                ->color('warning')
                ->icon('heroicon-m-clock'),
            Stat::make('Produk Aktif', (string) Product::active()->count())
                ->color('info')
                ->icon('heroicon-m-cube'),
        ];
    }
}
