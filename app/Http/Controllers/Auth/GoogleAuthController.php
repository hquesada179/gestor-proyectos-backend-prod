<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google after the user grants permission.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => 'No se pudo completar el inicio de sesión con Google. Intenta de nuevo.']);
        }

        $email = $googleUser->getEmail();
        $name  = $googleUser->getName() ?? Str::before($email, '@');

        if (! $email) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google no devolvió un correo electrónico válido.']);
        }

        // Busca usuario existente o crea uno nuevo.
        // UserObserver asigna plan gratuito y crea user_ai_credits automáticamente.
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
