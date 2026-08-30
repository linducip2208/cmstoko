<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CartRules\CartRuleResource\Pages;
use App\Models\Brand;
use App\Models\CartRule;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CartRuleResource extends Resource
{
    protected static ?string $model = CartRule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Promosi';

    protected static ?string $navigationLabel = 'Aturan Keranjang';

    protected static ?string $modelLabel = 'Aturan Keranjang';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Aturan Promosi')
                ->description('Dievaluasi otomatis di checkout (server-side). Beberapa aturan dapat menumpuk; total diskon dibatasi subtotal. Untuk pelanggan, cocokkan grup pada kolom "Grup Pelanggan".')
                ->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(150),
                    TextInput::make('description')->label('Deskripsi Internal')->maxLength(500),
                    Select::make('action_type')->label('Aksi')->options([
                        CartRule::ACTION_PERCENT => 'Diskon Persen',
                        CartRule::ACTION_FIXED => 'Diskon Nominal (Rp)',
                        CartRule::ACTION_FREE_SHIPPING => 'Gratis Ongkir',
                    ])->default(CartRule::ACTION_PERCENT)->live()->required(),
                    TextInput::make('action_value')->label(fn ($get) => $get('action_type') === CartRule::ACTION_PERCENT ? 'Persen (1-100)' : 'Nominal (Rp)')
                        ->numeric()->minValue(0)
                        ->visible(fn ($get) => $get('action_type') !== CartRule::ACTION_FREE_SHIPPING)
                        ->required(fn ($get) => $get('action_type') !== CartRule::ACTION_FREE_SHIPPING),
                    Select::make('customer_group_id')->label('Grup Pelanggan (opsional)')
                        ->options(fn () => CustomerGroup::orderBy('sort_order')->pluck('name', 'id'))
                        ->searchable()
                        ->helperText('Kosong = semua pelanggan. Pilih Guest hanya berlaku untuk checkout tanpa akun.'),
                    TextInput::make('priority')->label('Prioritas')->numeric()->default(0),
                ])
                ->columns(2),
            Section::make('Kondisi')
                ->description('Semua kondisi bersifat opsional dan digabung dengan AND. Kondisi produk/kategori/merek terpenuhi bila MINIMAL SATU item di keranjang cocok.')
                ->schema([
                    TextInput::make('conditions.min_subtotal')->label('Subtotal Minimum (Rp)')->numeric()->minValue(0),
                    TextInput::make('conditions.max_subtotal')->label('Subtotal Maksimum (Rp)')->numeric()->minValue(0),
                    TextInput::make('conditions.quantity_min')->label('Jumlah Item Minimum')->numeric()->minValue(1),
                    Select::make('conditions.product_ids')->label('Produk Tertentu')
                        ->options(fn () => Product::active()->orderBy('name')->limit(200)->pluck('name', 'id'))
                        ->multiple()->searchable()->native(false),
                    Select::make('conditions.category_ids')->label('Kategori (termasuk turunannya)')
                        ->options(fn () => Category::active()->orderBy('name')->pluck('name', 'id'))
                        ->multiple()->searchable()->native(false),
                    Select::make('conditions.brand_ids')->label('Merek')
                        ->options(fn () => Brand::active()->orderBy('name')->pluck('name', 'id'))
                        ->multiple()->searchable()->native(false),
                ])
                ->columns(2),
            Section::make('Jadwal & Kuota')
                ->schema([
                    Toggle::make('is_active')->label('Aktif')->default(true),
                    DateTimePicker::make('starts_at')->label('Mulai'),
                    DateTimePicker::make('ends_at')->label('Berakhir')->after('starts_at'),
                    TextInput::make('usage_limit')->label('Batas Pemakaian')->numeric()->minValue(1)->nullable(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                TextColumn::make('action_type')->label('Aksi')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        CartRule::ACTION_PERCENT => 'Persen',
                        CartRule::ACTION_FIXED => 'Nominal',
                        CartRule::ACTION_FREE_SHIPPING => 'Gratis Ongkir',
                        default => $state,
                    }),
                TextColumn::make('action_value')->label('Nilai')->formatStateUsing(fn ($state, CartRule $record) => match ($record->action_type) {
                    CartRule::ACTION_PERCENT => $state.'%',
                    CartRule::ACTION_FIXED => rupiah((int) $state),
                    default => '—',
                }),
                TextColumn::make('customerGroup.name')->label('Grup')->placeholder('Semua'),
                TextColumn::make('used_count')->label('Terpakai')->formatStateUsing(fn ($state, CartRule $record) => $state.($record->usage_limit ? ' / '.$record->usage_limit : '')),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCartRules::route('/'),
            'create' => Pages\CreateCartRule::route('/create'),
            'edit' => Pages\EditCartRule::route('/{record}/edit'),
        ];
    }
}
