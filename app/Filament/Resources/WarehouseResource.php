<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Warehouses\WarehouseResource\Pages;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|UnitEnum|null $navigationGroup = 'Persediaan';

    protected static ?string $navigationLabel = 'Gudang';

    protected static ?string $modelLabel = 'Gudang';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Gudang')
                ->description('Stok total produk adalah penjumlahan seluruh gudang aktif. Checkout mengalokasikan dari gudang default terlebih dahulu, lalu gudang lain.')
                ->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(120),
                    TextInput::make('code')->label('Kode')->required()->unique(ignoreRecord: true)->maxLength(30),
                    Toggle::make('is_default')->label('Gudang Default')
                        ->helperText('Hanya satu gudang default. Diprioritaskan saat alokasi otomatis.'),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                TextColumn::make('code')->label('Kode')->badge(),
                IconColumn::make('is_default')->label('Default')->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('is_default', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
