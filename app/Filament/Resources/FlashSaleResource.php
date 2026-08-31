<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlashSales\FlashSaleResource\Pages;
use App\Models\FlashSale;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class FlashSaleResource extends Resource
{
    protected static ?string $model = FlashSale::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|UnitEnum|null $navigationGroup = 'Promosi';

    protected static ?string $navigationLabel = 'Flash Sale';

    protected static ?string $modelLabel = 'Flash Sale';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Flash Sale')
                ->description('Harga flash sale bersifat server-authoritative: berlaku saat jendela aktif dan otomatis kembali setelah berakhir. Harga final = harga termurah antara flash price dan sale price produk.')
                ->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(120)->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')->required()->maxLength(140)->unique(ignoreRecord: true),
                    DateTimePicker::make('starts_at')->label('Mulai')->required(),
                    DateTimePicker::make('ends_at')->label('Berakhir')->required()->after('starts_at'),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])
                ->columns(2),
            Section::make('Produk & Harga Flash')
                ->schema([
                    Repeater::make('products')
                        ->label('Item')
                        ->relationship()
                        ->schema([
                            Select::make('product_id')
                                ->label('Produk')
                                ->options(fn () => Product::active()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->distinct(),
                            TextInput::make('flash_price')
                                ->label('Harga Flash (Rp)')
                                ->numeric()->minValue(1)->required(),
                            TextInput::make('stock_limit')
                                ->label('Batas Kuota (opsional)')
                                ->numeric()->minValue(1)->nullable()->helperText('Informatif — ditampilkan di storefront'),
                            TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                TextColumn::make('starts_at')->label('Mulai')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('ends_at')->label('Berakhir')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (FlashSale $record) => $record->isActiveNow() ? 'Aktif' : ($record->starts_at->isFuture() ? 'Terjadwal' : 'Selesai'))
                    ->color(fn (string $state) => match ($state) {
                        'Aktif' => 'success',
                        'Terjadwal' => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('products_count')->label('Item')->counts('products'),
            ])
            ->defaultSort('starts_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlashSales::route('/'),
            'create' => Pages\CreateFlashSale::route('/create'),
            'edit' => Pages\EditFlashSale::route('/{record}/edit'),
        ];
    }
}
