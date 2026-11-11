<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);

        // ── RATE LIMITERS DE RESILIENCIA Y MITIGACIÓN DE SOBRECARGA ───────────────

        // Limitador para autenticación móvil: previene ataques de fuerza bruta
        RateLimiter::for('firebase-auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Demasiados intentos de autenticación. Por favor intente más tarde.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers);
            });
        });

        // Limitador para inferencia de IA: previene agotamiento de presupuesto y saturación
        RateLimiter::for('ai-generation', function (Request $request) {
            $identifier = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(10)->by($identifier)->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Ha alcanzado el límite de solicitudes de IA por minuto. Espere antes de reintentar.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers);
            });
        });

        // Limitador para la API Pública (Canal 3): protege recursos ante ráfagas de CI/CD
        RateLimiter::for('public-api', function (Request $request) {
            $identifier = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(60)->by($identifier)->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Demasiadas solicitudes a la API pública. Cuota por minuto excedida.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429, $headers);
            });
        });
    }
}
