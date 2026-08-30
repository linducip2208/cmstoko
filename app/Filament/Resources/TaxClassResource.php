<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Taxes\TaxClassResource\Pages;
use App\Models\TaxClass;
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

class TaxClassResource extends Resource
{
    protected static ?string $model = TaxClass::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Kelas Pajak';

    protected static ?string $modelLabel = 'Kelas Pajak';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kelas Pajak')
                ->description('Kelas dipilih per produk (Produk → tab Organisasi). Kelas default dipakai bila produk tidak memilih.')
                ->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(120)->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                    TextInput::make('slug')->required()->maxLength(140)->unique(ignoreRecord: true),
                    Toggle::make('is_default')->label('Kelas Default')
                        ->helperText('Hanya satu kelas default. Kelas tanpa tarif aktif = produk tidak kena pajak.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                TextColumn::make('rates_count')->label('Tarif')->counts('rates')->alignEnd(),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxClasses::route('/'),
            'create' => Pages\CreateTaxClass::route('/create'),
            'edit' => Pages\EditTaxClass::route('/{record}/edit'),
        ];
    }
}
