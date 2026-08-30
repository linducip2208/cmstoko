<?php

namespace App\Filament\Resources\Collections\CollectionResource\Pages;

use App\Filament\Resources\CollectionResource;
use App\Models\Collection;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $ids = $this->form->getRawState()['manual_products'] ?? [];

        if ($this->record->type === Collection::TYPE_MANUAL && is_array($ids)) {
            $this->record->products()->sync(collect($ids)->mapWithKeys(fn ($id) => [$id => ['sort_order' => 0]])->all());
        }
    }
}
