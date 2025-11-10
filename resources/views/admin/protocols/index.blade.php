@extends('layouts.admin')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-0">Protocols</h1>
            <p class="text-muted mb-0">Manage EMS treatment protocols, AI-generated tags, and manual adjustments.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.protocols.create') }}" class="btn btn-primary">Add Protocol</a>
            <a href="{{ route('admin.protocols.index') }}" class="btn btn-outline-secondary">Refresh</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (!empty($currentRules))
        <div class="alert alert-info">
            <strong>Current AI Rules:</strong>
            <pre class="mb-0 small">{{ $currentRules }}</pre>
            <div class="mt-2"><a href="{{ route('admin.ai-rules.edit') }}" class="btn btn-sm btn-outline-dark">Edit AI Rules</a></div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-fixed align-middle">
                    <thead>
                    <tr>
                        <th style="width: 6%;">#</th>
                        <th style="width: 26%;">Title</th>
                        <th style="width: 28%;">Tags</th>
                        <th style="width: 20%;">Updated</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($protocols as $protocol)
                        <tr>
                            <td class="text-muted">{{ $protocol->id }}</td>
                            <td>
                                <strong>{{ $protocol->title }}</strong>
                                <div class="small text-muted text-truncate-3 mt-1">{{ $protocol->description }}</div>
                            </td>
                            <td style="word-wrap: break-word;">
                                @php
                                    $tags = is_array($protocol->tags) ? $protocol->tags : [];
                                @endphp
                                @if(count($tags))
                                    @foreach($tags as $tag)
                                        <span class="badge text-bg-primary me-1 mb-1">{{ $tag }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $protocol->updated_at?->format('Y-m-d H:i') ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.protocols.edit', $protocol) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="POST" action="{{ route('admin.protocols.generate-tags', $protocol) }}" class="inline-generate-form">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary d-flex align-items-center gap-2" type="submit">
                                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                            <span>Generate Tags</span>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.protocols.destroy', $protocol) }}" onsubmit="return confirm('Delete this protocol? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No protocols found. <a href="{{ route('admin.protocols.create') }}">Create your first protocol.</a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.inline-generate-form').forEach(form => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('button');
            const spin = form.querySelector('.spinner-border');
            if (btn && spin) {
                btn.disabled = true;
                spin.classList.remove('d-none');
                btn.querySelector('span:last-child').textContent = 'Generating…';
            }
        });
    });
});
</script>
@endpush


