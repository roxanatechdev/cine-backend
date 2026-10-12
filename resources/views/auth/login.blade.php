<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso administrativo | CinePrime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body class="bg-dark text-light">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="card bg-black text-light border-secondary p-4 w-100"
            style="max-width: 420px;">
            <h1 class="h3 text-center mb-2">CinePrime</h1>
            <p class="text-secondary text-center mb-4">
                Acceso administrativo
            </p>

            @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                Las credenciales no son válidas.
            </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input id="email"
                        type="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <input id="password"
                        type="password"
                        name="password"
                        class="form-control"
                        autocomplete="current-password"
                        required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Iniciar sesión
                </button>
            </form>
        </div>
    </main>
</body>

</html>