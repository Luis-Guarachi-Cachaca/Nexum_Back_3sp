<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminSkillSuggestionResource;
use App\Models\PortfolioSkill;
use App\Models\Skill;
use App\Models\SkillSuggestion;
use App\Notifications\SkillSuggestionApproved;
use App\Notifications\SkillSuggestionRejected;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminSkillSuggestionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status', 'pending');

        $suggestions = SkillSuggestion::with(['user', 'reviewer'])
            ->when(in_array($status, ['pending', 'approved', 'rejected']), fn ($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return AdminSkillSuggestionResource::collection($suggestions);
    }

    public function approve(Request $request, SkillSuggestion $suggestion): AdminSkillSuggestionResource|JsonResponse
    {
        if ($suggestion->status !== 'pending') {
            return response()->json(['message' => 'Esta sugerencia ya fue procesada.'], 422);
        }

        // Use existing skill if it already exists (same name + category), otherwise create it
        $skill = Skill::whereRaw('LOWER(name) = ?', [strtolower($suggestion->name)])
            ->where('category', $suggestion->category)
            ->first();

        if (! $skill) {
            $skill = Skill::create([
                'name'     => $suggestion->name,
                'type'     => $suggestion->type,
                'category' => $suggestion->category,
            ]);
        }

        // Add skill to user's portfolio (handle soft-delete pattern)
        $existing = PortfolioSkill::where('portfolio_id', $suggestion->portfolio_id)
            ->where('skill_id', $skill->id)
            ->first();

        if ($existing) {
            $existing->update(['is_active' => true, 'level' => $suggestion->level]);
        } else {
            PortfolioSkill::create([
                'portfolio_id' => $suggestion->portfolio_id,
                'skill_id'     => $skill->id,
                'level'        => $suggestion->level,
                'is_active'    => true,
            ]);
        }

        $suggestion->update([
            'status'      => 'approved',
            'skill_id'    => $skill->id,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // Notify the user who created the suggestion
        $suggestion->user->notify(new SkillSuggestionApproved($suggestion));

        return new AdminSkillSuggestionResource($suggestion->fresh()->load(['user', 'reviewer']));
    }

    public function reject(Request $request, SkillSuggestion $suggestion): AdminSkillSuggestionResource|JsonResponse
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
        $suggestion->user->notify(new SkillSuggestionRejected($suggestion));

        return new AdminSkillSuggestionResource($suggestion->fresh()->load(['user', 'reviewer']));
    }
}
