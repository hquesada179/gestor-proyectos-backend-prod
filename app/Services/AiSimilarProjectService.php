<?php

namespace App\Services;

use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AiSimilarProjectService
{
    /**
     * Spanish stopwords that carry no useful meaning for comparison.
     */
    private const STOPWORDS = [
        'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas',
        'de', 'del', 'al', 'a', 'en', 'que', 'es', 'se', 'su',
        'sus', 'para', 'por', 'con', 'y', 'o', 'no', 'si', 'me',
        'te', 'le', 'nos', 'les', 'pero', 'como', 'más', 'mas',
        'ya', 'esto', 'esta', 'este', 'son', 'has', 'han', 'hay',
        'ser', 'tener', 'poder', 'quiero', 'quiere', 'quieren',
        'necesito', 'necesita', 'hacer', 'haga', 'nuevo', 'nueva',
    ];

    /**
     * Minimum number of meaningful words that must overlap between
     * the user's prompt and a project's name+description to be considered similar.
     */
    private int $threshold;

    public function __construct(int $threshold = 2)
    {
        $this->threshold = $threshold;
    }

    /**
     * Find the user's existing projects that seem similar to the given prompt.
     *
     * @return Collection<int, Proyecto>
     */
    public function findSimilar(string $prompt, int $userId, int $limit = 5): Collection
    {
        $promptWords = $this->extractWords($prompt);

        if (empty($promptWords)) {
            return collect();
        }

        $projects = Proyecto::where('user_id', $userId)
            ->select(['id', 'nombre', 'descripcion', 'estado', 'created_at'])
            ->get();

        $scored = $projects
            ->map(function (Proyecto $p) use ($promptWords) {
                $projectWords = $this->extractWords($p->nombre . ' ' . ($p->descripcion ?? ''));
                $overlap      = count(array_intersect($promptWords, $projectWords));
                return ['project' => $p, 'score' => $overlap];
            })
            ->filter(fn($item) => $item['score'] >= $this->threshold)
            ->sortByDesc('score')
            ->take($limit);

        Log::debug('AiSimilarProjectService: found similar projects', [
            'prompt_words' => $promptWords,
            'matches'      => $scored->pluck('score', 'project.nombre')->toArray(),
        ]);

        return $scored->pluck('project')->values();
    }

    /**
     * Extract meaningful words from a text block.
     * - Lowercases and removes accents
     * - Removes non-letter characters
     * - Filters stopwords and short words (≤ 3 chars)
     * - Returns unique words
     */
    public function extractWords(string $text): array
    {
        $text = $this->normalize($text);

        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $filtered = array_filter(
            $words,
            fn($w) => strlen($w) > 3 && !in_array($w, self::STOPWORDS, true)
        );

        return array_values(array_unique($filtered));
    }

    /**
     * Normalize text: lowercase, remove accents, strip non-letter characters.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        $from = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'à', 'è', 'ì', 'ò', 'ù'];
        $to   = ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u'];
        $text = str_replace($from, $to, $text);

        return preg_replace('/[^a-z0-9\s]/u', ' ', $text);
    }
}
