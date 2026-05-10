@props([
    'proyecto',
    'countValue'  => 0,
    'countLabel'  => '',
    'countIcon'   => 'tag',
    'actionLabel' => 'Entrar',
    'actionUrl'   => '#',
    'coverPriority' => false,
])
@php
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
    $idx  = $proyecto->id % 8;
    $c1   = $colors[$idx][0];
    $c2   = $colors[$idx][1];
    $ini  = mb_strtoupper(mb_substr(trim($proyecto->nombre), 0, 2));

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

    $avPalette = [
        ['#6d28d9','#818cf8'], ['#1e40af','#38bdf8'], ['#065f46','#34d399'],
        ['#9f1239','#f472b6'], ['#92400e','#fb923c'], ['#6b21a8','#c084fc'],
        ['#0c4a6e','#60a5fa'], ['#14532d','#86efac'],
    ];
    $displayMembers = $proyecto->displayMembers();
    $visibleMembers = $displayMembers->take(3);
    $extraCount     = max(0, $displayMembers->count() - 3);
    $coverUrl       = !empty($proyecto->cover_image)
        ? asset('storage/'.$proyecto->cover_image).'?v='.(optional($proyecto->updated_at)->timestamp ?? '1')
        : null;
    $coverPriority  = (bool) $coverPriority;
@endphp

