<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Homepage\HomepageSectionResource\Pages;
use App\Models\Category;
use App\Models\Collection;
use App\Models\HomepageSection;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class HomepageSectionResource extends Resource
{
    protected static ?string $model = HomepageSection::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static string|UnitEnum|null $navigationGroup = 'Tampilan';

    protected static ?string $navigationLabel = 'Homepage Builder';

    protected static ?string $modelLabel = 'Section Beranda';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Section')
                    ->schema([
                        Select::make('type')
                            ->label('Tipe Section')
                            ->options(HomepageSection::TYPES)
                            ->native(false)
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn ($component) => $component->state([])),
                        TextInput::make('title')
                            ->label('Judul')
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->helperText(fn ($get) => $get('type') === 'hero'
                                ? 'Judul besar pada hero. Untuk penekanan berwarna gunakan field Highlight.'
                                : null)
                            ->columnSpanFull(),
                        Textarea::make('subtitle')
                            ->label('Subjudul')
                            ->maxLength(300)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Tabs::make('Konten')
                    ->tabs([
                        Tabs\Tab::make('Konten')
                            ->schema(self::contentFields()),

                        Tabs\Tab::make('Jadwal & Status')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true)
                                    ->columnSpanFull(),
                                DatePicker::make('starts_at')
                                    ->label('Tayang Mulai'),
                                DatePicker::make('ends_at')
                                    ->label('Tayang Sampai'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    protected static function contentFields(): array
    {
        return [
            Group::make()
                ->schema(fn ($get) => match ($get('type')) {
                    'hero' => [
                        TextInput::make('config.eyebrow')
                            ->label('Teks Kecil di Atas Judul (Eyebrow)')
                            ->maxLength(60),
                        TextInput::make('config.highlight')
                            ->label('Highlight (dalam italic berwarna)')
                            ->maxLength(60),
                        TextInput::make('config.primary_cta.label')->label('Tombol Utama — Label')->maxLength(40),
                        TextInput::make('config.primary_cta.url')->label('Tombol Utama — URL')->default(fn () => route('shop'))->url(),
                        TextInput::make('config.secondary_cta.label')->label('Tombol Kedua — Label')->maxLength(40),
                        TextInput::make('config.secondary_cta.url')->label('Tombol Kedua — URL')->url(),
                        Select::make('config.source')
                            ->label('Sumber Gambar')
                            ->options(['featured' => 'Produk Unggulan Pertama'])
                            ->default('featured')
                            ->native(false),
                        FileUpload::make('config.image')
                            ->label('Atau Gambar Sendiri')
                            ->image()
                            ->disk('public')
                            ->directory('homepage')
                            ->maxSize(3072)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                        TextInput::make('config.padding')->label('Ketinggian Padding')->default('large')->maxLength(10)->hidden(),
                    ],
                    'product_grid' => [
                        Select::make('config.source')
                            ->label('Sumber Produk')
                            ->options([
                                'featured' => 'Unggulan',
                                'new' => 'Terbaru',
                                'best' => 'Terlaris (flag unggulan)',
                                'discount' => 'Sedang Diskon',
                                'collection' => 'Dari Koleksi',
                                'category' => 'Dari Kategori',
                                'product_ids' => 'Pilih Manual (ID)',
                            ])
                            ->native(false)
                            ->live(),
                        Select::make('config.collection_slug')
                            ->label('Koleksi')
                            ->options(fn () => Collection::active()->pluck('name', 'slug'))
                            ->native(false)
                            ->searchable()
                            ->visible(fn ($get) => $get('config.source') === 'collection'),
                        Select::make('config.category_slug')
                            ->label('Kategori')
                            ->options(fn () => Category::active()->orderBy('name')->pluck('name', 'slug'))
                            ->native(false)
                            ->searchable()
                            ->visible(fn ($get) => $get('config.source') === 'category'),
                        CheckboxList::make('config.product_ids')
                            ->label('Produk')
                            ->options(fn () => Product::active()->orderBy('name')->limit(100)->pluck('name', 'id'))
                            ->columns(2)
                            ->searchable()
                            ->visible(fn ($get) => $get('config.source') === 'product_ids'),
                        TextInput::make('config.limit')->label('Jumlah Produk')->numeric()->default(8)->minValue(1)->maxValue(24),
                        Select::make('config.columns')
                            ->label('Kolom Grid')
                            ->options([2 => '2', 3 => '3', 4 => '4'])
                            ->default(4)
                            ->native(false),
                        TextInput::make('config.link_label')->label('Label Tautan Lihat Semua')->maxLength(40),
                        TextInput::make('config.link_url')->label('URL Tautan Lihat Semua')->url(),
                        TextInput::make('config.padding')->label('Padding')->default('normal')->maxLength(10)->hidden(),
                    ],
                    'category_grid' => [
                        TextInput::make('config.limit')->label('Jumlah Kategori')->numeric()->default(4)->minValue(1)->maxValue(12),
                        TextInput::make('config.padding')->label('Padding')->default('normal')->maxLength(10)->hidden(),
                    ],
                    'rich_text' => [
                        Textarea::make('config.html')
                            ->label('Isi (HTML terbatas)')
                            ->rows(8)
                            ->columnSpanFull()
                            ->helperText('Tag dibatasi: p, h2, h3, ul, ol, li, strong, a, em. Script otomatis dibuang.'),
                        Select::make('config.align')->label('Perataan')->options(['left' => 'Kiri', 'center' => 'Tengah'])->default('left')->native(false),
                        TextInput::make('config.padding')->label('Padding')->default('normal')->maxLength(10)->hidden(),
                    ],
                    'banner' => [
                        FileUpload::make('config.image')
                            ->label('Gambar')
                            ->image()
                            ->disk('public')
                            ->directory('homepage')
                            ->maxSize(3072)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                        Select::make('config.align')->label('Posisi Gambar')->options(['left' => 'Kiri', 'right' => 'Kanan'])->default('left')->native(false),
                        TextInput::make('config.eyebrow')->label('Eyebrow')->maxLength(60),
                        TextInput::make('config.link_label')->label('Label Tombol')->maxLength(40),
                        TextInput::make('config.link_url')->label('URL Tombol')->url(),
                        TextInput::make('config.padding')->label('Padding')->default('normal')->maxLength(10)->hidden(),
                    ],
                    'newsletter' => [
                        TextInput::make('config.padding')->label('Padding')->default('normal')->maxLength(10)->hidden(),
                    ],
                    'cta' => [
                        TextInput::make('config.cta.label')->label('Label Tombol')->maxLength(40),
                        TextInput::make('config.cta.url')->label('URL Tombol')->default(fn () => route('shop'))->url(),
                        TextInput::make('config.padding')->label('Padding')->default('normal')->maxLength(10)->hidden(),
                    ],
                    'trust_bar' => [
                        KeyValue::make('trust_bar_items')
                            ->label('Item Bar Kepercayaan')
                            ->helperText('Dikelola dari Pengaturan — nilai berupa teks klaim yang benar-benar berlaku. Kosong = section tersembunyi.')
                            ->disabled(),
                        TextInput::make('config.padding')->label('Padding')->default('compact')->maxLength(10)->hidden(),
                    ],
                    'faq' => [
                        TextInput::make('config.overline')->label('Eyebrow')->maxLength(40)->default('FAQ'),
                        TextInput::make('config.group')->label('Grup Pertanyaan')->maxLength(80)->helperText('Kosong = tampilkan semua grup'),
                        TextInput::make('config.limit')->label('Jumlah Pertanyaan')->numeric()->default(8)->minValue(1)->maxValue(20),
                        TextInput::make('config.padding')->label('Padding')->default('normal')->maxLength(10)->hidden(),
                    ],
                    'testimonials' => [
                        TextInput::make('config.overline')->label('Eyebrow')->maxLength(40)->default('Testimoni'),
                        TextInput::make('config.limit')->label('Jumlah Testimoni')->numeric()->default(6)->minValue(1)->maxValue(12),
                        TextInput::make('config.padding')->label('Padding')->default('normal')->maxLength(10)->hidden(),
                    ],
                    'blog_posts' => [
                        TextInput::make('config.overline')->label('Eyebrow')->maxLength(40)->default('Blog'),
                        TextInput::make('config.heading')->label('Judul Section')->maxLength(80),
                        Select::make('config.category_slug')->label('Kategori (opsional)')
                            ->options(fn () => \App\Models\BlogCategory::orderBy('name')->pluck('name', 'slug'))
                            ->native(false)->searchable(),
                        TextInput::make('config.limit')->label('Jumlah Artikel')->numeric()->default(3)->minValue(1)->maxValue(6),
                        TextInput::make('config.padding')->label('Padding')->default('normal')->maxLength(10)->hidden(),
                    ],
                    default => [],
                })
                ->columns(2)
                ->columnSpanFull()
                ->statePath(fn ($get) => null),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable()
                    ->width(60),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => HomepageSection::TYPES[$state] ?? Str::headline($state)),
                TextColumn::make('title')
                    ->label('Judul')
                    ->limit(40)
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime('d M Y')
                    ->placeholder('—'),
                TextColumn::make('ends_at')
                    ->label('Sampai')
                    ->dateTime('d M Y')
                    ->placeholder('—'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomepageSections::route('/'),
            'create' => Pages\CreateHomepageSection::route('/create'),
            'edit' => Pages\EditHomepageSection::route('/{record}/edit'),
        ];
    }
}
