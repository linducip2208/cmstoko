<?php

namespace App\Filament\Resources\Medias\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Unggah Media')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(static::$resource::getUrl('create')),
        ];
    }
}
