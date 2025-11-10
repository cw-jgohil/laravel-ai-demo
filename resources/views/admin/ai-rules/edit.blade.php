@extends('layouts.admin')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-0">AI Tagging Rules</h1>
            <p class="text-muted mb-0">Define the instructions the AI must follow when suggesting protocol tags.</p>
        </div>
        <a href="{{ route('admin.protocols.index') }}" class="btn btn-outline-secondary">Back to Protocols</a>
    </div>

    <div class="card">
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.ai-rules.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Rule Set Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $rule->name ?? 'Default') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="instructions" class="form-label">Instructions</label>
                    <textarea class="form-control @error('instructions') is-invalid @enderror" id="instructions" name="instructions" rows="10" required>{{ old('instructions', $rule->instructions ?? '') }}</textarea>
                    <div class="form-text">
                        Explain how the AI should respond. Example: “Return abbreviations and their full forms as separate tags.”
                    </div>
                    @error('instructions')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Rules</button>
                    <a href="{{ route('admin.protocols.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection


