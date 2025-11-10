<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin - AI Tag Generator Demo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-fixed {
            table-layout: fixed;
        }
        .text-truncate-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
    @yield('head')
    @stack('head')
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ route('admin.protocols.index') }}">Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link{{ request()->routeIs('admin.protocols.*') ? ' active' : '' }}" href="{{ route('admin.protocols.index') }}">Protocols</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ request()->routeIs('admin.ai-rules.*') ? ' active' : '' }}" href="{{ route('admin.ai-rules.edit') }}">AI Rules</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>


