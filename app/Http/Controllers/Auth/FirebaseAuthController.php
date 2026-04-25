<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Throwable;

class FirebaseAuthController extends Controller
{
    public function __construct(private readonly FirebaseAuth $firebaseAuth) {}

    public function handleCallback(Request $request): RedirectResponse
    {
        $idToken = $request->input('firebase_id_token');

        if (! $idToken) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Token de autenticación no recibido.']);
        }

        try {
            $verifiedToken = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => 'No se pudo verificar la autenticación con Google. Intenta de nuevo.']);
        }

        $uid   = $verifiedToken->claims()->get('sub');
        $email = $verifiedToken->claims()->get('email');
        $name  = $verifiedToken->claims()->get('name') ?? Str::before($email, '@');

        if (! $email) {
            return redirect()->route('login')
                ->withErrors(['email' => 'No se pudo obtener el correo de la cuenta de Google.']);
        }

        // Busca por firebase_uid primero, luego por email (usuarios existentes)
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

        // Vincula el UID si el usuario ya existía por email pero sin UID
        if (! $user->firebase_uid) {
            $user->update(['firebase_uid' => $uid]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
