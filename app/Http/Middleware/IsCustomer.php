<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    private array $allowedRoutes = [
        'data.*',
        'transaksi.*',
        'organizations.*',
        'session.*',
        'data_requests.*',
        'data_reports.*',
        'wilayah.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Jika bukan Customer (group_id != 3), lewati — akses penuh
        if ($user->group_id !== 3) {
            return $next($request);
        }

        // Customer: cek apakah route saat ini ada di whitelist
        $currentRoute = $request->route()?->getName() ?? '';

        foreach ($this->allowedRoutes as $pattern) {
            if (fnmatch($pattern, $currentRoute)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}