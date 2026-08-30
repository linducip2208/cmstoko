<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'name' => $this->name,
            'phone' => $this->phone,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'province_name' => $this->province_name,
            'city_name' => $this->city_name,
            'postal_code' => $this->postal_code,
            'address' => $this->address,
            'is_default' => $this->is_default,
        ];
    }
}
