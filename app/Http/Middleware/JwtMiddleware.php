<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            
            $payload = JWTAuth::parseToken()->getPayload();
            
        
            $request->merge(['jwt_user_id' => $payload->get('sub')]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o no proporcionado'
            ], 401);
        }

        return $next($request);
    }
}