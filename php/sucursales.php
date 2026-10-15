<?php
declare(strict_types=1);

require_once __DIR__ . '/api.php';
iniciar_api('GET');
session_write_close();

$bodega = filter_input(INPUT_GET, 'bodega_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
if (!$bodega) {
    responder(['ok' => false, 'campo' => 'bodega_id', 'mensaje' => 'Debe seleccionar una bodega.'], 422);
}

$consulta = conexion()->prepare('SELECT id, nombre FROM sucursales WHERE bodega_id = :bodega ORDER BY id');
$consulta->execute(['bodega' => $bodega]);
responder(['ok' => true, 'sucursales' => $consulta->fetchAll()]);
