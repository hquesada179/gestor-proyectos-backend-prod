<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-indigo-400" style="font-size: 22px;">auto_awesome</span>
            <span class="text-sm font-semibold text-white">Asistente IA</span>
            <span class="text-gray-600 text-sm">·</span>
            <span class="text-xs text-gray-500">Redirigiendo al asistente IA</span>
        </div>
    </x-slot>

    <div class="min-h-[60vh] flex items-center justify-center px-6">
        <div class="max-w-md w-full rounded-2xl border border-slate-700/60 bg-slate-900/80 p-6 text-center shadow-xl">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl border border-indigo-500/30 bg-indigo-500/15">
                <span class="material-symbols-outlined text-indigo-300" style="font-size: 24px;">auto_awesome</span>
            </div>
            <h1 class="text-lg font-bold text-white">Asistente IA actualizado</h1>
            <p class="mt-2 text-sm text-slate-400">
                El asistente ha sido actualizado. Usa el nuevo Asistente IA.
            </p>
            <a href="{{ route('asistente-ia.index') }}"
               class="mt-5 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">
                Abrir asistente IA
            </a>
        </div>
    </div>

    <script>
        window.location.replace(@json(route('asistente-ia.index')));
    </script>
</x-app-layout>
