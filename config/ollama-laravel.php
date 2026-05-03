<?php

// Config for Cloudstudio/Ollama

return [
    'model' => env('OLLAMA_MODEL', 'qwen3.5'),
    'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
    'default_prompt' => env('OLLAMA_DEFAULT_PROMPT', 'Hello, how can I assist you today?'),

    'keep_alive' => env('OLLAMA_KEEP_ALIVE', null),

    'connection' => [
        'timeout' => env('OLLAMA_CONNECTION_TIMEOUT', 300),
    ],
    'headers' => [
        'Authorization' => 'Bearer ' . env('OLLAMA_API_KEY'),
    ],
];
