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
        <label for="tags" class="form-label">Tags (comma or newline separated)</label>
        <textarea class="form-control @error('tags') is-invalid @enderror" id="tags" name="tags" rows="2" placeholder="e.g. anaphylaxis, allergic reaction, epinephrine">{{ $tagsValue }}</textarea>
        <div class="form-text">You can edit these manually. The AI generator will overwrite this field with new tags.</div>
        @error('tags')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
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
            const tagsInput = document.getElementById('tags');
            const statusEl = document.getElementById('generate-tags-status');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const providerSelect = document.getElementById('ai-provider');

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

                    tagsInput.value = data.tags.join(', ');
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

