<?php

namespace App\AI;

use Cloudstudio\Ollama\Facades\Ollama;

class Ochat {

    public function send($message){
        $response = Ollama::model('qwen3.5')
        ->prompt($message)
        ->options(['temperature' => 0.8])
        ->stream(false)
        ->ask();
        
        return $response;
    }

}