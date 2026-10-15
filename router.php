<?php
declare(strict_types=1);

// El servidor de desarrollo solo debe exponer el formulario y sus endpoints.
$ruta = rawurldecode(explode('?', $_SERVER['REQUEST_URI'], 2)[0]);
if ($ruta === '/') {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/index.html');
    return true;
}

if (in_array($ruta, [
    '/index.html', '/css/estilos.css', '/js/formulario.js',
    '/php/catalogos.php', '/php/sucursales.php', '/php/productos.php',
], true)) {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Recurso no encontrado.';
return true;
