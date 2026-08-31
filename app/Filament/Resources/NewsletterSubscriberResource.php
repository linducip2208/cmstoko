<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource\Pages;
use App\Models\NewsletterSubscriber;
use App\Support\Csv;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'Promosi';

    protected static ?string $navigationLabel = 'Newsletter';

    protected static ?string $modelLabel = 'Pelanggan Newsletter';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->label('Email')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('source')->label('Sumber')->badge()->placeholder('—'),
                TextColumn::make('subscribed_at')->label('Berlangganan')->since()->sortable(),
                TextColumn::make('unsubscribed_at')->label('Berhenti')->since()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'unsubscribed' => 'Berhenti',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'active' => $query->whereNull('unsubscribed_at'),
                            'unsubscribed' => $query->whereNotNull('unsubscribed_at'),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('subscribed_at', 'desc');
    }

    public static function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Csv::streamDownload(
                    'newsletter-'.now()->format('Ymd-His').'.csv',
                    ['Email', 'Status', 'Sumber', 'Berlangganan', 'Berhenti'],
                    NewsletterSubscriber::query()->orderBy('subscribed_at')->cursor()->map(fn (NewsletterSubscriber $s) => [
                        $s->email,
                        $s->unsubscribed_at ? 'Berhenti' : 'Aktif',
                        $s->source,
                        $s->subscribed_at?->format('Y-m-d H:i'),
                        $s->unsubscribed_at?->format('Y-m-d H:i'),
                    ]),
                )),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscribers::route('/'),
        ];
    }
}
