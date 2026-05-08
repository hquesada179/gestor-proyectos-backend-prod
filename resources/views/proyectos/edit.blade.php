<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('proyectos.index') }}" class="text-gray-500 hover:text-gray-300 transition-colors">Proyectos</a>
            <span class="text-gray-600">›</span>
            <a href="{{ route('proyectos.show', $proyecto) }}" class="text-gray-500 hover:text-gray-300 transition-colors truncate max-w-[200px]">{{ $proyecto->nombre }}</a>
            <span class="text-gray-600">›</span>
            <span class="text-white font-semibold">Editar</span>
        </div>
    </x-slot>

    <style>
        .form-input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #e2e4ec;
            font-size: 0.875rem;
            border-radius: 0.75rem;
            padding: 0.625rem 0.875rem;
            outline: none;
            transition: border-color .2s, background .2s, box-shadow .2s;
            appearance: none;
            -webkit-appearance: none;
        }
        .form-input::placeholder { color: #3f424e; }
        .form-input:hover { border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.07); }
        .form-input:focus { border-color: #7c6af7; background: rgba(124,106,247,0.07); box-shadow: 0 0 0 3px rgba(124,106,247,0.15); }
        select.form-input option { background: #1a1a2e; color: #e2e4ec; }
        textarea.form-input { resize: none; }
    </style>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel rounded-2xl overflow-hidden shadow-2xl border border-white/5">

                <div class="bg-surface/50 p-6 border-b border-white/10">
                    <h3 class="text-xl font-bold text-white">Editar Proyecto</h3>
                    <p class="text-sm text-on-surface-variant mt-1">Modifica la información del proyecto «{{ $proyecto->nombre }}».</p>
                </div>

                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('proyectos.update', $proyecto) }}"
                          enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        {{-- Nombre --}}
                        <div>
                            <label for="nombre" class="block mb-2 text-sm font-medium text-gray-300">
                                Nombre del proyecto <span class="text-red-400">*</span>
                            </label>
                            <input id="nombre" name="nombre" type="text"
                                class="form-input"
                                value="{{ old('nombre', $proyecto->nombre) }}"
                                required autofocus />
                            @error('nombre') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        {{-- Descripción --}}
                        <div>
                            <label for="descripcion" class="block mb-2 text-sm font-medium text-gray-300">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="4"
                                class="form-input"
                                placeholder="Describe brevemente los objetivos y el alcance del proyecto...">{{ old('descripcion', $proyecto->descripcion) }}</textarea>
                            @error('descripcion') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        {{-- Estado --}}
                        <div>
                            <label for="estado" class="block mb-2 text-sm font-medium text-gray-300">Estado</label>
                            <select id="estado" name="estado" class="form-input" style="color:#000000;">
                                @foreach (['activo','pausado','completado','cancelado'] as $opcion)
                                    <option value="{{ $opcion }}" {{ old('estado', $proyecto->estado) === $opcion ? 'selected' : '' }}>
                                        {{ ucfirst($opcion) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('estado') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                        </div>

                        {{-- Fechas --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="fecha_inicio" class="block mb-2 text-sm font-medium text-gray-300">Fecha de inicio</label>
                                <input id="fecha_inicio" name="fecha_inicio" type="date"
                                    class="form-input [color-scheme:dark]"
                                    value="{{ old('fecha_inicio', $proyecto->fecha_inicio?->format('Y-m-d')) }}" />
                                @error('fecha_inicio') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="fecha_fin_estimada" class="block mb-2 text-sm font-medium text-gray-300">Fecha estimada de cierre</label>
                                <input id="fecha_fin_estimada" name="fecha_fin_estimada" type="date"
                                    class="form-input [color-scheme:dark]"
                                    value="{{ old('fecha_fin_estimada', $proyecto->fecha_fin_estimada?->format('Y-m-d')) }}" />
                                @error('fecha_fin_estimada') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- ── Imagen de portada ─────────────────────────────────── --}}
                        @php
                            $colors = [['#6d28d9','#818cf8'],['#1e40af','#38bdf8'],['#065f46','#34d399'],['#9f1239','#f472b6'],['#92400e','#fb923c'],['#6b21a8','#c084fc'],['#0c4a6e','#60a5fa'],['#14532d','#86efac']];
                            $pc = $colors[$proyecto->id % 8];
                            $hasCover = !empty($proyecto->cover_image);
                        @endphp
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-300">
                                Imagen de portada
                                <span class="text-gray-500 font-normal">(opcional)</span>
                            </label>

                            {{-- Preview area --}}
                            <div id="cover-preview-wrap"
                                 style="position:relative; width:100%; height:150px; border-radius:12px; overflow:hidden;
                                        background:linear-gradient(135deg,{{ $pc[0] }},{{ $pc[1] }});
                                        margin-bottom:10px; cursor:pointer;"
                                 onclick="document.getElementById('cover_image_input').click()"
                                 title="Haz clic para cambiar imagen">

                                {{-- Current or new preview image --}}
                                <img id="cover-preview-img"
                                     src="{{ $hasCover ? asset('storage/'.$proyecto->cover_image) : '' }}"
                                     alt="Portada del proyecto"
                                     style="width:100%;height:100%;object-fit:cover;display:{{ $hasCover ? 'block' : 'none' }};">

                                {{-- Overlay with camera icon --}}
                                <div id="cover-preview-overlay"
                                     style="position:absolute;inset:0;display:flex;flex-direction:column;
                                            align-items:center;justify-content:center;gap:6px;
                                            background:{{ $hasCover ? 'rgba(0,0,0,0.0)' : 'rgba(0,0,0,0.25)' }};
                                            transition:background .15s;"
                                     onmouseover="this.style.background='rgba(0,0,0,0.45)';this.querySelector('.ov-icon').style.opacity='1';this.querySelector('.ov-txt').style.opacity='1';"
                                     onmouseout="this.style.background='{{ $hasCover ? 'rgba(0,0,0,0)' : 'rgba(0,0,0,0.25)' }}';this.querySelector('.ov-icon').style.opacity='{{ $hasCover ? '0' : '0.7' }}';this.querySelector('.ov-txt').style.opacity='{{ $hasCover ? '0' : '0.7' }}';">
                                    <span class="material-symbols-outlined ov-icon" style="font-size:28px;color:white;opacity:{{ $hasCover ? '0' : '0.7' }};transition:opacity .15s;">photo_camera</span>
                                    <span class="ov-txt" style="font-size:11px;color:white;font-weight:600;opacity:{{ $hasCover ? '0' : '0.7' }};transition:opacity .15s;">{{ $hasCover ? 'Clic para cambiar' : 'Clic para subir imagen' }}</span>
                                </div>
                            </div>

                            <input type="file" id="cover_image_input" name="cover_image"
                                   accept="image/jpeg,image/jpg,image/png,image/webp"
                                   style="display:none;"
                                   onchange="previewCoverImage(this, 'cover-preview-img', 'cover-preview-overlay')">

                            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                <p style="font-size:10px;color:#475569;margin:0;">JPG, PNG o WEBP · Máximo 5 MB</p>
                                @if($hasCover)
                                <label style="display:flex;align-items:center;gap:5px;font-size:11px;color:#f87171;cursor:pointer;">
                                    <input type="checkbox" name="remove_cover_image" value="1" style="accent-color:#f87171;">
                                    Eliminar imagen actual
                                </label>
                                @endif
                            </div>
                            @error('cover_image')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-white/10 mt-8">
                            <a href="{{ route('proyectos.show', $proyecto) }}"
                               class="flex items-center gap-2 bg-surface border border-white/10 text-gray-400 px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-white/5 transition-all active:scale-95">
                                Cancelar
                            </a>
                            <button type="submit"
                               class="flex items-center gap-2 bg-secondary-container text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:opacity-90 transition-all active:scale-95 shadow-lg shadow-secondary-container/20">
                                <span class="material-symbols-outlined" style="font-size:18px;">save</span>
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function previewCoverImage(input, imgId, overlayId) {
        if (!input.files || !input.files[0]) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById(imgId);
            var ov  = document.getElementById(overlayId);
            if (img) { img.src = e.target.result; img.style.display = 'block'; }
            if (ov)  { ov.style.background = 'rgba(0,0,0,0)'; }
            var icons = ov ? ov.querySelectorAll('.ov-icon,.ov-txt') : [];
            icons.forEach(function(el){ el.style.opacity = '0'; });
        };
        reader.readAsDataURL(input.files[0]);
    }
    </script>
    @endpush
</x-app-layout>
