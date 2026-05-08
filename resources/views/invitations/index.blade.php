<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition-colors">Dashboard</a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold">Mis Invitaciones</span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        .inv-card { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:14px; overflow:hidden; transition:border-color .15s; }
        .inv-card:hover { border-color:rgba(99,102,241,0.25); }
        .status-pending  { background:rgba(245,158,11,.12); color:#fbbf24; border-color:rgba(245,158,11,.3); }
        .status-accepted { background:rgba(16,185,129,.12); color:#34d399; border-color:rgba(16,185,129,.3); }
        .status-rejected { background:rgba(100,116,139,.1); color:#94a3b8; border-color:rgba(100,116,139,.3); }
        .status-cancelled{ background:rgba(239,68,68,.1);  color:#f87171; border-color:rgba(239,68,68,.3); }
        .status-badge { display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:10px; font-weight:700; text-transform:uppercase; border:1px solid; }
    </style>
    @endpush

    <div style="padding:1.5rem; max-width:900px; margin:0 auto;">

        {{-- Flash messages --}}
        @if(session('success'))
        <div style="background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.25); border-radius:10px; padding:10px 16px; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="font-size:16px;color:#34d399;">check_circle</span>
            <span style="font-size:13px;color:#34d399;">{{ session('success') }}</span>
        </div>
        @endif
        @if(session('info'))
        <div style="background:rgba(99,102,241,.1); border:1px solid rgba(99,102,241,.25); border-radius:10px; padding:10px 16px; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="font-size:16px;color:#818cf8;">info</span>
            <span style="font-size:13px;color:#818cf8;">{{ session('info') }}</span>
        </div>
        @endif

        {{-- Page header --}}
        <div style="margin-bottom:1.5rem;">
            <h1 style="font-size:1.25rem; font-weight:800; color:#f1f5f9; margin:0 0 4px;">Mis Invitaciones</h1>
            <p style="font-size:12px; color:#64748b; margin:0;">Invitaciones a proyectos que has recibido.</p>
        </div>

        @if($invitations->isEmpty())
        <div style="padding:4rem; text-align:center; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:14px;">
            <span class="material-symbols-outlined" style="font-size:44px; color:#374151; display:block; margin-bottom:12px;">mail_outline</span>
            <p style="font-size:14px; color:#6b7280; margin:0 0 4px; font-weight:600;">Sin invitaciones</p>
            <p style="font-size:12px; color:#4b5563; margin:0;">Cuando alguien te invite a un proyecto aparecerá aquí.</p>
        </div>
        @else

        {{-- Pending invitations --}}
        @php $pending = $invitations->where('status', 'pending'); @endphp
        @if($pending->count() > 0)
        <div style="margin-bottom:1.5rem;">
            <h2 style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.08em; margin-bottom:.75rem;">
                Pendientes ({{ $pending->count() }})
            </h2>
            <div style="display:flex; flex-direction:column; gap:.75rem;">
                @foreach($pending as $inv)
                <div class="inv-card" style="padding:1.25rem;">
                    <div style="display:flex; align-items:flex-start; gap:1rem; flex-wrap:wrap;">
                        {{-- Project icon --}}
                        <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#6d28d9,#818cf8); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:900; color:white; flex-shrink:0;">
                            {{ strtoupper(mb_substr($inv->proyecto->nombre ?? '?', 0, 1)) }}
                        </div>
                        {{-- Info --}}
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                                <h3 style="font-size:14px; font-weight:800; color:#f1f5f9; margin:0;">{{ $inv->proyecto->nombre ?? '—' }}</h3>
                                <span class="status-badge status-pending">Pendiente</span>
                            </div>
                            <p style="font-size:12px; color:#94a3b8; margin:0 0 6px;">
                                Invitado por
                                <span style="color:#e2e8f0; font-weight:600;">{{ $inv->invitedBy->name ?? '—' }}</span>
                                ({{ $inv->invitedBy->email ?? '' }})
                            </p>
                            @if($inv->role)
                            <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:600; border:1px solid; border-color:rgba(99,102,241,.3); background:rgba(99,102,241,.1); color:#a5b4fc;">
                                <span class="material-symbols-outlined" style="font-size:12px;">badge</span>
                                {{ $inv->role->name }}
                            </span>
                            @endif
                            <p style="font-size:11px; color:#475569; margin:6px 0 0;">
                                <span class="material-symbols-outlined" style="font-size:12px; vertical-align:middle;">schedule</span>
                                {{ $inv->created_at->diffForHumans() }}
                            </p>
                        </div>
                        {{-- Actions --}}
                        <div style="display:flex; gap:8px; align-items:center; flex-shrink:0;">
                            <form method="POST" action="{{ route('invitations.accept', $inv) }}">
                                @csrf
                                <button type="submit"
                                        style="display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:10px; background:rgba(16,185,129,.15); border:1px solid rgba(16,185,129,.3); color:#34d399; font-size:12px; font-weight:700; cursor:pointer; transition:background .15s;"
                                        onmouseover="this.style.background='rgba(16,185,129,.25)'" onmouseout="this.style.background='rgba(16,185,129,.15)'">
                                    <span class="material-symbols-outlined" style="font-size:15px;">check_circle</span>
                                    Aceptar
                                </button>
                            </form>
                            <form method="POST" action="{{ route('invitations.reject', $inv) }}">
                                @csrf
                                <button type="submit"
                                        style="display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:10px; background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.2); color:#f87171; font-size:12px; font-weight:700; cursor:pointer; transition:background .15s;"
                                        onmouseover="this.style.background='rgba(239,68,68,.2)'" onmouseout="this.style.background='rgba(239,68,68,.1)'">
                                    <span class="material-symbols-outlined" style="font-size:15px;">cancel</span>
                                    Rechazar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Past invitations --}}
        @php $past = $invitations->whereNotIn('status', ['pending']); @endphp
        @if($past->count() > 0)
        <div>
            <h2 style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.08em; margin-bottom:.75rem;">
                Historial
            </h2>
            <div style="display:flex; flex-direction:column; gap:.5rem;">
                @foreach($past as $inv)
                <div class="inv-card" style="padding:1rem 1.25rem; opacity:.7;">
                    <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                        <div style="width:36px; height:36px; border-radius:9px; background:rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800; color:#94a3b8; flex-shrink:0;">
                            {{ strtoupper(mb_substr($inv->proyecto->nombre ?? '?', 0, 1)) }}
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <span style="font-size:13px; font-weight:700; color:#94a3b8;">{{ $inv->proyecto->nombre ?? '—' }}</span>
                                <span class="status-badge status-{{ $inv->status }}">
                                    {{ match($inv->status) { 'accepted'=>'Aceptada', 'rejected'=>'Rechazada', 'cancelled'=>'Cancelada', default=>ucfirst($inv->status) } }}
                                </span>
                            </div>
                            <p style="font-size:11px; color:#475569; margin:2px 0 0;">
                                De {{ $inv->invitedBy->name ?? '—' }} · {{ $inv->created_at->format('d/m/Y') }}
                                @if($inv->responded_at) · Respondida {{ $inv->responded_at->diffForHumans() }} @endif
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @endif

    </div>
</x-app-layout>
