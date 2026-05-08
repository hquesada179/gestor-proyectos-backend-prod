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
    @stack('styles')
</head>
<body class="h-full overflow-hidden bg-app-bg font-sans antialiased text-on-surface">

@php
    $localeNames = [
        'es'    => 'Español',
        'en'    => 'English',
        'fr'    => 'Français',
        'pt'    => 'Português',
        'de'    => 'Deutsch',
        'it'    => 'Italiano',
        'zh_CN' => '中文',
    ];
    $currentLocale = app()->getLocale();
@endphp

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
                {{ __('app.nav.dashboard') }}
            </a>

            <a href="{{ route('proyectos.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isProyectos ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">folder_open</span>
                {{ __('app.nav.projects') }}
            </a>

            <a href="{{ route('mis-tareas') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isMisTareas ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">task_alt</span>
                {{ __('app.nav.my_tasks') }}
            </a>

            <div class="my-3 mx-1 border-t border-white/5"></div>

            <p class="px-4 py-1 text-[10px] uppercase tracking-widest text-gray-600 font-semibold select-none">
                {{ __('app.nav.modules') }}
            </p>

            <a href="{{ route('modulo.selector', 'requerimientos') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isReqs ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">edit_note</span>
                {{ __('app.nav.requirements') }}
            </a>

            <a href="{{ route('scrum-board.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isScrumBoard ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">view_kanban</span>
                {{ __('app.nav.scrum_board') }}
            </a>

            <a href="{{ route('modulo.selector', 'sprints') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isSprints ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">sprint</span>
                {{ __('app.nav.sprints') }}
            </a>

            <a href="{{ route('modulo.selector', 'tareas') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isTareas ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">checklist</span>
                {{ __('app.nav.tasks_board') }}
            </a>

            <a href="{{ route('modulo.selector', 'insumos') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium nav-link-inactive hover:translate-x-0.5 transition-all duration-150">
                <span class="material-symbols-outlined" style="font-size: 20px;">inventory_2</span>
                {{ __('app.nav.inputs') }}
            </a>

            <a href="{{ route('calendario.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isCalendario ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px;">calendar_month</span>
                {{ __('app.nav.calendar') }}
            </a>

            <div class="my-3 mx-1 border-t border-white/5"></div>

            <p class="px-4 py-1 text-[10px] uppercase tracking-widest text-gray-600 font-semibold select-none">
                {{ __('app.nav.ai_section') }}
            </p>

            <a href="{{ route('asistente-ia.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isAiAssist ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}">
                <span class="material-symbols-outlined" style="font-size: 20px; font-variation-settings: 'FILL' 1;">auto_awesome</span>
                {{ __('app.nav.ai_assistant') }}
            </a>

        </nav>

        {{-- Bottom: CTA + user actions --}}
        <div class="px-4 mt-4 space-y-1">
            <a href="{{ route('proyectos.create') }}"
               class="w-full py-2.5 px-4 bg-secondary-container text-white rounded-xl font-bold text-sm
                      flex items-center justify-center gap-2 hover:opacity-90 transition-all active:scale-95 mb-1">
                <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                {{ __('app.actions.new_project') }}
            </a>

            <div class="pt-3 border-t border-white/5 space-y-0.5">
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm nav-link-inactive hover:translate-x-0.5 transition-all duration-150">
                    <span class="material-symbols-outlined" style="font-size: 18px;">manage_accounts</span>
                    {{ __('app.nav.profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-sm nav-link-inactive hover:translate-x-0.5 transition-all duration-150 text-left">
                        <span class="material-symbols-outlined" style="font-size: 18px;">logout</span>
                        {{ __('app.nav.logout') }}
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
                 x-data="{ open: false, showLang: false }"
                 @click.outside="open = false; showLang = false"
                 @keydown.escape.window="open = false; showLang = false">

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
                            {{ __('app.profile.active') }}
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
                                <p class="text-sm font-semibold text-white">{{ __('app.profile.change_photo') }}</p>
                                <p class="text-[11px] text-on-surface-variant">{{ __('app.profile.upload_hint') }}</p>
                            </div>
                        </button>
                    </div>

                    {{-- ── Opciones principales ───────────────────── --}}
                    <div class="px-3 py-2 border-b border-white/5">
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">person</span>
                            <span class="text-sm font-medium">{{ __('app.profile.my_profile') }}</span>
                        </a>
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">manage_accounts</span>
                            <span class="text-sm font-medium">{{ __('app.profile.account_settings') }}</span>
                        </a>
                        <a href="#"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">tune</span>
                            <span class="text-sm font-medium">{{ __('app.profile.preferences') }}</span>
                        </a>
                        <a href="#"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">security</span>
                            <span class="text-sm font-medium">{{ __('app.profile.security') }}</span>
                        </a>
                        <a href="#"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition-all text-on-surface-variant hover:text-white group">
                            <span class="material-symbols-outlined group-hover:text-blue-400 transition-colors" style="font-size:20px;">notifications_active</span>
                            <span class="text-sm font-medium">{{ __('app.profile.notifications') }}</span>
                        </a>
                    </div>

                    {{-- ── Preferencias visuales ──────────────────── --}}
                    <div class="px-3 py-3 space-y-1 border-b border-white/5">
                        <div class="flex items-center justify-between px-3 py-1.5">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-blue-400" style="font-size:20px;">dark_mode</span>
                                <span class="text-sm text-on-surface-variant">{{ __('app.profile.dark_mode') }}</span>
                            </div>
                            <div class="w-10 h-5 bg-blue-500 rounded-full relative cursor-default flex-shrink-0">
                                <div class="absolute right-0.5 top-0.5 w-4 h-4 bg-white rounded-full"></div>
                            </div>
                        </div>

                        {{-- ── Selector de idioma ─────────────────── --}}
                        <div>
                            <button type="button"
                                    @click="showLang = !showLang"
                                    class="w-full flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-white/5 transition-all cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-on-surface-variant" style="font-size:20px;">language</span>
                                    <span class="text-sm text-on-surface-variant">{{ __('app.profile.language') }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm text-blue-400">{{ $localeNames[$currentLocale] ?? $currentLocale }}</span>
                                    <span class="material-symbols-outlined text-on-surface-variant transition-transform duration-200"
                                          :class="showLang ? 'rotate-180' : ''"
                                          style="font-size:16px;">expand_more</span>
                                </div>
                            </button>

                            {{-- Submenú de idiomas --}}
                            <div x-show="showLang"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-1"
                                 class="mt-1 mx-1 rounded-xl overflow-hidden bg-[#111a24] border border-white/[0.06]"
                                 style="display:none;">
                                @foreach($localeNames as $code => $label)
                                <form method="POST" action="{{ route('language.change') }}">
                                    @csrf
                                    <input type="hidden" name="locale" value="{{ $code }}">
                                    <button type="submit"
                                            class="w-full flex items-center justify-between px-4 py-2 text-sm transition-all
                                                   {{ $currentLocale === $code
                                                       ? 'text-blue-400 bg-blue-500/10 font-semibold'
                                                       : 'text-on-surface-variant hover:bg-white/5 hover:text-white' }}">
                                        <span>{{ $label }}</span>
                                        @if($currentLocale === $code)
                                            <span class="material-symbols-outlined" style="font-size:16px;">check</span>
                                        @endif
                                    </button>
                                </form>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-white/5 transition-all cursor-default">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-on-surface-variant" style="font-size:20px;">palette</span>
                                <span class="text-sm text-on-surface-variant">{{ __('app.profile.theme') }}</span>
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
                                <span class="text-sm font-bold">{{ __('app.nav.logout') }}</span>
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
