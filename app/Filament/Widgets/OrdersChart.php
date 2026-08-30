<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersChart extends ChartWidget
{
    protected ?string $heading = 'Pesanan 30 Hari Terakhir';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $from = now()->subDays(29)->startOfDay();

        $rows = Order::where('created_at', '>=', $from)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END) as revenue")
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $data = [];

        foreach (collect(range(0, 29)) as $i) {
            $date = $from->copy()->addDays($i);
            $labels[] = $date->format('d M');
            $data[] = (int) ($rows[$date->format('Y-m-d')] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pesanan',
                    'data' => $data,
                    'backgroundColor' => '#6366f1',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }
}
