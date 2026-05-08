<?php

namespace App\Services;

class AiPromptBuilderService
{
    /**
     * Returns the language name for the current locale to inject into prompts.
     */
    private function langInstruction(): string
    {
        $locale = app()->getLocale();
        $names = [
            'es'    => 'español',
            'en'    => 'English',
            'fr'    => 'français',
            'pt'    => 'português',
            'de'    => 'Deutsch',
            'it'    => 'italiano',
            'zh_CN' => '中文简体',
        ];
        $lang = $names[$locale] ?? 'español';

        return "LANGUAGE INSTRUCTION: Always respond in {$lang}. Do not mix languages. All titles, descriptions, requirements, tasks, sprints, supplies and recommendations must be written in {$lang}. If the user writes in a different language, still respond in {$lang}.\n\n";
    }

    /**
     * Phase 1–2: Ask the model to interpret the user's raw idea.
     * Returns a compact JSON with project metadata and suggested modules.
     */
    public function interpretationPrompt(string $userIdea): string
    {
        $lang = $this->langInstruction();

        return <<<PROMPT
{$lang}Eres un experto en gestión de proyectos de software. Analiza la siguiente idea y responde ÚNICAMENTE con un objeto JSON válido. No incluyas texto antes ni después del JSON, sin markdown, sin explicaciones.

IDEA DEL USUARIO:
{$userIdea}

Responde con esta estructura JSON exacta (sin añadir ni quitar campos):
{
  "nombre_sugerido": "Nombre descriptivo del proyecto en 3 a 6 palabras",
  "descripcion_mejorada": "Descripción profesional del proyecto en 2 a 3 oraciones claras",
  "modulos_detectados": ["Módulo A", "Módulo B", "Módulo C"],
  "tecnologias_sugeridas": ["Tecnología 1", "Tecnología 2"],
  "complejidad": "media",
  "duracion_semanas_estimada": 8,
  "tipo_proyecto": "web"
}

REGLAS ESTRICTAS:
- complejidad: exactamente "baja", "media" o "alta"
- tipo_proyecto: exactamente "web", "movil", "escritorio", "api" o "mixto"
- modulos_detectados: array de 2 a 8 strings
- tecnologias_sugeridas: array de 1 a 5 strings
- duracion_semanas_estimada: número entero positivo
- Solo el JSON, en el idioma indicado
PROMPT;
    }

    /**
     * Unified assistant – CREATE mode.
     * Returns a full project draft (nombre, descripcion, requerimientos, tareas, sprints, insumos).
     */
    public function createProjectPrompt(string $userIdea): string
    {
        $lang = $this->langInstruction();

        return <<<PROMPT
{$lang}Eres un analista de software experto en metodologías ágiles. Analiza la siguiente idea y genera un borrador completo de proyecto. Responde ÚNICAMENTE con un objeto JSON válido, sin texto adicional.

IDEA DEL PROYECTO:
{$userIdea}

Usa exactamente esta estructura JSON:
{
  "nombre": "Nombre descriptivo en 3-6 palabras",
  "descripcion": "Descripción profesional clara en 2-3 oraciones",
  "requerimientos": [
    {"titulo": "Título breve", "descripcion": "Descripción detallada", "tipo": "funcional", "prioridad": "alta"}
  ],
  "tareas": [
    {"titulo": "Título de la tarea", "descripcion": "Qué hay que implementar", "prioridad": "alta"}
  ],
  "sprints": [
    {"nombre": "Sprint 1 - Nombre descriptivo", "objetivo": "Objetivo del sprint", "semanas": 2}
  ],
  "insumos": [
    {"titulo": "Nombre del insumo", "tipo": "software", "contenido": "Para qué se usa"}
  ]
}

REGLAS:
- 3-6 requerimientos (mix de funcionales y no_funcionales)
- 4-8 tareas
- 1-2 sprints
- 2-4 insumos
- tipo (req): "funcional" o "no_funcional"
- tipo (insumo): "software", "hardware", "servicio", "recurso_humano" o "otro"
- prioridad: "alta", "media" o "baja"
- semanas: entero 1-4
- Solo el JSON, en el idioma indicado
PROMPT;
    }

