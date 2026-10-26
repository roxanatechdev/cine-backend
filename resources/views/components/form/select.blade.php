@props([
'name',
'options' => [],
'selected' => '',
'placeholder' => 'Selecciona una opción',
'id' => null,
'disabled' => false,
'help' => null,
])

@php
$id = $id ?: $name;
$selected = (string) old($name, $selected ?? '');
$selectedLabel = $options[$selected] ?? $placeholder;
$hasError = $errors->has($name);

$helpId = $id . '-ayuda';
$errorId = $id . '-error';
@endphp

<div class="dropdown form-cm__dropdown">
    <input
        type="hidden"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $selected }}"
        {{ $attributes }}
        @if ($disabled) disabled @endif>

    <button
        type="button"
        id="{{ $id }}_toggle"
        class="form-cm__control form-cm__select-toggle
            dropdown-toggle {{ $hasError ? 'is-invalid' : '' }}"
        data-bs-toggle="dropdown"
        data-bs-auto-close="true"
        aria-expanded="false"
        aria-haspopup="true"
        aria-controls="{{ $id }}_menu"
        aria-label="{{ $name }}"
        @if ($hasError) aria-describedby="{{ $errorId }}" @elseif ($help) aria-describedby="{{ $helpId }}" @endif
        @if ($disabled) disabled @endif>
        <span data-dropdown-label>{{ $selectedLabel }}</span>
    </button>

    <ul
        id="{{ $id }}_menu"
        class="dropdown-menu form-cm__select-menu w-100"
        aria-labelledby="{{ $id }}_toggle">
        @foreach ($options as $value => $label)
        <li>
            <button
                type="button"
                class="dropdown-item {{ $selected === (string) $value ? 'active' : '' }}"
                data-dropdown-option
                data-value="{{ $value }}"
                @if ($selected===(string) $value)
                aria-current="true"
                @endif>
                {{ $label }}
            </button>
        </li>
        @endforeach
    </ul>

    @if ($help)
    <div id="{{ $helpId }}" class="form-cm__text">
        {{ $help }}
    </div>
    @endif

    @error($name)
    <div id="{{ $errorId }}" class="form-cm__feedback" role="alert">
        {{ $message }}
    </div>
    @enderror
</div>