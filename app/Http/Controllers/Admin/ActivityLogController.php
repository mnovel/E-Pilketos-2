<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * List activity log.
     */
    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')->latest('created_at');

        // Filter user
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        // Filter action
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        // Filter tanggal
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Search (meta / subject)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [20, 50, 100, 200])) {
            $perPage = 50;
        }

        $logs = $query->paginate($perPage)->withQueryString();

        // Stats
        $stats = [
            'total'     => ActivityLog::count(),
            'today'     => ActivityLog::whereDate('created_at', today())->count(),
            'this_week' => ActivityLog::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
            'unique_user' => ActivityLog::distinct('user_id')->count('user_id'),
        ];

        // Filter options
        $userIds = ActivityLog::whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $users = User::whereIn('id', $userIds)
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $actions = ActivityLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.activity-logs.index', compact(
            'logs',
            'stats',
            'users',
            'actions',
            'perPage'
        ));
    }

    /**
     * Detail log.
     */
    public function show(ActivityLog $log): View
    {
        $log->load('user');

        return view('admin.activity-logs.show', compact('log'));
    }
}
