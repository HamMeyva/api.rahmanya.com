<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfUserBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $request->user('sanctum') && $user->is_banned) {
            $reason = $user->ban_reason ?? 'Belirtilmemiş';
            abort(403, "Hesabınız askıya alınmıştır. Sebep: {$reason}");
        }

        return $next($request);
    }
}
