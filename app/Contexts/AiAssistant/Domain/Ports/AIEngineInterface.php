<?php

namespace App\Contexts\AiAssistant\Domain\Ports;

/**
 * Puerto de salida agnóstico para interactuar con proveedores de LLM (OpenAI, Ollama, etc.).
 */
interface AIEngineInterface
{
    /**
     * Envía una instrucción al modelo y recibe la respuesta estructurada o texto generado.
     *
     * @param  string  $systemPrompt  Instrucciones del sistema y esquema esperado.
     * @param  string  $userPrompt  Insumo o mensaje del usuario.
     * @param  array  $options  Opciones como temperature, model, etc.
     * @return array Datos parseados de la respuesta del modelo.
     */
    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array;
}
