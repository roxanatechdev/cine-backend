<!doctype html>
<html lang="es" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>@yield('title', 'Panel de administración') | CinePrime</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('css/estilos-globales-proyecto.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin-cineprime.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css')}}">
    <link rel="stylesheet" href="{{ asset('css/admin-peliculas.css')}}">
    <link rel="stylesheet" href="{{ asset('css/formulario.css')}}">
    <link rel="stylesheet" href="{{ asset('css/modal.css')}}">
    @stack('styles')
    <style>
        /* Sidebar Contenedor Base */
        .admin-sidebar {
            width: 260px;
            background-color: var(--cm-surface-container-lowest, #0c0e17);
            min-height: 100vh;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Marca / Logo */
        .brand-icon {
            font-size: 2.5rem;
            color: var(--cm-primary-container, #e50914);
        }

        .brand-title {
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 1.5px;
        }

        .brand-subtitle {
            font-size: 0.625rem;
            color: var(--cm-on-surface-variant, #c2c4d6);
            font-weight: 600;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        .admin-brand-mark {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        /* Etiquetas de Sección */
        .admin-nav-section-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: #7d6c77;
            letter-spacing: 0.8px;
            padding: 0.5rem 0.75rem 0.25rem 0.75rem;
        }

        /* Enlaces del Sidebar */
        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.2rem 0.85rem;
            color: var(--cm-on-surface, #e1e1ef);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.375rem;
            transition: all 0.2s ease-in-out;
        }

        .admin-nav-item .nav-icon {
            font-size: 1.15rem;
            color: var(--cm-on-surface, #e1e1ef);
            transition: color 0.2s ease;
        }

        /* Estado Hover */
        .admin-nav-item:hover {
            background-color: var(--cm-surface-container-high, #282933);
            color: #ffffff;
        }

        .admin-nav-item:hover .nav-icon {
            color: var(--cm-primary-container, #e50914);
        }

        /* Estado Activo */
        .admin-nav-item.active {
            background-color: var(--cm-primary-container, #e50914) !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(229, 9, 20, 0.35);
        }

        .admin-nav-item.active .nav-icon {
            color: #ffffff !important;
        }

        /* ==========================================================================
           2. ESTILOS EN MODO COLAPSADO (.sidebar-collapsed - Solo Escritorio)
           ========================================================================== */
        .sidebar-collapsed .admin-sidebar {
            width: 72px;
            z-index: 1000;
        }

        /* Permitir que el contenido flotante (tooltips) no sea recortado por los contenedores */
        .sidebar-collapsed .admin-sidebar,
        .sidebar-collapsed .admin-sidebar-inner {
            overflow: visible !important;
        }

        .sidebar-collapsed .admin-sidebar-inner,
        .sidebar-collapsed .admin-nav {
            padding: 0;
            margin: 0;
        }

        /* Ocultar texto de subtítulos y nombres de la marca */
        .sidebar-collapsed .admin-brand-mark {
            padding-left: 1.15rem !important;
            padding-bottom: 0;
        }

        .sidebar-collapsed .admin-brand-mark .d-flex.flex-column {
            display: none !important;
        }

        /* Ocultar etiquetas de las secciones */
        .sidebar-collapsed .admin-nav-section-label {
            opacity: 0;
            height: 12px;
            padding: 0;
            margin: 0 !important;
            overflow: hidden;
        }

        .sidebar-collapsed .principal {
            height: 0;
        }

        /* Centrar items e iconos */
        .sidebar-collapsed .admin-nav-item {
            justify-content: center;
            padding: 0.1rem 0;
            position: relative;
            /* Necesario para anclar el tooltip */
        }

        /* Ocultar texto del enlace */
        .sidebar-collapsed .admin-nav-item span {
            display: none !important;
        }

        /* Asegurar que el icono quede bien centrado y visible */
        .sidebar-collapsed .admin-nav-item .nav-icon {
            margin: 0;
            font-size: 1.25rem;
        }

        /* Tooltip personalizado con CSS Puro (Solo visible en modo colapsado y hover) */
        .sidebar-collapsed .admin-sidebar .admin-nav-item:hover::after {
            content: attr(data-tooltip) !important;
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 12px;
            background-color: var(--cm-surface-container-highest, #32343e);
            color: var(--cm-on-surface, #e1e1ef);
            padding: 0.35rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            font-weight: 500;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            z-index: 9999;
            pointer-events: none;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* ==========================================================================
           3. SEGURIDAD MÓVIL Y TABLET (Forzar versión extendida en pantallas < 992px)
           ========================================================================== */
        @media (max-width: 991.98px) {

            /* Aunque el estado colapsado esté activo internamente, en móvil se anula y se extiende */
            .admin-shell.sidebar-collapsed .admin-sidebar {
                width: 260px !important;
                overflow-y: auto !important;
            }

            .admin-shell.sidebar-collapsed .admin-sidebar .admin-brand-mark .d-flex.flex-column {
                display: flex !important;
            }

            .admin-shell.sidebar-collapsed .admin-sidebar .admin-nav-section-label {
                opacity: 1 !important;
                height: auto !important;
                padding: 0.5rem 0.75rem 0.25rem 0.75rem !important;
                overflow: visible !important;
            }

            .admin-shell.sidebar-collapsed .admin-sidebar .principal {
                height: auto !important;
            }

            .admin-shell.sidebar-collapsed .admin-sidebar .admin-nav-item {
                justify-content: flex-start !important;
                padding: 0.2rem 0.85rem !important;
            }

            .admin-shell.sidebar-collapsed .admin-sidebar .admin-nav-item span {
                display: inline !important;
            }

            /* Desactivar tooltips flotantes en móvil */
            .admin-shell.sidebar-collapsed .admin-sidebar .admin-nav-item:hover::after {
                display: none !important;
            }


        }
    </style>
    @livewireStyles
</head>

<body class="admin-body">
    <div class="admin-shell d-flex" id="adminShell">
        <aside class="admin-sidebar" id="adminSidebar" aria-label="Navegación administrativa">
            <div class="admin-sidebar-inner d-flex flex-column justify-content-between h-100">
                <div>
                    <!-- Marca / Logo CINEPRIME -->
                    <a class="admin-brand-mark d-flex align-items-center gap-2 text-decoration-none px-3" href="{{ route('admin.dashboard') }}">
                        <!-- Icono Claqueta Material -->
                        <span class="material-symbols-outlined text-cm-primary-container fs-2">movie</span>
                        <div class="d-flex flex-column">
                            <span class="brand-title font-display">
                                <span class="text-white">CINE</span><span class="text-cm-brand-red">PRIME</span>
                            </span>
                            <span class="brand-subtitle text-uppercase text-nowrap">CONSOLA DE CONTROL </span>
                        </div>
                    </a>

                    <nav class="admin-nav d-flex flex-column gap-1 px-2 mt-2">
                        <!-- SECCIÓN: PRINCIPAL -->
                        <div class="admin-nav-section-label principal">PRINCIPAL</div>
                        <a class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"
                            data-tooltip="Dashboard">
                            <i class="bi bi-grid-fill nav-icon"></i>
                            <span>Dashboard</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.peliculas.*') ? 'active' : '' }}" href="{{ route('admin.peliculas.index') }}" data-tooltip="Peliculas">
                            <i class="bi bi-film nav-icon"></i>
                            <span>Películas</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.funciones.*') ? 'active' : '' }}" href="{{ route('admin.funciones.index') }}" data-tooltip="Programación / Funciones">
                            <i class="bi bi-calendar-event nav-icon"></i>
                            <span>Programación / Funciones</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.salas.*') ? 'active' : '' }}" href="{{ route('admin.salas.index') }}" data-tooltip="Salas y Aforo">
                            <i class="bi bi-display nav-icon"></i>
                            <span>Salas y Aforo</span>
                        </a>

                        <!-- SECCIÓN: CATÁLOGOS -->
                        <div class="admin-nav-section-label mt-3">CATÁLOGOS</div>
                        <a class="admin-nav-item {{ request()->routeIs('admin.generos.*') ? 'active' : '' }}" href="{{ route('admin.generos.index') }}" data-tooltip="Géneros">
                            <i class="bi bi-collection-play nav-icon"></i>
                            <span>Géneros</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.idiomas.*') ? 'active' : '' }}" href="{{ route('admin.idiomas.index') }}" data-tooltip="Idiomas">
                            <i class="bi bi-translate nav-icon"></i>
                            <span>Idiomas</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.paises.*') ? 'active' : '' }}" href="{{ route('admin.paises.index') }}" data-tooltip="Países">
                            <i class="bi bi-globe nav-icon"></i>
                            <span>Países</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.personas.*') ? 'active' : '' }}" href="{{ route('admin.personas.index') }}" data-tooltip="Personas">
                            <i class="bi bi-people-fill nav-icon"></i>
                            <span>Personas</span>
                        </a>

                        <!-- SECCIÓN: CONCESIONES -->
                        <div class="admin-nav-section-label mt-3">CONCESIONES</div>
                        <a class="admin-nav-item {{ request()->routeIs('admin.categorias-producto.*') ? 'active' : '' }}" href="{{ route('admin.categorias-producto.index') }}" data-tooltip="Categorías Dulcería">
                            <i class="bi bi-cup-straw nav-icon"></i>
                            <span>Categorías Dulcería</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.productos.*') ? 'active' : '' }}" href="{{ route('admin.productos.index') }}" data-tooltip="Productos">
                            <i class="bi bi-box-seam nav-icon"></i>
                            <span>Productos</span>
                        </a>

                        <!-- SECCIÓN: GESTIÓN Y FINANZAS -->
                        <div class="admin-nav-section-label mt-3">GESTIÓN Y FINANZAS</div>
                        <a class="admin-nav-item {{ request()->routeIs('admin.compradores.*') ? 'active' : '' }}" href="{{ route('admin.compradores.index') }}" data-tooltip="Clientes">
                            <i class="bi bi-people nav-icon"></i>
                            <span>Clientes</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.compras.*') ? 'active' : '' }}" href="{{ route('admin.compras.index') }}" data-tooltip="Historial de Ventas">
                            <i class="bi bi-receipt-cutoff nav-icon"></i>
                            <span>Historial de Ventas</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.pagos.*') ? 'active' : '' }}" href="{{ route('admin.pagos.index') }}" data-tooltip="Pagos">
                            <i class="bi bi-credit-card nav-icon"></i>
                            <span>Pagos</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.reembolsos.*') ? 'active' : '' }}" href="{{ route('admin.reembolsos.index') }}" data-tooltip="Reembolsos">
                            <i class="bi bi-arrow-counterclockwise nav-icon"></i>
                            <span>Reembolsos</span>
                        </a>
                        <a class="admin-nav-item {{ request()->routeIs('admin.tickets.*') || request()->routeIs('admin.entradas.*') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}" data-tooltip="Validación de Boletos / Tickets">
                            <i class="bi bi-qr-code-scan nav-icon"></i>
                            <span>Validación de Boletos / Tickets</span>
                        </a>
                    </nav>
                </div>
            </div>
        </aside>

        <button class="admin-sidebar-backdrop border-0" type="button" aria-label="Cerrar menú" id="adminBackdrop"></button>

        <div class="admin-main-wrap">
            <header class="admin-topbar px-3 px-md-4 py-2 d-flex align-items-center justify-content-between">

                <!-- Lado Izquierdo: Controles de Sidebar y Buscador Global -->
                <div class="d-flex align-items-center gap-3 flex-grow-1 me-3">
                    <!-- Botón Móvil -->
                    <button class="btn admin-topbar-btn d-lg-none flex-shrink-0" id="adminMobileToggle" type="button" aria-label="Abrir menú" aria-controls="adminSidebar">
                        <span class="material-symbols-outlined fs-5" aria-hidden="true">menu</span>
                    </button>

                    <!-- Botón Escritorio (Colapsar/Expandir) -->
                    <button class="btn admin-topbar-btn d-none d-lg-inline-flex flex-shrink-0" id="adminDesktopToggle" type="button" aria-label="Contraer o expandir menú" aria-controls="adminSidebar">
                        <span class="material-symbols-outlined fs-5" aria-hidden="true">left_panel_close</span>
                    </button>

                    <!-- Buscador Global -->
                    <div class="admin-search-container position-relative d-none d-md-block" style="max-width: 380px; width: 100%;">
                        <div class="input-group input-group-sm bg-cm-surface-container rounded-2 overflow-hidden" style="border: 1px solid rgba(232, 187, 167, 0.1) !important;">
                            <span class="input-group-text bg-transparent border-0 text-cm-variant ps-3 pe-2">
                                <span class="material-symbols-outlined fs-5">search</span>
                            </span>
                            <input type="text" class="form-control bg-transparent border-0 text-white shadow-none ps-1 fs-7" placeholder="Buscar películas, ventas, clientes..." aria-label="Buscar en el sistema">
                        </div>
                    </div>
                </div>

                <!-- Lado Derecho: Estado del Sistema y Menú de Usuario Mejorado -->
                <div class="d-flex align-items-center gap-3 flex-shrink-0">

                    <!-- Indicador de Estado / Contexto Rápido -->
                    <div class="d-none d-xxl-flex align-items-center gap-2 bg-cm-surface-container px-3 py-1 rounded-pill border border-secondary border-opacity-10 fs-8 text-cm-variant">
                        <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 8px; height: 8px;"></span>
                        <span>Sistema Activo - CinePrime</span>
                    </div>

                    <!-- Menú Desplegable de Usuario / Perfil -->
                    <div class="dropdown">
                        <button class="btn admin-user-chip dropdown-toggle d-flex align-items-center gap-2 border-0 bg-transparent text-white p-1 pe-2 rounded-pill"
                            type="button" id="adminUserDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="admin-user-avatar d-flex align-items-center justify-content-center bg-cm-primary-container text-white fw-bold rounded-circle shadow-sm" style="width: 38px; height: 38px; font-size: 0.95rem;">
                                {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'A', 0, 1)) }}
                            </span>
                            <div class="text-start d-none d-sm-block lh-sm">
                                <span class="d-block fw-semibold fs-7 text-white">{{ auth()->user()->name ?? 'Administrador' }}</span>
                                <span class="text-cm-variant text-truncate d-block" style="font-size: 0.7rem; max-width: 140px;">{{ auth()->user()->email ?? 'admin@cineprime.com' }}</span>
                            </div>
                        </button>

                        <!-- Dropdown Menú-->
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border border-secondary border-opacity-15 text-white py-2 mt-2" aria-labelledby="adminUserDropdown" style="min-width: 240px; border-radius: 0.75rem; background-color: var(--cm-surface-container-lowest) !important;">
                            <li class="px-1 py-2 mb-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-cm-primary-container text-white fs-10 py-1 rounded-pill">Administrador</span>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item text-white d-flex align-items-center gap-2 py-2 px-3 fs-7 transition-all" href="#">
                                    <span class="material-symbols-outlined fs-5 text-cm-variant">manage_accounts</span>
                                    Mi Perfil y Ajustes
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider my-1" style="border: 0.5px solid rgba(232, 187, 167, 0.1) !important;">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger d-flex align-items-center gap-2 py-2 px-3 w-100 text-start fs-7 transition-all" type="submit">
                                        <span class="material-symbols-outlined fs-5">logout</span>
                                        Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>

            </header>

            <main class="admin-content">
                @if (session('success'))<div class="alert alert-success mb-4" role="status">{{ session('success') }}</div>@endif
                @if (session('error'))<div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>@endif
                @if ($errors->any())
                <div class="alert alert-danger mb-4" role="alert">
                    <div class="fw-semibold mb-1">Revisa la información ingresada.</div>
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
                @endif
                @yield('content')
            </main>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const shell = document.getElementById('adminShell');
            const mobile = document.getElementById('adminMobileToggle');
            const desktop = document.getElementById('adminDesktopToggle');
            const backdrop = document.getElementById('adminBackdrop');
            const closeMobile = () => shell.classList.remove('sidebar-open');
            mobile?.addEventListener('click', () => shell.classList.toggle('sidebar-open'));
            backdrop?.addEventListener('click', closeMobile);
            desktop?.addEventListener('click', () => {
                const collapsed = shell.classList.toggle('sidebar-collapsed');
                desktop.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
                desktop.querySelector('.material-symbols-outlined').textContent = collapsed ? 'left_panel_open' : 'left_panel_close';
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeMobile();
            });
        })();
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    <script>
        document.addEventListener('click', function(event) {
            const option = event.target.closest('[data-dropdown-option]');
            if (!option) return;
            const dropdown = option.closest('.form-cm__dropdown');
            if (!dropdown) return;
            const input = dropdown.querySelector('input[type="hidden"]');
            const label = dropdown.querySelector('[data-dropdown-label]');
            if (!input || !label) return;
            input.value = option.dataset.value ?? '';
            label.textContent = option.textContent.trim();
            dropdown.querySelectorAll('[data-dropdown-option]').forEach((item) => {
                const active = item === option;
                item.classList.toggle('active', active);
                if (active) item.setAttribute('aria-current', 'true');
                else item.removeAttribute('aria-current');
            });
            input.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            input.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        });
    </script>
    @stack('scripts')
    @livewireScripts
</body>

</html>