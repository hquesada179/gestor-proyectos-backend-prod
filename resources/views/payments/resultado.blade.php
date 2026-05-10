<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-gray-400">Resultado del pago</span>
    </x-slot>

    @php
        $isApproved = $status === 'approved';
        $isRejected = in_array($status, ['rejected', 'failed', 'voided']);
        $isPending  = !$isApproved && !$isRejected;
    @endphp

    <div class="flex items-center justify-center min-h-[75vh] p-6">
        <div class="text-center max-w-md w-full rounded-2xl p-10"
             style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">

            {{-- Ícono de estado --}}
            @if($isApproved)
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
                 style="background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.3);">
                <span class="material-symbols-outlined"
                      style="font-size:32px;color:#34d399;font-variation-settings:'FILL' 1;">check_circle</span>
            </div>
            @elseif($isRejected)
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
                 style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);">
                <span class="material-symbols-outlined"
                      style="font-size:32px;color:#f87171;font-variation-settings:'FILL' 1;">cancel</span>
            </div>
            @else
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
                 style="background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.3);">
                <span class="material-symbols-outlined"
                      style="font-size:32px;color:#f59e0b;font-variation-settings:'FILL' 1;">pending</span>
            </div>
            @endif

            {{-- Título y mensaje --}}
            @if($isApproved && $isFree)
            <h1 class="text-2xl font-black text-white mb-2">¡Plan activado!</h1>
            <p class="text-sm mb-6" style="color:#64748b;">
                Tu plan <strong class="text-white">{{ $planName ?? 'Gratis' }}</strong>
                ha sido activado. Ya puedes usar el asistente IA.
            </p>

            @elseif($isApproved)
            <h1 class="text-2xl font-black text-white mb-2">¡Pago aprobado!</h1>
            <p class="text-sm mb-3" style="color:#64748b;">
                Tu plan <strong class="text-white">{{ $payment?->plan?->name ?? $planName ?? 'nuevo' }}</strong>
                está activo. Tus créditos IA ya están disponibles.
            </p>
            @if($payment?->reference)
            <p class="text-xs mb-6" style="color:#334155;">
                Referencia: <span style="color:#818cf8;font-family:monospace;">{{ $payment->reference }}</span>
            </p>
            @else
            <div class="mb-6"></div>
            @endif

            @elseif($isRejected)
            <h1 class="text-2xl font-black text-white mb-2">Pago no completado</h1>
            <p class="text-sm mb-6" style="color:#64748b;">
                Tu pago no pudo procesarse correctamente. No se realizó ningún cargo
                ni se modificó tu plan actual. Puedes intentarlo de nuevo.
            </p>

            @else
            <h1 class="text-2xl font-black text-white mb-2">Procesando tu pago</h1>
            <p class="text-sm mb-3" style="color:#64748b;">
                Tu transacción está siendo confirmada. Este proceso puede tomar
                unos minutos. Tu plan se activará automáticamente.
            </p>
            @if($payment?->reference)
            <p class="text-xs mb-4" style="color:#334155;">
                Referencia: <span style="color:#818cf8;font-family:monospace;">{{ $payment->reference }}</span>
            </p>
            @endif
            <p class="text-xs mb-6" style="color:#1e3a5f;">
                No es necesario hacer nada más. Puedes cerrar esta página.
            </p>
            @endif

            {{-- Acciones --}}
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                @if($isApproved)
                <a href="{{ route('asistente-ia.index') }}"
                   class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all hover:opacity-90 active:scale-95"
                   style="background:#3422cc;color:#c3c0ff;box-shadow:0 3px 12px rgba(52,34,204,0.35);">
                    <span class="material-symbols-outlined align-middle"
                          style="font-size:14px;font-variation-settings:'FILL' 1">auto_awesome</span>
                    Usar asistente IA
                </a>
                @elseif($isRejected)
                <a href="{{ route('planes.index') }}"
                   class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all hover:opacity-90 active:scale-95"
                   style="background:#3422cc;color:#c3c0ff;box-shadow:0 3px 12px rgba(52,34,204,0.35);">
                    Intentar de nuevo
                </a>
                @else
                <a href="{{ route('dashboard') }}"
                   class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all hover:opacity-90 active:scale-95"
                   style="background:rgba(99,102,241,0.1);color:#a5b4fc;border:1px solid rgba(99,102,241,0.22);">
                    Ir al panel
                </a>
                @endif

                <a href="{{ route('planes.index') }}"
                   class="px-6 py-2.5 rounded-xl text-sm font-semibold transition-all hover:bg-white/5"
                   style="border:1px solid rgba(255,255,255,0.1);color:#64748b;">
                    Ver planes
                </a>
            </div>

        </div>
    </div>

</x-app-layout>
