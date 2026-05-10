<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Throwable;

class FirebaseApiAuthController extends Controller
{
    public function __construct(private readonly FirebaseAuth $firebaseAuth) {}

    /**
     * Verifica un Firebase ID Token enviado desde Android y devuelve
     * el usuario MySQL junto con un Sanctum API token para autenticación posterior.
     *
     * Body JSON esperado:
     *   { "id_token": "<Firebase ID Token>" }
     *
     * Respuesta exitosa:
     *   { "user": {...}, "token": "...", "token_type": "Bearer" }
     */
    public function login(Request $request): JsonResponse
    {
        $idToken = $request->input('id_token');

        if (! $idToken) {
            return response()->json(['message' => 'Se requiere el campo id_token.'], 422);
        }

        try {
            $verifiedToken = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (Throwable) {
            return response()->json(['message' => 'Token de Firebase inválido o expirado.'], 401);
        }

        $uid   = $verifiedToken->claims()->get('sub');
        $email = $verifiedToken->claims()->get('email');
        $name  = $verifiedToken->claims()->get('name') ?? Str::before($email, '@');

        if (! $email) {
            return response()->json(['message' => 'El token no contiene un correo electrónico.'], 422);
        }

        // Busca por UID → luego por email (vincula UID si existía antes)
        $user = User::where('firebase_uid', $uid)->first()
            ?? User::firstOrCreate(
                ['email' => $email],
                [
                    'name'              => $name,
                    'firebase_uid'      => $uid,
                    'email_verified_at' => now(),
                    'password'          => Str::random(32),
                ]
            );

        if (! $user->firebase_uid) {
            $user->update(['firebase_uid' => $uid]);
        }

        // Revocar tokens anteriores del mismo device name para evitar acumulación
        $user->tokens()->where('name', 'android-app')->delete();
        $token = $user->createToken('android-app')->plainTextToken;

        return response()->json([
            'user' => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'firebase_uid' => $user->firebase_uid,
                'plan_id'      => $user->plan_id,
            ],
            'token'      => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
