@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->class(['form-cm']) }}>
    @if ($title || $subtitle)
        <header class="form-cm__section form-cm__section--heading">
            @if ($title)
                <h2 class="form-cm__title">{{ $title }}</h2>
            @endif
            @if ($subtitle)
                <p class="form-cm__subtitle">{{ $subtitle }}</p>
            @endif
        </header>
    @endif

    {{ $slot }}
</div>
