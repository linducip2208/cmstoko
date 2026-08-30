<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Medias\MediaResource\Pages;
use App\Models\Media;
use App\Services\MediaService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Media';

    protected static ?string $modelLabel = 'Media';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Unggah Berkas')
                ->description('Hanya gambar (JPG/PNG/WebP/AVIF/GIF/SVG) maksimal 5 MB. Nama berkas diacak & SVG dibersihkan otomatis.')
                ->schema([
                    FileUpload::make('upload')
                        ->label('Berkas')
                        ->multiple()
                        ->image()
                        ->disk('temp')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif', 'image/svg+xml'])
                        ->required(fn (string $operation) => $operation === 'create')
                        ->columnSpanFull(),
                ])
                ->visible(fn (string $operation) => $operation === 'create')
                ->columnSpanFull(),
            Section::make('Metadata')
                ->schema([
                    TextInput::make('title')->label('Judul')->maxLength(200),
                    TextInput::make('alt')->label('Teks Alternatif (alt)')->maxLength(300)->helperText('Penting untuk aksesibilitas & SEO gambar'),
                    TextInput::make('caption')->label('Keterangan')->maxLength(500),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->extraImgAttributes(['loading' => 'lazy']),
                TextColumn::make('original_name')->label('Nama Asli')->searchable()->limit(40)->weight('semibold'),
                TextColumn::make('mime')->label('Tipe')->badge(),
                TextColumn::make('size')->label('Ukuran')->formatStateUsing(fn (int $state) => number_format($state / 1024, 0).' KB'),
                TextColumn::make('dimensions')
                    ->label('Dimensi')
                    ->state(fn (Media $record) => $record->width ? "{$record->width}×{$record->height}" : '—'),
                TextColumn::make('uploader.name')->label('Diunggah Oleh')->placeholder('—'),
                TextColumn::make('created_at')->label('Tanggal')->since(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}
