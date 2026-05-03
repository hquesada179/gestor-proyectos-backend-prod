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

        $ochat = new \App\AI\Ochat();
        $response = $ochat->send($request->message);

        return response()->json([
            'response' => $response['response'] ?? 'Sin respuesta de la IA.',
        ]);
    }
    public function index()
    {
        return view('proyectos.chat');
    }
}
