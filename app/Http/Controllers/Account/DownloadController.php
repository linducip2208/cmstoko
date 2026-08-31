<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Services\DownloadService;
use Illuminate\Support\Facades\Auth;

class DownloadController extends Controller
{
    public function show(OrderItem $orderItem, DownloadService $downloads)
    {
        // Ownership + paid + expiry + limit — all inside authorize().
        $downloads->authorize($orderItem, (int) Auth::id());

        [$response] = $downloads->stream($orderItem);

        $downloads->log($orderItem, (int) Auth::id(), request()->ip());

        return $response;
    }
}
