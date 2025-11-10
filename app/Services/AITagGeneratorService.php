<?php

namespace App\Services;

use App\Models\AiPromptRule;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;

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
     * @return array<int, string>
     */
    public function generateTags(string $title, string $description, int $maxTags = 12, ?string $overrideRules = null): array
    {
        $instructions = $this->combineInstructions($overrideRules);
        [$systemMessage, $userPrompt] = $this->buildPrompt($title, $description, $maxTags, $instructions);

        $model = (string) env('OPENAI_MODEL', 'gpt-4o-mini');

        $content = $this->callOpenAIChat($model, $systemMessage, $userPrompt);
        $tags = $this->parseTagsFromContent($content);

        if (empty($tags)) {
            throw new RuntimeException('Failed to parse tags from AI response.');
        }

        // Normalize: lowercase, trim, unique, limit
        $normalized = [];
        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                continue;
            }
            $t = trim(mb_strtolower($tag));
            if ($t === '') {
                continue;
            }
            $normalized[$t] = true;
        }

        $result = array_keys($normalized);
        if ($maxTags > 0) {
            $result = array_slice($result, 0, $maxTags);
        }

        return $result;
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
            . 'Always respond with a compact JSON array (lowercase strings) of standardized medical tags. '
            . 'Avoid explanations or additional text.';

        if ($instructions !== '') {
            $system .= "\n\nAdmin rules to follow:\n" . $instructions;
        }

        $user = "Generate at most {$maxTags} high-quality search tags for the following protocol. "
            . "Include synonymous medical terms when appropriate. "
            . "Return ONLY the JSON array.\n\n"
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
     * Attempt to parse a JSON array from the model output, even if fenced or with extra text.
     *
     * @param string $content
     * @return array<int, string>
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
}


