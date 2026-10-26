@props([
    'name',
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'maxlength' => null,
    'rows' => 3,
])

@php
    $id = $id ?: $name;
    $hasError = $errors->has($name);
    $describedBy = trim(implode(' ', array_filter([
        $hasError ? $id . '-error' : null,
        $attributes->get('aria-describedby'),
    ])));
@endphp

<textarea
    id="{{ $id }}"
    name="{{ $name }}"
    rows="{{ $rows }}"
    @if ($placeholder !== null) placeholder="{{ $placeholder }}" @endif
    @if ($required) required @endif
    @if ($maxlength !== null) maxlength="{{ $maxlength }}" @endif
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    {{ $attributes->except(['aria-describedby'])->class(['form-cm__control', 'is-invalid' => $hasError]) }}>{{ old($name, $value) }}</textarea>
