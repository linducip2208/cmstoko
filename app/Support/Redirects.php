<?php

namespace App\Support;

use App\Models\SeoRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class Redirects
{
    /**
     * Resolve an SEO redirect for a 404 path. Returns a Response when a safe
     * redirect matches, null otherwise.
     *
     * Safety rules:
     * - destination must be a relative path (or same-host absolute URL)
     * - scheme whitelist: http/https only (blocks javascript:, data:, etc.)
     * - no self-redirects / no chains deeper than 3 hops
     */
    public static function responseFor(Request $request): ?Response
    {
        if ($request->is('admin', 'admin/*', 'api/*', 'livewire/*', 'up') || $request->getMethod() !== 'GET') {
            return null;
        }

        $path = SeoRedirect::normalizePath($request->getPathInfo());

        $match = SeoRedirect::match($path);

        if ($match === null) {
            return null;
        }

        $destination = trim((string) $match['destination']);

        if ($destination === '') {
            return null;
        }

        // Resolve the destination to a safe local URL.
        $location = null;

        if (str_starts_with($destination, '/')) {
            $location = $destination;
        } elseif (preg_match('#^https?://#i', $destination)) {
            $host = parse_url($destination, PHP_URL_HOST);
            $scheme = strtolower((string) parse_url($destination, PHP_URL_SCHEME));

            if ($scheme !== 'http' && $scheme !== 'https') {
                return null; // blocked scheme (defence in depth)
            }

            if ($host && strcasecmp($host, $request->getHost()) === 0) {
                $location = parse_url($destination, PHP_URL_PATH).('' != ($query = parse_url($destination, PHP_URL_QUERY)) ? '?'.$query : '');
            } else {
                return null; // external hosts are not allowed (open redirect)
            }
        } else {
            return null;
        }

        // Drop query strings of the original request; redirect target is exact.
        $location = SeoRedirect::normalizePath($location);

        if ($location === $path) {
            return null; // self redirect
        }

        // Prevent redirect chains.
        $hops = 0;
        $current = $location;

        while ($next = SeoRedirect::match($current)) {
            $hops++;

            if ($hops > 3) {
                return null;
            }

            $current = SeoRedirect::normalizePath($next['destination']);

            if ($current === $path) {
                return null; // loop back to source
            }
        }

        SeoRedirect::query()
            ->where('source', $match['source'])
            ->update([
                'hit_count' => DB::raw('hit_count + 1'),
                'last_hit_at' => now(),
            ]);

        return redirect($location, $match['status_code']);
    }
}
