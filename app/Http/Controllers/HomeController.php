<?php

namespace App\Http\Controllers;

use App\Models\HomepageSection;
use App\Shop\SectionResolver;

class HomeController extends Controller
{
    public function __invoke()
    {
        $sections = HomepageSection::active()->get();

        $resolver = app(SectionResolver::class);

        $resolved = $sections
            ->map(fn (HomepageSection $section) => $resolver->resolve($section))
            ->filter(function (array $data) {
                $section = $data['section'];

                // Skip sections that would render empty.
                if (in_array($section->type, ['product_grid'], true) && $data['products']->isEmpty()) {
                    return false;
                }

                if ($section->type === 'category_grid' && $data['categories']->isEmpty()) {
                    return false;
                }

                return true;
            });

        return view('pages.home', ['sections' => $resolved]);
    }
}
