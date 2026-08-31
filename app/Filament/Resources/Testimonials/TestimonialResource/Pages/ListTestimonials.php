<?php

namespace App\Filament\Resources\Testimonials\TestimonialResource\Pages;

use App\Filament\Resources\TestimonialResource;
use Filament\Resources\Pages\ListRecords;

class ListTestimonials extends ListRecords
{
    protected static string $resource = TestimonialResource::class;
}
