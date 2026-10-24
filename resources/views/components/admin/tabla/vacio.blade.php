{{--
    Fila reutilizable para estados vacíos de tablas administrativas.
    La vista consumidora conserva el mensaje, las acciones y la lógica del módulo.
    Los atributos se aplican a la celda para preservar sus clases y configuración.
--}}
<tr>
    <td {{ $attributes }}>
        {{ $slot }}
    </td>
</tr>
