<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Timezone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tz = $request->user()?->timezone ?? 'Europe/Istanbul';
        Timezone::set($tz);
        
        return $next($request);
    }
}
