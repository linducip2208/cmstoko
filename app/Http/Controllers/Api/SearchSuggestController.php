<?php

namespace App\Http\Controllers\Api;

use App\Contracts\SearchEngine;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function __invoke(Request $request, SearchEngine $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:120'],
        ]);

        return response()->json($search->suggest($validated['q']));
    }
}
