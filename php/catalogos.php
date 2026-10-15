<?php
declare(strict_types=1);

require_once __DIR__ . '/api.php';
iniciar_api('GET');

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$token = $_SESSION['csrf'];
session_write_close();

$db = conexion();
responder([
    'ok' => true,
    'csrf' => $token,
    'bodegas' => $db->query('SELECT id, nombre FROM bodegas ORDER BY id')->fetchAll(),
    'monedas' => $db->query('SELECT id, nombre FROM monedas ORDER BY id')->fetchAll(),
]);
