<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatController extends Controller
{


    public function sendMessage(Request $request)
    {
        set_time_limit(0); // Prevent PHP from timing out while waiting for Ollama
        
        $request->validate([
            'message' => 'required|string'
        ]);

        try {
            $ochat = new \App\AI\Ochat();
            $response = $ochat->send($request->message);
        } catch (\Exception $e) {
            return response()->json([
                'response' => 'Error: La IA tardó demasiado en responder o está apagada. Detalles: ' . $e->getMessage()
            ]);
        }
        $aiText = $response['response'] ?? '';
        
        if (empty($aiText)) {
            return response()->json(['response' => 'Error: La IA no devolvió ninguna respuesta.']);
        }

        // Limpiar el JSON por si la IA incluye bloques de código markdown
        $aiText = str_replace(['```json', '```'], '', $aiText);
        $data = json_decode(trim($aiText), true);

        if (!$data) {
            return response()->json(['response' => 'La IA no devolvió un JSON válido. Respuesta cruda: <br><pre class="text-xs text-gray-500 mt-2">' . htmlspecialchars($aiText) . '</pre>']);
        }

        if (isset($data['tipo']) && $data['tipo'] === 'chat') {
            return response()->json(['response' => $data['mensaje'] ?? 'Hola, soy tu asistente.']);
        }

        if (!isset($data['nombre'])) {
            return response()->json(['response' => 'Faltan datos del proyecto en la respuesta. Respuesta cruda: <br><pre class="text-xs text-gray-500 mt-2">' . htmlspecialchars($aiText) . '</pre>']);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $proyecto = \App\Models\Proyecto::create([
                'user_id' => auth()->id() ?? 1, // Fallback
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? 'Proyecto generado por Inteligencia Artificial.',
                'estado' => 'activo',
                'fecha_inicio' => now(),
            ]);

            $reqCount = 0;
            if (isset($data['requirements']) && is_array($data['requirements'])) {
                foreach ($data['requirements'] as $req) {
                    $proyecto->requirements()->create([
                        'titulo' => $req['titulo'] ?? 'Requerimiento sin título',
                        'descripcion' => $req['descripcion'] ?? '',
                        'tipo' => $req['tipo'] ?? 'funcional',
                        'prioridad' => 'media',
                    ]);
                    $reqCount++;
                }
            }

            $taskCount = 0;
            if (isset($data['tasks']) && is_array($data['tasks'])) {
                // Obtener el primer status o crear uno por defecto si la BD está vacía
                $status = \App\Models\TaskStatus::first();
                if (!$status) {
                    $status = \App\Models\TaskStatus::create([
                        'nombre' => 'To Do',
                        'color' => '#808080',
                        'orden' => 1
                    ]);
                }
                $statusId = $status->id;
                
                foreach ($data['tasks'] as $task) {
                    $proyecto->tasks()->create([
                        'titulo' => $task['titulo'] ?? 'Tarea sin título',
                        'descripcion' => $task['descripcion'] ?? '',
                        'task_status_id' => $statusId, 
                    ]);
                    $taskCount++;
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            $url = route('scrum-board.show', $proyecto);
            $msg = "<strong>¡Proyecto '{$proyecto->nombre}' generado con éxito!</strong><br><br>";
            $msg .= "✅ Se crearon <strong>{$reqCount}</strong> requerimientos.<br>";
            $msg .= "✅ Se crearon <strong>{$taskCount}</strong> tareas.<br><br>";
            $msg .= "<a href='{$url}' class='inline-block mt-2 bg-secondary-container text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg hover:opacity-90 transition-all'>Ir al Tablero Scrum</a>";

            return response()->json(['response' => $msg]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['response' => 'Ocurrió un error en la base de datos al guardar el proyecto: ' . $e->getMessage()]);
        }
    }
    public function index()
    {
        return view('proyectos.chat');
    }
}
