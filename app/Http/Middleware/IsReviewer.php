<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsReviewer
{
    public function handle(Request $request, Closure $next)
    {
        $groupId = auth()->user()?->group_id;

        if (!in_array($groupId, [1, 2], true)) { // 1 = Super Admin, 2 = Pengelola
            abort(403, 'Anda tidak memiliki akses untuk meninjau pengajuan ini.');
        }

        return $next($request);
    }
}