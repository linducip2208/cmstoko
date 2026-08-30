<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Coupons\CouponResource\Pages;
use App\Models\Coupon;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Kupon';

    protected static ?string $modelLabel = 'Kupon';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(30)
                    ->prefixIcon('heroicon-m-ticket'),
                Select::make('type')
                    ->label('Tipe')
                    ->options([
                        Coupon::TYPE_FIXED => 'Nominal (Rp)',
                        Coupon::TYPE_PERCENT => 'Persentase (%)',
                    ])
                    ->required()
                    ->native(false),
                TextInput::make('value')
                    ->label(fn (string $operation) => 'Nilai')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('Isi dalam Rupiah atau persen sesuai tipe.'),
                TextInput::make('min_purchase')
                    ->label('Minimum Belanja')
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0)
                    ->default(0),
                TextInput::make('max_uses')
                    ->label('Batas Pemakaian')
                    ->numeric()
                    ->minValue(1),
                DateTimePicker::make('starts_at')
                    ->label('Mulai Berlaku'),
                DateTimePicker::make('expires_at')
                    ->label('Berakhir'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->copyable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state) => $state === Coupon::TYPE_PERCENT ? 'Persentase' : 'Nominal'),
                TextColumn::make('value')
                    ->label('Nilai')
                    ->formatStateUsing(fn ($record, $state) => $record->type === Coupon::TYPE_PERCENT
                        ? $state.'%'
                        : 'Rp '.number_format((int) $state, 0, ',', '.')),
                TextColumn::make('used_count')
                    ->label('Terpakai')
                    ->formatStateUsing(fn ($record, $state) => $state.($record->max_uses ? " / {$record->max_uses}" : '')),
                TextColumn::make('expires_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCoupons::route('/'),
        ];
    }
}
