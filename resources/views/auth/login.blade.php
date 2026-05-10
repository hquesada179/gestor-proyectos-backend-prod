<x-guest-layout>

{{-- ── Fondo con gradiente sutil ──────────────────────────────────────────── --}}
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-10 relative overflow-hidden"
     style="background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(52,34,204,0.18) 0%, transparent 70%),
                        radial-gradient(ellipse 60% 40% at 90% 100%, rgba(99,102,241,0.1) 0%, transparent 60%),
                        #051424;">

    {{-- ── Tarjeta central ──────────────────────────────────────────────────── --}}
    <div class="w-full max-w-md"
         style="background:rgba(255,255,255,0.03);
                backdrop-filter:blur(20px);
                -webkit-backdrop-filter:blur(20px);
                border:1px solid rgba(255,255,255,0.09);
                border-radius:1.25rem;">

        <div class="px-8 pt-8 pb-6">

            {{-- ── Marca ─────────────────────────────────────────────────────── --}}
            <div class="flex flex-col items-center text-center mb-8">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 flex-shrink-0"
                     style="background:linear-gradient(135deg,#3422cc,#6366f1);
                            box-shadow:0 0 24px rgba(99,102,241,0.35);">
                    <span class="material-symbols-outlined text-white"
                          style="font-size:22px;font-variation-settings:'FILL' 1,'wght' 500">rocket_launch</span>
                </div>
                <h1 class="text-xl font-black text-white tracking-tight leading-none mb-1">
                    {{ config('app.name', 'Gestor de Proyectos') }}
                </h1>
                <p class="text-xs leading-relaxed mt-1" style="color:#475569;max-width:280px;">
                    Gestiona proyectos, sprints, tareas y créditos IA desde una sola plataforma.
                </p>
            </div>

            {{-- ── Estado de sesión (ej: "enlace de restablecimiento enviado") ── --}}
            <x-auth-session-status class="mb-4" :status="session('status')" />

            {{-- ══════════════════════════════════════════════════════════════════ --}}
            {{-- FORMULARIO PRINCIPAL — NO MODIFICAR RUTAS NI NOMBRES DE CAMPOS    --}}
            {{-- ══════════════════════════════════════════════════════════════════ --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <x-input-label for="email" value="Correo electrónico" />
                    <div class="relative mt-1">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                              style="font-size:16px;color:#475569;">mail</span>
                        <x-text-input
                            id="email"
                            class="block w-full pl-9"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="tu@correo.com"
                        />
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                </div>

                {{-- Contraseña --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <x-input-label for="password" value="Contraseña" />
                        @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="text-xs transition-colors hover:text-indigo-300"
                           style="color:#6366f1;">
                            ¿Olvidaste tu contraseña?
                        </a>
                        @endif
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
                              style="font-size:16px;color:#475569;">lock</span>
                        <x-text-input
                            id="password"
                            class="block w-full pl-9"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                </div>

                {{-- Recordarme --}}
                <div class="flex items-center gap-2.5">
                    <input id="remember_me"
                           type="checkbox"
                           name="remember"
                           class="rounded"
                           style="width:15px;height:15px;accent-color:#6366f1;background:#0d1c2d;border:1px solid #475569;cursor:pointer;">
                    <label for="remember_me" class="text-sm cursor-pointer select-none" style="color:#64748b;">
                        Mantener sesión iniciada
                    </label>
                </div>

                {{-- Botón iniciar sesión --}}
                <x-primary-button class="w-full justify-center py-2.5 text-sm">
                    <span class="material-symbols-outlined mr-1.5"
                          style="font-size:16px;font-variation-settings:'FILL' 1">login</span>
                    Iniciar sesión
                </x-primary-button>

            </form>
            {{-- ══ fin formulario ══════════════════════════════════════════════ --}}

            {{-- ── Separador ──────────────────────────────────────────────────── --}}
            <div class="my-5 flex items-center gap-3">
                <div class="flex-1" style="height:1px;background:rgba(255,255,255,0.06);"></div>
                <span class="text-xs px-1" style="color:#334155;">o continúa con</span>
                <div class="flex-1" style="height:1px;background:rgba(255,255,255,0.06);"></div>
            </div>

            {{-- ── Botón Google → Firebase Auth (redirect) ─────────────────── --}}
            <div id="firebase-error"
                 class="hidden text-xs text-center py-2 px-3 rounded-lg"
                 style="color:#f87171;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.2);margin-bottom:0.5rem;">
            </div>
            <button type="button"
                    id="btn-google-signin"
                    onclick="window.signInWithGoogle()"
                    class="w-full flex items-center justify-center gap-3 py-2.5 rounded-xl text-sm font-semibold transition-all hover:bg-white/5 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                    style="border:1px solid rgba(255,255,255,0.1);color:#d4e4fa;">
                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continuar con Google
            </button>

            {{-- ── Separador registro ──────────────────────────────────────────── --}}
            <p class="text-center text-xs mt-5" style="color:#334155;">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}"
                   class="font-semibold transition-colors hover:text-indigo-300"
                   style="color:#6366f1;">
                    Regístrate gratis
                </a>
            </p>

        </div>{{-- /card inner --}}
    </div>{{-- /card --}}


</div>{{-- /full-screen --}}

{{-- ══ Formulario oculto — envía idToken a Laravel para verificación ═══════ --}}
<form id="firebase-token-form" method="POST" action="{{ route('firebase.login') }}" class="hidden">
    @csrf
    <input type="hidden" id="firebase-id-token" name="firebase_id_token">
</form>

</x-guest-layout>
