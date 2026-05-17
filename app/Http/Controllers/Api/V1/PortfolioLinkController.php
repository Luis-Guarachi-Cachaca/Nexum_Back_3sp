<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\PortfolioLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioLinkController extends Controller
{
    /**
     * Listar todos los enlaces adicionales del usuario autenticado
     */
    public function index(): JsonResponse
    {
        $user = request()->user();
        $portfolio = $user->portfolio;

        if (!$portfolio) {
            return response()->json(['message' => 'Portfolio not found'], 404);
        }

        $links = $portfolio->additionalLinks()
            ->orderBy('created_at', 'desc')
            ->get(['id', 'url', 'platform', 'created_at']);

        return response()->json(['data' => $links]);
    }

    /**
     * Agregar un nuevo enlace adicional
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:255',
        ], [
            'url.required' => 'The URL field is required.',
            'url.url' => 'The URL must be a valid URL.',
            'url.max' => 'The URL may not be greater than 255 characters.',
        ]);

        $user = request()->user();
        $portfolio = $user->portfolio;

        if (!$portfolio) {
            return response()->json(['message' => 'Portfolio not found'], 404);
        }

        // Verificar límite de 10 enlaces
        $currentLinksCount = $portfolio->additionalLinks()->count();
        if ($currentLinksCount >= 10) {
            return response()->json([
                'message' => 'Maximum of 10 additional links allowed per portfolio.',
                'current_count' => $currentLinksCount,
            ], 422);
        }

        // Detectar automáticamente la plataforma
        $platform = PortfolioLink::detectPlatform($validated['url']);

        $link = $portfolio->additionalLinks()->create([
            'url' => $validated['url'],
            'platform' => $platform,
        ]);

        return response()->json(['data' => $link], 201);
    }

    /**
     * Eliminar un enlace adicional
     */
    public function destroy(int $id): JsonResponse
    {
        $user = request()->user();
        $portfolio = $user->portfolio;

        if (!$portfolio) {
            return response()->json(['message' => 'Portfolio not found'], 404);
        }

        $link = PortfolioLink::where('id', $id)
            ->where('portfolio_id', $portfolio->id)
            ->first();

        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        $link->delete();

        return response()->json(['message' => 'Link deleted successfully']);
    }
}
