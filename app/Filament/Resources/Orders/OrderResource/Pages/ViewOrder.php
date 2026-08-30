<?php

namespace App\Filament\Resources\Orders\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                OrderResource::statusAction(Order::STATUS_PAID),
                OrderResource::statusAction(Order::STATUS_PROCESSING),
                OrderResource::statusAction(Order::STATUS_SHIPPED),
                OrderResource::statusAction(Order::STATUS_COMPLETED),
                OrderResource::statusAction(Order::STATUS_CANCELLED),
            ])
                ->label('Ubah Status')
                ->icon('heroicon-m-adjustments-horizontal')
                ->color('primary')
                ->button(),
        ];
    }
}
