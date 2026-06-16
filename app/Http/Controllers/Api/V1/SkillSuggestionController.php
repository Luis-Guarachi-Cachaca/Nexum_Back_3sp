<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSkillSuggestionRequest;
use App\Http\Resources\SkillSuggestionResource;
use App\Models\SkillSuggestion;
use App\Models\User;
use App\Notifications\NewPendingSuggestion;
use Illuminate\Http\JsonResponse;

class SkillSuggestionController extends Controller
{
    public function store(StoreSkillSuggestionRequest $request): SkillSuggestionResource|JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json(['message' => 'Portfolio not found. Create your portfolio first.'], 404);
        }

        $suggestion = SkillSuggestion::create([
            'portfolio_id'  => $portfolio->id,
            'user_id'       => $request->user()->id,
            'type'          => $request->validated()['type'],
            'category'      => $request->validated()['category'],
            'name'          => $request->validated()['name'],
            'level'         => $request->validated()['level'],
            'justification' => $request->validated()['justification'] ?? null,
            'status'        => 'pending',
        ]);

        // Notify all admins about the new pending suggestion
        User::role('admin')->each(function ($admin) use ($suggestion) {
            $admin->notify(new NewPendingSuggestion('skill', $suggestion));
        });

        return (new SkillSuggestionResource($suggestion))->response()->setStatusCode(201);
    }
}
