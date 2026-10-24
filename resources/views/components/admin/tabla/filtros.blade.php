{{--
    Contenedor reutilizable para la zona de filtros de una tabla administrativa.

    Reglas de uso:
    - La etiqueta raíz permitida puede ser `section` (predeterminada) o `div`.
    - El módulo consumidor conserva los controles, el formulario y la lógica de filtrado.
    - Las clases y los demás atributos HTML se reciben mediante $attributes.
    - El contenido original de la vista se inserta mediante $slot.

    Se usan ramas explícitas para evitar depender de etiquetas HTML dinámicas en Blade.
--}}
@props([
    'tag' => 'section',
])

@if ($tag === 'div')
    <div {{ $attributes }}>
        {{ $slot }}
    </div>
@else
    <section {{ $attributes }}>
        {{ $slot }}
    </section>
@endif
