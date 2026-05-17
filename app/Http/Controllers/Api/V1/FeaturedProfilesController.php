<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\JsonResponse;

class FeaturedProfilesController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::role('professional')
            ->whereHas('portfolio', function ($query) {
                $query->where('global_privacy', 'public');
            })
            ->with(['portfolio' => fn ($q) => $q->withCount('projects')])
            ->latest()
            ->take(3)
            ->get();

        $profiles = $users->map(function (User $user) {
            $portfolio = $user->portfolio;

            // Solo incluir perfiles que sean públicos
            if (! $portfolio || $portfolio->global_privacy === 'private') {
                return null;
            }

            return [
                'id'             => $portfolio?->id,
                'first_name'     => $user->first_name,
                'last_name'      => $user->last_name,
                'location'       => $portfolio?->location,
                'avatar_url'     => $portfolio?->avatar_path
                    ? Cloudinary::image($portfolio->avatar_path)->toUrl()
                    : null,
                'projects_count' => $portfolio->show_projects ? ($portfolio?->projects_count ?? 0) : 0,
            ];
        })->filter()->values();

        return response()->json([
            'data'  => $profiles,
            'stats' => [
                'total_users'    => User::where('is_active', true)->role('professional')->count(),
                'total_projects' => Project::count(),
            ],
        ]);
    }
}
