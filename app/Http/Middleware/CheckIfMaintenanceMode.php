<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maintenanceMode = AppSetting::getSetting('maintenance_mode');
        if ($maintenanceMode) {
            abort(503, 'Sistem şu anda bakım modundadır. Geliştirmeler yapılıyor, kısa süre sonra tekrar erişebilirsiniz. Anlayışınız için teşekkür ederiz 🙏');
        }
        return $next($request);
    }
}
