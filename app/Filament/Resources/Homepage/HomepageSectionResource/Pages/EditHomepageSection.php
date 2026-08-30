<?php

namespace App\Filament\Resources\Homepage\HomepageSectionResource\Pages;

use App\Filament\Resources\HomepageSectionResource;
use App\Models\HomepageSection;
use Filament\Actions\DeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Resources\Pages\EditRecord;

class EditHomepageSection extends EditRecord
{
    protected static string $resource = HomepageSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ReplicateAction::make()
                ->label('Duplikat')
                ->before(function (ReplicateAction $action, HomepageSection $record) {
                    $record->title .= ' (Salinan)';
                    $record->is_active = false;
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['trust_bar_items']);

        if (($data['config']['padding'] ?? null) === null) {
            $data['config']['padding'] = 'normal';
        }

        return $data;
    }
}
