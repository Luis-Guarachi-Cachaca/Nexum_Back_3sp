<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\ProfileVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileVisitController extends Controller
{
    /**
     * Registrar visita a un perfil
     */
    public function visit(Request $request, string $profileId): JsonResponse
    {
        // Validar que el portfolio existe
        $portfolio = Portfolio::findOrFail($profileId);

        // Obtener información del visitante
        $visitor = $request->user('sanctum');
        $visitorId = $visitor?->id;
        $ipAddress = $request->ip();

        // Si es el dueño del perfil, no contar la visita
        if ($visitor && $visitor->id === $portfolio->user_id) {
            return response()->json(['message' => 'Owner visit detected - not counted'], 200);
        }

        // Si es admin, no registrar visita
        if ($visitor && $visitor->hasRole('admin')) {
            return response()->json(['message' => 'Admin visit detected - not counted'], 200);
        }

        // Evitar duplicados: verificar por user_id si está registrado, por IP si es anónimo
        if ($visitor) {
            // Usuario registrado — verificar por user_id
            $recentVisit = ProfileVisit::where('portfolio_id', $portfolio->id)
                ->where('user_id', $visitor->id)
                ->where('visited_at', '>=', now()->subHours(2))
                ->first();
        } else {
            // Anónimo — verificar por IP
            $recentVisit = ProfileVisit::where('portfolio_id', $portfolio->id)
                ->where('ip_address', $ipAddress)
                ->where('visited_at', '>=', now()->subHours(2))
                ->first();
        }

        if ($recentVisit) {
            if ($visitor) {
                return response()->json(['message' => 'Recent visit from same user - not counted'], 200);
            } else {
                return response()->json(['message' => 'Recent visit from same IP - not counted'], 200);
            }
        }

        // Registrar la visita
        ProfileVisit::create([
            'portfolio_id' => $portfolio->id,
            'user_id' => $visitorId,
            'ip_address' => $ipAddress,
            'visited_at' => now(),
        ]);

        // Incrementar el contador en el portfolio
        $portfolio->increment('views_count');

        return response()->json(['message' => 'Visit recorded successfully'], 201);
    }

    /**
     * Obtener estadísticas del perfil
     */
    public function stats(Request $request, string $profileId): JsonResponse
    {
        $portfolio = Portfolio::findOrFail($profileId);

        // Obtener contador total
        $visitsCount = $portfolio->visits()->count();

        // Obtener últimos 5 visitantes (excluyendo admins y dueño)
        $recentVisitors = $portfolio->visits()
            ->excludeAdmins()
            ->excludeOwner($portfolio->id)
            ->recent(5)
            ->get()
            ->map(function ($visit) {
                return [
                    'user_id' => $visit->user_id,
                    'name' => $visit->user_id 
                        ? ($visit->user->first_name . ' ' . $visit->user->last_name)
                        : 'Visitante anónimo',
                    'visited_at' => $visit->visited_at->toISOString(),
                ];
            });

        return response()->json([
            'visits_count' => $visitsCount,
            'recent_visitors' => $recentVisitors,
        ]);
    }

    /**
     * Obtener lista completa de visitantes (paginada)
     */
    public function visitors(Request $request, string $profileId): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $portfolio = Portfolio::findOrFail($profileId);

        $perPage = $request->input('per_page', 20);

        $visitors = $portfolio->visits()
            ->excludeAdmins()
            ->excludeOwner($portfolio->id)
            ->with('user')
            ->orderByDesc('visited_at')
            ->paginate($perPage);

        $data = $visitors->getCollection()->map(function ($visit) {
            return [
                'id' => $visit->id,
                'user_id' => $visit->user_id,
                'name' => $visit->user_id 
                    ? ($visit->user->first_name . ' ' . $visit->user->last_name)
                    : 'Visitante anónimo',
                'visited_at' => $visit->visited_at->toISOString(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $visitors->currentPage(),
                'per_page' => $visitors->perPage(),
                'total' => $visitors->total(),
            ],
        ]);
    }
}
