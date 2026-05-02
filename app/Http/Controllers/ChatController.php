<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatController extends Controller
{


    public function __invoke(Request $request)
    {
        // ✅ Puedes validar los datos si quieres
        // $request->validate([...]);
        
        // ✅ Puedes acceder a cualquier campo del formulario
        $nombre = $request->input('nombre_proyecto');
        $descripcion = $request->input('descripcion');
        
        // ✅ Aquí guardas en la base de datos
        // $proyecto = \App\Models\Proyecto::create([...]);
        
        // ✅ Aquí llamas a la API de OpenAI
        // $respuestaIA = \Illuminate\Support\Facades\Http::post('https://api.openai.com/v1/chat/completions', [...]);
        

    }
    public function index()
    {
        return view('proyectos.chat');
    }
}
