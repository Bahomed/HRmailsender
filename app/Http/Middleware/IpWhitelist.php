<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class IpWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = config('app.allowed_ips');

        // Skip IP check if no whitelist is configured
        if (empty($allowedIps)) {
            return $next($request);
        }

        // Allow all IPs if wildcard is configured
        if ($allowedIps === '*') {
            return $next($request);
        }

        $allowedIpsArray = array_map('trim', explode(',', $allowedIps));
        $clientIp = $request->ip();

        // Check if wildcard exists in array
        if (in_array('*', $allowedIpsArray)) {
            return $next($request);
        }

        if (!in_array($clientIp, $allowedIpsArray)) {
            Log::warning('IP address not in whitelist', [
                'ip' => $clientIp,
                'path' => $request->path(),
                'allowed_ips' => $allowedIpsArray
            ]);

            return response()->json([
                'error' => 'IP address not allowed'
            ], 403);
        }

        return $next($request);
    }
}
