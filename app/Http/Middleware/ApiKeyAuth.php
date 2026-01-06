<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $expectedApiKey = config('app.api_key');

        // Skip auth if no API key is configured (for initial setup)
        if (empty($expectedApiKey) || $expectedApiKey === 'your-super-secret-api-key-here-change-this') {
            Log::warning('API key not configured - authentication bypassed', [
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);
            return $next($request);
        }

        if ($apiKey !== $expectedApiKey) {
            Log::warning('Unauthorized API access attempt', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'provided_key' => $apiKey ? substr($apiKey, 0, 10) . '...' : 'none'
            ]);

            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }
}
