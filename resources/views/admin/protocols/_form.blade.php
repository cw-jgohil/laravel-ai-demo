@php
    $tagsValue = old('tags');
    if (is_array($tagsValue)) {
        $tagsValue = implode(', ', $tagsValue);
    }
    if ($tagsValue === null) {
        $tagsValue = implode(', ', $protocol->tags ?? []);
    }
@endphp

<form method="POST" action="{{ $route }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $protocol->title) }}" required>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="6" required>{{ old('description', $protocol->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Tags</label>
        <div id="tag-editor" class="form-control d-flex flex-wrap gap-2 align-items-center" style="min-height: 42px;"></div>
        <input type="hidden" id="tags-json" name="tags_json" value="">
        <div class="mt-2 d-flex gap-2">
            <input type="text" id="tag-input" class="form-control" placeholder="Type and press Enter to add tag" list="tag-options">
            <datalist id="tag-options"></datalist>
        </div>
        <div class="form-text">Use Enter to add. Click × on a chip to remove. Suggestions load from saved tags.</div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <label for="ai-provider" class="form-label">AI Provider</label>
            @php
                $defaultProvider = env('AI_PROVIDER', 'openai');
            @endphp
            <select id="ai-provider" class="form-select">
                <option value="openai" {{ $defaultProvider === 'openai' ? 'selected' : '' }}>OpenAI</option>
                <option value="groq" {{ $defaultProvider === 'groq' ? 'selected' : '' }}>Groq</option>
            </select>
            <div class="form-text">Choose which AI platform to use for tag generation.</div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mb-3">
        <button type="button"
                class="btn btn-outline-primary d-flex align-items-center gap-2"
                id="generate-tags-btn"
                data-generate-tags-url="{{ route('admin.protocols.generate-tags.preview') }}">
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="ai-spinner"></span>
            <span id="ai-btn-label">Generate Tags with AI</span>
        </button>
        <div id="generate-tags-status" class="text-muted small"></div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.protocols.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const button = document.getElementById('generate-tags-btn');
            if (!button) {
                return;
            }

            const titleInput = document.getElementById('title');
            const descriptionInput = document.getElementById('description');
            const tagsHidden = document.getElementById('tags-json');
            const statusEl = document.getElementById('generate-tags-status');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const providerSelect = document.getElementById('ai-provider');
            const tagEditor = document.getElementById('tag-editor');
            const tagInput = document.getElementById('tag-input');
            const tagOptions = document.getElementById('tag-options');

            const slugify = (s) => {
                return (s || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '') || Math.random().toString(36).slice(2, 10);
            };

            let currentTags = [];

            const renderChips = () => {
                tagEditor.innerHTML = '';
                currentTags.forEach((t, idx) => {
                    const span = document.createElement('span');
                    span.className = 'badge rounded-pill text-bg-primary d-flex align-items-center gap-2';
                    span.textContent = t.label;
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn-close btn-close-white btn-sm ms-2';
                    btn.addEventListener('click', () => {
                        currentTags.splice(idx, 1);
                        updateHidden();
                        renderChips();
                    });
                    span.appendChild(btn);
                    tagEditor.appendChild(span);
                });
            };

            const updateHidden = () => {
                const uniqueByKey = {};
                currentTags.forEach(t => uniqueByKey[t.key] = t);
                const arr = Object.values(uniqueByKey);
                tagsHidden.value = JSON.stringify(arr);
            };

            const addTag = (label, key) => {
                const lbl = (label || '').trim();
                if (!lbl) return;
                const k = (key || '').trim() || slugify(lbl);
                currentTags.push({ key: k, label: lbl });
                updateHidden();
                renderChips();
            };

            // Seed from server-side values
            try {
                const initialFromOld = @json(old('tags_json'));
                if (initialFromOld) {
                    const parsed = JSON.parse(initialFromOld);
                    if (Array.isArray(parsed)) {
                        parsed.forEach(t => addTag(t.label, t.key));
                    }
                } else {
                    const initialLabels = @json($protocol->tags ?? []);
                    if (Array.isArray(initialLabels)) {
                        initialLabels.forEach(l => addTag(l));
                    }
                }
            } catch (e) {}

            // Suggestions (datalist)
            const loadSuggestions = async (q) => {
                try {
                    const res = await fetch(`{{ route('admin.tags.suggest') }}?q=${encodeURIComponent(q||'')}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    tagOptions.innerHTML = '';
                    (data.tags || []).forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.label;
                        tagOptions.appendChild(opt);
                    });
                } catch {}
            };
            loadSuggestions('');

            tagInput.addEventListener('input', (e) => {
                loadSuggestions(e.target.value || '');
            });
            tagInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    addTag(tagInput.value);
                    tagInput.value = '';
                }
            });

            button.addEventListener('click', async () => {
                const title = titleInput.value.trim();
                const description = descriptionInput.value.trim();
                const provider = (providerSelect?.value || 'openai').toLowerCase();

                if (!title || !description) {
                    statusEl.classList.remove('text-success');
                    statusEl.classList.add('text-danger');
                    statusEl.textContent = 'Please enter both a title and description before generating tags.';
                    return;
                }

                statusEl.classList.remove('text-danger');
                statusEl.classList.remove('text-success');
                statusEl.textContent = 'Thinking… generating tags from AI.';
                button.disabled = true;
                document.getElementById('ai-spinner')?.classList.remove('d-none');
                document.getElementById('ai-btn-label').textContent = 'Generating…';

                try {
                    const response = await fetch(button.dataset.generateTagsUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            title,
                            description,
                            provider,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error(`Request failed (${response.status})`);
                    }

                    const data = await response.json();
                    if (!Array.isArray(data.tags)) {
                        throw new Error('Unexpected response from AI service.');
                    }

                    // Accept [{key,label}] or [string]
                    currentTags = [];
                    data.tags.forEach(t => {
                        if (typeof t === 'string') {
                            addTag(t);
                        } else if (t && typeof t === 'object') {
                            addTag(t.label || '', t.key || '');
                        }
                    });
                    updateHidden();
                    renderChips();
                    statusEl.classList.remove('text-danger');
                    statusEl.classList.add('text-success');
                    statusEl.textContent = 'Tags generated. Review and adjust if needed.';
                } catch (error) {
                    console.error(error);
                    statusEl.classList.remove('text-success');
                    statusEl.classList.add('text-danger');
                    statusEl.textContent = 'Unable to generate tags. Please review server logs or try again.';
                } finally {
                    button.disabled = false;
                    document.getElementById('ai-spinner')?.classList.add('d-none');
                    document.getElementById('ai-btn-label').textContent = 'Generate Tags with AI';
                }
            });
        });
    </script>
@endpush

