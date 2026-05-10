<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-gray-400">Pago pendiente</span>
    </x-slot>

    <div class="flex items-center justify-center min-h-[70vh] p-6">
        <div class="text-center max-w-md w-full rounded-2xl p-10"
             style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">

            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
                 style="background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.3);">
                <span class="material-symbols-outlined"
                      style="font-size:32px;color:#f59e0b;font-variation-settings:'FILL' 1;">pending</span>
            </div>

            <h1 class="text-2xl font-black text-white mb-2">Pago en proceso</h1>
            <p class="text-sm mb-2" style="color:#64748b;">
                Tu pago está siendo procesado. Este proceso puede tomar unos minutos.
            </p>
            @if($payment)
            <p class="text-xs mb-6" style="color:#334155;">
                Referencia: <span style="color:#818cf8;font-family:monospace;">{{ $payment->reference }}</span>
            </p>
            @else
            <div class="mb-6"></div>
            @endif

            <p class="text-xs mb-6" style="color:#475569;">
                Tu plan se activará automáticamente una vez confirmado el pago.
                No es necesario hacer nada más.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('dashboard') }}"
                   class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all hover:opacity-90 active:scale-95"
                   style="background:rgba(99,102,241,0.12);color:#a5b4fc;border:1px solid rgba(99,102,241,0.22);">
                    Ir al panel
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
