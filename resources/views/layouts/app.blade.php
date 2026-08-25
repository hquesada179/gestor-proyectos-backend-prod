<!DOCTYPE html>
<html class="dark h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- ── Pre-render sidebar state — MUST run before first paint ─────────── --}}
    {{-- Prevents the flash/shift when the sidebar is in collapsed state:       --}}
    {{-- without this, the page renders at margin-left:280px then snaps to 72px --}}
    <script>
    (function(){
        try {
            var projectViewMode = localStorage.getItem('projectViewMode') || 'grid';
            document.cookie = 'project_view_mode=' + encodeURIComponent(projectViewMode) + ';path=/;max-age=31536000;SameSite=Lax';

            var _sc  = localStorage.getItem('sidebarCollapsed');
            var _mid = window.innerWidth >= 768 && window.innerWidth < 1200;
            // Collapse on load if: stored as true, OR medium screen with no explicit preference
            if (window.innerWidth >= 768 && (_sc === 'true' || (_sc === null && _mid))) {
                document.body.classList.add('sidebar-collapsed');
                var s = document.createElement('style');
                s.id = 'sb-pre';
                s.textContent =
                    '#sidebar{width:72px!important;transition:none!important}' +
                    '#mainContent{margin-left:72px!important;transition:none!important}';
                document.head.appendChild(s);
            }

            if (projectViewMode === 'list') {
                var pv = document.createElement('style');
                pv.id = 'pv-pre';
                pv.textContent =
                    '#projectsGrid{display:grid!important;position:absolute!important;left:-10000px!important;top:0!important;width:100%!important;visibility:hidden!important;pointer-events:none!important}' +
                    '#projectsList{display:flex!important;flex-direction:column!important;gap:.625rem!important}' +
                    'html body #projectsList.pv-view-hidden{display:flex!important;position:static!important;left:auto!important;top:auto!important;width:100%!important;visibility:visible!important;pointer-events:auto!important}';
                document.head.appendChild(pv);
            }
        } catch(e){}
    })();
    </script>

    <title>{{ config('app.name', 'Scrumter') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    {{-- ── Sidebar collapse system ────────────────────────────────────────── --}}
    <style>
        html { color-scheme: dark; overflow-x: hidden; }
        body { overflow-x: hidden; }

        .dark-form-input,
        .dark-form-select,
        .dark-form-textarea,
        .form-input,
        .ds-select,
        .sb-input,
        .sb-select,
        .my-task-input,
        input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="color"]):not([type="hidden"]),
        textarea,
        select {
            background-color: #111827 !important;
            color: #f8fafc !important;
            border: 1px solid #475569 !important;
            border-radius: 0.75rem;
            color-scheme: dark !important;
            caret-color: #f8fafc;
        }

        .dark-form-input::placeholder,
        .dark-form-textarea::placeholder,
        .form-input::placeholder,
        .sb-input::placeholder,
        .my-task-input::placeholder,
        input::placeholder,
        textarea::placeholder {
            color: #94a3b8 !important;
            opacity: 1;
        }

        .dark-form-input:focus,
        .dark-form-select:focus,
        .dark-form-textarea:focus,
        .form-input:focus,
        .ds-select:focus,
        .sb-input:focus,
        .sb-select:focus,
        .my-task-input:focus,
        input:focus,
        textarea:focus,
        select:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.35) !important;
        }

        .dark-form-select option,
        .dark-form-select optgroup,
        .form-input option,
        .form-input optgroup,
        .ds-select option,
        .ds-select optgroup,
        .sb-select option,
        .sb-select optgroup,
        .my-task-input option,
        .my-task-input optgroup,
        select option,
        select optgroup {
            background-color: #0f172a !important;
            color: #f8fafc !important;
        }

        .dark-form-select option:checked,
        .form-input option:checked,
        .ds-select option:checked,
        .sb-select option:checked,
        .my-task-input option:checked,
        select option:checked {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1) opacity(0.75);
        }

        /* Smooth transitions */
        #sidebar      { transition: width 280ms cubic-bezier(.4,0,.2,1); }
        #mainContent  { transition: margin-left 280ms cubic-bezier(.4,0,.2,1); }

        /* Text labels: fade + shrink when collapsed */
        .sb-text {
            overflow: hidden;
            white-space: nowrap;
            /* Delay opacity on EXPAND so text only appears when sidebar is almost fully open */
            transition: opacity 150ms ease 250ms, max-width 280ms cubic-bezier(.4,0,.2,1);
            max-width: 200px;
            opacity: 1;
            display: inline-block;
            vertical-align: middle;
        }
        .sidebar-collapsed .sb-text {
            opacity: 0;
            max-width: 0;
            /* Fast fade-out on COLLAPSE — no delay needed */
            transition: opacity 80ms ease, max-width 240ms cubic-bezier(.4,0,.2,1);
            pointer-events: none;
            user-select: none;
        }

        /* Section labels — display toggle, sin animación de max-height ni opacidad */
        .sb-section-label {
            display: block;
            visibility: visible;
            opacity: 1;
            max-height: none;
            overflow: visible;
            white-space: nowrap;
        }
        /* Sidebar expandido: labels completamente visibles */
        body:not(.sidebar-collapsed) .sb-section-label {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            max-height: none !important;
            overflow: visible !important;
            white-space: nowrap !important;
        }
        /* Sidebar colapsado: labels completamente ocultos, sin rayas ni intermedios */
        body.sidebar-collapsed .sb-section-label {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        /* Separadores también ocultos cuando colapsado (evita líneas finas visibles) */
        body.sidebar-collapsed .sb-separator {
            display: none !important;
        }

        /* Nav link: center icon when collapsed */
        .sidebar-collapsed .sb-nav-link {
            justify-content: center !important;
            padding-left:  6px !important;
            padding-right: 6px !important;
            gap: 0 !important;
        }
        /* Bottom profile/logout links */
        .sidebar-collapsed .sb-bottom-link {
            justify-content: center !important;
            padding-left:  6px !important;
            padding-right: 6px !important;
            gap: 0 !important;
        }
        /* Logo area */
        .sidebar-collapsed .sb-logo-link {
            justify-content: center !important;
            gap: 0 !important;
        }
        .sidebar-collapsed .sb-logo-wrap {
            padding-left:  0 !important;
            padding-right: 0 !important;
        }
        /* CTA new project button */
        .sidebar-collapsed .sb-new-btn {
            padding-left:  6px !important;
            padding-right: 6px !important;
            justify-content: center !important;
            gap: 0 !important;
        }
        /* Hide bottom border when collapsed */
        .sidebar-collapsed .sb-bottom-wrap {
            padding-left:  6px !important;
            padding-right: 6px !important;
        }

        /* ── Toggle button ─────────────────────────────────────────────── */
        #sidebarToggle {
            position: absolute;
            right: -12px;
            top: 76px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #0d1c2d;
            border: 1px solid rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 55;
            transition: background .15s, box-shadow .15s;
            flex-shrink: 0;
        }
        #sidebarToggle:hover {
            background: #1c2b3c;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        #sbToggleIcon {
            font-size: 15px;
            color: #94a3b8;
            transition: transform 280ms cubic-bezier(.4,0,.2,1);
            line-height: 1;
            display: block;
        }
        .sidebar-collapsed #sbToggleIcon { transform: rotate(180deg); }

        /* ── Tooltip for collapsed icons ───────────────────────────────── */
        .sidebar-collapsed .sb-nav-link,
        .sidebar-collapsed .sb-bottom-link {
            position: relative;
        }
        .sidebar-collapsed .sb-nav-link::after,
        .sidebar-collapsed .sb-bottom-link::after {
            content: attr(data-tip);
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
            background: rgba(10,15,25,0.97);
            color: #e2e8f0;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 200;
            opacity: 0;
            pointer-events: none;
            transition: opacity 120ms;
        }
        .sidebar-collapsed .sb-nav-link:hover::after,
        .sidebar-collapsed .sb-bottom-link:hover::after {
            opacity: 1;
        }

        /* ── Mobile: hide toggle button, keep sidebar full-width ───────── */
        @media (max-width: 767px) {
            #sidebarToggle { display: none; }
        }

        /* ── Mobile overlay ────────────────────────────────────────────── */
        #sidebarOverlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            z-index: 48;
            display: none;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }
        #sidebarOverlay.active { display: block; }

        /* ── Mobile sidebar as slide-in drawer ─────────────────────────── */
        @media (max-width: 767px) {
            #sidebar {
                width: 280px !important;
                transform: translateX(-100%);
                transition: transform 280ms cubic-bezier(.4,0,.2,1) !important;
            }
            #sidebar.mobile-open {
                transform: translateX(0) !important;
                box-shadow: 4px 0 40px rgba(0,0,0,0.6) !important;
            }
            /* No collapsed state on mobile */
            body.sidebar-collapsed #sidebar { width: 280px !important; }
            body.sidebar-collapsed .sb-text  { opacity:1; max-width:180px; }
            body.sidebar-collapsed .sb-section-label { opacity:1; max-height:40px; }
            body.sidebar-collapsed .sb-nav-link  { justify-content:flex-start !important; padding-left:16px !important; padding-right:16px !important; gap:12px !important; }
            body.sidebar-collapsed .sb-bottom-link { justify-content:flex-start !important; padding-left:16px !important; padding-right:16px !important; gap:12px !important; }
            body.sidebar-collapsed .sb-logo-link { justify-content:flex-start !important; gap:12px !important; }
            body.sidebar-collapsed .sb-new-btn { padding-left:16px !important; padding-right:16px !important; justify-content:flex-start !important; gap:8px !important; }
            /* Main content full width on mobile */
            #mainContent {
                margin-left: 0 !important;
                transition: none !important;
                width: 100% !important;
            }
            /* Responsive header padding */
            #topHeader { padding-left: 1rem !important; padding-right: 1rem !important; gap: 0.5rem !important; }
            /* Dropdowns safe width on mobile */
            .header-dropdown-panel { max-width: calc(100vw - 0.5rem) !important; }
        }

        /* ── Hamburger button (only on mobile) ─────────────────────────── */
        #mobileMenuBtn { display: none; }
        @media (max-width: 767px) { #mobileMenuBtn { display: flex; } }

        /* ── Global responsive: content & tables ─────────────────────── */
        @media (max-width: 767px) {
            /* Panel horizontal scroll safety (tables inside glass-panel) */
            .glass-panel { overflow-x: auto !important; }
            /* Kanban / horizontal boards */
            .kanban-board, [class*="kanban"] { overflow-x: auto; }
            /* Content area: reduce padding from p-8 to 1rem on mobile */
            main > div:first-child {
                padding-left:  1rem !important;
                padding-right: 1rem !important;
                padding-top:   1rem !important;
            }
            /* Grids: force 1 column on very small screens */
            main .grid-cols-3,
            main .xl\:grid-cols-3 { grid-template-columns: 1fr !important; }
            main .grid-cols-2     { grid-template-columns: 1fr 1fr !important; }
        }
    </style>
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

    {{-- Mobile overlay (tap to close sidebar) --}}
    <div id="sidebarOverlay" onclick="mobileSidebarClose()" role="button" aria-label="Cerrar menú"></div>

    {{-- ─── SIDEBAR ───────────────────────────────────────────────────── --}}
    <aside id="sidebar"
           style="width:280px; position:fixed; left:0; top:0; height:100%; z-index:50;"
           class="bg-white/5 backdrop-blur-2xl border-r border-white/5
                  flex flex-col py-6 shadow-2xl shadow-black/50">

        {{-- Toggle button (positioned on right edge) --}}
        <button id="sidebarToggle" onclick="sidebarToggle()" title="Colapsar/Expandir"
                aria-label="Colapsar menú lateral"
                aria-expanded="true"
                aria-controls="sidebar">
            <span class="material-symbols-outlined" id="sbToggleIcon">chevron_left</span>
        </button>

        {{-- ── Logo ────────────────────────────────────────────────────── --}}
        <div class="sb-logo-wrap px-6 mb-6">
            <a href="{{ route('dashboard') }}" class="sb-logo-link flex items-center gap-3 group">
                <img src="{{ asset('brand/scrumter-logo.png') }}"
                     alt="Scrumter"
                     class="flex-shrink-0 object-contain"
                     style="width:32px;height:32px;border-radius:6px;">
                <span class="sb-text text-base font-black text-white tracking-tight">
                    Scrumter
                </span>
            </a>
        </div>

        {{-- ── Primary navigation ───────────────────────────────────────── --}}
        <nav class="flex-1 flex flex-col gap-0.5 px-3 overflow-y-auto">

            @php
                $ruta           = request()->route()?->getName() ?? '';
                $isDashboard    = $ruta === 'dashboard';
                $isReqs         = str_starts_with($ruta, 'proyectos.requirements');
                $isSprints      = str_starts_with($ruta, 'proyectos.sprints');
                $isTareas       = str_starts_with($ruta, 'proyectos.tasks');
                $isInputs       = str_starts_with($ruta, 'proyectos.inputs');
                $isProyectos    = str_starts_with($ruta, 'proyectos.') && !$isReqs && !$isSprints && !$isTareas && !$isInputs;
                $isMisTareas    = str_starts_with($ruta, 'mis-tareas');
                $isScrumBoard   = str_starts_with($ruta, 'scrum-board');
                $isCalendario   = $ruta === 'calendario.index';
                $isAiAssist     = str_starts_with($ruta, 'asistente-ia');
                $isTeam         = str_starts_with($ruta, 'team');
                $isRoles        = str_starts_with($ruta, 'roles');
                $isInvitations  = str_starts_with($ruta, 'invitations');
                $isPlanes       = $ruta === 'planes.index';
                $sbInvCount     = \App\Models\ProjectInvitation::where('invited_user_id', Auth::id())->where('status','pending')->count();
            @endphp

            <a href="{{ route('dashboard') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isDashboard ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="{{ __('app.nav.dashboard') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">dashboard</span>
                <span class="sb-text">{{ __('app.nav.dashboard') }}</span>
            </a>

            <a href="{{ route('proyectos.index') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isProyectos ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="{{ __('app.nav.projects') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">folder_open</span>
                <span class="sb-text">{{ __('app.nav.projects') }}</span>
            </a>

            <a href="{{ route('mis-tareas.index') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isMisTareas ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="{{ __('app.nav.my_tasks') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">task_alt</span>
                <span class="sb-text">{{ __('app.nav.my_tasks') }}</span>
            </a>

            <div class="sb-separator my-3 mx-1 border-t border-white/5"></div>

            <p class="sb-section-label px-4 py-1 text-[10px] uppercase tracking-widest text-gray-600 font-semibold select-none">
                {{ __('app.nav.modules') }}
            </p>

            <a href="{{ route('modulo.selector', 'requerimientos') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isReqs ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="{{ __('app.nav.requirements') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">edit_note</span>
                <span class="sb-text">{{ __('app.nav.requirements') }}</span>
            </a>

            <a href="{{ route('scrum-board.index') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isScrumBoard ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="{{ __('app.nav.scrum_board') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">view_kanban</span>
                <span class="sb-text">{{ __('app.nav.scrum_board') }}</span>
            </a>

            <a href="{{ route('modulo.selector', 'sprints') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isSprints ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="{{ __('app.nav.sprints') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">sprint</span>
                <span class="sb-text">{{ __('app.nav.sprints') }}</span>
            </a>

            <a href="{{ route('modulo.selector', 'tareas') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isTareas ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="{{ __('app.nav.tasks_board') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">checklist</span>
                <span class="sb-text">{{ __('app.nav.tasks_board') }}</span>
            </a>

            <a href="{{ route('modulo.selector', 'insumos') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium nav-link-inactive hover:translate-x-0.5 transition-all duration-150"
               data-tip="{{ __('app.nav.inputs') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">inventory_2</span>
                <span class="sb-text">{{ __('app.nav.inputs') }}</span>
            </a>

            <a href="{{ route('calendario.index') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isCalendario ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="{{ __('app.nav.calendar') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">calendar_month</span>
                <span class="sb-text">{{ __('app.nav.calendar') }}</span>
            </a>

            <div class="sb-separator my-3 mx-1 border-t border-white/5"></div>

            <p class="sb-section-label px-4 py-1 text-[10px] uppercase tracking-widest text-gray-600 font-semibold select-none">
                {{ __('app.nav.ai_section') }}
            </p>

            <a href="{{ route('asistente-ia.index') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isAiAssist ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="{{ __('app.nav.ai_assistant') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;font-variation-settings:'FILL' 1;">auto_awesome</span>
                <span class="sb-text">{{ __('app.nav.ai_assistant') }}</span>
            </a>

            <a href="{{ route('planes.index') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isPlanes ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="Planes">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">workspace_premium</span>
                <span class="sb-text">Planes</span>
            </a>

            <div class="sb-separator my-3 mx-1 border-t border-white/5"></div>

            <p class="sb-section-label px-4 py-1 text-[10px] uppercase tracking-widest text-gray-600 font-semibold select-none">
                Equipo
            </p>

            <a href="{{ route('team.index') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isTeam ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="Equipo">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">group</span>
                <span class="sb-text">Equipo</span>
            </a>

            <a href="{{ route('roles.index') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isRoles ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="Roles y Permisos">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">admin_panel_settings</span>
                <span class="sb-text">Roles y Permisos</span>
            </a>

            <a href="{{ route('invitations.index') }}"
               class="sb-nav-link flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ $isInvitations ? 'nav-link-active' : 'nav-link-inactive hover:translate-x-0.5' }}"
               data-tip="Mis Invitaciones">
                <span class="relative">
                    <span class="material-symbols-outlined flex-shrink-0" style="font-size:20px;">mail</span>
                    @if($sbInvCount > 0)
                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-red-500 text-white text-[8px] font-black rounded-full flex items-center justify-center border border-[#0d1c2d] leading-none">
                        {{ min($sbInvCount, 9) }}
                    </span>
                    @endif
                </span>
                <span class="sb-text">
                    Mis Invitaciones
                    @if($sbInvCount > 0)
                    <span style="margin-left:4px; padding:1px 6px; border-radius:999px; background:rgba(239,68,68,.15); color:#f87171; font-size:10px; font-weight:700; border:1px solid rgba(239,68,68,.25);">
                        {{ $sbInvCount }}
                    </span>
                    @endif
                </span>
            </a>

        </nav>

        {{-- ── Bottom: CTA + user actions ──────────────────────────────── --}}
        <div class="sb-bottom-wrap px-4 mt-4 space-y-1">
            <a href="{{ route('proyectos.create') }}"
               class="sb-new-btn w-full py-2.5 px-4 bg-secondary-container text-white rounded-xl font-bold text-sm
                      flex items-center justify-center gap-2 hover:opacity-90 transition-all active:scale-95 mb-1"
               title="{{ __('app.actions.new_project') }}">
                <span class="material-symbols-outlined flex-shrink-0" style="font-size:16px;">add</span>
                <span class="sb-text">{{ __('app.actions.new_project') }}</span>
            </a>

            <div class="pt-3 border-t border-white/5 space-y-0.5">
                <a href="{{ route('profile.edit') }}"
                   class="sb-bottom-link flex items-center gap-3 px-4 py-2 rounded-lg text-sm nav-link-inactive hover:translate-x-0.5 transition-all duration-150"
                   data-tip="{{ __('app.nav.profile') }}">
                    <span class="material-symbols-outlined flex-shrink-0" style="font-size:18px;">manage_accounts</span>
                    <span class="sb-text">{{ __('app.nav.profile') }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="sb-bottom-link w-full flex items-center gap-3 px-4 py-2 rounded-lg text-sm nav-link-inactive hover:translate-x-0.5 transition-all duration-150 text-left"
                            data-tip="{{ __('app.nav.logout') }}">
                        <span class="material-symbols-outlined flex-shrink-0" style="font-size:18px;">logout</span>
                        <span class="sb-text">{{ __('app.nav.logout') }}</span>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- ─── MAIN CONTENT AREA ─────────────────────────────────────────── --}}
    <div id="mainContent"
         style="margin-left:280px;"
         class="flex-1 min-w-0 flex flex-col h-full overflow-hidden">

        {{-- Topbar --}}
        <header id="topHeader"
                class="flex-shrink-0 h-16 bg-[#0F1117]/80 backdrop-blur-xl border-b border-white/10 z-40
                       flex items-center justify-between px-8 gap-4">

            {{-- Mobile hamburger (hidden on desktop via CSS) --}}
            <button id="mobileMenuBtn"
                    onclick="mobileSidebarToggle()"
                    aria-label="Abrir menú"
                    aria-expanded="false"
                    aria-controls="sidebar"
                    class="items-center justify-center w-9 h-9 rounded-xl hover:bg-white/5 transition-all flex-shrink-0">
                <span class="material-symbols-outlined text-on-surface-variant" style="font-size:22px;">menu</span>
            </button>

            <div class="flex items-center gap-3 min-w-0 flex-1 overflow-hidden">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>

            {{-- ─── NOTIFICATION BELL ───────────────────────────────── --}}
            @php
                $pendingInvitationCount = \App\Models\ProjectInvitation::where('invited_user_id', Auth::id())
                    ->where('status', 'pending')
                    ->count();
                $pendingInvitations = $pendingInvitationCount > 0
                    ? \App\Models\ProjectInvitation::where('invited_user_id', Auth::id())
                        ->where('status', 'pending')
                        ->with(['proyecto', 'invitedBy', 'role'])
                        ->latest()
                        ->get()
                    : collect();
            @endphp

            <div class="relative flex-shrink-0"
                 x-data="{ bellOpen: false }"
                 @click.outside="bellOpen = false"
                 @keydown.escape.window="bellOpen = false">

                {{-- Bell button --}}
                <button @click="bellOpen = !bellOpen"
                        aria-label="Notificaciones"
                        aria-haspopup="true"
                        :aria-expanded="bellOpen.toString()"
                        class="relative flex items-center justify-center w-9 h-9 rounded-xl hover:bg-white/5 transition-all duration-150 outline-none focus:ring-2 focus:ring-blue-500/40"
                        title="Invitaciones">
                    <span class="material-symbols-outlined text-on-surface-variant" style="font-size:20px;">notifications</span>
                    @if($pendingInvitationCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center
                                 bg-red-500 text-white text-[10px] font-black rounded-full border-2 border-[#0F1117]
                                 leading-none">
                        {{ $pendingInvitationCount > 9 ? '9+' : $pendingInvitationCount }}
                    </span>
                    @endif
                </button>

                {{-- Bell dropdown --}}
                <div x-show="bellOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                     class="header-dropdown-panel absolute right-0 top-full mt-3 w-[380px] z-[60] rounded-2xl overflow-hidden
                            bg-[#1c2b3c] border border-white/10 shadow-2xl shadow-black/60"
                     style="display:none; box-shadow: 0 0 30px rgba(99,102,241,0.12), 0 25px 50px rgba(0,0,0,0.5);">

                    <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-400" style="font-size:18px;">mail</span>
                            <h3 class="text-sm font-bold text-white">Invitaciones</h3>
                            @if($pendingInvitationCount > 0)
                            <span class="px-2 py-0.5 bg-indigo-500/20 text-indigo-400 text-[10px] font-bold rounded-full border border-indigo-500/30">
                                {{ $pendingInvitationCount }} pendiente{{ $pendingInvitationCount !== 1 ? 's' : '' }}
                            </span>
                            @endif
                        </div>
                        <a href="{{ route('invitations.index') }}"
                           class="text-[11px] text-indigo-400 hover:text-indigo-300 transition-colors font-medium">
                            Ver todas
                        </a>
                    </div>

                    @if($pendingInvitations->isEmpty())
                    <div class="px-5 py-8 text-center">
                        <span class="material-symbols-outlined text-gray-600 block mb-2" style="font-size:32px;">notifications_off</span>
                        <p class="text-[12px] text-gray-500">Sin invitaciones pendientes</p>
                    </div>
                    @else
                    <div class="max-h-[360px] overflow-y-auto">
                        @foreach($pendingInvitations as $inv)
                        <div class="px-4 py-4 border-b border-white/[0.04] last:border-0 hover:bg-white/[0.02] transition-all">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-600 to-purple-600
                                            flex items-center justify-center text-white text-xs font-black flex-shrink-0">
                                    {{ strtoupper(mb_substr($inv->proyecto->nombre ?? '?', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] font-semibold text-white truncate">{{ $inv->proyecto->nombre ?? '—' }}</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">
                                        Invitado por <span class="text-gray-300">{{ $inv->invitedBy->name ?? '—' }}</span>
                                    </p>
                                    @if($inv->role)
                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-md
                                                 bg-indigo-500/10 border border-indigo-500/20 text-[10px] text-indigo-400 font-semibold">
                                        {{ $inv->role->name }}
                                    </span>
                                    @endif
                                    <p class="text-[10px] text-gray-600 mt-1">{{ $inv->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 ml-12">
                                <form method="POST" action="{{ route('invitations.accept', $inv) }}" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                            class="w-full py-1.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30
                                                   text-emerald-400 text-[11px] font-bold transition-all">
                                        Aceptar
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('invitations.reject', $inv) }}" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                            class="w-full py-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 border border-red-500/20
                                                   text-red-400 text-[11px] font-bold transition-all">
                                        Rechazar
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                </div>
            </div>
            {{-- ─────────────────────────────────────────────────────────── --}}

            @include('layouts.project-chat-panel')

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
                    {{-- Small avatar: photo or initial --}}
                    @if(Auth::user()->profile_photo_path)
                        <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}"
                             alt="{{ Auth::user()->name }}"
                             class="w-9 h-9 rounded-full object-cover border border-white/10 flex-shrink-0
                                    ring-2 ring-transparent group-hover:ring-blue-500/50 transition-all duration-150">
                    @else
                        <div class="w-9 h-9 rounded-full bg-secondary-container flex items-center justify-center
                                    text-white text-sm font-black border border-white/10 flex-shrink-0 select-none
                                    ring-2 ring-transparent group-hover:ring-blue-500/50 transition-all duration-150">
                            {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    @endif
                </button>

                {{-- Dropdown panel --}}
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                     class="header-dropdown-panel absolute right-0 top-full mt-3 w-[360px] z-[60] rounded-2xl overflow-hidden
                            bg-[#1c2b3c] border border-white/10 shadow-2xl shadow-black/60"
                     style="display:none; box-shadow: 0 0 30px rgba(167,75,254,0.12), 0 25px 50px rgba(0,0,0,0.5);">

                    {{-- ── Cabecera de perfil ─────────────────────── --}}
                    <div class="px-6 py-5 border-b border-white/5 flex flex-col items-center text-center">
                        <div class="relative mb-3">
                            {{-- Large avatar: photo or initial --}}
                            @if(Auth::user()->profile_photo_path)
                                <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}"
                                     alt="{{ Auth::user()->name }}"
                                     class="w-20 h-20 rounded-full object-cover border-2 border-blue-500/40 select-none">
                            @else
                                <div class="w-20 h-20 rounded-full bg-secondary-container flex items-center justify-center
                                            text-white text-3xl font-black border-2 border-blue-500/40 select-none">
                                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="absolute bottom-0.5 right-0.5 w-4 h-4 bg-emerald-500 border-4 border-[#1c2b3c] rounded-full"></div>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-0.5">{{ Auth::user()->name }}</h3>
                        <p class="text-xs text-on-surface-variant">{{ Auth::user()->email }}</p>
                        <span class="mt-3 px-3 py-1 bg-blue-500/10 text-blue-400 text-[10px] uppercase font-bold tracking-widest rounded-full border border-blue-500/20">
                            {{ __('app.profile.active') }}
                        </span>
                    </div>

                    {{-- ── Cambiar foto (formulario real) ────────────────── --}}
                    <div class="px-4 py-3 border-b border-white/5">
                        <form method="POST"
                              action="{{ route('profile.photo.update') }}"
                              enctype="multipart/form-data"
                              id="profile-photo-form">
                            @csrf
                            <input id="profile_photo_input"
                                   type="file"
                                   name="profile_photo"
                                   accept="image/jpeg,image/jpg,image/png,image/webp"
                                   class="hidden"
                                   onchange="document.getElementById('profile-photo-form').submit()">
                            <button type="button"
                                    onclick="document.getElementById('profile_photo_input').click()"
                                    class="w-full flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-all text-left cursor-pointer">
                                <div class="w-10 h-10 rounded-lg bg-[#273647] flex items-center justify-center text-on-surface-variant flex-shrink-0">
                                    <span class="material-symbols-outlined" style="font-size:20px;">upload_file</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-white">{{ __('app.profile.change_photo') }}</p>
                                    <p class="text-[11px] text-on-surface-variant">{{ __('app.profile.upload_hint') }}</p>
                                </div>
                            </button>
                        </form>
                        {{-- Error de validación --}}
                        @error('profile_photo')
                            <p class="mt-1 px-3 text-[11px] text-red-400 flex items-center gap-1">
                                <span class="material-symbols-outlined" style="font-size:13px">error</span>
                                {{ $message }}
                            </p>
                        @enderror
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
                    </div>

                    {{-- ── Preferencias visuales ──────────────────── --}}
                    <div class="px-3 py-3 space-y-1 border-b border-white/5">
                        <div class="flex items-center justify-between px-3 py-1.5 cursor-default select-none">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-blue-400" style="font-size:20px;">dark_mode</span>
                                <span class="text-sm text-on-surface-variant">{{ __('app.profile.dark_mode') }}</span>
                            </div>
                            <span class="px-2 py-0.5 bg-blue-500/10 text-blue-400 text-[10px] font-bold rounded-full border border-blue-500/20">{{ __('app.profile.active') }}</span>
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

        {{-- Flash: profile photo success --}}
        @if(session('profile_photo_success'))
        <div id="flash-photo"
             style="background:rgba(16,185,129,0.12); border-bottom:1px solid rgba(16,185,129,0.2);
                    padding:10px 32px; display:flex; align-items:center; gap:8px; flex-shrink:0;">
            <span class="material-symbols-outlined" style="font-size:16px;color:#34d399;">check_circle</span>
            <span style="font-size:13px;color:#34d399;flex:1;">{{ session('profile_photo_success') }}</span>
            <button onclick="document.getElementById('flash-photo').remove()"
                    aria-label="Cerrar aviso"
                    style="background:none;border:none;cursor:pointer;color:#64748b;display:flex;align-items:center;">
                <span class="material-symbols-outlined" style="font-size:16px;">close</span>
            </button>
        </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 min-w-0 overflow-y-auto overflow-x-hidden">
            {{ $slot }}
        </main>

    </div>

</div>

{{-- ── Sidebar toggle script (runs on every page) ───────────────────────── --}}
<script>
(function () {
    'use strict';

    var LS_KEY      = 'sidebarCollapsed';
    var FULL_WIDTH  = 280;
    var MINI_WIDTH  = 72;
    var sidebar     = document.getElementById('sidebar');
    var mainContent = document.getElementById('mainContent');
    var toggleIcon  = document.getElementById('sbToggleIcon');
    var overlay     = document.getElementById('sidebarOverlay');

    function isMobile() { return window.innerWidth < 768; }

    // ── Desktop: width-based collapse ────────────────────────────────────
    var toggleBtn = document.getElementById('sidebarToggle');

    function syncToggleA11y(collapsed) {
        if (!toggleBtn) return;
        toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggleBtn.setAttribute('aria-label', collapsed ? 'Expandir menú lateral' : 'Colapsar menú lateral');
    }

    function applyState(collapsed) {
        if (isMobile()) return;
        var w = collapsed ? MINI_WIDTH : FULL_WIDTH;
        if (sidebar)     sidebar.style.width        = w + 'px';
        if (mainContent) mainContent.style.marginLeft = w + 'px';
        if (collapsed)  { document.body.classList.add('sidebar-collapsed'); }
        else            { document.body.classList.remove('sidebar-collapsed'); }
        if (toggleIcon) { toggleIcon.textContent = collapsed ? 'chevron_right' : 'chevron_left'; }
        syncToggleA11y(collapsed);
    }

    window.sidebarToggle = function () {
        if (isMobile()) { mobileSidebarToggle(); return; }
        var next = !document.body.classList.contains('sidebar-collapsed');
        applyState(next);
        try { localStorage.setItem(LS_KEY, next ? 'true' : 'false'); } catch (e) {}
    };

    // ── Mobile: slide-in drawer ───────────────────────────────────────────
    var mobileBtn = document.getElementById('mobileMenuBtn');

    function syncMobileA11y(open) {
        if (mobileBtn) mobileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    window.mobileSidebarToggle = function () {
        if (!sidebar) return;
        if (sidebar.classList.contains('mobile-open')) {
            mobileSidebarClose();
        } else {
            sidebar.classList.add('mobile-open');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            syncMobileA11y(true);
        }
    };

    window.mobileSidebarClose = function () {
        if (!sidebar) return;
        sidebar.classList.remove('mobile-open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
        syncMobileA11y(false);
    };

    // Close mobile sidebar when a nav link is clicked
    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { if (isMobile()) mobileSidebarClose(); });
        });
    }

    // Close mobile sidebar on resize to desktop
    window.addEventListener('resize', function () {
        if (!isMobile()) mobileSidebarClose();
    });

    // ── Initialization ────────────────────────────────────────────────────
    var saved = null;
    try { saved = localStorage.getItem(LS_KEY); } catch (e) {}

    // Medium screen (768-1199px): collapse by default unless user explicitly expanded
    var isMediumScreen = !isMobile() && window.innerWidth < 1200;
    var shouldCollapse  = !isMobile() && (saved === 'true' || (saved === null && isMediumScreen));

    if (shouldCollapse) {
        if (sidebar)     sidebar.style.transition    = 'none';
        if (mainContent) mainContent.style.transition = 'none';
        applyState(true);
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                if (sidebar)     sidebar.style.transition    = '';
                if (mainContent) mainContent.style.transition = '';
                var pre = document.getElementById('sb-pre');
                if (pre) pre.parentNode.removeChild(pre);
            });
        });
    } else {
        var pre = document.getElementById('sb-pre');
        if (pre) pre.parentNode.removeChild(pre);
    }

    function warmProjectCoverImages() {
        document.querySelectorAll('img[data-project-cover="true"][data-priority-cover="true"]').forEach(function (img) {
            if (img.decode && (!img.complete || !img.naturalWidth)) {
                img.decode().catch(function () {});
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', warmProjectCoverImages, { once: true });
    } else {
        warmProjectCoverImages();
    }
})();
</script>

@stack('scripts')

{{-- ── Chat Panel Alpine.js component ──────────────────────────────────── --}}
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
<script>
function chatPanel() {
    return {
        open:       false,
        view:       'projects',  // 'projects' | 'chat'
        projects:   [],
        selProject: null,
        messages:   [],
        newMsg:     '',
        search:     '',
        loading:    false,
        sending:    false,
        errMsg:     '',
        currentUserId: null,
        conversationMode: 'general',
        selectedReceiverId: null,
        _poll:      null,
        _urlId:     null,
        routes:     {
            projects:    '/mensajes/proyectos',
            projectBase: '/mensajes/proyectos',
        },

        get filteredProjects() {
            if (!this.search.trim()) return this.projects;
            var q = this.search.toLowerCase();
            return this.projects.filter(function(p){ return p.nombre.toLowerCase().indexOf(q) !== -1; });
        },

        get privateMembers() {
            if (!this.selProject || !Array.isArray(this.selProject.members)) return [];
            var current = parseInt(this.currentUserId || 0);
            return this.selProject.members.filter(function(member) {
                return parseInt(member.id) !== current;
            });
        },

        get selectedPrivateMember() {
            if (!this.selectedReceiverId) return null;
            var receiverId = parseInt(this.selectedReceiverId);
            return this.privateMembers.find(function(member) {
                return parseInt(member.id) === receiverId;
            }) || null;
        },

        init() {
            if (this.$el && this.$el.dataset) {
                this.routes.projects = this.$el.dataset.projectsUrl || this.routes.projects;
                this.routes.projectBase = this.$el.dataset.projectBaseUrl || this.routes.projectBase;
                this.currentUserId = parseInt(this.$el.dataset.currentUserId || '0');
            }

            // Auto-detect project ID from current URL
            var m = window.location.pathname.match(
                /(?:\/equipo\/|\/scrum-board\/|\/proyectos\/)(\d+)/
            );
            this._urlId = m ? parseInt(m[1]) : null;
        },

        async toggleOpen() {
            this.open = !this.open;
            if (!this.open) { this.stopPoll(); return; }
            if (!this.projects.length) await this.fetchProjects();
            // Auto-open project chat if user is on a project page
            if (this._urlId && this.projects.length) {
                var p = this.projects.find(function(x){ return x.id === this._urlId; }.bind(this));
                if (p) { await this.openChat(p); }
            }
        },

        async fetchProjects() {
            this.loading = true;
            this.errMsg = '';
            try {
                var r = await fetch(this.routes.projects, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!r.ok) throw new Error('projects');
                this.projects = await r.json();
            } catch(e) {
                this.errMsg = 'No se pudieron cargar los proyectos.';
            }
            this.loading = false;
        },

        async openChat(project) {
            this.selProject = project;
            this.view = 'chat';
            this.conversationMode = 'general';
            this.selectedReceiverId = null;
            this.messages = [];
            this.errMsg = '';
            this.loading = true;
            await this.fetchMessages();
            this.loading = false;
            this.$nextTick(function(){ this.scrollBottom(); }.bind(this));
            this.startPoll();
        },

        backToList() {
            this.stopPoll();
            this.view = 'projects';
            this.selProject = null;
            this.messages = [];
            this.errMsg = '';
            this.search = '';
            this.conversationMode = 'general';
            this.selectedReceiverId = null;
        },

        async switchConversation(mode) {
            if (this.conversationMode === mode) return;

            this.stopPoll();
            this.conversationMode = mode;
            this.messages = [];
            this.errMsg = '';
            this.newMsg = '';

            if (mode === 'general') {
                this.selectedReceiverId = null;
                this.loading = true;
                await this.fetchMessages();
                this.loading = false;
                this.$nextTick(function(){ this.scrollBottom(); }.bind(this));
                this.startPoll();
            }
        },

        async selectPrivateMember() {
            this.stopPoll();
            this.messages = [];
            this.errMsg = '';

            if (!this.selectedReceiverId) return;

            this.loading = true;
            await this.fetchMessages();
            this.loading = false;
            this.$nextTick(function(){ this.scrollBottom(); }.bind(this));
            this.startPoll();
        },

        async fetchMessages() {
            if (!this.selProject) return;
            if (this.conversationMode === 'private' && !this.selectedReceiverId) return;
            try {
                var r = await fetch(this.messagesUrl(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (r.status === 403) {
                    this.errMsg = 'No tienes acceso a este chat.';
                    this.messages = [];
                    return;
                }
                if (!r.ok) throw new Error('messages');
                this.messages = await r.json();
            } catch(e) {
                this.errMsg = 'No se pudieron cargar los mensajes.';
            }
        },

        async pollMessages() {
            if (!this.selProject) return;
            if (this.conversationMode === 'private' && !this.selectedReceiverId) return;
            var lastId = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
            var url    = this.messagesUrl(lastId);
            try {
                var r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (r.ok) {
                    var newMsgs = await r.json();
                    if (newMsgs.length) {
                        var wasBottom = this._isBottom();
                        this.messages = this.messages.concat(newMsgs);
                        if (wasBottom) this.$nextTick(function(){ this.scrollBottom(); }.bind(this));
                    }
                }
            } catch(e) {}
        },

        async sendMsg() {
            var txt = this.newMsg.trim();
            if (!txt || this.sending) return;
            if (this.conversationMode === 'private' && !this.selectedReceiverId) {
                this.errMsg = 'Selecciona un integrante para el chat privado.';
                return;
            }
            this.sending = true;
            this.errMsg  = '';
            var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
            try {
                var r = await fetch(this.routes.projectBase + '/' + this.selProject.id, {
                    method: 'POST',
                    headers: {
                        'Content-Type':    'application/json',
                        'X-CSRF-TOKEN':    csrf,
                        'X-Requested-With':'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        message: txt,
                        type: this.conversationMode,
                        receiver_id: this.conversationMode === 'private' ? this.selectedReceiverId : null,
                    }),
                });
                if (r.ok) {
                    this.messages.push(await r.json());
                    this.newMsg = '';
                    this.$nextTick(function(){ this.scrollBottom(); }.bind(this));
                } else {
                    var d = {};
                    try { d = await r.json(); } catch(e) {}
                    this.errMsg = (d.errors && d.errors.message && d.errors.message[0])
                        ? d.errors.message[0]
                        : 'Error al enviar el mensaje.';
                }
            } catch(e) {
                this.errMsg = 'Error de conexión.';
            }
            this.sending = false;
        },

        messagesUrl(afterId) {
            var params = new URLSearchParams();
            params.set('type', this.conversationMode);

            if (this.conversationMode === 'private') {
                params.set('receiver_id', this.selectedReceiverId || '');
            }

            if (afterId) {
                params.set('after', afterId);
            }

            return this.routes.projectBase + '/' + this.selProject.id + '?' + params.toString();
        },

        chatSubtitle() {
            if (this.view !== 'chat' || !this.selProject) return 'Mensajes por proyecto';
            if (this.conversationMode === 'private') {
                return this.selectedPrivateMember
                    ? 'Privado con ' + this.selectedPrivateMember.name
                    : 'Selecciona un integrante';
            }
            return 'Chat general del proyecto';
        },

        emptyTitle() {
            if (this.conversationMode === 'private' && !this.selectedReceiverId) {
                return 'Selecciona un integrante';
            }
            return 'Aun no hay mensajes';
        },

        emptyText() {
            if (this.conversationMode === 'private' && !this.selectedReceiverId) {
                return 'Elige una persona activa del proyecto para abrir una conversación privada.';
            }
            return this.conversationMode === 'private'
                ? 'Escribe el primer mensaje privado.'
                : 'Escribe el primer mensaje para este proyecto.';
        },

        inputPlaceholder() {
            if (this.conversationMode === 'private' && !this.selectedReceiverId) {
                return 'Selecciona un integrante...';
            }
            return this.conversationMode === 'private'
                ? 'Escribe un mensaje privado...'
                : 'Escribe un mensaje...';
        },

        startPoll() {
            this.stopPoll();
            var self = this;
            this._poll = setInterval(function(){ self.pollMessages(); }, 5000);
        },

        stopPoll() {
            if (this._poll) { clearInterval(this._poll); this._poll = null; }
        },

        scrollBottom() {
            var el = this.$refs.msgBody;
            if (el) el.scrollTop = el.scrollHeight;
        },

        _isBottom() {
            var el = this.$refs.msgBody;
            return el ? el.scrollTop >= el.scrollHeight - el.clientHeight - 60 : true;
        },

        avBg(uid) {
            var p = [
                '#6d28d9,#818cf8','#1e40af,#38bdf8','#065f46,#34d399','#9f1239,#f472b6',
                '#92400e,#fb923c','#6b21a8,#c084fc','#0c4a6e,#60a5fa','#14532d,#86efac'
            ];
            return 'linear-gradient(135deg,' + p[uid % 8] + ')';
        },

        projBg(pid) {
            var p = [
                '#6d28d9,#818cf8','#1e40af,#38bdf8','#065f46,#34d399','#9f1239,#f472b6',
                '#92400e,#fb923c','#6b21a8,#c084fc','#0c4a6e,#60a5fa','#14532d,#86efac'
            ];
            return 'linear-gradient(135deg,' + p[pid % 8] + ')';
        },
    };
}
</script>
</body>
</html>