    /**
     * Unified assistant – IMPROVE mode.
     * Proposes changes to an existing project given full current context.
     */
    public function improveProjectPrompt(
        string $userInstruction,
        string $projectName,
        string $projectDesc,
        array  $context  // keys: requerimientos, tareas, sprints, insumos
    ): string {
        $reqLines    = empty($context['requerimientos']) ? '  (ninguno)' : implode("\n", array_map(fn($r) => "  - ID={$r['id']} | \"{$r['titulo']}\"", $context['requerimientos']));
        $taskLines   = empty($context['tareas'])         ? '  (ninguna)' : implode("\n", array_map(fn($t) => "  - ID={$t['id']} | \"{$t['titulo']}\" | {$t['estado']}", $context['tareas']));
        $sprintLines = empty($context['sprints'])        ? '  (ninguno)' : implode("\n", array_map(fn($s) => "  - ID={$s['id']} | \"{$s['nombre']}\"", $context['sprints']));
        $inputLines  = empty($context['insumos'])        ? '  (ninguno)' : implode("\n", array_map(fn($i) => "  - ID={$i['id']} | \"{$i['titulo']}\"", $context['insumos']));
        $lang        = $this->langInstruction();

        return <<<PROMPT
{$lang}Eres un gestor de proyectos de software. El usuario quiere mejorar un proyecto existente. Responde ÚNICAMENTE con un objeto JSON válido, sin texto adicional.

PROYECTO: {$projectName}
DESCRIPCIÓN: {$projectDesc}

ESTADO ACTUAL:
Requerimientos:
{$reqLines}

Tareas:
{$taskLines}

Sprints:
{$sprintLines}

Insumos:
{$inputLines}

INSTRUCCIÓN DEL USUARIO: {$userInstruction}

Responde con esta estructura JSON exacta:
{
  "resumen": "Descripción de qué se propone en 1-2 oraciones",
  "requerimientos_nuevos": [
    {"titulo": "Título", "descripcion": "Descripción", "tipo": "funcional", "prioridad": "alta"}
  ],
  "tareas_nuevas": [
    {"titulo": "Título", "descripcion": "Descripción", "prioridad": "alta"}
  ],
  "actualizaciones_tareas": [
    {"id": <ID exacto de tarea>, "titulo_actual": "Título que tiene ahora", "titulo_nuevo": "Nuevo título", "descripcion_nueva": "Nueva descripción"}
  ],
  "sprints_nuevos": [
    {"nombre": "Sprint N - Nombre", "objetivo": "Objetivo", "semanas": 2}
  ],
  "insumos_nuevos": [
    {"titulo": "Nombre", "tipo": "software", "contenido": "Para qué se usa"}
  ]
}

REGLAS:
- Si el usuario pide AGREGAR algo, inclúyelo en los arrays "_nuevos"
- Si pide CAMBIAR una tarea existente, inclúyela en "actualizaciones_tareas" con su ID exacto
- Si no hay nada nuevo de un tipo, usa array vacío []
- Solo usa IDs de las listas de estado actual
- Solo el JSON, en el idioma indicado
PROMPT;
    }

