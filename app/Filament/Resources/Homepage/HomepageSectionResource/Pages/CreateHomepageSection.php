<?php

namespace App\Filament\Resources\Homepage\HomepageSectionResource\Pages;

use App\Filament\Resources\HomepageSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomepageSection extends CreateRecord
{
    protected static string $resource = HomepageSectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // trust_bar items are managed via settings; drop any state.
        unset($data['trust_bar_items']);

        if (($data['config']['padding'] ?? null) === null) {
            $data['config']['padding'] = 'normal';
        }

        return $data;
    }
}
