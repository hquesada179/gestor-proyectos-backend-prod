<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-gray-400">Pago fallido</span>
    </x-slot>

    <div class="flex items-center justify-center min-h-[70vh] p-6">
        <div class="text-center max-w-md w-full rounded-2xl p-10"
             style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);">

            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
                 style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);">
                <span class="material-symbols-outlined"
                      style="font-size:32px;color:#f87171;font-variation-settings:'FILL' 1;">cancel</span>
            </div>

            <h1 class="text-2xl font-black text-white mb-2">Pago no completado</h1>
            <p class="text-sm mb-6" style="color:#64748b;">
                Tu pago no pudo completarse. No se realizó ningún cargo ni se modificó tu plan actual.
                Puedes intentarlo de nuevo cuando quieras.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('planes.index') }}"
                   class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all hover:opacity-90 active:scale-95"
                   style="background:#3422cc;color:#c3c0ff;">
                    Intentar de nuevo
                </a>
                <a href="{{ route('dashboard') }}"
                   class="px-6 py-2.5 rounded-xl text-sm font-semibold transition-all hover:bg-white/5"
                   style="border:1px solid rgba(255,255,255,0.12);color:#94a3b8;">
                    Ir al panel
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
