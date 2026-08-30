<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);

        $key = 'newsletter:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('newsletter_status', 'Terlalu banyak percobaan. Coba lagi nanti.');
        }

        RateLimiter::hit($key, 300);

        NewsletterSubscriber::updateOrCreate(
            ['email' => strtolower($validated['email'])],
            ['subscribed_at' => now(), 'unsubscribed_at' => null],
        );

        return back()->with('newsletter_status', 'Terima kasih! Kamu akan menerima kabar dari kami.');
    }
}
