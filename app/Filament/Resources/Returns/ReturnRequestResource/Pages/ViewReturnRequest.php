<?php

namespace App\Filament\Resources\Returns\ReturnRequestResource\Pages;

use App\Filament\Resources\ReturnRequestResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnRequest extends ViewRecord
{
    protected static string $resource = ReturnRequestResource::class;

    public function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...ReturnRequestResource::getRecordActions(),
            ])->label('Aksi')->button()->color('primary'),
        ];
    }
}
