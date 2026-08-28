@if (session('success'))
    <div class="glass-panel border-green-500/30 rounded-xl px-5 py-3.5 flex items-center gap-3 text-sm text-green-400">
        <span class="material-symbols-outlined flex-shrink-0" style="font-size: 18px; font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;">check_circle</span>
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="glass-panel border-red-500/30 rounded-xl px-5 py-3.5 flex items-center gap-3 text-sm text-red-500">
        <span class="material-symbols-outlined flex-shrink-0" style="font-size: 18px; font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;">error</span>
        {{ session('error') }}
    </div>
@endif
