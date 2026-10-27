<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoCacheHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Let the secure-media stream keep its own (private, cacheable) headers so
        // protected images/videos aren't force-reloaded on every repaint.
        if ($request->is('secure-media')) {
            return $response;
        }

        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
    }
}
