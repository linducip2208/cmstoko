<?php

namespace App\Filament\Resources\Orders\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderFulfillmentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getHeaderActions(): array
    {
        $user = Auth::user();

        return [
            Action::make('add_note')
                ->label('Tambah Catatan')
                ->icon('heroicon-m-chat-bubble-left-ellipsis')
                ->color('gray')
                ->schema([
                    Textarea::make('body')
                        ->label('Catatan')
                        ->required()
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, Order $record) {
                    $record->notes()->create([
                        'user_id' => Auth::id(),
                        'body' => $data['body'],
                    ]);

                    Notification::make()->title('Catatan ditambahkan')->success()->send();
                })
                ->visible(fn () => $user->hasPermission('orders.update')),

            Action::make('create_invoice')
                ->label('Buat Invoice')
                ->icon('heroicon-m-document-text')
                ->color('info')
                ->requiresConfirmation()
                ->action(function (Order $record) {
                    app(OrderFulfillmentService::class)->createInvoice($record);

                    Notification::make()->title('Invoice dibuat')->success()->send();
                })
                ->visible(fn (Order $record) => $user->hasPermission('invoices.create')
                    && $record->invoices()->where('status', '!=', 'cancelled')->doesntExist()),

            Action::make('ship')
                ->label('Kirim Pesanan')
                ->icon('heroicon-m-truck')
                ->color('primary')
                ->schema(fn (Order $record) => [
                    TextInput::make('courier')
                        ->label('Kurir')
                        ->default($record->shipping_courier)
                        ->required()
                        ->maxLength(40),
                    TextInput::make('service')
                        ->label('Layanan')
                        ->default($record->shipping_service)
                        ->required()
                        ->maxLength(40),
                    TextInput::make('tracking_number')
                        ->label('Nomor Resi')
                        ->maxLength(60),
                    KeyValue::make('lines')
                        ->label('Jumlah Kirim per Item (ID Item = Qty)')
                        ->helperText('Kosongkan untuk mengirim seluruh sisa item. Format: ID item = jumlah.')
                        ->keyLabel('ID Order Item')
                        ->valueLabel('Qty'),
                ])
                ->action(function (array $data, Order $record) {
                    $remaining = collect($record->items()->get())
                        ->mapWithKeys(function ($item) use ($record) {
                            $shipped = $record->shippedQuantities()[$item->id] ?? 0;

                            return [$item->id => max(0, $item->quantity - $shipped)];
                        })
                        ->filter(fn ($qty) => $qty > 0);

                    $lines = [];

                    foreach ((array) ($data['lines'] ?? []) as $itemId => $qty) {
                        if ((int) $qty > 0) {
                            $lines[] = ['order_item_id' => (int) $itemId, 'quantity' => (int) $qty];
                        }
                    }

                    if ($lines === []) {
                        $lines = $remaining->map(fn ($qty, $itemId) => ['order_item_id' => $itemId, 'quantity' => $qty])->values()->all();
                    }

                    app(OrderFulfillmentService::class)->ship(
                        $record,
                        $lines,
                        $data['courier'],
                        $data['service'],
                        $data['tracking_number'] ?? null,
                    );

                    Notification::make()->title('Pesanan dikirim')->success()->send();
                })
                ->visible(fn (Order $record) => $user->hasPermission('shipments.create')
                    && in_array($record->status, [Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_READY_TO_SHIP, Order::STATUS_PARTIALLY_SHIPPED], true)),

            Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color(Color::Orange)
                ->requiresConfirmation()
                ->modalDescription(fn (Order $record) => 'Dana yang dapat dikembalikan: Rp '.number_format($record->refundableAmount(), 0, ',', '.'))
                ->schema(fn (Order $record) => [
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->prefix('Rp')
                        ->default($record->refundableAmount())
                        ->maxValue($record->refundableAmount())
                        ->required(),
                    Textarea::make('reason')
                        ->label('Alasan')
                        ->maxLength(300)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, Order $record) {
                    app(OrderFulfillmentService::class)->refund(
                        $record,
                        (int) $data['amount'],
                        $data['reason'] ?? null,
                    );

                    Notification::make()->title('Refund diproses')->success()->send();
                })
                ->visible(fn (Order $record) => $user->hasPermission('refunds.create') && $record->isRefundable()),

            ActionGroup::make([
                OrderResource::statusAction(Order::STATUS_PAID),
                OrderResource::statusAction(Order::STATUS_PROCESSING),
                OrderResource::statusAction(Order::STATUS_READY_TO_SHIP),
                OrderResource::statusAction(Order::STATUS_SHIPPED),
                OrderResource::statusAction(Order::STATUS_COMPLETED),
                OrderResource::statusAction(Order::STATUS_CANCELLED),
            ])
                ->label('Ubah Status')
                ->icon('heroicon-m-adjustments-horizontal')
                ->color('gray')
                ->button()
                ->visible(fn () => $user->hasPermission('orders.update')),
        ];
    }
}
