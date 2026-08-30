<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Users\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Akun')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(150),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(25),
                        TextInput::make('password')
                            ->label(fn (string $operation) => $operation === 'create' ? 'Password' : 'Password Baru (opsional)')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->required(fn (string $operation) => $operation === 'create')
                            ->minLength(8)
                            ->maxLength(64)
                            ->same('password_confirmation'),
                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation) => $operation === 'create'),
                    ])
                    ->columns(2),
                Section::make('Peran')
                    ->schema([
                        Select::make('role_id')
                            ->label('Peran')
                            ->options(fn () => Role::orderBy('sort_order')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->helperText('Super Admin memiliki akses penuh. Gunakan peran spesifik untuk operasional.'),
                        Select::make('customer_group_id')
                            ->label('Grup Pelanggan')
                            ->options(fn () => \App\Models\CustomerGroup::where('slug', '!=', \App\Models\CustomerGroup::SLUG_GUEST)->orderBy('sort_order')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->helperText('Untuk pelanggan — menentukan aturan promosi yang berlaku (mis. VIP).')
                            ->visible(fn ($get) => $get('role_id') == \App\Models\Role::where('slug', \App\Models\Role::CUSTOMER)->value('id')),
                    ]),
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
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role.name')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (User $record) => $record->isSuperAdmin() ? 'danger' : 'primary'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
