<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCategorySuggestionResource;
use App\Models\CategorySuggestion;
use App\Models\ProjectCategory;
use App\Notifications\CategorySuggestionApproved;
use App\Notifications\CategorySuggestionRejected;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminCategorySuggestionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status  = $request->query('status', 'pending');
        $perPage = min((int) $request->query('per_page', 20), 100);

        $suggestions = CategorySuggestion::with(['user', 'reviewer', 'project'])
            ->when(in_array($status, ['pending', 'approved', 'rejected']), fn ($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return AdminCategorySuggestionResource::collection($suggestions);
    }

    public function approve(Request $request, CategorySuggestion $suggestion): AdminCategorySuggestionResource|JsonResponse
    {
        if ($suggestion->status !== 'pending') {
            return response()->json(['message' => 'Esta sugerencia ya fue procesada.'], 422);
        }

        // Create category if it doesn't exist yet, or reuse an existing one
        $category = ProjectCategory::whereRaw('LOWER(name) = ?', [strtolower($suggestion->name)])->first();

        if (! $category) {
            $category = ProjectCategory::create([
                'name'      => $suggestion->name,
                'is_active' => true,
            ]);
        }

        // Assign the category to the project
        $suggestion->project->update(['category_id' => $category->id]);

        $suggestion->update([
            'status'      => 'approved',
            'category_id' => $category->id,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // Notify the user who created the suggestion
        $suggestion->user->notify(new CategorySuggestionApproved($suggestion));

        return new AdminCategorySuggestionResource($suggestion->fresh()->load(['user', 'reviewer', 'project']));
    }

    public function reject(Request $request, CategorySuggestion $suggestion): AdminCategorySuggestionResource|JsonResponse
    {
        if ($suggestion->status !== 'pending') {
            return response()->json(['message' => 'Esta sugerencia ya fue procesada.'], 422);
        }

        $suggestion->update([
            'status'      => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // Notify the user who created the suggestion
        $suggestion->user->notify(new CategorySuggestionRejected($suggestion));

        return new AdminCategorySuggestionResource($suggestion->fresh()->load(['user', 'reviewer', 'project']));
    }
}
