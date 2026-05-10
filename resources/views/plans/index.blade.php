<x-app-layout>
    <x-slot name="header">
        <span class="text-sm font-medium text-gray-400">Planes</span>
    </x-slot>

    @push('styles')
    <style>
        /* ── Estilos visuales de la página de planes ─── */
        .plan-glass {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.09);
        }
        .plan-ai-glow {
            box-shadow: 0 0 30px rgba(52,34,204,0.32), 0 0 0 1px rgba(195,192,255,0.38);
            border: 1px solid rgba(195,192,255,0.38) !important;
        }
        .plan-card-lift { transition: transform 200ms ease; }
        .plan-card-lift:hover { transform: translateY(-4px); }
        .plan-popular-lift { transform: scale(1.05); transition: transform 200ms ease; }
        .plan-popular-lift:hover { transform: scale(1.05) translateY(-4px); }
        /* Tabla comparativa */
        .cmp-table th, .cmp-table td { border: none; }
        .cmp-table tbody tr:last-child td { border-bottom: none; }
        @media (max-width: 1024px) {
            .plan-popular-lift { transform: none; }
            .plan-popular-lift:hover { transform: translateY(-4px); }
        }
    </style>
    @endpush

    @php
        // Orden: públicos por precio ASC, Enterprise siempre al final
        $publicPlans    = $plans->where('is_custom', false)->sortBy('monthly_price')->values();
        $enterprisePlan = $plans->firstWhere('is_custom', true);
        $allPlans       = $publicPlans
            ->when($enterprisePlan, fn($c) => $c->push($enterprisePlan))
            ->values();

        $planMeta = [
            'free'       => ['desc' => 'Para individuos explorando la gestión básica.',       'color' => '#c5c6ce'],
            'personal'   => ['desc' => 'Optimiza tu flujo de trabajo personal.',              'color' => '#c5c6ce'],
            'team'       => ['desc' => 'Colaboración avanzada para equipos en crecimiento.',  'color' => '#c3c0ff'],
            'business'   => ['desc' => 'Potencia total para organizaciones consolidadas.',    'color' => '#c5c6ce'],
            'enterprise' => ['desc' => 'Soluciones a medida para grandes organizaciones.',    'color' => '#c5c6ce'],
        ];

        // Features mostradas en las cards (presentación, no DB raw)
        $cardFeatures = [
            'free' => [
                ['icon' => 'stars',         'label' => '50 créditos IA / mes'],
                ['icon' => 'person',         'label' => '1 usuario'],
                ['icon' => 'folder_open',    'label' => '2 proyectos'],
            ],
            'personal' => [
                ['icon' => 'stars',         'label' => '300 créditos IA / mes'],
                ['icon' => 'person',         'label' => '1 usuario'],
                ['icon' => 'folder_open',    'label' => 'Hasta 10 proyectos'],
                ['icon' => 'history',        'label' => 'Historial IA completo'],
            ],
            'team' => [
                ['icon' => 'stars',              'label' => '1.500 créditos IA / mes'],
                ['icon' => 'group',              'label' => 'Hasta 5 usuarios'],
                ['icon' => 'folder_open',        'label' => 'Hasta 30 proyectos'],
                ['icon' => 'forum',              'label' => 'Chat por proyecto'],
                ['icon' => 'admin_panel_settings','label' => 'Roles y permisos'],
            ],
            'business' => [
                ['icon' => 'stars',              'label' => '5.000 créditos IA / mes'],
                ['icon' => 'group',              'label' => 'Hasta 15 usuarios'],
                ['icon' => 'folder_open',        'label' => 'Hasta 100 proyectos'],
                ['icon' => 'bar_chart',          'label' => 'Reportes y auditoría'],
                ['icon' => 'support_agent',      'label' => 'Soporte prioritario'],
            ],
            'enterprise' => [
                ['icon' => 'stars',              'label' => 'Créditos personalizados'],
                ['icon' => 'groups',             'label' => 'Usuarios ilimitados'],
                ['icon' => 'folder_open',        'label' => 'Proyectos ilimitados'],
                ['icon' => 'manage_accounts',    'label' => 'Gestor de cuentas dedicado'],
            ],
        ];

        // Filas de la tabla comparativa
        // Tipos de celda: valor string | 'check' | 'check:Label' | 'text:Label' | 'dash'
        $tableRows = [
            [
                'label' => 'Créditos IA / mes', 'icon' => 'stars',
                'vals'  => ['free'=>'50','personal'=>'300','team'=>'1.500','business'=>'5.000','enterprise'=>'Personalizado'],
            ],
            [
                'label' => 'Límite diario de IA', 'icon' => 'calendar_today',
                'vals'  => ['free'=>'20 / día','personal'=>'80 / día','team'=>'300 / día','business'=>'1.000 / día','enterprise'=>'Personalizado'],
            ],
            [
                'label' => 'Acciones por minuto', 'icon' => 'speed',
                'vals'  => ['free'=>'2 / min','personal'=>'5 / min','team'=>'10 / min','business'=>'20 / min','enterprise'=>'Personalizado'],
            ],
            [
                'label' => 'Usuarios incluidos', 'icon' => 'group',
                'vals'  => ['free'=>'1','personal'=>'1','team'=>'Hasta 5','business'=>'Hasta 15','enterprise'=>'Ilimitados'],
            ],
            [
                'label' => 'Proyectos permitidos', 'icon' => 'folder_open',
                'vals'  => ['free'=>'2','personal'=>'10','team'=>'Hasta 30','business'=>'Hasta 100','enterprise'=>'Ilimitados'],
            ],
            [
                'label' => 'Historial IA', 'icon' => 'history',
                'vals'  => ['free'=>'text:Básico','personal'=>'check:Completo','team'=>'check','business'=>'check','enterprise'=>'check'],
            ],
            [
                'label' => 'Chat por proyecto', 'icon' => 'forum',
                'vals'  => ['free'=>'dash','personal'=>'dash','team'=>'check','business'=>'check','enterprise'=>'check'],
            ],
            [
                'label' => 'Mensajes privados', 'icon' => 'lock',
                'vals'  => ['free'=>'dash','personal'=>'dash','team'=>'check','business'=>'check','enterprise'=>'check'],
            ],
            [
                'label' => 'Roles y permisos', 'icon' => 'admin_panel_settings',
                'vals'  => ['free'=>'dash','personal'=>'dash','team'=>'check','business'=>'check','enterprise'=>'check'],
            ],
            [
                'label' => 'Reportes y auditoría', 'icon' => 'bar_chart',
                'vals'  => ['free'=>'dash','personal'=>'dash','team'=>'dash','business'=>'check','enterprise'=>'check'],
            ],
            [
                'label' => 'Soporte prioritario', 'icon' => 'support_agent',
                'vals'  => ['free'=>'text:Básico','personal'=>'text:Básico','team'=>'text:Normal','business'=>'check:Prioritario','enterprise'=>'check:24/7'],
            ],
        ];
    @endphp

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- Contenido principal                                               --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="px-6 pt-10 pb-24" style="max-width:1440px;margin:0 auto;">

        {{-- ── Encabezado centrado ─────────────────────────────────────── --}}
        <header class="text-center max-w-4xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-widest mb-6"
                  style="background:rgba(52,34,204,0.18);color:#afadff;border:1px solid rgba(52,34,204,0.32);">
                PLANES Y CRÉDITOS IA
            </span>
            <h1 class="font-black text-white mb-4 leading-[1.1]"
                style="font-size:clamp(2rem,4vw,3rem);letter-spacing:-0.02em;">
                Elige el plan que mejor<br>se adapta a tu equipo
            </h1>
            <p style="font-size:15px;color:#64748b;line-height:1.6;max-width:600px;margin:0 auto;">
                Impulsa la productividad de tu organización con herramientas de inteligencia artificial
                avanzadas y gestión de proyectos de alto nivel.
            </p>
        </header>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- GRID DE CARDS DE PLANES                                       --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-24 items-start">
            @foreach($allPlans as $plan)
            @php
                $isCurrent    = $plan->id === $userPlanId;
                $isPopular    = $plan->slug === 'team';
                $isEnterprise = $plan->is_custom;
                $meta         = $planMeta[$plan->slug] ?? ['desc' => '', 'color' => '#c5c6ce'];
                $feats         = $cardFeatures[$plan->slug] ?? [];
            @endphp

            <div class="{{ $isPopular ? 'plan-ai-glow plan-popular-lift z-10' : 'plan-glass plan-card-lift' }} rounded-xl p-6 flex flex-col relative"
                 style="{{ $isCurrent && !$isPopular ? 'border:1.5px solid rgba(99,102,241,0.4)!important;background:rgba(99,102,241,0.07)!important;' : '' }}">

                {{-- Badge superior --}}
                @if($isPopular)
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 z-20">
                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest whitespace-nowrap"
                          style="background:#3422cc;color:#c3c0ff;box-shadow:0 2px 10px rgba(52,34,204,0.5);">
                        MÁS POPULAR
                    </span>
                </div>
                @elseif($isCurrent)
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 z-20">
                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest whitespace-nowrap"
                          style="background:rgba(99,102,241,0.22);color:#a5b4fc;border:1px solid rgba(99,102,241,0.38);">
                        TU PLAN ACTUAL
                    </span>
                </div>
                @endif

                {{-- Nombre del plan --}}
                <h3 class="font-bold text-white mb-2" style="font-size:20px;{{ ($isPopular || $isCurrent) ? 'margin-top:10px;' : '' }}">
                    {{ $plan->name }}
                </h3>

                {{-- Precio --}}
                <div class="flex items-baseline gap-1 mb-4">
                    @if($isEnterprise)
                        <span class="font-black" style="font-size:26px;color:#818cf8;">Personalizado</span>
                    @elseif($plan->monthly_price > 0)
                        <span class="font-black text-white" style="font-size:28px;">${{ (int)$plan->monthly_price }}</span>
                        <span style="font-size:13px;color:#64748b;">/mes</span>
                    @else
                        <span class="font-black" style="font-size:28px;color:#34d399;">$0</span>
                        <span style="font-size:13px;color:#64748b;">/mes</span>
                    @endif
                </div>

                {{-- Descripción --}}
                <p style="font-size:13px;color:#64748b;min-height:42px;line-height:1.5;" class="mb-6">
                    {{ $meta['desc'] }}
                </p>

                {{-- Lista de características de la card --}}
                <ul class="space-y-3 mb-8 flex-1">
                    @foreach($feats as $f)
                    <li class="flex items-center gap-2.5 text-white" style="font-size:13px;">
                        <span class="material-symbols-outlined flex-shrink-0"
                              style="font-size:16px;color:{{ $meta['color'] }};font-variation-settings:'FILL' 1;">
                            {{ $f['icon'] }}
                        </span>
                        {{ $f['label'] }}
                    </li>
                    @endforeach
                </ul>

                {{-- Botón de acción --}}
                @if($isCurrent)
                    {{-- Plan ya activo: deshabilitado --}}
                    <button disabled
                            class="w-full py-2.5 rounded-lg font-bold cursor-not-allowed"
                            style="font-size:13px;background:rgba(99,102,241,0.08);color:#4338ca;
                                   border:1px solid rgba(99,102,241,0.18);opacity:.55;">
                        <span class="material-symbols-outlined align-middle"
                              style="font-size:13px;font-variation-settings:'FILL' 1;">check_circle</span>
                        Plan actual
                    </button>
                @elseif($isEnterprise)
                    {{-- Enterprise: sin checkout, contacto directo --}}
                    <a href="{{ route('profile.edit') }}"
                       class="block w-full py-2.5 rounded-lg text-center font-semibold transition-all hover:bg-white/5 active:scale-95"
                       style="font-size:13px;border:1px solid rgba(255,255,255,0.15);color:#d4e4fa;">
                        Contactar ventas
                    </a>
                @else
                    {{-- Plan de pago o plan gratis no activo: POST al checkout --}}
                    <form method="POST" action="{{ route('planes.checkout', $plan) }}">
                        @csrf
                        <button type="submit"
                                class="w-full {{ $isPopular ? 'py-3' : 'py-2.5' }} rounded-lg font-{{ $isPopular ? 'bold' : 'semibold' }} transition-all hover:opacity-90 active:scale-95"
                                style="font-size:13px;
                                       {{ $isPopular
                                           ? 'background:#3422cc;color:#c3c0ff;box-shadow:0 4px 18px rgba(52,34,204,0.45);'
                                           : 'border:1px solid rgba(255,255,255,0.14);color:#d4e4fa;' }}">
                            {{ $plan->isFree() ? 'Cambiar a Gratis' : 'Seleccionar' }}
                        </button>
                    </form>
                @endif

            </div>
            @endforeach
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TABLA COMPARATIVA                                             --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="mb-24">
            <h3 class="font-bold text-white text-center mb-8" style="font-size:22px;">
                Comparativa de funciones
            </h3>

            <div class="plan-glass rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="cmp-table w-full text-left" style="min-width:680px;border-collapse:collapse;">
                        {{-- Cabecera de columnas --}}
                        <thead>
                            <tr style="background:rgba(28,43,60,0.55);border-bottom:1px solid rgba(68,71,76,0.55);">
                                <th class="px-6 py-5 text-white font-semibold"
                                    style="font-size:13px;width:190px;">Característica</th>
                                @foreach($allPlans as $plan)
                                @php
                                    $isTeamCol    = $plan->slug === 'team';
                                    $isCurrentCol = $plan->id === $userPlanId;
                                @endphp
                                <th class="px-5 py-5 text-center font-semibold text-white"
                                    style="font-size:13px;{{ $isTeamCol ? 'background:rgba(52,34,204,0.1);' : '' }}">
                                    {{ $plan->name }}
                                    @if($isCurrentCol)
                                    <span class="block font-bold uppercase tracking-widest mt-0.5"
                                          style="font-size:9px;color:#6366f1;">Tu plan</span>
                                    @endif
                                </th>
                                @endforeach
                            </tr>
                        </thead>

                        {{-- Filas de características --}}
                        <tbody>
                            @foreach($tableRows as $rowIdx => $row)
                            <tr class="hover:bg-white/[0.028] transition-colors"
                                style="border-bottom:1px solid rgba(68,71,76,0.22);">

                                {{-- Etiqueta de la característica --}}
                                <td class="px-6 py-4" style="color:#94a3b8;">
                                    <div class="flex items-center gap-2" style="font-size:13px;">
                                        <span class="material-symbols-outlined flex-shrink-0"
                                              style="font-size:14px;color:#334155;font-variation-settings:'FILL' 1;">{{ $row['icon'] }}</span>
                                        {{ $row['label'] }}
                                    </div>
                                </td>

                                {{-- Celda por plan --}}
                                @foreach($allPlans as $plan)
                                @php
                                    $isTeamCol = $plan->slug === 'team';
                                    $rawVal    = $row['vals'][$plan->slug] ?? 'dash';

                                    if (str_starts_with($rawVal, 'check:')) {
                                        $cType = 'check'; $cLabel = substr($rawVal, 6);
                                    } elseif ($rawVal === 'check') {
                                        $cType = 'check'; $cLabel = null;
                                    } elseif (str_starts_with($rawVal, 'text:')) {
                                        $cType = 'text'; $cLabel = substr($rawVal, 5);
                                    } elseif ($rawVal === 'dash') {
                                        $cType = 'dash'; $cLabel = null;
                                    } else {
                                        $cType = 'val'; $cLabel = $rawVal;
                                    }
                                @endphp
                                <td class="px-5 py-4 text-center"
                                    style="{{ $isTeamCol ? 'background:rgba(52,34,204,0.05);' : '' }}
                                           font-size:13px;
                                           color:{{ $isTeamCol ? '#d4e4fa' : '#64748b' }};
                                           font-weight:{{ $isTeamCol ? '600' : '400' }};">
                                    @if($cType === 'check')
                                        <span class="material-symbols-outlined"
                                              style="font-size:18px;color:{{ $isTeamCol ? '#c3c0ff' : '#c5c6ce' }};
                                                     font-variation-settings:'FILL' 1;display:block;margin:0 auto;">check_circle</span>
                                        @if($cLabel)
                                        <span style="font-size:10px;color:{{ $isTeamCol ? '#9d9aff' : '#64748b' }};">{{ $cLabel }}</span>
                                        @endif
                                    @elseif($cType === 'dash')
                                        <span style="color:rgba(255,255,255,0.1);font-size:16px;">—</span>
                                    @elseif($cType === 'text')
                                        {{ $cLabel }}
                                    @else
                                        {{ $cLabel }}
                                    @endif
                                </td>
                                @endforeach

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Flash de error / info --}}
        @if(session('error'))
        <div class="max-w-xl mx-auto mb-4 px-5 py-3.5 rounded-xl flex items-center gap-3 text-sm"
             style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#f87171;">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:17px;">error</span>
            {{ session('error') }}
        </div>
        @endif
        @if(session('info'))
        <div class="max-w-xl mx-auto mb-4 px-5 py-3.5 rounded-xl flex items-center gap-3 text-sm"
             style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);color:#a5b4fc;">
            <span class="material-symbols-outlined flex-shrink-0" style="font-size:17px;">info</span>
            {{ session('info') }}
        </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- SECCIÓN DE CONFIANZA                                          --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 max-w-4xl mx-auto">
            @foreach([
                ['icon' => 'verified_user',  'title' => 'Sin compromisos',    'desc' => 'Cancela cuando quieras, sin penalización ni cargos ocultos.'],
                ['icon' => 'trending_up',    'title' => 'Escalable',          'desc' => 'Tu plan crece junto a tu equipo y tus proyectos activos.'],
                ['icon' => 'support_agent',  'title' => 'Soporte incluido',   'desc' => 'Estamos disponibles para ayudarte cuando lo necesites.'],
            ] as $trust)
            <div class="plan-glass rounded-xl p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0"
                     style="background:rgba(28,43,60,0.9);">
                    <span class="material-symbols-outlined" style="font-size:20px;color:#c5c6ce;">{{ $trust['icon'] }}</span>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-0.5" style="font-size:14px;">{{ $trust['title'] }}</h4>
                    <p style="font-size:12px;color:#475569;">{{ $trust['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

    </div>

</x-app-layout>
