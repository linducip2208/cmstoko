<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Taxes\TaxRateResource\Pages;
use App\Models\TaxClass;
use App\Models\TaxRate;
use BackedEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TaxRateResource extends Resource
{
    protected static ?string $model = TaxRate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pajak';

    protected static ?string $modelLabel = 'Tarif Pajak';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tarif Pajak')
                ->description('Tarif dalam persen (mis. 11 = PPN 11%). Zona: kosongkan provinsi/kota untuk berlaku nasional. Pajak inkonsif: harga produk sudah termasuk pajak; eksklusif: ditambahkan di checkout. Pajak dihitung server-side atas harga setelah diskon.')
                ->schema([
                    Select::make('tax_class_id')->label('Kelas Pajak')
                        ->options(fn () => TaxClass::orderBy('name')->pluck('name', 'id'))
                        ->required()->searchable()->native(false),
                    TextInput::make('name')->label('Nama Tarif')->required()->maxLength(150)->columnSpanFull(),
                    TextInput::make('rate_bp')->label('Tarif (%)')
                        ->numeric()->minValue(0)->maxValue(100)->step(0.01)->required()
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round(((float) $state) * 100) : null)
                        ->helperText('Disimpan sebagai basis points (integer) — tanpa pembulatan float.'),
                    ToggleButtons::make('type')->label('Tipe')->options([
                        TaxRate::TYPE_EXCLUSIVE => 'Eksklusif (ditambahkan)',
                        TaxRate::TYPE_INCLUSIVE => 'Inklusif (sudah termasuk)',
                    ])->default(TaxRate::TYPE_EXCLUSIVE)->inline()->required(),
                    Toggle::make('applies_to_shipping')->label('Berlaku untuk ongkir')->default(false),
                ])
                ->columns(2),
            Section::make('Zona')
                ->schema([
                    Select::make('province_id')->label('Provinsi')
                        ->options(fn () => \App\Models\Province::orderBy('name')->pluck('name', 'id'))
                        ->searchable()->native(false)
                        ->helperText('Kosong = seluruh Indonesia'),
                    Select::make('city_id')->label('Kota')
                        ->options(fn ($get) => $get('province_id')
                            ? \App\Models\City::where('province_id', $get('province_id'))->orderBy('name')->pluck('name', 'id')
                            : [])
                        ->searchable()->native(false),
                ])
                ->columns(2),
            Section::make('Status')
                ->schema([
                    TextInput::make('priority')->label('Prioritas')->numeric()->default(0)->helperText('Lebih tinggi dievaluasi dulu'),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('taxClass'))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                TextColumn::make('taxClass.name')->label('Kelas')->badge(),
                TextColumn::make('rate_bp')->label('Tarif')->formatStateUsing(fn (int $state) => number_format($state / 100, 2).' %'),
                TextColumn::make('type')->label('Tipe')->badge()
                    ->formatStateUsing(fn (string $state) => $state === TaxRate::TYPE_INCLUSIVE ? 'Inklusif' : 'Eksklusif')
                    ->color(fn (string $state) => $state === TaxRate::TYPE_INCLUSIVE ? 'warning' : 'info'),
                TextColumn::make('zone')->label('Zona')
                    ->state(fn (TaxRate $record) => $record->city_id
                        ? 'Kota #'.$record->city_id
                        : ($record->province_id ? 'Provinsi #'.$record->province_id : 'Nasional')),
                IconColumn::make('applies_to_shipping')->label('Ongkir')->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxRates::route('/'),
            'create' => Pages\CreateTaxRate::route('/create'),
            'edit' => Pages\EditTaxRate::route('/{record}/edit'),
        ];
    }
}
