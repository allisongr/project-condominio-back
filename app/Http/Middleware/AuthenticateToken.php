<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Usuario;

class AuthenticateToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get token from Authorization header
        $token = $request->header('Authorization');
        
        if ($token) {
            // Remove "Bearer " prefix if present
            $token = str_replace('Bearer ', '', $token);
            
            // For now, we'll store the token in the session/request
            // In a real app, you'd verify the token against a database
            session(['auth_token' => $token]);
        }
        
        return $next($request);
    }
}
