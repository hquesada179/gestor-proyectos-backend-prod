<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AiResponseParserService
{
    /**
     * Parse the AI's interpretation response (Phase 1–2).
     *
     * Accepts either a pre-decoded array or a raw string.
     * Normalizes Spanish and English key aliases.
     *
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function parseInterpretation(mixed $raw): array
    {
        $data = $this->toArray($raw);

        if ($data === null) {
            Log::warning('AiResponseParserService@parseInterpretation: failed to extract JSON', [
                'raw_type'    => gettype($raw),
                'raw_snippet' => is_string($raw) ? substr($raw, 0, 300) : json_encode($raw),
            ]);

            return [
                'ok'    => false,
                'error' => 'La IA no devolvió un JSON reconocible. Puedes copiar la respuesta cruda y revisarla.',
                'raw'   => is_string($raw) ? $raw : json_encode($raw),
            ];
        }

        $result = [
            'nombre_sugerido'           => $this->str($data, ['nombre_sugerido', 'nombre', 'name', 'project_name', 'titulo', 'title'], 'Proyecto sin nombre'),
            'descripcion_mejorada'      => $this->str($data, ['descripcion_mejorada', 'descripcion', 'description', 'resumen', 'summary', 'about'], ''),
            'modulos_detectados'        => $this->arr($data, ['modulos_detectados', 'modulos', 'modules', 'features', 'components', 'caracteristicas'], []),
            'tecnologias_sugeridas'     => $this->arr($data, ['tecnologias_sugeridas', 'tecnologias', 'technologies', 'tech_stack', 'stack', 'tools'], []),
            'complejidad'               => $this->enum($data, ['complejidad', 'complexity', 'nivel'], ['baja', 'media', 'alta'], 'media'),
            'duracion_semanas_estimada' => $this->int($data, ['duracion_semanas_estimada', 'duracion_semanas', 'duration_weeks', 'weeks', 'duracion', 'semanas'], 8),
            'tipo_proyecto'             => $this->enum($data, ['tipo_proyecto', 'tipo', 'type', 'project_type', 'category'], ['web', 'movil', 'escritorio', 'api', 'mixto'], 'web'),
        ];

        // Safe fallbacks so the UI never breaks
        if (empty($result['modulos_detectados'])) {
            $result['modulos_detectados'] = ['Módulo principal'];
        }

        if (empty($result['descripcion_mejorada'])) {
            $result['descripcion_mejorada'] = 'Sistema de software a desarrollar según los requerimientos del usuario.';
        }

        return ['ok' => true, 'data' => $result];
    }

    /**
     * Parse requirements response (Phase 4).
     *
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function parseRequirements(mixed $raw): array
    {
        $data = $this->toArray($raw);

        if ($data === null) {
            return $this->failParse('requerimientos', $raw);
        }

        $functional    = $this->arr($data, ['requerimientos_funcionales', 'functional', 'requirements', 'funcionales', 'rf'], []);
        $nonFunctional = $this->arr($data, ['requerimientos_no_funcionales', 'non_functional', 'no_funcionales', 'rnf'], []);

        return [
            'ok'   => true,
            'data' => [
                'requerimientos_funcionales'    => $functional,
                'requerimientos_no_funcionales' => $nonFunctional,
            ],
        ];
    }

    /**
     * Parse tasks response (Phase 5).
     *
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function parseTasks(mixed $raw): array
    {
        $data = $this->toArray($raw);

        if ($data === null) {
            return $this->failParse('tareas', $raw);
        }

        $tasks = $this->arr($data, ['tareas', 'tasks', 'task_list', 'items'], []);

        return ['ok' => true, 'data' => ['tareas' => $tasks]];
    }

    /**
     * Parse sprints response (Phase 6).
     *
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function parseSprints(mixed $raw): array
    {
        $data = $this->toArray($raw);

        if ($data === null) {
            return $this->failParse('sprints', $raw);
        }

        $sprints = $this->arr($data, ['sprints', 'sprint_list', 'iteraciones'], []);

        return ['ok' => true, 'data' => ['sprints' => $sprints]];
    }

    /**
     * Parse supplies response (Phase 7).
     *
     * @return array{ok: bool, data?: array, error?: string, raw?: string}
     */
    public function parseSupplies(mixed $raw): array
    {
        $data = $this->toArray($raw);

        if ($data === null) {
            return $this->failParse('insumos', $raw);
        }

        $supplies = $this->arr($data, ['insumos', 'supplies', 'inputs', 'resources', 'recursos'], []);

        return ['ok' => true, 'data' => ['insumos' => $supplies]];
    }

    /**
     * Robustly extract valid JSON from a string that may have extra text around it.
     * Handles markdown fences, leading text, and trailing text.
     */
    public function extractJson(string $text): ?array
    {
        // Strip markdown code fences
        $text = preg_replace('/```(?:json)?\s*/', '', $text);
        $text = preg_replace('/```/', '', $text);
        $text = trim($text);

        // Direct parse
        if ($decoded = $this->tryDecode($text)) {
            return $decoded;
        }

        // Find and extract outermost JSON object
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            if ($decoded = $this->tryDecode(substr($text, $start, $end - $start + 1))) {
                return $decoded;
            }
        }

        // Find and extract outermost JSON array
        $start = strpos($text, '[');
        $end   = strrpos($text, ']');
        if ($start !== false && $end !== false && $end > $start) {
            if ($decoded = $this->tryDecode(substr($text, $start, $end - $start + 1))) {
                return is_array($decoded) ? ['items' => $decoded] : null;
            }
        }

        Log::warning('AiResponseParserService: could not extract JSON', [
            'snippet' => substr($text, 0, 300),
        ]);

        return null;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function toArray(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            return $this->extractJson($value);
        }
        return null;
    }

    private function tryDecode(string $text): ?array
    {
        $decoded = json_decode($text, true);
        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : null;
    }

    private function str(array $data, array $keys, string $default): string
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && trim($data[$key]) !== '') {
                return trim($data[$key]);
            }
        }
        return $default;
    }

    private function arr(array $data, array $keys, array $default): array
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_array($data[$key]) && count($data[$key]) > 0) {
                return array_values($data[$key]);
            }
        }
        return $default;
    }

    private function enum(array $data, array $keys, array $allowed, string $default): string
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $val = strtolower(trim((string) $data[$key]));
                if (in_array($val, $allowed, true)) {
                    return $val;
                }
            }
        }
        return $default;
    }

    private function int(array $data, array $keys, int $default): int
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                $val = (int) $data[$key];
                if ($val > 0) {
                    return $val;
                }
            }
        }
        return $default;
    }

    private function failParse(string $context, mixed $raw): array
    {
        Log::warning("AiResponseParserService: failed to parse {$context}", [
            'raw_snippet' => is_string($raw) ? substr($raw, 0, 300) : json_encode($raw),
        ]);

        return [
            'ok'    => false,
            'error' => "No se pudieron extraer los {$context} de la respuesta de la IA.",
            'raw'   => is_string($raw) ? $raw : json_encode($raw),
        ];
    }
}
