<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((int) $request->user()?->group_id !== 1) {
            abort(403, 'Log aktivitas hanya dapat diakses oleh Administrator.');
        }

        return $next($request);
    }
}
