<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogs\AuditLogResource\Pages;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Log Audit';

    protected static ?string $modelLabel = 'Log Audit';

    protected static ?int $navigationSort = 9;

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
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('user_name')
                    ->label('Aktor')
                    ->placeholder('sistem'),
                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state) => match (true) {
                        str_contains($state, 'delete') => 'danger',
                        str_contains($state, 'update') => 'warning',
                        str_contains($state, 'create') => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('subject_type')
                    ->label('Subjek')
                    ->formatStateUsing(fn (?string $state, AuditLog $record) => $state
                        ? class_basename($state).($record->subject_id ? ' #'.$record->subject_id : '')
                        : '—'),
                TextColumn::make('after')
                    ->label('Data Baru')
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? mb_substr(json_encode($state, JSON_UNESCAPED_UNICODE), 0, 80)
                        : '—')
                    ->wrap(),
                TextColumn::make('ip')
                    ->label('IP')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
