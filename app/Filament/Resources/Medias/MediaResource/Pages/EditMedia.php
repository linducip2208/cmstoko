<?php

namespace App\Filament\Resources\Medias\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action) {
                    try {
                        app(\App\Services\MediaService::class)->delete($action->getRecord());
                    } catch (\Throwable $e) {
                        Notification::make()->title('Tidak dapat dihapus')->body($e->getMessage())->danger()->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