    /**
     * Chat-mode: update (rename/improve) existing tasks of a project.
     * Sends current task list with IDs so the AI can reference them precisely.
     *
     * @param array<int, array{id: int, titulo: string, descripcion: string|null, estado: string}> $existingTasks
     */
    public function updateTasksPrompt(
        string $userRequest,
        string $projectName,
        string $projectDescription,
        array  $existingTasks
    ): string {
        $taskLines = '';
        foreach ($existingTasks as $task) {
            $desc      = $task['descripcion'] ? '"' . mb_substr($task['descripcion'], 0, 80) . '"' : 'Sin descripción';
            $estado    = $task['estado'] ?? 'pendiente';
            $taskLines .= "  - ID={$task['id']} | \"{$task['titulo']}\" | {$desc} | Estado: {$estado}\n";
        }

        return <<<PROMPT
Eres un gestor de proyectos de software. El usuario quiere mejorar los títulos y descripciones de tareas existentes. Responde ÚNICAMENTE con un objeto JSON válido, sin texto adicional, sin bloques markdown.

PROYECTO: {$projectName}
DESCRIPCIÓN DEL PROYECTO: {$projectDescription}

TAREAS EXISTENTES (usa los IDs exactos):
{$taskLines}

SOLICITUD DEL USUARIO: {$userRequest}

Propón los cambios necesarios usando los IDs exactos de la lista. Incluye SOLO las tareas que necesitan cambios.

Usa exactamente esta estructura JSON:
{
  "accion": "actualizar_tareas",
  "resumen": "Descripción de qué cambios se proponen en 1-2 oraciones",
  "actualizaciones_tareas": [
    {
      "id": <ID exacto de la tarea — debe ser un número de la lista>,
      "titulo_actual": "Título que tiene ahora la tarea",
      "titulo_nuevo": "Título nuevo descriptivo y claro",
      "descripcion_nueva": "Descripción detallada de qué hay que implementar en esta tarea"
    }
  ]
}

REGLAS ESTRICTAS:
- Usa SOLO los IDs que aparecen en la lista de tareas existentes
- No inventes IDs que no estén en la lista
- Solo incluye las tareas que realmente necesiten cambio
- titulo_nuevo debe ser claro, específico y relevante para el proyecto
- descripcion_nueva debe explicar qué hay que hacer en la tarea
- Solo el JSON, en el idioma del sistema
PROMPT;
    }

    /**
     * Chat-mode: propose deletion of specific tasks.
     *
     * @param array<int, array{id: int, titulo: string, descripcion: string|null}> $existingTasks
     */
    public function deleteTasksPrompt(
        string $userRequest,
        string $projectName,
        array  $existingTasks
    ): string {
        $taskLines = '';
        foreach ($existingTasks as $task) {
            $desc       = $task['descripcion'] ? '"' . mb_substr($task['descripcion'], 0, 60) . '"' : 'Sin descripción';
            $taskLines .= "  - ID={$task['id']} | \"{$task['titulo']}\" | {$desc}\n";
        }

        return <<<PROMPT
Eres un gestor de proyectos. Identifica qué tareas debe eliminar el usuario. Responde SOLO con JSON válido, sin texto extra.

PROYECTO: {$projectName}
TAREAS EXISTENTES:
{$taskLines}
SOLICITUD: {$userRequest}

{
  "accion": "eliminar_tareas",
  "resumen": "Razón de la eliminación en 1 oración",
  "tareas_a_eliminar": [
    {"id": <ID exacto de la tarea>, "titulo": "Título actual", "razon": "Por qué se elimina"}
  ]
}

REGLAS: Usa SOLO los IDs exactos de la lista. No inventes IDs. Solo el JSON.
PROMPT;
    }

