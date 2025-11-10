@php
    $route ??= '';
    $method ??= 'POST';
    $submitLabel ??= 'Save';
@endphp

<form method="POST" action="{{ $route }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="mb-3">
        <label for="key" class="form-label">Key</label>
        <input type="text" class="form-control @error('key') is-invalid @enderror" id="key" name="key" value="{{ old('key', $tag->key) }}" required>
        <div class="form-text">Unique, lowercase-slug identifier (e.g. ventricular-fibrillation).</div>
        @error('key')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="label" class="form-label">Label</label>
        <input type="text" class="form-control @error('label') is-invalid @enderror" id="label" name="label" value="{{ old('label', $tag->label) }}" required>
        @error('label')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.tags.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>


