<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JwtTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Missing or invalid Authorization token.',
            ], 401);
        }

        $clientToken = trim(str_replace('Bearer', '', $authHeader));

        // Accept the existing (old) token plus any additional tokens, all read from env,
        // so multiple tokens keep working. The old token and the API responses are unchanged.
        $validTokens = array_filter([
            env('MY_JWT_TOKEN'),
            env('MY_JWT_TOKEN_2'),
        ]);

        if (!in_array($clientToken, $validTokens, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid token.',
            ], 401);
        }

        return $next($request);
    }
}
