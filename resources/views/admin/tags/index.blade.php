@extends('layouts.admin')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-0">Tags</h1>
            <p class="text-muted mb-0">Manage reusable tags used by protocols.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tags.create') }}" class="btn btn-primary">Add Tag</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form class="row gy-2 gx-2 align-items-center mb-3" method="GET">
        <div class="col-auto">
            <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Search by key or label">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-fixed align-middle">
                    <thead>
                    <tr>
                        <th style="width: 10%;">#</th>
                        <th style="width: 30%;">Key</th>
                        <th style="width: 40%;">Label</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($tags as $tag)
                        <tr>
                            <td class="text-muted">{{ $tag->id }}</td>
                            <td><code>{{ $tag->key }}</code></td>
                            <td>{{ $tag->label }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.tags.edit', $tag) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" onsubmit="return confirm('Delete this tag?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No tags found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $tags->links() }}
            </div>
        </div>
    </div>
@endsection


