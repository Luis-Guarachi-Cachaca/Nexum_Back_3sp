<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioResource;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    public function professionals(Request $request): AnonymousResourceCollection
    {
        $isRegisteredUser = auth('sanctum')->check();
        
        $query = Portfolio::with(['user']);

        // Aplicar reglas de privacidad global
        // - Si el visitante NO está registrado: solo perfiles públicos
        // - Si está registrado: puede ver todos (public y private)
        if (!$isRegisteredUser) {
            $query->where('global_privacy', 'public');
        }

        // Solo usuarios activos y no desactivados por el administrador
        $query->whereHas('user', function ($q) {
            $q->where('is_active', true)
              ->where('deactivated_by_admin', false);
        });

        // Filtrar por término general de búsqueda (q)
        if ($request->filled('q')) {
            $searchTerm = mb_strtolower($request->input('q'));
            
            $query->where(function ($q) use ($searchTerm) {
                // Búsqueda por profesión (área)
                $q->whereRaw('LOWER(profession) LIKE ?', ["%{$searchTerm}%"])
                  // Búsqueda por nombre o apellido
                  ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                      $userQuery->whereRaw('LOWER(first_name) LIKE ?', ["%{$searchTerm}%"])
                                ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$searchTerm}%"])
                                // Nombre completo (first_name + last_name)
                                ->orWhereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", ["%{$searchTerm}%"]);
                  })
                  // Búsqueda por habilidades o especialidades (ej: React, Node.js)
                  ->orWhereHas('skills.skill', function ($skillQuery) use ($searchTerm) {
                      $skillQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                  })
                  // Búsqueda en los proyectos (si usaron React en algún proyecto, también son desarrolladores React)
                  ->orWhereHas('projects.skills', function ($projectSkillQuery) use ($searchTerm) {
                      $projectSkillQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                  });
            });
        }

        // Filtro opcional específico por array de skills (por ids o nombres)
        if ($request->filled('skills') && is_array($request->input('skills'))) {
            $skills = $request->input('skills');
            $query->where(function($q) use ($skills) {
                $q->whereHas('skills.skill', function ($skillQuery) use ($skills) {
                    $skillQuery->whereIn('name', $skills); 
                })->orWhereHas('projects.skills', function ($projectSkillQuery) use ($skills) {
                    $projectSkillQuery->whereIn('name', $skills);
                });
            });
        }
        
        // Filtro opcional específico por area (profession)
        if ($request->filled('area')) {
            $area = mb_strtolower($request->input('area'));
            $query->whereRaw('LOWER(profession) LIKE ?', ["%{$area}%"]);
        }

        // Retornar la colección paginada (15 por defecto o lo que envíe el front)
        $perPage = $request->input('per_page', 15);
        $portfolios = $query->paginate($perPage);

        return PortfolioResource::collection($portfolios);
    }
}
