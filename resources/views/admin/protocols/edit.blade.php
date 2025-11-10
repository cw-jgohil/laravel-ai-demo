@extends('layouts.admin')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-0">Edit Protocol</h1>
            <p class="text-muted mb-0">Update the protocol details, regenerate tags, or fine-tune them manually.</p>
        </div>
        <a href="{{ route('admin.protocols.index') }}" class="btn btn-outline-secondary">Back to list</a>
    </div>

    <div class="card">
        <div class="card-body">
            @include('admin.protocols._form', [
                'protocol' => $protocol,
                'route' => route('admin.protocols.update', $protocol),
                'method' => 'PUT',
                'submitLabel' => 'Update Protocol',
            ])
        </div>
    </div>
@endsection


