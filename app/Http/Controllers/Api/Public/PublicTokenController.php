<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Controlador para emisión de API Keys y Personal Access Tokens con permisos acotados.
 */
class PublicTokenController extends Controller
{
    /**
     * Emite un nuevo token con capacidades restringidas para integraciones de desarrollador.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'token_name' => ['required', 'string', 'max:50'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'in:projects:read,tasks:read,tasks:write'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Validación de credenciales de usuario
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Si no se envían abilities, se asignan permisos mínimos de solo lectura
        $abilities = $validated['abilities'] ?? ['projects:read', 'tasks:read'];

        $token = $user->createToken($validated['token_name'], $abilities);

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $token->plainTextToken,
            'token_name' => $validated['token_name'],
            'abilities' => $abilities,
            'instructions' => 'Incluya esta clave en la cabecera HTTP: "Authorization: Bearer <token>"',
        ], 201);
    }
}
