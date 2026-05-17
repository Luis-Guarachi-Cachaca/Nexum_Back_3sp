<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PortfolioTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePortfolioThemeRequest;
use Illuminate\Http\JsonResponse;

class PortfolioThemeController extends Controller
{
    /**
     * PATCH /api/v1/portfolio/theme
     * Requiere: auth:sanctum + role:professional
     */
    public function update(UpdatePortfolioThemeRequest $request): JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json([
                'message' => 'El usuario no tiene un portfolio creado.',
            ], 404);
        }

        $portfolio->update([
            'design_pattern' => $request->validated('design_pattern'),
        ]);

        $theme = PortfolioTheme::from($portfolio->design_pattern);

        return response()->json([
            'message' => 'Tema actualizado correctamente.',
            'data'    => [
                'design_pattern' => $portfolio->design_pattern,
                'theme'          => $theme->metadata(),
            ],
        ]);
    }
}