<div class="project-list-item"
     data-name="{{ strtolower($proyecto->nombre) }}"
     style="
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 16px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.07);
        background: #16213a;
        transition: border-color .15s, background .15s;
        isolation: isolate;
        contain: layout paint style;
        width: 100%;
        min-width: 0;
     "
     onmouseover="this.style.borderColor='rgba(255,255,255,0.18)';this.style.background='#1a2640'"
     onmouseout="this.style.borderColor='rgba(255,255,255,0.07)';this.style.background='#16213a'">

    {{-- Avatar / thumbnail --}}
    <div class="project-list-cover-frame"
         style="width:52px;height:52px;min-width:52px;min-height:52px;border-radius:12px;flex-shrink:0;overflow:hidden;position:relative;
                contain:layout paint style; isolation:isolate;
                @if($coverUrl)
                    background-color:#1b2440;
                    background-image:url('{{ $coverUrl }}');
                    background-size:cover;
                    background-position:center;
                    background-repeat:no-repeat;
                @else
                    background:linear-gradient(135deg,{{ $c1 }},{{ $c2 }});
                @endif">
        @unless($coverUrl)
        <div id="list-thumb-fallback-{{ $proyecto->id }}"
             style="
                position:absolute; inset:0;
                width:100%; height:100%;
                display:flex;
                align-items:center; justify-content:center;
                font-size:13px;font-weight:900;color:rgba(255,255,255,0.9);letter-spacing:-1px;user-select:none;
             ">
            {{ $ini }}
        </div>
        @endunless
        @if($coverUrl)
        <img src="{{ $coverUrl }}"
             alt="{{ $proyecto->nombre }}"
             class="project-list-cover-img project-cover-image"
             loading="{{ $coverPriority ? 'eager' : 'lazy' }}"
             decoding="{{ $coverPriority ? 'sync' : 'async' }}"
             fetchpriority="{{ $coverPriority ? 'high' : 'low' }}"
             width="52"
             height="52"
             data-project-cover="true"
             data-list-cover="true"
             data-priority-cover="{{ $coverPriority ? 'true' : 'false' }}"
             style="
                position:absolute; inset:0;
                width:100%; height:100%;
                max-width:100%; max-height:100%;
                object-fit:cover; display:block;
                border:0; opacity:1; transform:none; filter:none;
                transition:none; animation:none;
             "
             onerror="this.style.visibility='hidden';">
        @endif
    </div>

    {{-- Title + description --}}
    <div style="flex: 1; min-width: 0; overflow: hidden;">
        <p style="font-size: 13px; font-weight: 700; color: #f1f5f9; margin: 0 0 2px;
                  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            {{ $proyecto->nombre }}
        </p>
        @if($proyecto->descripcion)
        <p style="font-size: 11px; color: #64748b; margin: 0;
                  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            {{ $proyecto->descripcion }}
        </p>
        @endif
    </div>

    {{-- Status badge --}}
    <span style="
            flex-shrink: 0;
            font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
            padding: 3px 9px; border-radius: 999px;
            background: {{ $badgeBg }}; color: {{ $badgeFg }}; border: 1px solid {{ $badgeBorder }};
         ">{{ $badgeTxt }}</span>

    {{-- Progress bar --}}
    <div style="flex-shrink: 0; width: 100px; display: flex; align-items: center; gap: 6px;">
        <div style="flex: 1; height: 5px; border-radius: 999px; background: rgba(255,255,255,0.07); overflow: hidden;">
            <div style="height: 100%; border-radius: 999px; background: {{ $barColor }}; width: {{ $pct }}%;"></div>
        </div>
        <span style="font-size: 10px; font-weight: 700; color: #94a3b8; white-space: nowrap; min-width: 26px; text-align: right;">
            {{ $pct }}%
        </span>
    </div>

    {{-- Count --}}
    <div style="flex-shrink: 0; display: flex; align-items: center; gap: 4px;
                font-size: 11px; color: #94a3b8; min-width: 64px; justify-content: flex-end;">
        <span class="material-symbols-outlined" style="font-size: 13px; color: #475569;">{{ $countIcon }}</span>
        <span style="font-weight: 600;">{{ $countValue }}</span>
        <span style="color: #475569;">{{ $countLabel }}</span>
    </div>

    {{-- Date --}}
    @if($proyecto->fecha_inicio)
    <div style="flex-shrink: 0; display: flex; align-items: center; gap: 4px;
                font-size: 10px; color: #64748b; min-width: 80px; justify-content: flex-end;">
        <span class="material-symbols-outlined" style="font-size: 11px; color: #475569;">event</span>
        <span>{{ $proyecto->fecha_inicio->format('d M Y') }}</span>
    </div>
    @endif

    {{-- Stacked member avatars --}}
    <div style="flex-shrink:0; display:flex; align-items:center;">
        @forelse($visibleMembers as $member)
        @php $ac=$avPalette[$member->id%8]; @endphp
        <div title="{{ $member->name }}"
             style="width:28px;height:28px;border-radius:50%;border:2px solid #16213a;margin-left:{{ $loop->first?'0':'-8px' }};position:relative;z-index:{{ 4-$loop->index }};overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,{{ $ac[0] }},{{ $ac[1] }});">
            @if($member->profile_photo_path)
            <img src="{{ asset('storage/'.$member->profile_photo_path) }}" alt="{{ $member->name }}"
                 style="width:100%;height:100%;object-fit:cover;display:block;">
            @else
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;color:rgba(255,255,255,.95);user-select:none;">{{ mb_strtoupper(mb_substr($member->name,0,1)) }}</div>
            @endif
        </div>
        @empty
        <div style="width:28px;height:28px;border-radius:50%;border:2px solid #16213a;background:rgba(255,255,255,0.04);display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:13px;color:#475569;">person_outline</span>
        </div>
        @endforelse
        @if($extraCount > 0)
        <div title="{{ $extraCount }} más"
             style="width:28px;height:28px;border-radius:50%;border:2px solid rgba(99,102,241,.5);margin-left:-8px;position:relative;z-index:0;background:rgba(99,102,241,.2);display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;color:#a5b4fc;flex-shrink:0;">
            +{{ $extraCount }}
        </div>
        @endif
    </div>

    {{-- Action button --}}
    <a href="{{ $actionUrl }}"
       style="
            flex-shrink: 0;
            display: inline-flex; align-items: center; gap: 4px;
            padding: 6px 14px; border-radius: 8px;
            background: #4f46e5; color: white;
            font-size: 11px; font-weight: 700; text-decoration: none;
            white-space: nowrap;
            transition: background .15s;
       "
       onmouseover="this.style.background='#4338ca'"
       onmouseout="this.style.background='#4f46e5'">
        {{ $actionLabel }}
        <span class="material-symbols-outlined" style="font-size: 12px;">arrow_forward</span>
    </a>

</div>
