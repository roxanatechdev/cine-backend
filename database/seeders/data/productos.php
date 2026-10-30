<?php

/**
 * Productos de dulcería (40). Precios en Bs.
 * Claves: categoria (nombre exacto de catalogos.php), nombre, descripcion, precio, tamano,
 * tipo_variante, contenido_ml, peso_gramos, estado, stock (null = inventario no controlado),
 * promo (opcional): [precio_promocional, desde_dias, hasta_dias] relativos a hoy.
 *   vigente: desde negativo y hasta positivo · vencida: ambos negativos · futura: ambos positivos.
 */
$p = fn (string $cat, string $nom, string $desc, float $precio, ?string $tam, ?string $var, ?int $ml, ?int $g, string $estado = 'ACTIVO', ?int $stock = null, ?array $promo = null): array => [
    'categoria' => $cat, 'nombre' => $nom, 'descripcion' => $desc, 'precio' => $precio,
    'tamano' => $tam, 'tipo_variante' => $var, 'contenido_ml' => $ml, 'peso_gramos' => $g,
    'estado' => $estado, 'stock' => $stock, 'promo' => $promo,
];

return [
    // Palomitas
    $p('Palomitas', 'Palomitas saladas pequeñas', 'Palomitas recién hechas con sal.', 15, 'Pequeño', 'Salado', null, 60, 'ACTIVO', 120),
    $p('Palomitas', 'Palomitas saladas medianas', 'Palomitas recién hechas con sal.', 22, 'Mediano', 'Salado', null, 100, 'ACTIVO', 150),
    $p('Palomitas', 'Palomitas saladas grandes', 'Balde grande de palomitas saladas.', 30, 'Grande', 'Salado', null, 150, 'ACTIVO', 90),
    $p('Palomitas', 'Palomitas caramelo pequeñas', 'Palomitas cubiertas de caramelo.', 18, 'Pequeño', 'Caramelo', null, 65, 'ACTIVO', 100),
    $p('Palomitas', 'Palomitas caramelo medianas', 'Palomitas cubiertas de caramelo.', 26, 'Mediano', 'Caramelo', null, 105, 'ACTIVO', null),
    $p('Palomitas', 'Palomitas caramelo grandes', 'Balde grande de palomitas con caramelo.', 34, 'Grande', 'Caramelo', null, 160, 'ACTIVO', 70, [28, -10, 20]),

    // Bebidas
    $p('Bebidas', 'Gaseosa cola pequeña', 'Gaseosa de cola con hielo.', 8, 'Pequeño', 'Cola', 350, null, 'ACTIVO', null),
    $p('Bebidas', 'Gaseosa cola mediana', 'Gaseosa de cola con hielo.', 12, 'Mediano', 'Cola', 500, null, 'ACTIVO', null),
    $p('Bebidas', 'Gaseosa cola grande', 'Gaseosa de cola con hielo.', 16, 'Grande', 'Cola', 750, null, 'ACTIVO', null, [13, -10, 20]),
    $p('Bebidas', 'Gaseosa limón mediana', 'Gaseosa sabor limón.', 12, 'Mediano', 'Limón', 500, null, 'ACTIVO', 200),
    $p('Bebidas', 'Gaseosa limón grande', 'Gaseosa sabor limón.', 16, 'Grande', 'Limón', 750, null, 'ACTIVO', 180),
    $p('Bebidas', 'Agua mineral', 'Agua mineral con gas.', 8, 'Mediano', 'Con gas', 500, null, 'ACTIVO', 300),
    $p('Bebidas', 'Jugo de naranja', 'Jugo de naranja natural.', 14, 'Mediano', 'Naranja', 400, null, 'ACTIVO', 80),
    $p('Bebidas', 'Jugo de maracuyá', 'Jugo de maracuyá.', 14, 'Mediano', 'Maracuyá', 400, null, 'ACTIVO', 60),
    $p('Bebidas', 'Café americano', 'Café recién preparado.', 12, 'Pequeño', 'Americano', 240, null, 'ACTIVO', null),
    $p('Bebidas', 'Limonada frozen', 'Limonada granizada.', 20, 'Grande', 'Frozen', 500, null, 'INACTIVO', 40),

    // Dulces y snacks
    $p('Dulces y snacks', 'Confites de chocolate', 'Confites cubiertos de chocolate.', 12, null, 'Chocolate', null, 45, 'ACTIVO', 150),
    $p('Dulces y snacks', 'Gomitas ácidas', 'Gomitas con cubierta ácida.', 10, null, 'Ácido', null, 60, 'AGOTADO', 0),
    $p('Dulces y snacks', 'Barra de chocolate', 'Barra de chocolate con leche.', 9, null, 'Leche', null, 40, 'ACTIVO', 110),
    $p('Dulces y snacks', 'Caramelos masticables', 'Caramelos de frutas.', 8, null, 'Frutas', null, 50, 'ACTIVO', 95),
    $p('Dulces y snacks', 'Maní confitado', 'Maní cubierto de azúcar.', 10, null, 'Confitado', null, 70, 'ACTIVO', 85),
    $p('Dulces y snacks', 'Papas fritas', 'Bolsa de papas fritas.', 12, 'Mediano', 'Clásicas', null, 80, 'ACTIVO', 130),
    $p('Dulces y snacks', 'Galletas rellenas', 'Galletas rellenas de crema.', 8, null, 'Vainilla', null, 55, 'ACTIVO', null),

    // Comida caliente
    $p('Comida caliente', 'Nachos con queso', 'Nachos con salsa de queso.', 25, 'Mediano', 'Queso', null, 180, 'ACTIVO', 100, [21, 5, 30]),
    $p('Comida caliente', 'Nachos con carne', 'Nachos con queso y carne molida.', 35, 'Grande', 'Carne', null, 260, 'ACTIVO', 60),
    $p('Comida caliente', 'Hot dog clásico', 'Salchicha con pan y aderezos.', 20, 'Mediano', 'Clásico', null, 170, 'ACTIVO', 90),
    $p('Comida caliente', 'Hot dog jumbo', 'Hot dog grande con papas al hilo.', 28, 'Grande', 'Jumbo', null, 230, 'ACTIVO', 70, [24, -40, -10]),
    $p('Comida caliente', 'Pizza personal', 'Pizza personal de queso.', 30, 'Mediano', 'Queso', null, 250, 'ACTIVO', 40),

    // Helados y postres
    $p('Helados y postres', 'Helado de vainilla', 'Copa de helado de vainilla.', 12, 'Mediano', 'Vainilla', 150, null, 'ACTIVO', null),
    $p('Helados y postres', 'Helado de chocolate', 'Copa de helado de chocolate.', 12, 'Mediano', 'Chocolate', 150, null, 'ACTIVO', null),
    $p('Helados y postres', 'Sundae de frutilla', 'Helado con salsa de frutilla.', 16, 'Mediano', 'Frutilla', 180, null, 'ACTIVO', 75),
    $p('Helados y postres', 'Brownie con helado', 'Brownie tibio con helado.', 22, 'Mediano', 'Chocolate', null, 200, 'AGOTADO', 0),

    // Combos
    $p('Combos', 'Combo individual', 'Palomitas medianas y gaseosa mediana.', 40, 'Individual', null, 500, null, 'ACTIVO', null, [34, -40, -10]),
    $p('Combos', 'Combo pareja', 'Palomitas grandes y dos gaseosas medianas.', 65, 'Pareja', null, 1000, null, 'ACTIVO', null, [55, -10, 20]),
    $p('Combos', 'Combo familiar', 'Palomitas grandes, nachos y cuatro gaseosas.', 90, 'Familiar', null, 2000, null, 'ACTIVO', null, [78, -10, 20]),
    $p('Combos', 'Combo infantil', 'Palomitas pequeñas, jugo y sorpresa.', 42, 'Infantil', null, 400, null, 'ACTIVO', 80, [36, 5, 30]),
    $p('Combos', 'Combo nachos', 'Nachos con queso y dos gaseosas.', 55, 'Pareja', null, 1000, null, 'ACTIVO', 90),
    $p('Combos', 'Combo premium', 'Palomitas caramelo, hot dog y gaseosa grande.', 78, 'Individual', null, 750, null, 'AGOTADO', 0),

    // Temporada (categoría inactiva)
    $p('Temporada (inactiva)', 'Palomitas de calabaza', 'Edición Halloween, fuera de temporada.', 28, 'Mediano', 'Calabaza', null, 110, 'INACTIVO', 0),
    $p('Temporada (inactiva)', 'Chocolate caliente navideño', 'Edición de fin de año.', 18, 'Mediano', 'Chocolate', 300, null, 'INACTIVO', 25),
];
