<?php

namespace App\Http\Middleware;

use App\Models\UserActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAuthenticatedPageVisits
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isLogout = $request->isMethod('POST')
            && $request->routeIs('logout')
            && $user !== null;

        if (! $isLogout && (! $request->isMethod('GET') || $request->ajax() || $user === null)) {
            return $next($request);
        }

        $startedAt = hrtime(true);
        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type', '');

        if (! $isLogout && ! str_starts_with(strtolower($contentType), 'text/html')) {
            return $response;
        }

        try {
            UserActivityLog::create([
                'user_id' => $user->user_id,
                'user_name' => $user->name,
                'method' => $request->method(),
                'route_name' => $request->route()?->getName(),
                'request_path' => '/'.$request->path(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
