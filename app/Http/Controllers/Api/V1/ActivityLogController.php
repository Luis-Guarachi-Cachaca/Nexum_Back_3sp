<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'  => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Activity::with([
                'causer:id,first_name,last_name,email',
                'subject',  // ← agregado
            ])
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('causer_type', 'App\Models\User')
                    ->where('causer_id', $request->user_id);
        }

        $perPage = $request->input('per_page', 20);
        $logs    = $query->paginate($perPage);

        $data = $logs->getCollection()->map(fn (Activity $activity) => [
            'id'         => $activity->id,
            'event'      => $activity->event,
            'log_name'   => $activity->log_name,
            'created_at' => $activity->created_at,
            'causer'     => $activity->causer ? [
                'id'         => $activity->causer->id,
                'first_name' => $activity->causer->first_name,
                'last_name'  => $activity->causer->last_name,
                'email'      => $activity->causer->email,
            ] : null,
            'subject'    => $activity->subject ? [  // ← agregado
                'id'         => $activity->subject->id,
                'first_name' => $activity->subject->first_name ?? null,
                'last_name'  => $activity->subject->last_name  ?? null,
                'email'      => $activity->subject->email      ?? null,
            ] : null,
            'properties' => $activity->properties,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }
}
