<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoRedirects\SeoRedirectResource\Pages;
use App\Models\SeoRedirect;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SeoRedirectResource extends Resource
{
    protected static ?string $model = SeoRedirect::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'Redirects';

    protected static ?string $modelLabel = 'Redirect';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Redirect')
                ->description('Arahkan URL lama yang 404 ke URL baru. Tujuan hanya boleh path internal atau URL pada domain yang sama.')
                ->schema([
                    TextInput::make('source')
                        ->label('URL Lama')
                        ->required()
                        ->maxLength(500)
                        ->prefix('/')
                        ->unique(ignoreRecord: true)
                        ->helperText('Path internal tanpa domain, mis. produk-lama atau /produk/lama'),
                    TextInput::make('destination')
                        ->label('Tujuan')
                        ->required()
                        ->maxLength(500)
                        ->prefix('/')
                        ->helperText('Path internal, mis. /produk/nama-baru'),
                    Select::make('status_code')
                        ->label('Kode Status')
                        ->options([301 => '301 — Permanen (SEO)', 302 => '302 — Sementara'])
                        ->default(301)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source')
                    ->label('Dari')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->copyable(),
                TextColumn::make('destination')
                    ->label('Ke')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status_code')
                    ->label('Kode')
                    ->badge()
                    ->color(fn (string $state) => $state === '301' ? 'success' : 'warning'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('hit_count')
                    ->label('Hit')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('last_hit_at')
                    ->label('Hit Terakhir')
                    ->since()
                    ->placeholder('—'),
            ])
            ->defaultSort('hit_count', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
