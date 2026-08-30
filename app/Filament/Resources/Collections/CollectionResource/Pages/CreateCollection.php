<?php

namespace App\Filament\Resources\Collections\CollectionResource\Pages;

use App\Filament\Resources\CollectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCollection extends CreateRecord
{
    protected static string $resource = CollectionResource::class;

    protected function afterCreate(): void
    {
        $this->syncManualProducts();
    }

    protected function syncManualProducts(): void
    {
        $ids = $this->form->getRawState()['manual_products'] ?? [];

        if (is_array($ids) && $ids !== []) {
            $this->record->products()->syncWithPivotValues($ids, ['sort_order' => 0]);
        }
    }
}
