<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition-colors">Dashboard</a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold">Equipo</span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        #team-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.25rem;
        }
        @media (min-width: 768px)  { #team-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { #team-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .team-card.pv-hidden { display: none !important; }
    </style>
    @endpush

    <div style="padding:1.5rem;">

        {{-- Header --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem;">
            <div>
                <h1 style="font-size:1.2rem; font-weight:800; color:#f1f5f9; margin:0 0 4px;">Equipo</h1>
                <p style="font-size:12px; color:#64748b; margin:0;">Selecciona un proyecto para gestionar su equipo de trabajo.</p>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="position:relative;">
                    <span class="material-symbols-outlined"
                          style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:14px; color:#475569; pointer-events:none;">search</span>
                    <input id="team-search" type="text" placeholder="Buscar proyecto…"
                           style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
                                  color:#f1f5f9; border-radius:12px; font-size:12px;
                                  padding:7px 14px 7px 32px; width:200px; outline:none;" />
                </div>
            </div>
        </div>

        @if($proyectos->isEmpty())
            <div style="border-radius:1rem; border:1px solid rgba(255,255,255,0.06); background:rgba(255,255,255,0.02); padding:4rem; text-align:center;">
                <span class="material-symbols-outlined" style="font-size:44px; color:#374151; display:block; margin-bottom:12px;">group</span>
                <p style="font-size:14px; color:#6b7280; margin:0 0 12px;">No tienes proyectos registrados todavía.</p>
                <a href="{{ route('proyectos.create') }}" style="font-size:12px; color:#818cf8; text-decoration:underline;">Crear tu primer proyecto →</a>
            </div>
        @else
            <p style="font-size:11px; color:#475569; margin-bottom:1rem;">
                {{ $proyectos->count() }} {{ $proyectos->count() === 1 ? 'proyecto' : 'proyectos' }}
            </p>

            <div id="team-grid">
                @foreach($proyectos as $proyecto)
                @php
                    $tc       = [['#6d28d9','#818cf8'],['#1e40af','#38bdf8'],['#065f46','#34d399'],['#9f1239','#f472b6'],['#92400e','#fb923c'],['#6b21a8','#c084fc'],['#0c4a6e','#60a5fa'],['#14532d','#86efac']];
                    $c        = $tc[$proyecto->id % 8];
                    $ini      = mb_strtoupper(mb_substr($proyecto->nombre, 0, 2));
                    $avP      = $tc; // reuse same palette for member avatars
                    $dMembers = $proyecto->displayMembers();
                    $vMembers = $dMembers->take(3);
                    $extra    = max(0, $dMembers->count() - 3);
                @endphp
                <article class="team-card flex flex-col h-full rounded-2xl overflow-hidden border border-white/[0.08] hover:border-white/[0.22] hover:shadow-2xl hover:-translate-y-0.5 transition-all duration-200"
                         style="background:#16213a"
                         data-name="{{ strtolower($proyecto->nombre) }}">
                    {{-- Banner --}}
                    <div style="height:90px; flex-shrink:0; background:linear-gradient(135deg,{{ $c[0] }},{{ $c[1] }}); display:flex; align-items:center; justify-content:center; position:relative;">
                        <span style="font-size:1.8rem; font-weight:900; color:rgba(255,255,255,0.9); letter-spacing:-2px; user-select:none;">{{ $ini }}</span>
                    </div>
                    {{-- Body --}}
                    <div style="padding:16px; flex:1; display:flex; flex-direction:column; gap:8px;">
                        <h3 style="font-size:13px; font-weight:700; color:#f1f5f9; margin:0; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $proyecto->nombre }}</h3>
                        @if($proyecto->descripcion)
                        <p style="font-size:11px; color:#94a3b8; margin:0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $proyecto->descripcion }}</p>
                        @endif
                    </div>
                    {{-- Footer with stacked avatars --}}
                    <div style="border-top:1px solid rgba(255,255,255,0.06); padding:10px 16px; display:flex; align-items:center; justify-content:space-between; background:rgba(255,255,255,0.025); flex-shrink:0;">
                        {{-- Stacked avatars --}}
                        <div style="display:flex; align-items:center;">
                            @forelse($vMembers as $member)
                            @php $ma=$avP[$member->id%8]; @endphp
                            <div title="{{ $member->name }}"
                                 style="width:30px;height:30px;border-radius:50%;border:2px solid #16213a;margin-left:{{ $loop->first?'0':'-9px' }};position:relative;z-index:{{ 4-$loop->index }};overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,{{ $ma[0] }},{{ $ma[1] }});">
                                @if($member->profile_photo_path)
                                <img src="{{ asset('storage/'.$member->profile_photo_path) }}" alt="{{ $member->name }}"
                                     style="width:100%;height:100%;object-fit:cover;display:block;">
                                @else
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;color:rgba(255,255,255,.95);user-select:none;">{{ mb_strtoupper(mb_substr($member->name,0,1)) }}</div>
                                @endif
                            </div>
                            @empty
                            <span style="font-size:10px;color:#475569;">Sin equipo</span>
                            @endforelse
                            @if($extra > 0)
                            <div title="{{ $extra }} miembro{{ $extra!==1?'s':'' }} más"
                                 style="width:30px;height:30px;border-radius:50%;border:2px solid rgba(99,102,241,.5);margin-left:-9px;position:relative;z-index:0;background:rgba(99,102,241,.2);display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;color:#a5b4fc;flex-shrink:0;">
                                +{{ $extra }}
                            </div>
                            @endif
                            @if($dMembers->count() > 0)
                            <span style="font-size:10px;color:#475569;margin-left:8px;white-space:nowrap;">{{ $proyecto->members_count }} {{ $proyecto->members_count===1?'miembro':'miembros' }}</span>
                            @endif
                        </div>
                        {{-- Button --}}
                        <a href="{{ route('team.show', $proyecto) }}"
                           style="display:inline-flex;align-items:center;gap:4px;padding:6px 14px;border-radius:8px;background:#4f46e5;color:white;font-size:11px;font-weight:700;text-decoration:none;transition:background .15s;flex-shrink:0;"
                           onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                            Entrar
                            <span class="material-symbols-outlined" style="font-size:12px;">arrow_forward</span>
                        </a>
                    </div>
                </article>
                @endforeach
            </div>

            <p id="no-results" style="display:none; text-align:center; font-size:13px; color:#475569; padding:2rem 0;">Sin proyectos para esa búsqueda.</p>
        @endif
    </div>

    @push('scripts')
    <script>
    (function(){
        var input = document.getElementById('team-search');
        if (!input) return;
        input.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            var cards = document.querySelectorAll('#team-grid .team-card');
            var vis = 0;
            cards.forEach(function(c){ var m = !q||(c.dataset.name||'').includes(q); if(m){c.classList.remove('pv-hidden');vis++;}else{c.classList.add('pv-hidden');} });
            var nr = document.getElementById('no-results');
            if(nr) nr.style.display=(vis===0&&q)?'block':'none';
        });
    })();
    </script>
    @endpush
</x-app-layout>
