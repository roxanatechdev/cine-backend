{{--
    Contenedor visual reutilizable para tablas administrativas.
    Conserva las clases y atributos proporcionados por la vista que lo utiliza.
    No añade estilos ni estructura interna adicional.
--}}
<div {{ $attributes }}>
    {{ $slot }}
</div>
