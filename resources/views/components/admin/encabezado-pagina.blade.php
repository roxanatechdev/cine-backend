@props([
    'titulo',
    'descripcion' => null,
])

<header {{ $attributes->class(['admin-page-header']) }}>
    <div class="admin-page-header__text">
        <h1 class="admin-page-title">{{ $titulo }}</h1>
        @if ($descripcion)
            <p class="admin-page-description mb-0">{{ $descripcion }}</p>
        @endif
    </div>
    @if (isset($action))
        <div class="admin-page-header__actions">{{ $action }}</div>
    @endif
</header>
