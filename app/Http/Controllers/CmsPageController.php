<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Support\Seo;

class CmsPageController extends Controller
{
    public function show(string $slug)
    {
        $page = CmsPage::published()->where('slug', $slug)->firstOrFail();

        return view('pages.cms', ['page' => $page, 'seo' => Seo::forPage($page)]);
    }
}
