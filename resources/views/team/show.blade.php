<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition-colors">Dashboard</a>
            <span class="text-gray-600">›</span>
            <a href="{{ route('team.index') }}" class="text-gray-500 hover:text-gray-300 transition-colors">Equipo</a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold truncate">{{ $proyecto->nombre }}</span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        .member-row { transition: background .12s; }
        .member-row:hover { background: rgba(255,255,255,0.025); }
        .status-badge { display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:10px; font-weight:700; text-transform:uppercase; border:1px solid; }
        .status-activo     { background:rgba(16,185,129,.12); color:#34d399; border-color:rgba(16,185,129,.3); }
        .status-inactivo   { background:rgba(100,116,139,.1); color:#94a3b8; border-color:rgba(100,116,139,.3); }
        .status-invitado   { background:rgba(245,158,11,.12); color:#fbbf24; border-color:rgba(245,158,11,.3); }
        .status-suspendido { background:rgba(239,68,68,.12);  color:#f87171; border-color:rgba(239,68,68,.3);  }
        .sb-input { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:#f1f5f9; border-radius:10px; font-size:13px; padding:8px 12px; outline:none; transition:border-color .15s; }
        .sb-input:focus { border-color:rgba(99,102,241,0.5); }
        .sb-select { background:#111827; border:1px solid rgba(255,255,255,0.1); color:#f1f5f9; border-radius:10px; font-size:13px; padding:8px 12px; outline:none; }
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); z-index:200; display:flex; align-items:center; justify-content:center; }
        .modal-box { background:#0d1c2d; border:1px solid rgba(255,255,255,0.1); border-radius:1.25rem; padding:1.5rem; width:100%; max-width:520px; max-height:90vh; overflow-y:auto; }
    </style>
    @endpush

    <div style="padding:1.5rem;">

        {{-- Flash --}}
        @if(session('success'))
        <div style="background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.25); border-radius:10px; padding:10px 16px; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="font-size:16px;color:#34d399;">check_circle</span>
            <span style="font-size:13px;color:#34d399;">{{ session('success') }}</span>
        </div>
        @endif

        {{-- Page header --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem;">
            <div>
                <h1 style="font-size:1.2rem; font-weight:800; color:#f1f5f9; margin:0 0 4px;">Equipo — {{ $proyecto->nombre }}</h1>
                <p style="font-size:12px; color:#64748b; margin:0;">Gestiona los miembros, roles y accesos del equipo.</p>
            </div>
            <button onclick="openModal('add')"
                    style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:10px; background:#4f46e5; color:white; font-size:12px; font-weight:700; border:none; cursor:pointer; transition:background .15s;"
                    onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                <span class="material-symbols-outlined" style="font-size:16px;">person_add</span>
                Agregar miembro
            </button>
        </div>

        {{-- KPI cards --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:1rem; margin-bottom:1.25rem;">
            @foreach([['Total', $stats['total'], 'group', '#a5b4fc'], ['Activos', $stats['activos'], 'check_circle', '#34d399'], ['Invitados', $stats['invitados'], 'mail', '#fbbf24'], ['Remotos', $stats['remotos'], 'wifi', '#60a5fa']] as [$label, $value, $icon, $color])
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:14px 16px;">
                <p style="font-size:10px; color:#64748b; margin:0 0 6px; text-transform:uppercase; letter-spacing:.05em; font-weight:600;">{{ $label }}</p>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:20px; color:{{ $color }};">{{ $icon }}</span>
                    <span style="font-size:1.5rem; font-weight:800; color:#f1f5f9;">{{ $value }}</span>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('team.show', $proyecto) }}"
              style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:12px 16px; margin-bottom:1.25rem;">
            <div style="position:relative; flex:1; min-width:200px;">
                <span class="material-symbols-outlined" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:14px; color:#475569; pointer-events:none;">search</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar miembro…"
                       class="sb-input" style="padding-left:32px; width:100%;" />
            </div>
            <select name="role_id" class="sb-select">
                <option value="">Todos los roles</option>
                @foreach($roles as $r)
                <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
            <select name="status" class="sb-select">
                <option value="">Todos los estados</option>
                @foreach(['activo','inactivo','invitado','suspendido'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
            <button type="submit" style="padding:8px 16px; border-radius:10px; background:rgba(99,102,241,.2); color:#a5b4fc; border:1px solid rgba(99,102,241,.3); font-size:12px; font-weight:600; cursor:pointer;">
                Filtrar
            </button>
            @if(request()->hasAny(['search','role_id','status']))
            <a href="{{ route('team.show', $proyecto) }}" style="font-size:12px; color:#64748b; text-decoration:none;">× Limpiar</a>
            @endif
        </form>

        {{-- Members table --}}
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:14px; overflow:hidden;">
            @if($members->isEmpty())
            <div style="padding:3rem; text-align:center;">
                <span class="material-symbols-outlined" style="font-size:40px; color:#374151; display:block; margin-bottom:10px;">group</span>
                <p style="font-size:13px; color:#6b7280; margin:0;">No hay miembros en este proyecto todavía.</p>
                <button onclick="openModal('add')" style="margin-top:12px; font-size:12px; color:#818cf8; background:none; border:none; cursor:pointer; text-decoration:underline;">Agregar el primero</button>
            </div>
            @else
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.06);">
                        <th style="padding:12px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Miembro</th>
                        <th style="padding:12px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Rol</th>
                        <th style="padding:12px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Cargo</th>
                        <th style="padding:12px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Estado</th>
                        <th style="padding:12px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Modo trabajo</th>
                        <th style="padding:12px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Actualización</th>
                        <th style="padding:12px 16px; text-align:center; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                    <tr class="member-row" style="border-bottom:1px solid rgba(255,255,255,0.04);">
                        {{-- Member --}}
                        <td style="padding:14px 16px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#6d28d9,#818cf8); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:white; flex-shrink:0;">
                                    {{ $member->initials() }}
                                </div>
                                <div style="min-width:0;">
                                    <p style="font-size:13px; font-weight:600; color:#f1f5f9; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px;">{{ $member->name }}</p>
                                    <p style="font-size:11px; color:#64748b; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px;">{{ $member->email }}</p>
                                </div>
                            </div>
                        </td>
                        {{-- Role --}}
                        <td style="padding:14px 16px; font-size:12px; color:#94a3b8;">
                            @if($member->role)
                            <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:600; border:1px solid; border-color:rgba(99,102,241,.3); background:rgba(99,102,241,.1); color:#a5b4fc;">
                                {{ $member->role->name }}
                            </span>
                            @else
                            <span style="color:#475569;">—</span>
                            @endif
                        </td>
                        {{-- Position --}}
                        <td style="padding:14px 16px; font-size:12px; color:#94a3b8;">{{ $member->position ?: '—' }}</td>
                        {{-- Status --}}
                        <td style="padding:14px 16px;">
                            <span class="status-badge status-{{ $member->status }}">{{ $member->statusLabel() }}</span>
                        </td>
                        {{-- Work mode --}}
                        <td style="padding:14px 16px; font-size:12px; color:#94a3b8;">{{ $member->workModeLabel() }}</td>
                        {{-- Updated at --}}
                        <td style="padding:14px 16px; font-size:11px; color:#475569;">{{ $member->updated_at->diffForHumans() }}</td>
                        {{-- Actions --}}
                        <td style="padding:14px 16px; text-align:center;">
                            <div style="display:inline-flex; align-items:center; gap:4px;">
                                <button onclick="openEditModal({{ $member->id }}, {{ $member->toJson() }})"
                                        style="padding:5px; border-radius:7px; border:none; background:rgba(99,102,241,.1); color:#a5b4fc; cursor:pointer;" title="Editar">
                                    <span class="material-symbols-outlined" style="font-size:15px;">edit</span>
                                </button>
                                <form method="POST" action="{{ route('team.members.destroy', $member) }}"
                                      onsubmit="return confirm('¿Eliminar este miembro del equipo?')" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="padding:5px; border-radius:7px; border:none; background:rgba(239,68,68,.1); color:#f87171; cursor:pointer;" title="Eliminar">
                                        <span class="material-symbols-outlined" style="font-size:15px;">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Pending invitations (owner only) --}}
        @if($isOwner && $pendingInvitations->count() > 0)
        <div style="margin-top:1.25rem; background:rgba(99,102,241,0.05); border:1px solid rgba(99,102,241,0.15); border-radius:14px; overflow:hidden;">
            <div style="padding:12px 16px; border-bottom:1px solid rgba(99,102,241,0.1); display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-outlined" style="font-size:16px; color:#818cf8;">schedule_send</span>
                <span style="font-size:12px; font-weight:700; color:#a5b4fc; text-transform:uppercase; letter-spacing:.04em;">
                    Invitaciones pendientes ({{ $pendingInvitations->count() }})
                </span>
            </div>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                        <th style="padding:10px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Correo</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Usuario</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Rol</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Enviada</th>
                        <th style="padding:10px 16px; text-align:center; font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingInvitations as $inv)
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                        <td style="padding:12px 16px; font-size:12px; color:#e2e8f0;">{{ $inv->email }}</td>
                        <td style="padding:12px 16px; font-size:12px; color:#94a3b8;">{{ $inv->invitedUser->name ?? '—' }}</td>
                        <td style="padding:12px 16px; font-size:12px; color:#94a3b8;">
                            @if($inv->role)
                            <span style="padding:2px 8px; border-radius:6px; font-size:11px; font-weight:600; border:1px solid rgba(99,102,241,.3); background:rgba(99,102,241,.1); color:#a5b4fc;">
                                {{ $inv->role->name }}
                            </span>
                            @else —
                            @endif
                        </td>
                        <td style="padding:12px 16px; font-size:11px; color:#475569;">{{ $inv->created_at->diffForHumans() }}</td>
                        <td style="padding:12px 16px; text-align:center;">
                            <span style="display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:10px; font-weight:700; text-transform:uppercase; border:1px solid; background:rgba(245,158,11,.12); color:#fbbf24; border-color:rgba(245,158,11,.3);">
                                Pendiente
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div style="margin-top:1rem;">
            <a href="{{ route('roles.index') }}" style="font-size:12px; color:#818cf8; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                <span class="material-symbols-outlined" style="font-size:14px;">admin_panel_settings</span>
                Gestionar roles y permisos
            </a>
        </div>
    </div>

    {{-- ── ADD/EDIT MODAL ──────────────────────────────────────────────────── --}}
    <div id="member-modal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal()">
        <div class="modal-box">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
                <h2 id="modal-title" style="font-size:15px; font-weight:800; color:#f1f5f9; margin:0;">Agregar miembro</h2>
                <button onclick="closeModal()" style="background:none; border:none; color:#64748b; cursor:pointer; padding:4px;">
                    <span class="material-symbols-outlined" style="font-size:20px;">close</span>
                </button>
            </div>

            <form id="member-form" method="POST" action="{{ route('team.members.store', $proyecto) }}">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div style="grid-column:1/-1;">
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Nombre completo</label>
                        <input type="text" name="name" id="f-name" class="sb-input" style="width:100%;" placeholder="Ej: Juan Pérez">
                        <p style="font-size:10px; color:#475569; margin:4px 0 0;">Requerido solo si el correo no tiene cuenta en el sistema.</p>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Correo electrónico *</label>
                        <input type="email" name="email" id="f-email" required class="sb-input" style="width:100%;" placeholder="juan@empresa.com">
                        <p style="font-size:10px; color:#475569; margin:4px 0 0;">Si el correo tiene cuenta, recibirá una invitación pendiente en su campana de notificaciones.</p>
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Rol</label>
                        <select name="role_id" id="f-role" class="sb-select" style="width:100%;">
                            <option value="">Sin rol asignado</option>
                            @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Cargo / Posición</label>
                        <input type="text" name="position" id="f-position" class="sb-input" style="width:100%;" placeholder="Ej: Backend Dev">
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Estado *</label>
                        <select name="status" id="f-status" class="sb-select" style="width:100%;">
                            <option value="activo">Activo</option>
                            <option value="invitado">Invitado</option>
                            <option value="inactivo">Inactivo</option>
                            <option value="suspendido">Suspendido</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Modo de trabajo</label>
                        <select name="work_mode" id="f-work-mode" class="sb-select" style="width:100%;">
                            <option value="">No especificado</option>
                            <option value="presencial">Presencial</option>
                            <option value="remoto">Remoto</option>
                            <option value="hibrido">Híbrido</option>
                        </select>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Ubicación</label>
                        <input type="text" name="location" id="f-location" class="sb-input" style="width:100%;" placeholder="Ciudad, País">
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Notas</label>
                        <textarea name="notes" id="f-notes" rows="2" class="sb-input" style="width:100%; resize:vertical;" placeholder="Información adicional…"></textarea>
                    </div>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:1.25rem;">
                    <button type="button" onclick="closeModal()"
                            style="padding:8px 18px; border-radius:10px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); color:#94a3b8; font-size:13px; font-weight:600; cursor:pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="modal-submit-btn"
                            style="padding:8px 18px; border-radius:10px; background:#4f46e5; border:none; color:white; font-size:13px; font-weight:700; cursor:pointer;">
                        Agregar miembro
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    function openModal(mode) {
        document.getElementById('member-modal').style.display = 'flex';
        document.getElementById('modal-title').textContent = 'Agregar miembro';
        document.getElementById('modal-submit-btn').textContent = 'Agregar miembro';
        document.getElementById('member-form').action = '{{ route("team.members.store", $proyecto) }}';
        document.getElementById('form-method').value = 'POST';
        ['name','email','position','location','notes'].forEach(function(f){ document.getElementById('f-'+f).value = ''; });
        document.getElementById('f-role').value = '';
        document.getElementById('f-status').value = 'activo';
        document.getElementById('f-work-mode').value = '';
    }

    function openEditModal(id, data) {
        document.getElementById('member-modal').style.display = 'flex';
        document.getElementById('modal-title').textContent = 'Editar miembro';
        document.getElementById('modal-submit-btn').textContent = 'Guardar cambios';
        document.getElementById('member-form').action = '/equipo/miembros/' + id;
        document.getElementById('form-method').value = 'PUT';
        document.getElementById('f-name').value     = data.name     || '';
        document.getElementById('f-email').value    = data.email    || '';
        document.getElementById('f-position').value = data.position || '';
        document.getElementById('f-location').value = data.location || '';
        document.getElementById('f-notes').value    = data.notes    || '';
        document.getElementById('f-role').value     = data.role_id  || '';
        document.getElementById('f-status').value   = data.status   || 'activo';
        document.getElementById('f-work-mode').value= data.work_mode|| '';
    }

    function closeModal() {
        document.getElementById('member-modal').style.display = 'none';
    }

    @if($errors->any())
    openModal('add');
    @endif
    </script>
    @endpush
</x-app-layout>
