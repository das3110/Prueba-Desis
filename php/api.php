<?php
declare(strict_types=1);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/limite.php';

function responder(array $datos, int $estado = 200): never
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function iniciar_api(string $metodo): void
{
    ini_set('display_errors', '0');
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');

    set_exception_handler(static function (Throwable $error): void {
        error_log((string) $error);
        responder(['ok' => false, 'mensaje' => 'No se pudo completar la operación. Intente nuevamente.'], 500);
    });

    if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
        header('Allow: ' . $metodo);
        responder(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
    }

    if ($metodo === 'POST') {
        // shortcut: contador por IP directa en un servidor; usar el proxy al desplegar en varios servidores.
        $ruta = sys_get_temp_dir() . '/registro-productos-' . hash('sha256', __DIR__) . '.json';
        $espera = registrar_intento($ruta, $_SERVER['REMOTE_ADDR'], time());
        if ($espera > 0) {
            header('Retry-After: ' . $espera);
            responder(['ok' => false, 'mensaje' => "Demasiados intentos de guardado. Espere $espera segundos y vuelva a intentarlo."], 429);
        }
    }

    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}
