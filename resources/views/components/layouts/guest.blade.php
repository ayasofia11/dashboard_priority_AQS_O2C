@props(['title' => 'Connexion'])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-11 col-sm-8 col-md-6 col-lg-4">

            <div class="text-center mb-4">
                <h1 class="h4 mb-1" style="color: #1A1A1A;">AQS · Suivi des commandes O2C</h1>
                <p class="text-muted small mb-0">Connexion à l'application</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
