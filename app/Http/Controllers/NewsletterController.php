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
            ['subscribed_at' => now(), 'unsubscribed_at' => null, 'source' => 'storefront'],
        );

        return back()->with('newsletter_status', 'Terima kasih! Kamu akan menerima kabar dari kami.');
    }

    /**
     * Tokenized unsubscribe — safe link, no auth required, cannot unsubscribe others.
     */
    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (! $subscriber) {
            abort(404);
        }

        $subscriber->update(['unsubscribed_at' => $subscriber->unsubscribed_at ?? now()]);

        return redirect()->route('home')->with('newsletter_status', 'Kamu berhasil berhenti berlangganan.');
    }
}
