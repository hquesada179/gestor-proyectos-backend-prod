<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition-colors">Dashboard</a>
            <span class="text-gray-600">›</span>
            <a href="{{ route('roles.index') }}" class="text-gray-500 hover:text-gray-300 transition-colors">Roles</a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold">{{ $role->name }}</span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        .perm-check { width:18px; height:18px; border-radius:5px; accent-color:#6366f1; cursor:pointer; }
        .module-row { padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.05); display:grid; align-items:center; gap:12px; }
        .module-row:last-child { border-bottom:none; }
        .sb-input { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:#f1f5f9; border-radius:10px; font-size:13px; padding:8px 12px; outline:none; }
        .sb-input:focus { border-color:rgba(99,102,241,0.5); }
    </style>
    @endpush

    <div style="padding:1.5rem; display:grid; grid-template-columns:1fr 300px; gap:1.5rem; align-items:start;">

        {{-- Left: Permissions --}}
        <div>
            {{-- Flash --}}
            @if(session('success'))
            <div style="background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.25); border-radius:10px; padding:10px 16px; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-outlined" style="font-size:16px;color:#34d399;">check_circle</span>
                <span style="font-size:13px;color:#34d399;">{{ session('success') }}</span>
            </div>
            @endif

            <div style="display:flex; align-items:center; gap:12px; margin-bottom:1.25rem;">
                <div style="width:40px; height:40px; border-radius:10px; background:{{ $role->color }}25; border:1px solid {{ $role->color }}40; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <span class="material-symbols-outlined" style="font-size:20px; color:{{ $role->color }};">shield</span>
                </div>
                <div>
                    <h1 style="font-size:1.1rem; font-weight:800; color:#f1f5f9; margin:0;">{{ $role->name }}</h1>
                    <p style="font-size:12px; color:#64748b; margin:0;">{{ $role->description ?: 'Sin descripción.' }}</p>
                </div>
                @if(!$role->is_system)
                <button onclick="document.getElementById('edit-modal').style.display='flex'"
                        style="margin-left:auto; padding:7px 14px; border-radius:9px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); color:#94a3b8; font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">edit</span> Editar
                </button>
                @endif
            </div>

            {{-- Permissions by module --}}
            <form method="POST" action="{{ route('roles.permissions.update', $role) }}">
                @csrf @method('PUT')

                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px; overflow:hidden;">
                    {{-- Table header --}}
                    <div style="display:grid; grid-template-columns:200px repeat(5,1fr); gap:8px; padding:12px 20px; border-bottom:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02);">
                        <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em;">Módulo</div>
                        @foreach(['ver','crear','editar','eliminar','administrar'] as $action)
                        <div style="font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; text-align:center;">{{ ucfirst($action) }}</div>
                        @endforeach
                    </div>

                    @foreach($modules as $moduleKey => $moduleLabel)
                    <div class="module-row" style="grid-template-columns:200px repeat(5,1fr); padding:10px 20px;">
                        <span style="font-size:13px; color:#94a3b8; font-weight:500;">{{ $moduleLabel }}</span>
                        @foreach($actions as $action)
                        @php
                            $perm = $permissions->get($moduleKey)?->firstWhere('action', $action);
                        @endphp
                        <div style="display:flex; justify-content:center;">
                            @if($perm)
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $perm->id }}"
                                   class="perm-check"
                                   {{ in_array($perm->id, $rolePermIds) ? 'checked' : '' }}>
                            @else
                            <span style="color:#374151; font-size:12px;">—</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:1rem;">
                    <button type="button" onclick="toggleAll(true)"
                            style="font-size:12px; color:#818cf8; background:none; border:none; cursor:pointer;">Marcar todos</button>
                    <div style="display:flex; gap:10px;">
                        <button type="button" onclick="toggleAll(false)"
                                style="font-size:12px; color:#64748b; background:none; border:none; cursor:pointer;">Desmarcar todos</button>
                        <button type="submit"
                                style="padding:8px 20px; border-radius:10px; background:#4f46e5; border:none; color:white; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px;">
                            <span class="material-symbols-outlined" style="font-size:15px;">save</span>
                            Guardar permisos
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Right: Members sidebar --}}
        <div>
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:16px;">
                <p style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin:0 0 12px;">
                    Miembros con este rol ({{ $role->members->count() }})
                </p>
                @if($role->members->isEmpty())
                <p style="font-size:12px; color:#475569; text-align:center; padding:16px 0;">Ningún miembro asignado.</p>
                @else
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach($role->members->take(10) as $m)
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,{{ $role->color }}60,{{ $role->color }}); display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:800; color:white; flex-shrink:0;">
                            {{ $m->initials() }}
                        </div>
                        <div style="min-width:0;">
                            <p style="font-size:12px; font-weight:600; color:#f1f5f9; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $m->name }}</p>
                            <p style="font-size:10px; color:#64748b; margin:0;">{{ $m->proyecto->nombre }}</p>
                        </div>
                    </div>
                    @endforeach
                    @if($role->members->count() > 10)
                    <p style="font-size:11px; color:#475569; text-align:center; margin:4px 0 0;">y {{ $role->members->count() - 10 }} más…</p>
                    @endif
                </div>
                @endif
            </div>

            <div style="margin-top:12px;">
                <a href="{{ route('roles.index') }}"
                   style="display:inline-flex; align-items:center; gap:4px; font-size:12px; color:#64748b; text-decoration:none;"
                   onmouseover="this.style.color='#9ca3af'" onmouseout="this.style.color='#64748b'">
                    <span class="material-symbols-outlined" style="font-size:14px;">arrow_back</span>
                    Volver a roles
                </a>
            </div>
        </div>
    </div>

    {{-- Edit role modal --}}
    @if(!$role->is_system)
    <div id="edit-modal" style="position:fixed; inset:0; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); z-index:200; display:none; align-items:center; justify-content:center;"
         onclick="if(event.target===this)this.style.display='none'">
        <div style="background:#0d1c2d; border:1px solid rgba(255,255,255,0.1); border-radius:1.25rem; padding:1.5rem; width:100%; max-width:440px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
                <h2 style="font-size:15px; font-weight:800; color:#f1f5f9; margin:0;">Editar rol</h2>
                <button onclick="document.getElementById('edit-modal').style.display='none'" style="background:none; border:none; color:#64748b; cursor:pointer;">
                    <span class="material-symbols-outlined" style="font-size:20px;">close</span>
                </button>
            </div>
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf @method('PUT')
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Nombre del rol *</label>
                        <input type="text" name="name" value="{{ $role->name }}" required class="sb-input" style="width:100%;">
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Descripción</label>
                        <textarea name="description" rows="2" class="sb-input" style="width:100%; resize:vertical;">{{ $role->description }}</textarea>
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Color</label>
                        <input type="color" name="color" value="{{ $role->color }}" style="width:40px; height:36px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:none; cursor:pointer; padding:2px;">
                    </div>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:1.25rem;">
                    <button type="button" onclick="document.getElementById('edit-modal').style.display='none'"
                            style="padding:8px 18px; border-radius:10px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); color:#94a3b8; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                    <button type="submit"
                            style="padding:8px 18px; border-radius:10px; background:#4f46e5; border:none; color:white; font-size:13px; font-weight:700; cursor:pointer;">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @push('scripts')
    <script>
    function toggleAll(checked) {
        document.querySelectorAll('.perm-check').forEach(function(c){ c.checked = checked; });
    }
    </script>
    @endpush
</x-app-layout>
