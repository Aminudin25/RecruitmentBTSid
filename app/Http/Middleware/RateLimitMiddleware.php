<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $key        Cache key prefix (e.g. 'product_mutate' or 'auth')
     * @param  int     $maxAttempts  Maximum allowed hits
     * @param  int     $decaySeconds  Window in seconds
     */
    public static function make(string $key, int $maxAttempts, int $decaySeconds): Closure
    {
        return function (Request $request, Closure $next) use ($key, $maxAttempts, $decaySeconds): Response {
            // Use IP + route as unique identifier
            $identifier = $key . ':' . $request->ip() . ':' . $request->route()?->getName();
            $hitKey     = 'rate_limit:' . md5($identifier);
            $expiryKey  = $hitKey . ':expiry';

            $hits = (int) Cache::get($hitKey, 0);

            if ($hits >= $maxAttempts) {
                $ttl = Cache::get($expiryKey, $decaySeconds);
                return response()->json([
                    'success'     => false,
                    'message'     => 'Too many requests. Please try again later.',
                    'retry_after' => $ttl . ' seconds',
                ], 429);
            }

            if ($hits === 0) {
                Cache::put($hitKey, 1, $decaySeconds);
                Cache::put($expiryKey, $decaySeconds, $decaySeconds);
            } else {
                Cache::increment($hitKey);
            }

            $response = $next($request);

            return $response->withHeaders([
                'X-RateLimit-Limit'     => $maxAttempts,
                'X-RateLimit-Remaining' => max(0, $maxAttempts - $hits - 1),
            ]);
        };
    }

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
