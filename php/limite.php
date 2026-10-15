<?php
declare(strict_types=1);

function registrar_intento(string $ruta, string $ip, int $ahora): int
{
    $archivo = fopen($ruta, 'c+');
    if ($archivo === false) {
        throw new RuntimeException('No se pudo abrir el contador de solicitudes.');
    }

    try {
        if (!flock($archivo, LOCK_EX)) {
            throw new RuntimeException('No se pudo bloquear el contador de solicitudes.');
        }
        $contenido = stream_get_contents($archivo);
        if ($contenido === false) {
            throw new RuntimeException('No se pudo leer el contador de solicitudes.');
        }
        $registros = $contenido === '' ? [] : json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($registros)) {
            throw new RuntimeException('El contador de solicitudes no es válido.');
        }
        foreach ($registros as $clave => $instantes) {
            $registros[$clave] = array_values(array_filter($instantes, static fn(int $instante): bool => $instante > $ahora - 60));
            if ($registros[$clave] === []) {
                unset($registros[$clave]);
            }
        }

        $clave = hash('sha256', $ip);
        $instantes = $registros[$clave] ?? [];
        if (count($instantes) >= 20) {
            return max(1, $instantes[0] + 60 - $ahora);
        }
        $instantes[] = $ahora;
        $registros[$clave] = $instantes;
        $contenido = json_encode($registros, JSON_THROW_ON_ERROR);
        if (!rewind($archivo) || fwrite($archivo, $contenido) !== strlen($contenido)
            || !ftruncate($archivo, strlen($contenido)) || !fflush($archivo)) {
            throw new RuntimeException('No se pudo guardar el contador de solicitudes.');
        }
        return 0;
    } finally {
        flock($archivo, LOCK_UN);
        fclose($archivo);
    }
}
