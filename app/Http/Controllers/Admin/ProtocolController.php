<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiPromptRule;
use App\Models\Protocol;
use App\Services\AITagGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ProtocolController extends Controller
{
    public function index(): View
    {
        $protocols = Protocol::query()->orderByDesc('updated_at')->orderByDesc('id')->get();

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

        return redirect()
            ->route('admin.protocols.edit', $protocol)
            ->with('status', 'Protocol created. You can review tags before publishing.');
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
            $protocol->tags = $tags;
            $protocol->save();

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
        ]);

        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);

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
}


