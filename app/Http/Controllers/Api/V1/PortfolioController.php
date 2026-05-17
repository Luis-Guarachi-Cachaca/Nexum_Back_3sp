<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarRequest;
use App\Http\Requests\PortfolioRequest;
use App\Http\Resources\PortfolioResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SkillResource;
use App\Http\Resources\CertificationResource;
use App\Http\Resources\WorkExperienceResource;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\WorkExperience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function show(Request $request): PortfolioResource|JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json(['message' => 'Portfolio not found.'], 404);
        }

        return new PortfolioResource($portfolio->load('user'));
    }

    public function export(Request $request): JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json(['message' => 'Portfolio not found.'], 404);
        }

        $response = [
            'id' => $portfolio->id,
            'user' => [
                'id' => $request->user()->id,
                'first_name' => $request->user()->first_name,
                'last_name' => $request->user()->last_name,
                'email' => $request->user()->email,
            ],
            'profession' => $portfolio->profession,
            'biography' => $portfolio->biography,
            'phone' => $portfolio->phone,
            'location' => $portfolio->location,
            'avatar_url' => $portfolio->avatar_path ? cloudinary()->image($portfolio->avatar_path)->toUrl() : null,
            'linkedin_url' => $portfolio->linkedin_url,
            'github_url' => $portfolio->github_url,
        ];

        // Exportamos las secciones que explícitamente el usuario configuró para mostrar
        if ($portfolio->show_projects ?? true) {
            $projects = Project::where('portfolio_id', $portfolio->id)
                ->where('archived', false)
                ->with(['category', 'skills', 'files'])
                ->get();
            
            $response['projects'] = ProjectResource::collection($projects);
        }

        if ($portfolio->show_skills ?? true) {
            $skills = $portfolio->skills()->with('skill')->get();
            $response['skills'] = SkillResource::collection($skills);
        }

        if ($portfolio->show_certifications ?? true) {
            $certifications = $portfolio->certifications;
            $response['certifications'] = CertificationResource::collection($certifications);
        }

        if ($portfolio->show_experience ?? true) {
            $experiences = WorkExperience::where('user_id', $portfolio->user_id)
                ->where('is_active', true)
                ->with('skills')
                ->orderByDesc('start_date')
                ->get();
            
            $response['work_experiences'] = WorkExperienceResource::collection($experiences);
        }

        return response()->json($response);
    }

    public function update(PortfolioRequest $request): PortfolioResource
    {
        $validated = $request->validated();

        $userFields = collect($validated)->only(['first_name', 'last_name'])->all();
        if (! empty($userFields)) {
            $request->user()->update($userFields);
        }

        $portfolioFields = collect($validated)->except(['first_name', 'last_name'])->all();

        // Establecer valores por defecto para campos de privacidad si no se envían
        $privacyDefaults = [
            'global_privacy' => 'public',
            'show_projects' => true,
            'show_skills' => true,
            'show_experience' => true,
            'show_certifications' => true,
        ];

        foreach ($privacyDefaults as $field => $default) {
            if (! array_key_exists($field, $portfolioFields)) {
                $portfolioFields[$field] = $default;
            }
        }

        $portfolio = Portfolio::updateOrCreate(
            ['user_id' => $request->user()->id],
            $portfolioFields
        );

        return new PortfolioResource($portfolio->load('user'));
    }

    public function updateAvatar(AvatarRequest $request): PortfolioResource
    {
        $portfolio = $request->user()->portfolio;

        if ($portfolio?->avatar_path) {
            cloudinary()->uploadApi()->destroy($portfolio->avatar_path);
        }

        $result = cloudinary()->uploadApi()->upload($request->file('avatar')->getRealPath(), [
            'folder' => 'nexum/avatars',
        ]);

        $portfolio = Portfolio::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['avatar_path' => $result['public_id']]
        );

        return new PortfolioResource($portfolio->load('user'));
    }
}
