<?php

namespace App\Services\Ai;

class AiProposalEditorService
{
    public function __construct(
        private readonly AiAssistantService $aiAssistant,
    ) {}

    /**
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function refineProposal(array $proposal, string $instruction, ?string $model = null): array
    {
        return $this->aiAssistant->generate($this->buildRefinePrompt($proposal, $instruction), $model);
    }

    /**
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function regenerateSection(array $proposal, string $section, ?string $instruction = null, ?string $model = null): array
    {
        return $this->aiAssistant->generate($this->buildSectionPrompt($proposal, $section, $instruction), $model);
    }

    /**
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function regenerateItem(array $proposal, string $section, array $item, ?string $instruction = null, ?string $model = null): array
    {
        return $this->aiAssistant->generate($this->buildItemPrompt($proposal, $section, $item, $instruction), $model);
    }

    private function buildRefinePrompt(array $proposal, string $instruction): string
    {
        $json = json_encode($proposal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $tipo = $proposal['_tipo'] ?? 'create';
        $sectionKeys = $tipo === 'improve'
            ? ['requerimientos_nuevos', 'tareas_nuevas', 'sprints_nuevos', 'insumos_nuevos']
            : ['requerimientos', 'tareas', 'sprints', 'insumos'];

        $counts = array_map(
            fn($k) => "{$k}: " . count($proposal[$k] ?? []),
            $sectionKeys
        );
        $countsLine = implode(', ', $counts);

        return <<<PROMPT
Eres un asistente experto en gestion de proyectos. Edita el siguiente borrador JSON segun la instruccion del usuario.

CONTEO ACTUAL DE ELEMENTOS EN EL BORRADOR ({$countsLine})

BORRADOR ACTUAL JSON:
{$json}

INSTRUCCION DEL USUARIO:
{$instruction}

REGLAS CRITICAS — cumplelas todas sin excepcion:
1. Devuelve UNICAMENTE JSON valido. Sin markdown, sin bloques ```json, sin texto adicional, sin comentarios.
2. Conserva la misma estructura de claves del borrador original, incluyendo _tipo, _proyecto_id y cualquier campo que comience con guion bajo.
3. CAMPO _id: Si un elemento existente tiene campo "_id", conservalo EXACTAMENTE igual en tu respuesta. Los elementos nuevos que agregues NO deben tener campo "_id".
4. CONSERVACION DE DATOS: Conserva TODOS los elementos existentes en cada seccion, salvo que el usuario pida explicitamente eliminar alguno.
5. AGREGAR ELEMENTOS: Si el usuario pide agregar N elementos nuevos a una seccion, el array resultante debe tener (cantidad_actual + N) elementos en total.
   - Los nuevos van DESPUES de los existentes. No modifiques ni borres los existentes.
   - Ejemplo: si hay 7 tareas y el usuario pide "agrega 15 tareas nuevas", el resultado debe tener 22 tareas (las 7 originales intactas mas 15 nuevas).
6. ELIMINAR ELEMENTOS: Si el usuario pide eliminar elementos especificos, elimina solo esos y conserva todos los demas.
7. MODIFICAR SECCION: Si el usuario pide cambiar solo una seccion, no modifiques el contenido de las otras secciones del borrador.
8. Cada elemento nuevo debe incluir todos los campos requeridos, ser coherente con el proyecto y estar en espanol.
9. No cambies nombre ni descripcion del proyecto a menos que el usuario lo pida.

Responde SOLO con el objeto JSON completo actualizado:
PROMPT;
    }

    private function buildSectionPrompt(array $proposal, string $section, ?string $instruction): string
    {
        $json = json_encode($proposal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $extra = $instruction ? "Instruccion adicional: {$instruction}" : 'Sin instruccion adicional.';

        return <<<PROMPT
Eres un asistente experto en gestion de proyectos. Regenera solo la seccion "{$section}" del borrador.

{$extra}

BORRADOR ACTUAL JSON:
{$json}

Devuelve UNICAMENTE este JSON:
{
  "{$section}": []
}

La clave "{$section}" debe contener elementos completos, coherentes y en espanol.
No modifiques ni devuelvas otras secciones. No uses markdown.
PROMPT;
    }

    private function buildItemPrompt(array $proposal, string $section, array $item, ?string $instruction): string
    {
        $proposalJson = json_encode($proposal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $itemJson = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $extra = $instruction ? "Instruccion adicional: {$instruction}" : 'Sin instruccion adicional.';

        return <<<PROMPT
Eres un asistente experto en gestion de proyectos. Regenera solo un elemento de la seccion "{$section}".

{$extra}

BORRADOR ACTUAL JSON:
{$proposalJson}

ELEMENTO ACTUAL JSON:
{$itemJson}

Devuelve UNICAMENTE este JSON:
{
  "item": {}
}

El objeto "item" debe conservar los campos esperados para la seccion "{$section}" y estar en espanol.
No uses markdown.
PROMPT;
    }
}
