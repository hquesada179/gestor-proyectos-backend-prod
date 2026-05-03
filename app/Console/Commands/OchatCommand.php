<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\AI\Ochat;
use function Laravel\Prompts\text;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\info;
use function Laravel\Prompts\outro;

#[Signature('ochat')]
#[Description('Chat With Ollama')]
class OchatCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ochat = new Ochat();

        $question = text(label: "Que pregunta tienes a ollama");

        $reponse = spin(fn() => $ochat->send($question), 'Ollama thinking...');

        dd($reponse);

        info($reponse['response'] ?? 'Sin respuesta');

        while($question = text('Te gustaria seguir conversando con ollama (Si/No)')){
            $reponse = spin(fn() => $ochat->send($question), 'Ollama thinking...');
            info($reponse['response'] ?? 'Sin respuesta');
        }
    
        outro('Adios, vuelve pronto');
    }
}
