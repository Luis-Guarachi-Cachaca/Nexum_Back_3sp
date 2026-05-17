<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SkillResource;
use App\Http\Resources\CertificationResource;
use App\Http\Resources\WorkExperienceResource;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\WorkExperience;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicPortfolioController extends Controller
{
    public function show(Request $request, string $portfolioId): JsonResponse
    {
        $portfolio = Portfolio::with('user')->findOrFail($portfolioId);

        // Verificar si el perfil es completamente privado
        if ($portfolio->global_privacy === 'private') {
            return response()->json(['message' => 'Portfolio not found.'], 404);
        }

        // Incrementar contador de vistas
        $portfolio->increment('views_count');

        $response = [
            'id' => $portfolio->id,
            'user' => [
                'id' => $portfolio->user->id,
                'first_name' => $portfolio->user->first_name,
                'last_name' => $portfolio->user->last_name,
                'email' => $portfolio->user->email,
            ],
            'profession' => $portfolio->profession,
            'biography' => $portfolio->biography,
            'phone' => $portfolio->phone,
            'location' => $portfolio->location,
            'avatar_url' => $portfolio->avatar_path ? cloudinary()->image($portfolio->avatar_path)->toUrl() : null,
            'linkedin_url' => $portfolio->linkedin_url,
            'github_url' => $portfolio->github_url,
            'design_pattern' => $portfolio->design_pattern,
            'views_count' => $portfolio->views_count,
            'created_at' => $portfolio->created_at->toISOString(),
            'updated_at' => $portfolio->updated_at->toISOString(),
        ];

        // Agregar secciones según configuración de privacidad
        if ($portfolio->isSectionVisible('projects')) {
            $projects = Project::where('portfolio_id', $portfolio->id)
                ->where('archived', false)
                ->with(['category', 'skills', 'files'])
                ->get();
            
            $response['projects'] = ProjectResource::collection($projects);
        }

        if ($portfolio->isSectionVisible('skills')) {
            $skills = $portfolio->skills()->with('skill')->get();
            $response['skills'] = SkillResource::collection($skills);
        }

        if ($portfolio->isSectionVisible('certifications')) {
            $certifications = $portfolio->certifications;
            $response['certifications'] = CertificationResource::collection($certifications);
        }

        if ($portfolio->isSectionVisible('experience')) {
            $experiences = WorkExperience::where('user_id', $portfolio->user_id)
                ->where('is_active', true)
                ->with('skills')
                ->orderByDesc('start_date')
                ->get();
            
            $response['work_experiences'] = WorkExperienceResource::collection($experiences);
        }

        // Agregar enlaces adicionales
        $additionalLinks = $portfolio->additionalLinks()
            ->orderBy('created_at', 'desc')
            ->get(['id', 'url', 'platform', 'created_at']);

        $response['additional_links'] = $additionalLinks;

        return response()->json($response);
    }
}
