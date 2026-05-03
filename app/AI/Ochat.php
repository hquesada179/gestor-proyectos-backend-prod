<?php

namespace App\AI;

use Cloudstudio\Ollama\Facades\Ollama;

class Ochat {

    public function send($message){
        $systemPrompt = "You are an expert Software Architect and Project Assistant. " .
                        "Analyze the user's input. If the input its no a project just prompt normally. If the user wants to create or scaffold a new project, output a JSON with this exact structure: " .
                        '{ "tipo": "proyecto", "nombre": "Project Name", "descripcion": "Description", "requirements": [{"titulo": "Req 1", "descripcion": "...", "tipo": "funcional"}], "tasks": [{"titulo": "Task 1", "descripcion": "..."}] }. ' .
                        "If the user is just saying hello, asking a question, or the prompt is NOT about creating a project, output a JSON with this exact structure: " .
                        '{ "tipo": "chat", "mensaje": "Your conversational response here" }. ' .
                        "You MUST output STRICTLY valid JSON and nothing else. Do not use markdown blocks.";

        $response = Ollama::model('phi3')
        ->prompt($systemPrompt . "\n\nUser Idea: " . $message)
        ->options(['temperature' => 0.2])
        ->stream(false)
        ->ask();
        
        return $response;
    }

}