<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-gray-400">Pago exitoso</span>
    </x-slot>

    <div class="flex items-center justify-center min-h-[70vh] p-6">
        <div class="text-center max-w-md w-full rounded-2xl p-10"
             style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">

            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
                 style="background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.3);">
                <span class="material-symbols-outlined"
                      style="font-size:32px;color:#34d399;font-variation-settings:'FILL' 1;">check_circle</span>
            </div>

            @if(session('free'))
                <h1 class="text-2xl font-black text-white mb-2">¡Plan activado!</h1>
                <p class="text-sm mb-6" style="color:#64748b;">
                    Tu plan <strong class="text-white">{{ session('plan_name', 'Gratis') }}</strong>
                    ha sido activado correctamente.
                </p>
            @elseif($payment?->status === 'approved')
                <h1 class="text-2xl font-black text-white mb-2">¡Pago aprobado!</h1>
                <p class="text-sm mb-6" style="color:#64748b;">
                    Tu plan <strong class="text-white">{{ $payment->plan->name ?? '' }}</strong>
                    está activo. Tus créditos IA ya están disponibles.
                </p>
            @else
                <h1 class="text-2xl font-black text-white mb-2">Pago en proceso</h1>
                <p class="text-sm mb-6" style="color:#64748b;">
                    Tu pago está siendo confirmado. Recibirás una notificación cuando se complete.
                    Puede tomar unos minutos.
                </p>
            @endif

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('asistente-ia.index') }}"
                   class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all hover:opacity-90 active:scale-95"
                   style="background:#3422cc;color:#c3c0ff;">
                    Usar asistente IA
                </a>
                <a href="{{ route('planes.index') }}"
                   class="px-6 py-2.5 rounded-xl text-sm font-semibold transition-all hover:bg-white/5"
                   style="border:1px solid rgba(255,255,255,0.12);color:#94a3b8;">
                    Ver planes
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
