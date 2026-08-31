<?php

namespace App\Filament\Pages;

use App\Models\CustomerGroup;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Support\Csv;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Contracts\HasSchemas;
use UnitEnum;

class CustomersReport extends Page implements HasSchemas
{
    public string $view = 'filament.pages.customers-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Laporan Pelanggan';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('reports.view') ?? false;
    }

    public string $range = '30';

    protected function from(): CarbonInterface
    {
        return now()->subDays((int) $this->range - 1)->startOfDay();
    }

    public function totalCustomers(): int
    {
        return (int) User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', Role::CUSTOMER))
            ->count();
    }

    public function newCustomers(): int
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', Role::CUSTOMER))
            ->where('created_at', '>=', $this->from())
            ->count();
    }

    public function returningCustomers(): int
    {
        return (int) User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', Role::CUSTOMER))
            ->whereHas('orders', fn ($q) => $q->whereIn('status', Order::PAID_STATUSES)->where('created_at', '>=', $this->from()), '>=', 2)
            ->count();
    }

    public function topSpenders()
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', Role::CUSTOMER))
            ->withSum(['orders' => fn ($q) => $q->whereIn('status', Order::PAID_STATUSES)->where('created_at', '>=', $this->from())], 'total')
            ->withCount(['orders' => fn ($q) => $q->whereIn('status', Order::PAID_STATUSES)->where('created_at', '>=', $this->from())])
            ->having('orders_sum_total', '>', 0)
            ->orderByDesc('orders_sum_total')
            ->limit(10)
            ->get(['id', 'name', 'email']);
    }

    public function groupBreakdown()
    {
        return CustomerGroup::query()
            ->withCount(['users' => fn ($q) => $q->whereHas('role', fn ($r) => $r->where('slug', Role::CUSTOMER))])
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);
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
                        'laporan-pelanggan-'.now()->format('Ymd-His').'.csv',
                        ['Nama', 'Email', 'Grup', 'Pesanan Dibayar', 'Total Belanja'],
                        User::query()
                            ->whereHas('role', fn ($q) => $q->where('slug', Role::CUSTOMER))
                            ->withSum(['orders' => fn ($q) => $q->whereIn('status', Order::PAID_STATUSES)->where('created_at', '>=', $from)], 'total')
                            ->with('customerGroup:id,name')
                            ->cursor()
                            ->map(fn (User $user) => [
                                $user->name,
                                $user->email,
                                $user->customerGroup?->name ?? '-',
                                (int) $user->orders_sum_total !== 0 ? 1 : 0,
                                (int) $user->orders_sum_total,
                            ]),
                    );
                }),
        ];
    }

    public function title(): string
    {
        return 'Laporan Pelanggan';
    }

    public function getHeading(): string
    {
        return 'Laporan Pelanggan';
    }

    public function getSubheading(): ?string
    {
        return $this->range.' hari terakhir';
    }
}
