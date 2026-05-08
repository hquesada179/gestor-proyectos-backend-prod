<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-300 transition-colors">Dashboard</a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold">Roles y Permisos</span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        .role-card { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:20px; transition:border-color .15s, box-shadow .15s; }
        .role-card:hover { border-color:rgba(255,255,255,0.18); box-shadow:0 8px 30px rgba(0,0,0,.4); }
        .sb-input { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:#f1f5f9; border-radius:10px; font-size:13px; padding:8px 12px; outline:none; transition:border-color .15s; }
        .sb-input:focus { border-color:rgba(99,102,241,0.5); }
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.6); backdrop-filter:blur(4px); z-index:200; display:flex; align-items:center; justify-content:center; }
        .modal-box { background:#0d1c2d; border:1px solid rgba(255,255,255,0.1); border-radius:1.25rem; padding:1.5rem; width:100%; max-width:460px; }
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
        @if(session('error'))
        <div style="background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.25); border-radius:10px; padding:10px 16px; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="font-size:16px;color:#f87171;">error</span>
            <span style="font-size:13px;color:#f87171;">{{ session('error') }}</span>
        </div>
        @endif

        {{-- Header --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
            <div>
                <h1 style="font-size:1.2rem; font-weight:800; color:#f1f5f9; margin:0 0 4px;">Roles y Permisos</h1>
                <p style="font-size:12px; color:#64748b; margin:0;">Define las responsabilidades y niveles de acceso para cada tipo de usuario.</p>
            </div>
            <button onclick="document.getElementById('create-modal').style.display='flex'"
                    style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:10px; background:#4f46e5; color:white; font-size:12px; font-weight:700; border:none; cursor:pointer; transition:background .15s;"
                    onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                <span class="material-symbols-outlined" style="font-size:16px;">add_circle</span>
                Crear nuevo rol
            </button>
        </div>

        @if($roles->isEmpty())
        <div style="text-align:center; padding:4rem; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:14px;">
            <span class="material-symbols-outlined" style="font-size:44px; color:#374151; display:block; margin-bottom:12px;">admin_panel_settings</span>
            <p style="font-size:14px; color:#6b7280; margin:0 0 12px;">No hay roles definidos. Ejecuta el seeder o crea el primero.</p>
        </div>
        @else
        {{-- Roles grid --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1rem;">
            @foreach($roles as $role)
            <div class="role-card">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:10px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:{{ $role->color }}20; border:1px solid {{ $role->color }}40; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <span class="material-symbols-outlined" style="font-size:18px; color:{{ $role->color }};">shield</span>
                        </div>
                        <div>
                            <p style="font-size:14px; font-weight:700; color:#f1f5f9; margin:0;">{{ $role->name }}</p>
                            @if($role->is_system)
                            <span style="font-size:9px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Sistema</span>
                            @endif
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:4px;">
                        <a href="{{ route('roles.show', $role) }}"
                           style="padding:5px; border-radius:7px; background:rgba(99,102,241,.1); color:#a5b4fc; display:flex; align-items:center; text-decoration:none;" title="Ver detalle">
                            <span class="material-symbols-outlined" style="font-size:15px;">visibility</span>
                        </a>
                        @if(!$role->is_system)
                        <form method="POST" action="{{ route('roles.destroy', $role) }}"
                              onsubmit="return confirm('¿Eliminar el rol «{{ $role->name }}»?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" style="padding:5px; border-radius:7px; background:rgba(239,68,68,.1); color:#f87171; border:none; cursor:pointer;" title="Eliminar">
                                <span class="material-symbols-outlined" style="font-size:15px;">delete</span>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @if($role->description)
                <p style="font-size:12px; color:#64748b; margin:0 0 12px; line-height:1.5;">{{ $role->description }}</p>
                @endif
                <div style="display:flex; align-items:center; gap:16px; font-size:11px; color:#475569; padding-top:10px; border-top:1px solid rgba(255,255,255,0.05);">
                    <span style="display:flex; align-items:center; gap:4px;">
                        <span class="material-symbols-outlined" style="font-size:13px;">key</span>
                        {{ $role->permissions_count }} permisos
                    </span>
                    <span style="display:flex; align-items:center; gap:4px;">
                        <span class="material-symbols-outlined" style="font-size:13px;">group</span>
                        {{ $role->members_count }} miembros
                    </span>
                    <a href="{{ route('roles.show', $role) }}" style="margin-left:auto; font-size:11px; color:#818cf8; text-decoration:none; display:flex; align-items:center; gap:2px;">
                        Gestionar <span class="material-symbols-outlined" style="font-size:12px;">arrow_forward</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Create modal --}}
    <div id="create-modal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
        <div class="modal-box">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
                <h2 style="font-size:15px; font-weight:800; color:#f1f5f9; margin:0;">Crear nuevo rol</h2>
                <button onclick="document.getElementById('create-modal').style.display='none'" style="background:none; border:none; color:#64748b; cursor:pointer;">
                    <span class="material-symbols-outlined" style="font-size:20px;">close</span>
                </button>
            </div>
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Nombre del rol *</label>
                        <input type="text" name="name" required class="sb-input" style="width:100%;" placeholder="Ej: Desarrollador Senior">
                        @error('name') <p style="font-size:11px; color:#f87171; margin:4px 0 0;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Descripción</label>
                        <textarea name="description" rows="2" class="sb-input" style="width:100%; resize:vertical;" placeholder="Describe las responsabilidades de este rol…"></textarea>
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#94a3b8; display:block; margin-bottom:4px;">Color</label>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="color" name="color" value="#6366f1" style="width:40px; height:36px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:none; cursor:pointer; padding:2px;">
                            <span style="font-size:11px; color:#64748b;">Elige un color de identificación</span>
                        </div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:1.25rem;">
                    <button type="button" onclick="document.getElementById('create-modal').style.display='none'"
                            style="padding:8px 18px; border-radius:10px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); color:#94a3b8; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                    <button type="submit"
                            style="padding:8px 18px; border-radius:10px; background:#4f46e5; border:none; color:white; font-size:13px; font-weight:700; cursor:pointer;">Crear rol</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    @if($errors->any())
    document.getElementById('create-modal').style.display = 'flex';
    @endif
    </script>
    @endpush
</x-app-layout>
