<?php

namespace App\Filament\Resources\Products\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->formId('form');
    }
}
