@props([
    'name',
    'label',
    'id' => null,
    'required' => false,
    'help' => null,
    'showError' => true,
])

@php
    $id = $id ?: $name;
    $helpId = $id . '-ayuda';
    $errorId = $id . '-error';
@endphp

<div {{ $attributes->class(['form-cm__group']) }}>
    <label for="{{ $id }}" class="form-cm__label">
        <span>{{ $label }}</span>
        @if ($required)
            <span class="required" aria-hidden="true">*</span>
            <span class="visually-hidden">(obligatorio)</span>
        @endif
    </label>

    {{ $slot }}

    @if ($help)
        <div id="{{ $helpId }}" class="form-cm__text">{{ $help }}</div>
    @endif

    @if ($showError)
        @error($name)
            <div id="{{ $errorId }}" class="form-cm__feedback" role="alert">{{ $message }}</div>
        @enderror
    @endif
</div>
