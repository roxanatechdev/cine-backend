<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Administración') | CinePrime</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        :root {
            color-scheme: dark;

            --cp-bg: #11131c;
            --cp-surface: #1b1e2a;
            --cp-surface-hover: #222637;
            --cp-border: #343849;
            --cp-border-strong: #454b5e;
            --cp-text: #f5f5f7;
            --cp-muted: #aeb4c4;
            --cp-accent: #72d5f4;
            --cp-accent-hover: #a0e5fa;

            --cp-radius: 14px;
            --cp-shadow: 0 8px 24px rgb(0 0 0 / 12%);
        }

        /* Base */

        html {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--cp-bg);
            color: var(--cp-text);
            font-family:
                Inter, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 0.95rem;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        ::selection {
            background: rgb(114 213 244 / 28%);
            color: #fff;
        }

        a {
            text-underline-offset: 3px;
        }

        a:focus-visible,
        button:focus-visible {
            outline: 2px solid var(--cp-accent);
            outline-offset: 3px;
        }

        /* Navegación */

        .admin-navbar {
            background: rgb(27 30 42 / 96%);
            border-bottom: 1px solid var(--cp-border);
            box-shadow: 0 3px 16px rgb(0 0 0 / 10%);
        }

        .admin-navbar .container {
            min-height: 68px;
        }

        .admin-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: var(--cp-text);
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            text-decoration: none;
        }

        .admin-brand:hover {
            color: #fff;
        }

        .admin-brand-accent {
            color: var(--cp-accent);
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0;
        }

        .admin-user {
            color: var(--cp-muted);
            font-size: 0.85rem;
        }

        /* Contenido y títulos */

        .admin-main {
            padding-top: 2.25rem;
            padding-bottom: 3.5rem;
        }

        .admin-page-title {
            margin-bottom: 0.4rem;
            font-size: clamp(1.45rem, 2.5vw, 1.9rem);
            font-weight: 750;
            letter-spacing: -0.035em;
            line-height: 1.25;
        }

        .muted {
            color: var(--cp-muted) !important;
        }

        /* Paneles */

        .panel {
            min-width: 0;
            background: var(--cp-surface);
            border: 1px solid var(--cp-border);
            border-radius: var(--cp-radius);
            box-shadow: var(--cp-shadow);
        }

        /* Campos */

        .form-label {
            margin-bottom: 0.45rem;
            color: #e6e8ef;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            min-height: 43px;
            background-color: #131621;
            border: 1px solid var(--cp-border-strong);
            border-radius: 8px;
            color: var(--cp-text);
            font-size: 0.925rem;
            transition:
                border-color 0.18s ease,
                box-shadow 0.18s ease;
        }

        textarea.form-control {
            min-height: auto;
        }

        .form-control::placeholder {
            color: #8991a5;
            opacity: 1;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #131621;
            border-color: var(--cp-accent);
            color: #fff;
            box-shadow: 0 0 0 0.2rem rgb(114 213 244 / 15%);
        }

        .form-control:disabled,
        .form-select:disabled {
            background-color: #202330;
            color: var(--cp-muted);
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #ef7d88;
        }

        .invalid-feedback {
            color: #ff9ca5;
        }

        /* Botones */

        .btn {
            min-height: 38px;
            border-radius: 8px;
            font-weight: 600;
            transition:
                background-color 0.18s ease,
                border-color 0.18s ease,
                transform 0.18s ease;
        }

        .btn-primary {
            --bs-btn-color: #10151b;
            --bs-btn-bg: #72d5f4;
            --bs-btn-border-color: #72d5f4;
            --bs-btn-hover-color: #10151b;
            --bs-btn-hover-bg: #a0e5fa;
            --bs-btn-hover-border-color: #a0e5fa;
            --bs-btn-active-color: #10151b;
            --bs-btn-active-bg: #a0e5fa;
            --bs-btn-active-border-color: #a0e5fa;
            --bs-btn-disabled-color: #10151b;
        }

        .btn-outline-light {
            --bs-btn-color: #e4e7ef;
            --bs-btn-border-color: #51586d;
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: #303548;
            --bs-btn-hover-border-color: #727b94;
        }

        .btn-outline-info {
            --bs-btn-color: var(--cp-accent);
            --bs-btn-border-color: #4d8da4;
            --bs-btn-hover-color: #10151b;
            --bs-btn-hover-bg: var(--cp-accent);
            --bs-btn-hover-border-color: var(--cp-accent);
        }

        /* Tablas */

        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--cp-text);
            --bs-table-border-color: var(--cp-border);
            --bs-table-hover-bg: rgb(255 255 255 / 3%);
            --bs-table-hover-color: #fff;
            margin-bottom: 0;
        }

        .table> :not(caption)>*>* {
            padding: 0.9rem 0.85rem;
            vertical-align: middle;
        }

        .table thead th {
            background: rgb(12 14 22 / 35%);
            color: var(--cp-muted);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.055em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .table tbody tr:last-child>* {
            border-bottom-width: 0;
        }

        .table-responsive {
            border-radius: 9px;
        }

        /* Etiquetas */

        .badge {
            padding: 0.45em 0.7em;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 650;
        }

        /* Alertas */

        .alert {
            border-radius: 10px;
        }

        .alert-success {
            --bs-alert-bg: #152e29;
            --bs-alert-color: #a8e7d1;
            --bs-alert-border-color: #285849;
        }

        .alert-danger {
            --bs-alert-bg: #351d26;
            --bs-alert-color: #ffc0c7;
            --bs-alert-border-color: #713744;
        }

        /* Oculta únicamente el texto "Showing ... of ..." predeterminado de la paginación de Laravel */
        #paginacion .d-none.flex-sm-fill.d-sm-flex .small.text-muted {
            display: none !important;
        }

        /* Adaptación a pantallas pequeñas */

        @media (max-width: 575.98px) {
            .admin-navbar .container {
                min-height: 62px;
            }

            .admin-brand {
                font-size: 1.05rem;
            }

            .admin-brand-accent {
                font-size: 0.8rem;
            }

            .admin-user {
                max-width: 110px;
                overflow-wrap: anywhere;
                text-align: right;
            }

            .admin-main {
                padding-top: 1.5rem;
                padding-bottom: 2rem;
            }

            .panel {
                border-radius: 11px;
            }

            .table> :not(caption)>*>* {
                padding: 0.75rem 0.65rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar admin-navbar">
        <div class="container d-flex justify-content-between gap-3">
            <a class="admin-brand"
                href="{{ route('admin.peliculas.index') }}">
                CinePrime
                <span class="admin-brand-accent">/ Admin</span>
            </a>

            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <span class="admin-user">
                    {{ auth()->user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm"
                        type="submit">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container admin-main">
        @if (session('success'))
        <div class="alert alert-success mb-4" role="status">
            {{ session('success') }}
        </div>
        @endif

        @yield('content')
    </main>
</body>

</html>