<?php

namespace App\Http\Controllers;

use App\Models\UserActivityLog;
use Illuminate\Http\Request;

class AdminUserActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'days' => ['sometimes', 'integer', 'in:1,7,14,30'],
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $days = (int) ($validated['days'] ?? 30);
        $search = trim((string) ($validated['search'] ?? ''));

        $query = UserActivityLog::query()
            ->where('created_at', '>=', now()->startOfDay()->subDays($days - 1));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('user_name', 'like', "%{$search}%")
                    ->orWhere('request_path', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%");
            });
        }

        $activityLogs = $query->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'rows' => $activityLogs->getCollection()->map(fn (UserActivityLog $log): array => [
                    'created_at' => $log->created_at?->format('d-m-Y H:i:s'),
                    'user_name' => $log->user_name ?? 'Akun dihapus',
                    'request_path' => $log->request_path,
                    'route_name' => $log->route_name ?? '—',
                    'method' => $log->method,
                    'status_code' => $log->status_code,
                    'duration_ms' => $log->duration_ms,
                ])->values(),
                'pagination' => [
                    'current_page' => $activityLogs->currentPage(),
                    'last_page' => $activityLogs->lastPage(),
                    'total' => $activityLogs->total(),
                ],
                'days' => $days,
                'search' => $search,
            ]);
        }

        return view('pages.admin.activity-logs.index', compact('activityLogs', 'days'));
    }

    public function destroyOlderThan(Request $request)
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'in:14,30'],
        ]);
        $days = (int) $validated['days'];
        $deleted = UserActivityLog::where('created_at', '<', now()->subDays($days))->delete();

        return back()->with(
            'success',
            "{$deleted} log aktivitas yang lebih lama dari {$days} hari berhasil dihapus."
        );
    }
}
