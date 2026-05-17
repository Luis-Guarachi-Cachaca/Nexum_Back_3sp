<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PortfolioTheme;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ThemeController extends Controller
{
    /**
     * GET /api/v1/themes
     * Endpoint público — no requiere autenticación.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => PortfolioTheme::allMetadata(),
        ]);
    }
}