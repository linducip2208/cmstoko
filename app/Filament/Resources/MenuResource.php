<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Menus\MenuResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Menu;
use App\Models\MenuItem;
use BackedEnum;
use Filament\Forms\Components\Repeater;
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

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|UnitEnum|null $navigationGroup = 'Tampilan';

    protected static ?string $navigationLabel = 'Menu Navigasi';

    protected static ?string $modelLabel = 'Menu';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu')
                ->description('Menu header menggantikan navigasi kategori default; menu footer mengisi kolom Jelajahi. Item dengan target yang sudah dihapus otomatis disembunyikan.')
                ->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(120),
                    TextInput::make('slug')->required()->maxLength(140)->unique(ignoreRecord: true),
                    Select::make('location')->label('Lokasi')->options([
                        Menu::LOCATION_HEADER => 'Header',
                        Menu::LOCATION_FOOTER => 'Footer',
                    ])->required(),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])
                ->columns(2),
            Section::make('Item')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label('')
                        ->schema([
                            TextInput::make('label')->label('Label')->required()->maxLength(120),
                            Select::make('target_type')->label('Tautan Ke')->options([
                                MenuItem::TARGET_CUSTOM => 'URL Kustom',
                                MenuItem::TARGET_CATEGORY => 'Kategori',
                                MenuItem::TARGET_BRAND => 'Merek',
                                MenuItem::TARGET_PAGE => 'Halaman CMS',
                            ])->default(MenuItem::TARGET_CUSTOM)->live()->required(),
                            TextInput::make('url')
                                ->label('URL')
                                ->maxLength(500)
                                ->visible(fn ($get) => $get('target_type') === MenuItem::TARGET_CUSTOM)
                                ->helperText('Path internal, mis. /produk atau /halaman/tentang-kami'),
                            Select::make('target_id')
                                ->label('Kategori')
                                ->options(fn () => Category::active()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn ($get) => $get('target_type') === MenuItem::TARGET_CATEGORY)
                                ->required(fn ($get) => $get('target_type') === MenuItem::TARGET_CATEGORY),
                            Select::make('target_id')
                                ->label('Merek')
                                ->options(fn () => Brand::active()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn ($get) => $get('target_type') === MenuItem::TARGET_BRAND)
                                ->required(fn ($get) => $get('target_type') === MenuItem::TARGET_BRAND),
                            Select::make('target_id')
                                ->label('Halaman')
                                ->options(fn () => CmsPage::published()->orderBy('title')->pluck('title', 'id'))
                                ->searchable()
                                ->visible(fn ($get) => $get('target_type') === MenuItem::TARGET_PAGE)
                                ->required(fn ($get) => $get('target_type') === MenuItem::TARGET_PAGE),
                            Toggle::make('open_in_new')->label('Tab Baru'),
                            Toggle::make('is_active')->label('Aktif')->default(true),
                            TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                TextColumn::make('location')->label('Lokasi')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'header' ? 'Header' : 'Footer'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('items_count')->label('Item')->counts('items'),
            ])
            ->defaultSort('location');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
