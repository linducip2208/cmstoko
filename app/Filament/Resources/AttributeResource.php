<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Attributes\AttributeResource\Pages;
use App\Models\Attribute;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Atribut';

    protected static ?string $modelLabel = 'Atribut';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Atribut')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(60)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, TextInput $component) {
                                if ($operation === 'create') {
                                    $component->state(null);
                                }
                            }),
                        Select::make('type')
                            ->label('Tipe Tampilan')
                            ->options([
                                Attribute::TYPE_SELECT => 'Pilihan',
                                Attribute::TYPE_COLOR => 'Warna',
                                Attribute::TYPE_TEXT => 'Teks',
                                Attribute::TYPE_NUMBER => 'Angka',
                            ])
                            ->default(Attribute::TYPE_SELECT)
                            ->native(false),
                        Toggle::make('is_variant')
                            ->label('Digunakan untuk varian')
                            ->default(true),
                        Toggle::make('is_required')
                            ->label('Wajib')
                            ->default(false),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                Section::make('Opsi')
                    ->description('Nilai-nilai yang tersedia untuk atribut ini (mis. ukuran: 39, 40, 41).')
                    ->schema([
                        Repeater::make('options')
                            ->hiddenLabel()
                            ->relationship('options')
                            ->reorderable()
                            ->itemLabel(fn (array $state) => $state['label'] ?? $state['value'] ?? 'Opsi')
                            ->schema([
                                TextInput::make('value')
                                    ->label('Nilai (slug)')
                                    ->required()
                                    ->maxLength(60),
                                TextInput::make('label')
                                    ->label('Label')
                                    ->required()
                                    ->maxLength(60),
                                ColorPicker::make('color')
                                    ->label('Warna (opsional, untuk swatch)')
                                    ->hex(),
                                TextInput::make('position')
                                    ->label('Urutan')
                                    ->numeric()
                                    ->default(0)
                                    ->hidden(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('is_variant')
                    ->label('Varian')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Ya' : 'Tidak'),
                TextColumn::make('options_count')
                    ->label('Opsi')
                    ->counts('options'),
            ])
            ->defaultSort('position');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributes::route('/'),
            'create' => Pages\CreateAttribute::route('/create'),
            'edit' => Pages\EditAttribute::route('/{record}/edit'),
        ];
    }
}