    /**
     * Chat-mode: propose status changes for tasks.
     *
     * @param array<int, array{id: int, titulo: string, estado: string}> $existingTasks
     * @param string[] $availableStatuses
     */
    public function moveTaskStatusPrompt(
        string $userRequest,
        string $projectName,
        array  $existingTasks,
        array  $availableStatuses
    ): string {
        $taskLines    = '';
        foreach ($existingTasks as $task) {
            $taskLines .= "  - ID={$task['id']} | \"{$task['titulo']}\" | Estado actual: {$task['estado']}\n";
        }
        $statusList = implode(', ', array_map(fn($s) => "\"{$s}\"", $availableStatuses));

        return <<<PROMPT
Eres un gestor de proyectos. Propón cambios de estado para las tareas indicadas. Responde SOLO con JSON válido.

PROYECTO: {$projectName}
TAREAS EXISTENTES:
{$taskLines}
ESTADOS DISPONIBLES: {$statusList}

SOLICITUD: {$userRequest}

{
  "accion": "mover_tareas_estado",
  "resumen": "Descripción de los cambios de estado propuestos",
  "cambios_estado": [
    {"id": <ID exacto>, "titulo_actual": "Título actual", "estado_nuevo": "Nombre EXACTO del estado destino de la lista"}
  ]
}

REGLAS: Usa SOLO IDs de la lista. estado_nuevo debe ser exactamente uno de los estados disponibles. Solo el JSON.
PROMPT;
    }

    /**
     * Chat-mode: edit/improve an existing project.
     * Returns a structured JSON with proposed requirements, tasks, sprints and supplies.
     * Use with OllamaService (format=json) for best results.
     */
    public function editProjectPrompt(
        string $userRequest,
        string $projectName,
        string $projectDescription
    ): string {
        return <<<PROMPT
Eres un gestor de proyectos de software. El usuario quiere mejorar un proyecto existente. Responde ÚNICAMENTE con un objeto JSON válido, sin texto adicional, sin bloques markdown.

PROYECTO ACTUAL:
- Nombre: {$projectName}
- Descripción: {$projectDescription}

SOLICITUD DEL USUARIO: {$userRequest}

Usa exactamente esta estructura JSON:
{
  "accion": "editar_proyecto",
  "resumen": "Descripción de qué se propone en 1-2 oraciones",
  "requerimientos": [
    {"titulo": "Título breve", "descripcion": "Descripción detallada", "tipo": "funcional", "prioridad": "alta"}
  ],
  "tareas": [
    {"titulo": "Título de la tarea", "descripcion": "Qué hay que implementar", "prioridad": "alta"}
  ],
  "sprints": [
    {"nombre": "Sprint 1 - Nombre descriptivo", "objetivo": "Objetivo del sprint", "semanas": 2}
  ],
  "insumos": [
    {"titulo": "Nombre del insumo", "tipo": "software", "contenido": "Para qué se usa"}
  ]
}

REGLAS ESTRICTAS:
- Si el usuario no pide requerimientos, devuelve "requerimientos": []
- Si el usuario no pide tareas, devuelve "tareas": []
- Si el usuario no pide sprints, devuelve "sprints": []
- Si el usuario no pide insumos, devuelve "insumos": []
- prioridad: exactamente "alta", "media" o "baja"
- tipo (requerimiento): "funcional" o "no_funcional"
- tipo (insumo): "software", "hardware", "servicio", "recurso_humano" o "otro"
- semanas: número entero positivo (1-4)
- Todo en español, solo el JSON
PROMPT;
    }

    /**
     * Phase 3: Generate only the base project data (name, description, dates).
     */
    public function projectBasePrompt(string $improvedDescription, string $projectName): string
    {
        return <<<PROMPT
Eres un experto en gestión de proyectos. Genera los datos base de un proyecto de software. Responde SOLO con JSON válido, sin texto adicional.

PROYECTO: {$projectName}
DESCRIPCIÓN: {$improvedDescription}

{
  "nombre": "Nombre final del proyecto",
  "descripcion": "Descripción detallada del proyecto en 3-4 oraciones",
  "estado": "activo",
  "fecha_inicio": "YYYY-MM-DD",
  "fecha_fin_estimada": "YYYY-MM-DD"
}

REGLAS:
- estado: exactamente "activo", "en_progreso" o "pendiente"
- fechas en formato YYYY-MM-DD (fecha_inicio = hoy aprox, fecha_fin = fecha_inicio + duración)
- Solo el JSON
PROMPT;
    }

