@props([
    'proyecto',
    'countValue'  => 0,
    'countLabel'  => '',
    'countIcon'   => 'tag',
    'actionLabel' => 'Entrar',
    'actionUrl'   => '#',
])
@php
    // Color pairs for the gradient banner
    $colors = [
        ['#6d28d9','#818cf8'],
        ['#1e40af','#38bdf8'],
        ['#065f46','#34d399'],
        ['#9f1239','#f472b6'],
        ['#92400e','#fb923c'],
        ['#6b21a8','#c084fc'],
        ['#0c4a6e','#60a5fa'],
        ['#14532d','#86efac'],
    ];
    $idx   = $proyecto->id % 8;
    $c1    = $colors[$idx][0];
    $c2    = $colors[$idx][1];
    $ini   = mb_strtoupper(mb_substr(trim($proyecto->nombre), 0, 2));

    $total = (int)($proyecto->tasks_count ?? 0);
    $done  = (int)($proyecto->completed_tasks_count ?? 0);
    $pct   = $total > 0 ? (int)round($done / $total * 100) : 0;

    $estado = $proyecto->estado ?? 'activo';
    [$badgeTxt, $badgeBg, $badgeFg, $badgeBorder, $barColor] = match ($estado) {
        'completado'  => ['Completado',  'rgba(16,185,129,.12)',  '#34d399', 'rgba(16,185,129,.3)',  '#10b981'],
        'en_progreso' => ['En progreso', 'rgba(59,130,246,.12)', '#60a5fa', 'rgba(59,130,246,.3)',  '#3b82f6'],
        'planificado' => ['Planificado', 'rgba(245,158,11,.12)', '#fbbf24', 'rgba(245,158,11,.3)',  '#f59e0b'],
        default       => ['Activo',      'rgba(99,102,241,.12)', '#a5b4fc', 'rgba(99,102,241,.3)',  '#6366f1'],
    };

    $sprintCount = (int)($proyecto->sprints_count ?? 0);
    $userName    = auth()->user()->name ?? 'U';
    $userInitial = mb_strtoupper(mb_substr($userName, 0, 1));
@endphp

