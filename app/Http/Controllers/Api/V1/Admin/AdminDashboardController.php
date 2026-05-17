<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalUsers    = User::count();
        $activeUsers   = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();

        return response()->json([
            'data' => [
                'users' => [
                    'total'    => $totalUsers,
                    'active'   => $activeUsers,
                    'inactive' => $inactiveUsers,
                ],
                'portfolios' => Portfolio::count(),
                'activity'   => [
                    'total_actions' => Activity::count(),
                    'recent'        => Activity::latest()->take(5)->get()->map(fn (Activity $activity) => [
                        'id'         => $activity->id,
                        'event'      => $activity->event,
                        'log_name'   => $activity->log_name,
                        'created_at' => $activity->created_at->toISOString(),
                        'causer'     => $activity->causer ? [
                            'id'         => $activity->causer->id,
                            'first_name' => $activity->causer->first_name,
                            'last_name'  => $activity->causer->last_name,
                            'email'      => $activity->causer->email,
                        ] : null,
                    ]),
                ],
            ],
        ]);
    }
}