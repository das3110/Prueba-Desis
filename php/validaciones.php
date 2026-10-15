<?php
declare(strict_types=1);

function validar_producto(array $datos): ?array
{
    $texto = static fn(string $campo): string => is_string($datos[$campo] ?? null) ? $datos[$campo] : '';
    $vacio = static fn(string $valor): bool => preg_match('/\A[\s\x{FEFF}]*\z/u', $valor) === 1;
    $error = static fn(string $campo, string $mensaje): array => ['campo' => $campo, 'mensaje' => $mensaje];

    $codigo = $texto('codigo');
    if ($vacio($codigo)) {
        return $error('codigo', 'El código del producto no puede estar en blanco.');
    }
    if (preg_match('/\A(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9]+\z/', $codigo) !== 1) {
        return $error('codigo', 'El código del producto debe contener letras y números');
    }
    if (strlen($codigo) < 5 || strlen($codigo) > 15) {
        return $error('codigo', 'El código del producto debe tener entre 5 y 15 caracteres.');
    }

    $nombre = $texto('nombre');
    if ($vacio($nombre)) {
        return $error('nombre', 'El nombre del producto no puede estar en blanco.');
    }
    if (!mb_check_encoding($nombre, 'UTF-8') || mb_strlen($nombre, 'UTF-8') < 2 || mb_strlen($nombre, 'UTF-8') > 50) {
        return $error('nombre', 'El nombre del producto debe tener entre 2 y 50 caracteres.');
    }

    foreach ([
        'bodega_id' => 'Debe seleccionar una bodega.',
        'sucursal_id' => 'Debe seleccionar una sucursal para la bodega seleccionada.',
        'moneda_id' => 'Debe seleccionar una moneda para el producto.',
    ] as $campo => $mensaje) {
        if (preg_match('/\A[1-9][0-9]*\z/', $texto($campo)) !== 1
            || filter_var($texto($campo), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false) {
            return $error($campo, $mensaje);
        }
    }

    $precio = $texto('precio');
    if ($vacio($precio)) {
        return $error('precio', 'El precio del producto no puede estar en blanco.');
    }
    if (preg_match('/\A[0-9]+(?:\.[0-9]{1,2})?\z/', $precio) !== 1 || preg_match('/[1-9]/', $precio) !== 1) {
        return $error('precio', 'El precio del producto debe ser un número positivo con hasta dos decimales.');
    }

    $materiales = $datos['materiales'] ?? [];
    if (!is_array($materiales) || count($materiales) < 2 || count($materiales) > 5) {
        return $error('materiales', 'Debe seleccionar al menos dos materiales para el producto.');
    }
    foreach ($materiales as $material) {
        if (!is_string($material) || !in_array($material, ['1', '2', '3', '4', '5'], true)) {
            return $error('materiales', 'Los materiales seleccionados no son válidos.');
        }
    }
    if (count(array_unique($materiales)) !== count($materiales)) {
        return $error('materiales', 'Debe seleccionar al menos dos materiales distintos para el producto.');
    }

    $descripcion = $texto('descripcion');
    if ($vacio($descripcion)) {
        return $error('descripcion', 'La descripción del producto no puede estar en blanco.');
    }
    if (!mb_check_encoding($descripcion, 'UTF-8') || mb_strlen($descripcion, 'UTF-8') < 10 || mb_strlen($descripcion, 'UTF-8') > 1000) {
        return $error('descripcion', 'La descripción del producto debe tener entre 10 y 1000 caracteres.');
    }

    return null;
}
