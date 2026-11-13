<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint de telemetría y verificación de conectividad para el 3.er canal (API Pública).
 */
class PublicPingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // Retorna estado operativo, versión y capacidades del canal público
        return response()->json([
            'status' => 'healthy',
            'channel' => 'public_api',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
            'server' => 'Scrumter Engine 13',
            'abilities' => [
                'projects:read',
                'tasks:read',
                'tasks:write',
            ],
        ]);
    }
}
