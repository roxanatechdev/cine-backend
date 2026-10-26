@props([
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'maxlength' => null,
    'autocomplete' => null,
])

@php
    $id = $id ?: $name;
    $hasError = $errors->has($name);
    $describedBy = trim(implode(' ', array_filter([
        $hasError ? $id . '-error' : null,
        $attributes->get('aria-describedby'),
    ])));
@endphp

<input
    id="{{ $id }}"
    name="{{ $name }}"
    type="{{ $type }}"
    value="{{ old($name, $value) }}"
    @if ($placeholder !== null) placeholder="{{ $placeholder }}" @endif
    @if ($required) required @endif
    @if ($maxlength !== null) maxlength="{{ $maxlength }}" @endif
    @if ($autocomplete !== null) autocomplete="{{ $autocomplete }}" @endif
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    {{ $attributes->except(['aria-describedby'])->class(['form-cm__control', 'is-invalid' => $hasError]) }}>
