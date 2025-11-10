<?php

namespace App\Services;

use App\Models\AiPromptRule;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;
use GuzzleHttp\Client;

class AITagGeneratorService
{
    private const ABBREVIATION_MAP = [
        'vf' => 'ventricular fibrillation',
        'vt' => 'ventricular tachycardia',
        'vr' => 'ventricular response',
        'afib' => 'atrial fibrillation',
        'svt' => 'supraventricular tachycardia',
        'sob' => 'shortness of breath',
        'copd' => 'chronic obstructive pulmonary disease',
        'acs' => 'acute coronary syndrome',
        'cpap' => 'continuous positive airway pressure',
        'cpr' => 'cardiopulmonary resuscitation',
        'mi' => 'myocardial infarction',
    ];

    private const FULL_FORM_MAP = [
        'ventricular fibrillation' => 'vf',
        'ventricular tachycardia' => 'vt',
        'ventricular response' => 'vr',
        'atrial fibrillation' => 'afib',
        'supraventricular tachycardia' => 'svt',
        'shortness of breath' => 'sob',
        'chronic obstructive pulmonary disease' => 'copd',
        'acute coronary syndrome' => 'acs',
        'continuous positive airway pressure' => 'cpap',
        'cardiopulmonary resuscitation' => 'cpr',
        'myocardial infarction' => 'mi',
    ];

    /**
     * Generate standardized medical tags using the configured AI provider.
     *
     * @param string $title
     * @param string $description
     * @param int $maxTags Maximum number of tags to keep
     * @param string|null $overrideRules Additional per-request rules that will be appended to the saved admin rules
     * @return array<int, array{key:string,label:string}>
     */
    public function generateTags(string $title, string $description, int $maxTags = 12, ?string $overrideRules = null, ?string $provider = null): array
    {
        $instructions = $this->combineInstructions($overrideRules);
        [$systemMessage, $userPrompt] = $this->buildPrompt($title, $description, $maxTags, $instructions);

        $selectedProvider = mb_strtolower(trim((string) $provider ?: (string) env('AI_PROVIDER', 'openai')));
        if ($selectedProvider === '' || !in_array($selectedProvider, ['openai', 'groq'], true)) {
            $selectedProvider = 'openai';
        }

        if ($selectedProvider === 'groq') {
            $model = (string) env('GROQ_MODEL', 'llama-3.1-8b-instant');
            $content = $this->callGroqChat($model, $systemMessage, $userPrompt);
        } else {
            $model = (string) env('OPENAI_MODEL', 'gpt-4o-mini');
            $content = $this->callOpenAIChat($model, $systemMessage, $userPrompt);
        }
        $raw = $this->parseTagsFromContent($content);
        $structured = $this->normalizeToStructured($raw);

        if (empty($structured)) {
            throw new RuntimeException('Failed to parse tags from AI response.');
        }

        if ($maxTags > 0) {
            $structured = array_slice($structured, 0, $maxTags);
        }

        return $structured;
    }

    /**
     * Combine saved admin rules with optional per-request overrides.
     */
    private function combineInstructions(?string $overrideRules): string
    {
        $adminRules = trim(AiPromptRule::currentInstructions());
        $override = trim((string) $overrideRules);

        if ($adminRules !== '' && $override !== '') {
            return $adminRules . "\n\n" . $override;
        }

        return $adminRules !== '' ? $adminRules : $override;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function buildPrompt(string $title, string $description, int $maxTags, string $instructions): array
    {
        $system = 'You are a medical tagging assistant for EMS treatment protocols. '
            . 'Always respond with a compact JSON array of objects, each with fields { "key": string, "label": string }. '
            . '"key" must be a stable, lowercase, URL-safe slug (e.g., "ventricular-fibrillation"); '
            . '"label" is a human-readable tag. Avoid explanations or additional text; return ONLY the JSON array.';

        if ($instructions !== '') {
            $system .= "\n\nAdmin rules to follow:\n" . $instructions;
        }

        $user = "Generate at most {$maxTags} high-quality search tags for the following protocol. "
            . "Include synonymous medical terms when appropriate. "
            . "Return ONLY the JSON array of objects with {key,label}.\n\n"
            . "Title: {$title}\n\nDescription: {$description}";

        return [$system, $user];
    }

    /**
     * Call OpenAI via Laravel facade.
     */
    private function callOpenAIChat(string $model, string $systemMessage, string $userPrompt): string
    {
        $result = OpenAI::chat()->create([
            'model' => $model,
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemMessage,
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
        ]);

        $content = $result->choices[0]->message->content ?? null;
        if (!is_string($content) || $content === '') {
            throw new RuntimeException('OpenAI returned an empty response.');
        }
        return $content;
    }

    /**
     * Call Groq's OpenAI-compatible Chat Completions API.
     */
    private function callGroqChat(string $model, string $systemMessage, string $userPrompt): string
    {
        $apiKey = (string) env('GROQ_API_KEY', '');
        if ($apiKey === '') {
            throw new RuntimeException('GROQ_API_KEY is not configured.');
        }

        $client = new Client([
            'base_uri' => 'https://api.groq.com/openai/v1/',
            'timeout' => (float) env('GROQ_REQUEST_TIMEOUT', 30),
        ]);

        $response = $client->post('chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemMessage,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || $content === '') {
            throw new RuntimeException('Groq returned an empty response.');
        }

        return $content;
    }

    /**
     * Attempt to parse a JSON array from the model output, even if fenced or with extra text.
     *
     * @param string $content
     * @return array<int, mixed>
     */
    private function parseTagsFromContent(string $content): array
    {
        // Direct JSON decode first
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Extract first JSON array substring
        if (preg_match('/\[(?:[^\[\]]+|(?R))*\]/m', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Extract from fenced code block with json
        if (preg_match('/```(?:json)?\s*(\[[\s\S]*?\])\s*```/i', $content, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    // Removed mock and OpenRouter branches; always uses OpenAI via facade

    /**
     * Normalize any tag payload (strings or objects) into [{key,label}] unique by key.
     *
     * @param array<int, mixed> $tags
     * @return array<int, array{key:string,label:string}>
     */
    private function normalizeToStructured(array $tags): array
    {
        $items = [];
        foreach ($tags as $t) {
            if (is_string($t)) {
                $label = trim($t);
                if ($label === '') {
                    continue;
                }
                $items[] = [
                    'key' => $this->slugifyKey($label),
                    'label' => $label,
                ];
            } elseif (is_array($t)) {
                $label = trim((string)($t['label'] ?? $t['name'] ?? ''));
                $key = trim((string)($t['key'] ?? ''));
                if ($label === '') {
                    continue;
                }
                if ($key === '') {
                    $key = $this->slugifyKey($label);
                }
                $items[] = [
                    'key' => $key,
                    'label' => $label,
                ];
            }
        }
        // Unique by key
        $unique = [];
        foreach ($items as $i) {
            $unique[$i['key']] = $i;
        }
        return array_values($unique);
    }

    private function slugifyKey(string $label): string
    {
        $k = mb_strtolower($label);
        $k = preg_replace('/[^a-z0-9]+/i', '-', $k) ?? '';
        $k = trim($k, '-');
        return $k !== '' ? $k : substr(md5($label), 0, 8);
    }
}


