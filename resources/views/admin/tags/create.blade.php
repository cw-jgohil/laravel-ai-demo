@extends('layouts.admin')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-0">Create Tag</h1>
            <p class="text-muted mb-0">Add a new reusable tag.</p>
        </div>
        <a href="{{ route('admin.tags.index') }}" class="btn btn-outline-secondary">Back to list</a>
    </div>

    <div class="card">
        <div class="card-body">
            @include('admin.tags._form', [
                'tag' => $tag,
                'route' => route('admin.tags.store'),
                'method' => 'POST',
                'submitLabel' => 'Create Tag',
            ])
        </div>
    </div>
@endsection


