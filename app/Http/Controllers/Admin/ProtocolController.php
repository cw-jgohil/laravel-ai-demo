<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiPromptRule;
use App\Models\Protocol;
use App\Models\Tag;
use App\Services\AITagGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ProtocolController extends Controller
{
    public function index(): View
    {
        $protocols = Protocol::query()
            ->with('tagsRelation')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.protocols.index', [
            'protocols' => $protocols,
            'currentRules' => AiPromptRule::currentInstructions(),
        ]);
    }

    public function create(): View
    {
        $protocol = new Protocol();

        return view('admin.protocols.create', compact('protocol'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $protocol = Protocol::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'tags' => $data['tags'],
        ]);

        // Sync tags pivot if provided
        $structured = $this->structuredTagsFromRequest($request);
        if (!empty($structured) && method_exists($protocol, 'tagsRelation')) {
            $tagIds = $this->ensureTagsExistAndGetIds($structured);
            $protocol->tagsRelation()->sync($tagIds);
        }

        return redirect()
            ->route('admin.protocols.index')
            ->with('status', 'Protocol created.');
    }

    public function edit(Protocol $protocol): View
    {
        return view('admin.protocols.edit', compact('protocol'));
    }

    public function update(Request $request, Protocol $protocol): RedirectResponse
    {
        $data = $this->validatedData($request);

        $protocol->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'tags' => $data['tags'],
        ]);

        // Sync tags pivot if provided
        $structured = $this->structuredTagsFromRequest($request);
        if (!empty($structured) && method_exists($protocol, 'tagsRelation')) {
            $tagIds = $this->ensureTagsExistAndGetIds($structured);
            $protocol->tagsRelation()->sync($tagIds);
        }

        return redirect()
            ->route('admin.protocols.edit', $protocol)
            ->with('status', 'Protocol updated.');
    }

    public function destroy(Protocol $protocol): RedirectResponse
    {
        $title = $protocol->title;
        $protocol->delete();

        return redirect()
            ->route('admin.protocols.index')
            ->with('status', 'Protocol "' . $title . '" deleted.');
    }

    public function previewGenerateTags(Request $request, AITagGeneratorService $ai): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'max_tags' => ['nullable', 'integer', 'min:1', 'max:25'],
            'provider' => ['nullable', 'string', 'in:openai,groq'],
            'override_rules' => ['nullable', 'string'],
        ]);

        $max = $validated['max_tags'] ?? 12;
        $provider = $validated['provider'] ?? null;
        $override = $validated['override_rules'] ?? null;

        $tags = $ai->generateTags(
            $validated['title'],
            $validated['description'],
            $max,
            $override,
            $provider
        );

        return [
            'tags' => $tags,
        ];
    }

    public function generateTags(Request $request, Protocol $protocol, AITagGeneratorService $ai): RedirectResponse
    {
        $validated = $request->validate([
            'max_tags' => ['nullable', 'integer', 'min:1', 'max:25'],
            'provider' => ['nullable', 'string', 'in:openai,groq'],
            'override_rules' => ['nullable', 'string'],
        ]);

        try {
            $tags = $ai->generateTags(
                $protocol->title,
                (string) $protocol->description,
                $validated['max_tags'] ?? 12,
                $validated['override_rules'] ?? null,
                $validated['provider'] ?? null
            );
            $structured = $this->normaliseToStructuredTags($tags);
            $protocol->tags = array_values(array_unique(array_map(fn ($t) => (string)($t['label'] ?? ''), $structured)));
            $protocol->save();
            if (method_exists($protocol, 'tagsRelation')) {
                $tagIds = $this->ensureTagsExistAndGetIds($structured);
                $protocol->tagsRelation()->sync($tagIds);
            }

            return redirect()
                ->route('admin.protocols.index')
                ->with('status', 'Tags generated for "' . $protocol->title . '".');
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.protocols.index')
                ->with('error', 'Failed to generate tags: ' . $e->getMessage());
        }
    }

    /**
     * Validate incoming protocol data and normalise tags.
     *
     * @return array{title: string, description: string, tags: array<int, string>}
     */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'tags' => ['nullable', 'string'],
            'tags_json' => ['nullable', 'string'],
        ]);

        // Prefer structured tags JSON to derive labels for JSON column
        $structured = [];
        if (!empty($data['tags_json'])) {
            $decoded = json_decode((string) $data['tags_json'], true);
            if (is_array($decoded)) {
                $structured = $this->normaliseToStructuredTags($decoded);
            }
        }
        if (!empty($structured)) {
            $data['tags'] = array_values(array_unique(array_map(fn ($t) => (string)($t['label'] ?? ''), $structured)));
        } else {
            $data['tags'] = $this->normalizeTags($data['tags'] ?? []);
        }

        return $data;
    }

    /**
     * @param mixed $tags
     * @return array<int, string>
     */
    private function normalizeTags(mixed $tags): array
    {
        if (is_array($tags)) {
            $items = $tags;
        } elseif (is_string($tags)) {
            $items = preg_split('/[\n,]+/', $tags);
        } else {
            $items = [];
        }

        $normalized = [];
        foreach ($items as $item) {
            $t = trim((string) $item);
            if ($t !== '') {
                $normalized[] = $t;
            }
        }

        return $normalized;
    }

    /**
     * Convert to array of ['key'=>string,'label'=>string]
     * @param mixed $tags
     * @return array<int, array{key:string,label:string}>
     */
    private function normaliseToStructuredTags(mixed $tags): array
    {
        $items = [];
        if (is_array($tags)) {
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

    /**
     * @return array<int, array{key:string,label:string}>
     */
    private function structuredTagsFromRequest(Request $request): array
    {
        $raw = (string) $request->input('tags_json', '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return $this->normaliseToStructuredTags($decoded);
    }

    /**
     * Ensure tags exist and return IDs
     * @param array<int, array{key:string,label:string}> $structured
     * @return array<int, int>
     */
    private function ensureTagsExistAndGetIds(array $structured): array
    {
        $ids = [];
        foreach ($structured as $t) {
            $tag = Tag::query()->firstOrCreate(
                ['key' => $t['key']],
                ['label' => $t['label']]
            );
            $ids[] = $tag->id;
        }
        return $ids;
    }
}