    /**
     * Phase 4: Generate requirements for an already-named project.
     */
    public function requirementsPrompt(string $projectName, string $description): string
    {
        return <<<PROMPT
Eres un analista de software experto en Scrum. Genera los requerimientos del siguiente proyecto. Responde SOLO con JSON válido.

PROYECTO: {$projectName}
DESCRIPCIÓN: {$description}

{
  "requerimientos_funcionales": [
    {"codigo": "RF-001", "titulo": "Título breve", "descripcion": "Descripción detallada", "prioridad": "alta", "tipo": "funcional"}
  ],
  "requerimientos_no_funcionales": [
    {"codigo": "RNF-001", "titulo": "Título breve", "descripcion": "Descripción detallada", "prioridad": "media", "tipo": "no_funcional"}
  ]
}

REGLAS:
- 3 a 6 requerimientos funcionales (RF-001, RF-002, ...)
- 2 a 4 requerimientos no funcionales (RNF-001, RNF-002, ...)
- prioridad: "alta", "media" o "baja"
- tipo: exactamente "funcional" o "no_funcional"
- Todo en español, solo el JSON
PROMPT;
    }

    /**
     * Phase 5: Generate tasks for the project.
     */
    public function tasksPrompt(string $projectName, string $description): string
    {
        return <<<PROMPT
Eres un líder técnico experto en Scrum. Genera las tareas de desarrollo para este proyecto. Responde SOLO con JSON válido.

PROYECTO: {$projectName}
DESCRIPCIÓN: {$description}

{
  "tareas": [
    {"titulo": "Título de la tarea", "descripcion": "Descripción clara de lo que hay que hacer", "prioridad": "alta", "estado": "pendiente", "estimacion_horas": 8}
  ]
}

REGLAS:
- 4 a 10 tareas
- prioridad: "alta", "media" o "baja"
- estado: exactamente "pendiente"
- estimacion_horas: número entero positivo
- Todo en español, solo el JSON
PROMPT;
    }

    /**
     * Phase 6: Generate sprints grouping the given tasks.
     */
    public function sprintsPrompt(string $projectName, array $taskTitles): string
    {
        $taskList = implode("\n", array_map(fn($t, $i) => ($i + 1) . '. ' . $t, $taskTitles, array_keys($taskTitles)));

        return <<<PROMPT
Eres un Scrum Master experto. Organiza las siguientes tareas en sprints de 2 semanas. Responde SOLO con JSON válido.

PROYECTO: {$projectName}
TAREAS:
{$taskList}

{
  "sprints": [
    {
      "nombre": "Sprint 1 - Nombre descriptivo",
      "objetivo": "Objetivo del sprint en una oración",
      "duracion_semanas": 2,
      "estado": "pendiente",
      "tareas_incluidas": ["Título de tarea 1", "Título de tarea 2"]
    }
  ]
}

REGLAS:
- 1 a 4 sprints
- estado: exactamente "pendiente"
- duracion_semanas: 1 o 2
- tareas_incluidas: usa los títulos exactos de las tareas de arriba
- Todo en español, solo el JSON
PROMPT;
    }

    /**
     * Phase 7: Generate supplies/inputs for the project.
     */
    public function suppliesPrompt(string $projectName, string $description): string
    {
        return <<<PROMPT
Eres un gestor de proyectos. Lista los insumos y recursos necesarios para este proyecto. Responde SOLO con JSON válido.

PROYECTO: {$projectName}
DESCRIPCIÓN: {$description}

{
  "insumos": [
    {"nombre": "Nombre del insumo", "tipo": "software", "descripcion": "Para qué se usa", "cantidad": 1, "unidad": "licencia"}
  ]
}

REGLAS:
- 3 a 8 insumos
- tipo: "software", "hardware", "servicio", "recurso_humano" o "otro"
- cantidad: número positivo
- Todo en español, solo el JSON
PROMPT;
    }
}
