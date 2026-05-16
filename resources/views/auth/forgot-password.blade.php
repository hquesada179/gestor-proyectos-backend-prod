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
                <img src="{{ asset('brand/scrumter-logo.png') }}"
                     alt="Scrumter"
                     class="mb-4 object-contain"
                     style="width:56px;height:56px;border-radius:12px;">
                <h1 class="text-xl font-black text-white tracking-tight leading-none mb-1">
                    Recuperar contraseña
                </h1>
                <p class="text-xs leading-relaxed mt-2" style="color:#475569;max-width:300px;">
                    Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
                </p>
            </div>

            {{-- ── Mensaje de confirmación tras envío exitoso ────────────────── --}}
            <x-auth-session-status class="mb-5" :status="session('status')" />

            {{-- ══════════════════════════════════════════════════════════════════ --}}
            {{-- FORMULARIO — usa route('password.email'), no modificar            --}}
            {{-- ══════════════════════════════════════════════════════════════════ --}}
            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                {{-- Correo electrónico --}}
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

                {{-- Botón enviar enlace --}}
                <x-primary-button class="w-full justify-center py-2.5 text-sm">
                    <span class="material-symbols-outlined mr-1.5"
                          style="font-size:16px;font-variation-settings:'FILL' 1">send</span>
                    Enviar enlace de recuperación
                </x-primary-button>

            </form>
            {{-- ══ fin formulario ══════════════════════════════════════════════ --}}

            {{-- ── Volver al login ─────────────────────────────────────────────── --}}
            <p class="text-center text-xs mt-5" style="color:#334155;">
                <a href="{{ route('login') }}"
                   class="font-semibold transition-colors hover:text-indigo-300"
                   style="color:#6366f1;">
                    ← Volver al inicio de sesión
                </a>
            </p>

        </div>{{-- /card inner --}}
    </div>{{-- /card --}}

</div>{{-- /full-screen --}}

</x-guest-layout>
