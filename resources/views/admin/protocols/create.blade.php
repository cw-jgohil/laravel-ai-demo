@extends('layouts.admin')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-0">Create Protocol</h1>
            <p class="text-muted mb-0">Draft a new treatment protocol and generate standardized tags automatically.</p>
        </div>
        <a href="{{ route('admin.protocols.index') }}" class="btn btn-outline-secondary">Back to list</a>
    </div>

    <div class="card">
        <div class="card-body">
            @include('admin.protocols._form', [
                'protocol' => $protocol,
                'route' => route('admin.protocols.store'),
                'method' => 'POST',
                'submitLabel' => 'Create Protocol',
            ])
        </div>
    </div>
@endsection


