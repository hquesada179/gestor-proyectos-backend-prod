<!DOCTYPE html>
<html class="dark h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GestorApp') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-hidden bg-app-bg font-sans antialiased text-on-surface">

<div class="flex h-full">

    {{-- ─── SIDEBAR ───────────────────────────────────────────────── --}}
    <aside class="fixed left-0 top-0 h-full w-[280px] z-50
                  bg-white/5 backdrop-blur-2xl border-r border-white/5
                  flex flex-col py-6 shadow-2xl shadow-black/50">

        {{-- Logo --}}
        <div class="px-6 mb-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                <div class="w-8 h-8 rounded bg-secondary-container flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-white" style="font-size: 17px; font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;">rocket_launch</span>
                </div>
                <span class="text-base font-black text-white tracking-tight truncate">
                    {{ config('app.name', 'GestorApp') }}
                </span>
            </a>
        </div>

        {{-- Primary navigation --}}
        <nav class="flex-1 flex flex-col gap-0.5 px-3 overflow-y-auto">

            @php
                $ruta         = request()->route()?->getName() ?? '';
                $isDashboard  = $ruta === 'dashboard';
                $isReqs       = str_starts_with($ruta, 'proyectos.requirements');
                $isSprints    = str_starts_with($ruta, 'proyectos.sprints');
                $isTareas     = str_starts_with($ruta, 'proyectos.tasks');
                $isInputs     = str_starts_with($ruta, 'proyectos.inputs');
                $isProyectos  = str_starts_with($ruta, 'proyectos.')
                    && !$isReqs && !$isSprints && !$isTareas && !$isInputs;
                $isMisTareas  = $ruta === 'mis-tareas';
                $isScrumBoard  = str_starts_with($ruta, 'scrum-board');
                $isCalendario  = $ruta === 'calendario.index';
                $isAiAssist    = str_starts_with($ruta, 'asistente-ia');
            @endphp

            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isDashboard ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">dashboard</span>
                Dashboard
            </a>

            <a href="{{ route('proyectos.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isProyectos ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">folder_open</span>
                Proyectos
            </a>

            <a href="{{ route('mis-tareas') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isMisTareas ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">task_alt</span>
                Mis Tareas
            </a>

            <div class="my-3 mx-1 border-t border-white/5"></div>

            <p class="px-4 py-1 text-[10px] uppercase tracking-widest text-gray-600 font-semibold select-none">
                Módulos
            </p>

            <a href="{{ route('modulo.selector', 'requerimientos') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isReqs ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">edit_note</span>
                Requerimientos
            </a>

            <a href="{{ route('scrum-board.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isScrumBoard ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">view_kanban</span>
                Scrum Board
            </a>

            <a href="{{ route('modulo.selector', 'sprints') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isSprints ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">sprint</span>
                Sprints
            </a>

            <a href="{{ route('modulo.selector', 'tareas') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isTareas ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">checklist</span>
                Tablero Tareas
            </a>

            <a href="{{ route('modulo.selector', 'insumos') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium nav-link-inactive hover:translate-x-0.5 transition-all duration-150">
                <span class="material-symbols-outlined" style="font-size: 20px;">inventory_2</span>
                Insumos
            </a>

            <a href="{{ route('calendario.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isCalendario ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">calendar_month</span>
                Calendario
            </a>

            <div class="my-3 mx-1 border-t border-white/5"></div>

            <p class="px-4 py-1 text-[10px] uppercase tracking-widest text-gray-600 font-semibold select-none">
                Inteligencia Artificial
            </p>

            <a href="{{ route('asistente-ia.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isAiAssist ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px; font-variation-settings: 'FILL' 1;">auto_awesome</span>
                Asistente IA
            </a>

        </nav>

        {{-- Bottom: CTA + user actions --}}
        <div class="px-4 mt-4 space-y-1">
            <a href="{{ route('proyectos.create') }}"
               class="w-full py-2.5 px-4 bg-secondary-container text-white rounded-xl font-bold text-sm
                      flex items-center justify-center gap-2 hover:opacity-90 transition-all active:scale-95 mb-1">
                <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                Nuevo proyecto
            </a>

            <div class="pt-3 border-t border-white/5 space-y-0.5">
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm nav-link-inactive hover:translate-x-0.5 transition-all duration-150">
                    <span class="material-symbols-outlined" style="font-size: 18px;">manage_accounts</span>
                    Perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-sm nav-link-inactive hover:translate-x-0.5 transition-all duration-150 text-left">
                        <span class="material-symbols-outlined" style="font-size: 18px;">logout</span>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- ─── MAIN CONTENT AREA ─────────────────────────────────────── --}}
    <div class="ml-[280px] flex-1 flex flex-col h-full overflow-hidden">

        {{-- Topbar --}}
        <header class="flex-shrink-0 h-16 bg-[#0F1117]/80 backdrop-blur-xl border-b border-white/10 z-40
                       flex items-center justify-between px-8 gap-4">

            <div class="flex items-center gap-3 min-w-0 flex-1">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>

            {{-- ─── PROFILE DROPDOWN ────────────────────────────────── --}}
            <div class="relative flex-shrink-0"
                 x-data="{ open: false }"
                 @click.outside="open = false"
                 @keydown.escape.window="open = false">

                {{-- Avatar trigger --}}
                <button @click="open = !open"
                        :aria-expanded="open.toString()"
                        aria-haspopup="true"
                        class="flex items-center gap-3 px-2 py-1 rounded-xl hover:bg-white/5 transition-all duration-150 cursor-pointer group outline-none focus:ring-2 focus:ring-blue-500/40">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-white leading-none">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] text-on-primary-container mt-0.5">{{ Auth::user()->email }}</p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-secondary-container flex items-center justify-center
                                text-white text-sm font-black border border-white/10 flex-shrink-0 select-none
                                ring-2 ring-transparent group-hover:ring-blue-500/50 transition-all duration-150">
                        {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                    </div>
                </button>

                {{-- Dropdown panel --}}
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                     class="absolute right-0 top-full mt-3 w-[360px] z-[60] rounded-2xl overflow-hidden
                            bg-[#1c2b3c] border border-white/10 shadow-2xl shadow-black/60"
                     style="display:none; box-shadow: 0 0 30px rgba(167,75,254,0.12), 0 25px 50px rgba(0,0,0,0.5);">

                    {{-- ── Cabecera de perfil ─────────────────────── --}}
                    <div class="px-6 py-5 border-b border-white/5 flex flex-col items-center text-center">
                        <div class="relative mb-3">
                            <div class="w-20 h-20 rounded-full bg-secondary-container flex items-center justify-center
                                        text-white text-3xl font-black border-2 border-blue-500/40 select-none">
                                {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div class="absolute bottom-0.5 right-0.5 w-4 h-4 bg-emerald-500 border-4 border-[#1c2b3c] rounded-full"></div>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-0.5">{{ Auth::user()->name }}</h3>
                        <p class="text-xs text-on-surface-variant">{{ Auth::user()->email }}</p>
                        <span class="mt-3 px-3 py-1 bg-blue-500/10 text-blue-400 text-[10px] uppercase font-bold tracking-widest rounded-full border border-blue-500/20">
                            Activo
                        </span>
                    </div>

                    {{-- ── Cambiar foto (visual) ──────────────────── --}}
                    <div class="px-4 py-3 border-b border-white/5">
                        <button type="button"
                                class="w-full flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-all text-left cursor-pointer">
                            <div class="w-10 h-10 rounded-lg bg-[#273647] flex items-center justify-center text-on-surface-variant flex-shrink-0">
                                <span class="material-symbols-outlined" style="font-size:20px;">upload_file</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">Cambiar foto de perfil</p>
                                <p class="text-[11px] text-on-surface-variant">Sube JPG o PNG</p>
                            </div>
                        </button>
                    </div>

                    {{-- ── Opciones principales ───────────────────── --}}
                    <div class="px-3 py-2 border-b border-white/5">
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">person</span>
                            <span class="text-sm font-medium">Mi perfil</span>
                        </a>
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">manage_accounts</span>
                            <span class="text-sm font-medium">Configuración de cuenta</span>
                        </a>
                        <a href="#"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">tune</span>
                            <span class="text-sm font-medium">Preferencias</span>
                        </a>
                        <a href="#"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">security</span>
                            <span class="text-sm font-medium">Seguridad</span>
                        </a>
                        <a href="#"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">notifications_active</span>
                            <span class="text-sm font-medium">Notificaciones</span>
                        </a>
                    </div>

                    {{-- ── Preferencias visuales ──────────────────── --}}
                    <div class="px-3 py-3 space-y-1 border-b border-white/5">
                        <div class="flex items-center justify-between px-3 py-1.5">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-blue-400" style="font-size:20px;">dark_mode</span>
                                <span class="text-sm text-on-surface-variant">Modo oscuro</span>
                            </div>
                            <div class="w-10 h-5 bg-blue-500 rounded-full relative cursor-default flex-shrink-0">
                                <div class="absolute right-0.5 top-0.5 w-4 h-4 bg-white rounded-full"></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-white/5 transition-all cursor-default">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-on-surface-variant" style="font-size:20px;">language</span>
                                <span class="text-sm text-on-surface-variant">Idioma</span>
                            </div>
                            <span class="text-sm text-blue-400">Español</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-white/5 transition-all cursor-default">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-on-surface-variant" style="font-size:20px;">palette</span>
                                <span class="text-sm text-on-surface-variant">Tema</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                <span class="text-[10px] text-on-surface-variant ml-0.5">Azul/Violeta</span>
                            </div>
                        </div>
                    </div>

                    {{-- ── Cerrar sesión ──────────────────────────── --}}
                    <div class="px-3 py-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl
                                           hover:bg-red-500/10 transition-all text-red-400 cursor-pointer group">
                                <span class="material-symbols-outlined" style="font-size:20px;">logout</span>
                                <span class="text-sm font-bold">Cerrar sesión</span>
                            </button>
                        </form>
                    </div>

                </div>
            </div>
            {{-- ─────────────────────────────────────────────────────── --}}

        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto">
            {{ $slot }}
        </main>

    </div>

</div>

@stack('scripts')
</body>
</html>