{{-- ─────────────────────────────────────────────────────────────────────────
     CARD — fully vertical layout, all critical styles are inline.
     The parent grid (set with CSS media queries in @push('styles'))
     constrains the card width to ~1/3 of the content area on desktop.
──────────────────────────────────────────────────────────────────────────── --}}
<article class="project-card"
         data-name="{{ strtolower($proyecto->nombre) }}"
         style="
            display: flex;
            flex-direction: column;
            height: 100%;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.09);
            background: #16213a;
            transition: border-color .18s, box-shadow .18s, transform .18s;
            cursor: default;
         "
         onmouseover="this.style.borderColor='rgba(255,255,255,0.22)';this.style.boxShadow='0 20px 60px rgba(0,0,0,0.5)';this.style.transform='translateY(-2px)'"
         onmouseout="this.style.borderColor='rgba(255,255,255,0.09)';this.style.boxShadow='none';this.style.transform='none'">

    {{-- ── BANNER (gradient image placeholder) ──────────────────────────── --}}
    <div style="
            background: linear-gradient(135deg, {{ $c1 }}, {{ $c2 }});
            height: 108px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
         ">
        {{-- Initials --}}
        <span style="font-size: 2rem; font-weight: 900; color: rgba(255,255,255,0.9);
                     letter-spacing: -2px; user-select: none;">{{ $ini }}</span>

        {{-- Status badge (top-right) --}}
        <span style="
                position: absolute;
                top: 10px;
                right: 10px;
                font-size: 9px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .05em;
                padding: 2px 8px;
                border-radius: 999px;
                background: {{ $badgeBg }};
                color: {{ $badgeFg }};
                border: 1px solid {{ $badgeBorder }};
                backdrop-filter: blur(4px);
             ">{{ $badgeTxt }}</span>
    </div>

    {{-- ── CONTENT ────────────────────────────────────────────────────────── --}}
    <div style="padding: 16px; flex: 1; display: flex; flex-direction: column; gap: 0;">

        {{-- Title --}}
        <h3 style="
                font-size: 13px;
                font-weight: 700;
                color: #f1f5f9;
                line-height: 1.4;
                margin: 0 0 6px;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
             ">{{ $proyecto->nombre }}</h3>

        {{-- Description --}}
        <p style="
                font-size: 11px;
                line-height: 1.55;
                color: {{ $proyecto->descripcion ? '#94a3b8' : '#475569' }};
                font-style: {{ $proyecto->descripcion ? 'normal' : 'italic' }};
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                margin: 0 0 12px;
             ">{{ $proyecto->descripcion ?: 'Sin descripción.' }}</p>

        {{-- Progress bar --}}
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
            <div style="flex: 1; height: 6px; border-radius: 999px; background: rgba(255,255,255,0.07); overflow: hidden;">
                <div style="height: 100%; border-radius: 999px; background: {{ $barColor }}; width: {{ $pct }}%; transition: width .5s;"></div>
            </div>
            <span style="font-size: 10px; font-weight: 700; color: #94a3b8; white-space: nowrap; min-width: 28px; text-align: right;">{{ $pct }}%</span>
        </div>

        {{-- Meta info (2-column grid via CSS) --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 8px; margin-top: auto;">

            @if($proyecto->fecha_inicio)
            <div style="display: flex; align-items: center; gap: 4px; font-size: 10px; color: #64748b;">
                <span class="material-symbols-outlined" style="font-size: 11px; color: #475569; flex-shrink:0">event</span>
                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $proyecto->fecha_inicio->format('d M Y') }}</span>
            </div>
            @endif

            @if($proyecto->fecha_fin_estimada)
            <div style="display: flex; align-items: center; gap: 4px; font-size: 10px; color: #64748b;">
                <span class="material-symbols-outlined" style="font-size: 11px; color: #475569; flex-shrink:0">schedule</span>
                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $proyecto->fecha_fin_estimada->format('d M Y') }}</span>
            </div>
            @endif

            <div style="display: flex; align-items: center; gap: 4px; font-size: 10px; color: #94a3b8; font-weight: 600;">
                <span class="material-symbols-outlined" style="font-size: 11px; flex-shrink:0">{{ $countIcon }}</span>
                <span>{{ $countValue }}&nbsp;</span>
                <span style="color: #64748b; font-weight: 400;">{{ $countLabel }}</span>
            </div>

            @if($sprintCount > 0)
            <div style="display: flex; align-items: center; gap: 4px; font-size: 10px; color: #64748b;">
                <span class="material-symbols-outlined" style="font-size: 11px; flex-shrink:0">sprint</span>
                <span>{{ $sprintCount }} {{ $sprintCount === 1 ? 'sprint' : 'sprints' }}</span>
            </div>
            @endif

        </div>
    </div>

    {{-- ── FOOTER ─────────────────────────────────────────────────────────── --}}
    <div style="
            border-top: 1px solid rgba(255,255,255,0.06);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.025);
            flex-shrink: 0;
         ">

        {{-- Owner avatar + name --}}
        <div style="display: flex; align-items: center; gap: 8px; min-width: 0; overflow: hidden;">
            <div style="
                    width: 28px; height: 28px;
                    border-radius: 999px;
                    background: linear-gradient(135deg, {{ $c1 }}, {{ $c2 }});
                    border: 1.5px solid rgba(255,255,255,0.15);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 11px;
                    font-weight: 900;
                    color: white;
                    flex-shrink: 0;
                 ">{{ $userInitial }}</div>
            <span style="font-size: 10px; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $userName }}</span>
        </div>

        {{-- Action button --}}
        <a href="{{ $actionUrl }}"
           style="
                display: flex;
                align-items: center;
                gap: 4px;
                padding: 6px 14px;
                border-radius: 8px;
                background: #4f46e5;
                color: white;
                font-size: 11px;
                font-weight: 700;
                text-decoration: none;
                white-space: nowrap;
                transition: background .15s, transform .1s;
                flex-shrink: 0;
           "
           onmouseover="this.style.background='#4338ca'"
           onmouseout="this.style.background='#4f46e5'"
           onmousedown="this.style.transform='scale(0.96)'"
           onmouseup="this.style.transform='scale(1)'">
            {{ $actionLabel }}
            <span class="material-symbols-outlined" style="font-size: 12px;">arrow_forward</span>
        </a>
    </div>

</article>
