{{--
    Contenedor visual reutilizable para la paginación de tablas administrativas.
    El módulo consumidor conserva el condicional y Livewire genera los enlaces
    mediante la plantilla de paginación que corresponda.
    Se preservan los atributos y el contenido proporcionados por la vista.
--}}
<div {{ $attributes }}>
    {{ $slot }}
</div>
