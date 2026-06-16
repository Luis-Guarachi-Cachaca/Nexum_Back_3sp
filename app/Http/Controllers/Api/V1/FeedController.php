<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class FeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        $activities = Activity::with([
                'causer',
                'causer.portfolio',
                'subject'
            ])
            ->where('subject_type', 'App\Models\Portfolio')
            ->whereIn('event', ['created', 'updated'])
            ->latest()
            ->paginate($perPage);

        $data = $activities->map(function (Activity $activity) {
            $user = $activity->causer;
            $portfolio = $user ? $user->portfolio : null;

            // Only show activities if the user and portfolio still exist
            if (!$user || !$portfolio) {
                return null;
            }

            // Exclude if portfolio is private (assuming show_projects is an indicator of public visibility, 
            // or just rely on global_privacy if it exists). For now, we just ensure it exists.

            return [
                'id' => $activity->id,
                'event' => $activity->event,
                'created_at' => $activity->created_at->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar_url' => $portfolio->avatar_path ? cloudinary()->image($portfolio->avatar_path)->toUrl() : null,
                    'profession' => $portfolio->headline ?? $portfolio->profession ?? null, // Will refine this
                    'portfolio_id' => $portfolio->id,
                ],
            ];
        })->filter()->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }
}
