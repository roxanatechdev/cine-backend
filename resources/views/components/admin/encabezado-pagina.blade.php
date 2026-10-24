@props([
'titulo',
'descripcion' => null,
])

<header {{ $attributes->class(['admin-page-header']) }}>
    <div class="admin-page-header__text">
        <h1 class="admin-page-title text-white fw-bold mb-1 tracking-tight text-uppercase">{{ $titulo }}</h1>
        @if ($descripcion)
        <p class="admin-page-description mb-0 text-text-white-50 small mb-0 max-w-2xl">{{ $descripcion }}</p>
        @endif
    </div>
    @if (isset($action))
    <div class="admin-page-header__actions">{{ $action }}</div>
    @endif
</header>