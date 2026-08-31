<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerGroups\CustomerGroupResource\Pages;
use App\Models\CustomerGroup;
use BackedEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CustomerGroupResource extends Resource
{
    protected static ?string $model = CustomerGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Pelanggan';

    protected static ?string $navigationLabel = 'Grup Pelanggan';

    protected static ?string $modelLabel = 'Grup Pelanggan';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Grup Pelanggan')
                ->description('Grup dipakai untuk targeting aturan promosi. Grup Guest tidak dipakai sebagai grup pengguna — mewakili checkout tanpa akun.')
                ->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(80)->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')->required()->maxLength(100)->unique(ignoreRecord: true),
                    TextInput::make('description')->label('Deskripsi')->maxLength(300)->columnSpanFull(),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                    TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                TextColumn::make('slug')->label('Slug')->badge(),
                TextColumn::make('users_count')->label('Pelanggan')->counts('users')->alignEnd(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerGroups::route('/'),
            'create' => Pages\CreateCustomerGroup::route('/create'),
            'edit' => Pages\EditCustomerGroup::route('/{record}/edit'),
        ];
    }
}
