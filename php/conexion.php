<?php
declare(strict_types=1);

function conexion(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $puerto = getenv('DB_PORT') ?: '5432';
    $base = getenv('DB_NAME') ?: 'registro_productos';
    $usuario = getenv('DB_USER');
    $clave = getenv('DB_PASSWORD');

    if ($usuario === false || $clave === false) {
        throw new RuntimeException('Configure DB_USER y DB_PASSWORD.');
    }

    return new PDO("pgsql:host=$host;port=$puerto;dbname=$base;options='--client_encoding=UTF8'", $usuario, $clave, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
