<?php
declare(strict_types=1);

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/validaciones.php';
iniciar_api('POST');

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    responder(['ok' => false, 'mensaje' => 'La sesión no es válida. Recargue la página e intente nuevamente.'], 403);
}
session_write_close();

// Los formularios multipart transmiten saltos CRLF; el textarea los cuenta como LF.
if (is_string($_POST['descripcion'] ?? null)) {
    $_POST['descripcion'] = str_replace(["\r\n", "\r"], "\n", $_POST['descripcion']);
}

$error = validar_producto($_POST);
if ($error !== null) {
    responder(['ok' => false] + $error, 422);
}

$db = conexion();
foreach ([
    ['SELECT id FROM bodegas WHERE id = ?', [$_POST['bodega_id']], 'bodega_id', 'Debe seleccionar una bodega.'],
    ['SELECT id FROM sucursales WHERE id = ? AND bodega_id = ?', [$_POST['sucursal_id'], $_POST['bodega_id']], 'sucursal_id', 'Debe seleccionar una sucursal para la bodega seleccionada.'],
    ['SELECT id FROM monedas WHERE id = ?', [$_POST['moneda_id']], 'moneda_id', 'Debe seleccionar una moneda para el producto.'],
] as [$sql, $parametros, $campo, $mensaje]) {
    $consulta = $db->prepare($sql);
    $consulta->execute($parametros);
    if ($consulta->fetchColumn() === false) {
        responder(['ok' => false, 'campo' => $campo, 'mensaje' => $mensaje], 422);
    }
}

$consulta = $db->prepare('SELECT id FROM productos WHERE codigo = ?');
$consulta->execute([$_POST['codigo']]);
if ($consulta->fetchColumn() !== false) {
    responder(['ok' => false, 'campo' => 'codigo', 'mensaje' => 'El código del producto ya está registrado.'], 409);
}

try {
    $db->beginTransaction();
    $consulta = $db->prepare('INSERT INTO productos (codigo, nombre, bodega_id, sucursal_id, moneda_id, precio, descripcion)
        VALUES (:codigo, :nombre, :bodega_id, :sucursal_id, :moneda_id, :precio, :descripcion) RETURNING id');
    $consulta->execute([
        'codigo' => $_POST['codigo'],
        'nombre' => $_POST['nombre'],
        'bodega_id' => $_POST['bodega_id'],
        'sucursal_id' => $_POST['sucursal_id'],
        'moneda_id' => $_POST['moneda_id'],
        'precio' => $_POST['precio'],
        'descripcion' => $_POST['descripcion'],
    ]);
    $producto = $consulta->fetchColumn();
    $consulta = $db->prepare('INSERT INTO producto_material (producto_id, material_id) VALUES (?, ?)');
    foreach ($_POST['materiales'] as $material) {
        $consulta->execute([$producto, $material]);
    }
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if ($error instanceof PDOException && $error->getCode() === '23505') {
        responder(['ok' => false, 'campo' => 'codigo', 'mensaje' => 'El código del producto ya está registrado.'], 409);
    }
    throw $error;
}

responder(['ok' => true, 'mensaje' => 'Producto guardado correctamente.', 'producto_id' => $producto], 201);
