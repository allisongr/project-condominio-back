<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware para debuggear CORS y ver qué origen está siendo rechazado
 */
class CorsDebug
{
    public function handle(Request $request, Closure $next)
    {
        $origin = $request->header('Origin');
        
        if ($origin) {
            \Log::info('CORS Request Origin: ' . $origin, [
                'from_url' => $origin,
                'request_url' => $request->url(),
                'request_method' => $request->method(),
                'referer' => $request->header('Referer'),
            ]);
        }
        
        return $next($request);
    }
}
