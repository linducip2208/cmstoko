<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Roles\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Peran & Izin';

    protected static ?string $modelLabel = 'Peran';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $groups = Permission::query()->orderBy('group')->get()->groupBy('group');

        $permissionTabs = $groups
            ->map(fn ($permissions, string $group) => Tabs\Tab::make(Str::title(str_replace('-', ' ', $group)))
                ->schema([
                    CheckboxList::make('permissions')
                        ->hiddenLabel()
                        ->options(fn () => $permissions->pluck('name', 'slug')->all())
                        ->bulkToggleable()
                        ->columns(2)
                        ->gridDirection('row'),
                ]))
            ->values()
            ->all();

        return $schema
            ->components([
                Section::make('Peran')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(60),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(60),
                        Toggle::make('is_staff')
                            ->label('Staf (boleh mengakses panel admin)')
                            ->default(true)
                            ->helperText('Nonaktif = peran pelanggan, tanpa akses admin.'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Tabs::make('Izin')
                    ->tabs($permissionTabs)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Peran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color(fn (Role $record) => $record->slug === Role::SUPER_ADMIN ? 'danger' : 'gray'),
                TextColumn::make('users_count')
                    ->label('Pengguna')
                    ->counts('users'),
                TextColumn::make('permissions_count')
                    ->label('Izin')
                    ->counts('permissions'),
                TextColumn::make('is_staff')
                    ->label('Tipe')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Staf' : 'Pelanggan')
                    ->badge()
                    ->color(fn (bool $state) => $state ? 'primary' : 'gray'),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
