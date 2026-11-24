<?php
declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * ajax/generar/restriccion_datos.php
 *
 * Helper compartido por generar_xls_restriccion.php y
 * generar_pdf_restriccion.php: abre la conexion, verifica que el
 * proyecto sea del usuario y calcula la restriccion de RR.HH. con los
 * mismos services que usa la pantalla (rrhh.php?action=calcular_restriccion).
 *
 * Errores: texto plano + exit (igual que los generadores existentes).
 * =====================================================================
 */

require_once __DIR__ . '/../services/RRHHService.php';
require_once __DIR__ . '/../services/PrestacionPerfilService.php';
require_once __DIR__ . '/../services/RestriccionProyectoService.php';

function ephdem_restriccion_error(int $http, string $mensaje): void
{
    http_response_code($http);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

/**
 * Lee proyecto_id y usuario_id de la URL, verifica el dueño y devuelve
 * ['nombre' => string, 'res' => array (mismo formato que la pantalla)].
 */
function ephdem_restriccion_cargar(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        ephdem_restriccion_error(405, 'Metodo no permitido. Usar GET.');
    }

    $proyectoId = isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0;
    $usuarioId  = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
    if ($proyectoId <= 0 || $usuarioId <= 0) {
        ephdem_restriccion_error(400, 'proyecto_id y usuario_id son obligatorios.');
    }

    // Conexion: misma logica que generar_xls_cerrada.php
    global $conn;
    if (!isset($conn) || !($conn instanceof \mysqli)) {
        $localConfig = __DIR__ . '/../config/database.local.php';
        if (file_exists($localConfig)) {
            require_once $localConfig;
        } else {
            require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';
            $conn = SIGEM_UV_C_Nueva_Conexion();
        }
    }
    if (!($conn instanceof \mysqli)) {
        ephdem_restriccion_error(500, 'No se pudo conectar a la base de datos.');
    }
    mysqli_set_charset($conn, 'utf8mb4');

    // El proyecto debe pertenecer al usuario
    $st = $conn->prepare('SELECT Nombre_proyecto FROM EPHAC_Proyectos WHERE id_proyecto = ? AND usuario_id = ? LIMIT 1');
    if (!$st) {
        ephdem_restriccion_error(500, 'Error al verificar el proyecto.');
    }
    $st->bind_param('ii', $proyectoId, $usuarioId);
    $st->execute();
    $fila = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$fila) {
        mysqli_close($conn);
        ephdem_restriccion_error(403, 'No tienes acceso a este proyecto.');
    }
    $nombre = (string)$fila['Nombre_proyecto'];

    try {
        $pps    = new PrestacionPerfilService($conn);
        $rps    = new RestriccionProyectoService($conn);
        $rrhh   = new RRHHService($conn);
        $actual = $rps->datosCalculoActual($proyectoId);
        $res = RestriccionProyectoService::calcular(
            $pps->prestacionesProyecto($proyectoId),
            $pps->catalogoProyecto($proyectoId),
            $rrhh->obtener($proyectoId),
            $actual['modulos'],
            $actual['tipo5'],
            $actual['equipamiento']
        );
    } catch (\Throwable $e) {
        mysqli_close($conn);
        ephdem_restriccion_error(500, 'Error en el calculo: ' . $e->getMessage());
    }
    mysqli_close($conn);

    return ['nombre' => $nombre, 'res' => $res];
}

/** Total de personas que faltan (suma de brechas de todos los perfiles). */
function ephdem_restriccion_faltan(array $res): float
{
    $t = 0.0;
    foreach ($res['recintos'] as $r) {
        foreach ($r['por_perfil'] as $p) {
            if ($p['brecha'] !== null && $p['brecha'] > 0) {
                $t += (float)$p['brecha'];
            }
        }
    }
    return $t;
}

/** Total de prestaciones no cubiertas al año. */
function ephdem_restriccion_no_cubiertas(array $res): float
{
    $t = 0.0;
    foreach ($res['recintos'] as $r) {
        $t += (float)$r['prestaciones_no_cubiertas'];
    }
    return $t;
}

function ephdem_num($x, int $dec = 0): string
{
    return number_format((float)$x, $dec, ',', '.');
}

function ephdem_pct($x): string
{
    return number_format((float)$x * 100, 0, ',', '.') . ' %';
}

function ephdem_nombre_archivo(string $prefijo, string $nombre, string $ext): string
{
    return $prefijo . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre) . '_' . date('YmdHis') . '.' . $ext;
}