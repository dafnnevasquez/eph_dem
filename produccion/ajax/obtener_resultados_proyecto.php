<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * ajax/obtener_resultados_proyecto.php
 *
 * Endpoint: GET
 * Params:
 *   proyecto_id : int, obligatorio, > 0
 *   usuario_id  : int, obligatorio, > 0
 *
 * Verifica que el proyecto exista y pertenezca a usuario_id (tabla
 * EPHAC_Proyectos), luego recalcula resultados desde proyecto_id (la
 * demanda ya esta persistida en BD), igual patron que
 * generar_xls_cerrada.php / generar_pdf_cerrada.php. No recibe filas ni
 * llama a GuardarDemanda.
 *
 * Devuelve el mismo contrato JSON que ajax/calcular_demanda.php
 * (ver docs/contrato_resultados_equipamiento.md), agregando
 * 'nombre_proyecto' al nivel superior de 'datos'.
 * =====================================================================
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

/**
 * Responde con JSON y termina.
 */
function ephdem_resultados_responder(int $http, array $payload): void
{
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/* --- Solo GET -------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    ephdem_resultados_responder(405, ['ok' => false, 'error' => 'Metodo no permitido. Usar GET.']);
}

/* --- Parametros -------------------------------------------------------- */
$proyecto_id = isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0;
$usuario_id  = isset($_GET['usuario_id'])  ? (int)$_GET['usuario_id']  : 0;

if ($proyecto_id <= 0 || $usuario_id <= 0) {
    ephdem_resultados_responder(400, ['ok' => false, 'error' => 'proyecto_id y usuario_id son obligatorios y deben ser > 0.']);
}

/* --- Conexion ---
 * Servidor: SIGEM ya inyecta su propio $conn.
 * Local: si existe config/database.local.php (gitignored) lo usa para
 * definir $conn; si no existe, cae al conector propio de SIGEM-UV
 * (funciones_sigemuv_C_BaseDatos.php / SIGEM_UV_C_Nueva_Conexion). */
if (!isset($conn) || !($conn instanceof \mysqli)) {
    $localConfig = __DIR__ . '/../config/database.local.php';
    if (file_exists($localConfig)) {
        require_once $localConfig;
    } else {
        require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';
        $conn = SIGEM_UV_C_Nueva_Conexion();
    }
}

mysqli_set_charset($conn, 'utf8mb4');

/* --- Carga de funciones del modulo -------------------------------- */
require_once __DIR__ . '/../calculo_equipamiento.php';

/* --- Ownership check: el proyecto debe existir y pertenecer a usuario_id */
$stmt = $conn->prepare('SELECT id_proyecto, Nombre_proyecto FROM EPHAC_Proyectos WHERE id_proyecto = ? AND usuario_id = ? LIMIT 1');
$stmt->bind_param('ii', $proyecto_id, $usuario_id);
$stmt->execute();
$proyectoRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($proyectoRow === null) {
    mysqli_close($conn);
    ephdem_resultados_responder(404, ['ok' => false, 'error' => 'Proyecto no encontrado o no pertenece al usuario.']);
}

$nombre_proyecto = (string)$proyectoRow['Nombre_proyecto'];

/* --- Recalculo ----------------------------------------------------- */
try {
    $pabellones   = SIGEM_EPHDEM_CERRADA_CalcularPabellones($conn, $proyecto_id);
    $boxes        = SIGEM_EPHDEM_CERRADA_CalcularBoxes($conn, $proyecto_id);
    $equipamiento = SIGEM_EPHDEM_CERRADA_CalcularEquipamiento($conn, $proyecto_id);
    $vistas       = SIGEM_EPHDEM_CERRADA_CalcularEquipamientoPorRecinto($conn, $equipamiento);
    $urpa         = SIGEM_EPHDEM_CERRADA_CalcularURPA($conn, (int)$pabellones['pabellones_total']);
} catch (\Throwable $e) {
    error_log('obtener_resultados_proyecto.php: ' . $e->getMessage());
    mysqli_close($conn);
    ephdem_resultados_responder(500, ['ok' => false, 'error' => 'Error al calcular resultados del proyecto.']);
}

mysqli_close($conn);

ephdem_resultados_responder(200, [
    'ok'    => true,
    'datos' => [
        'proyecto_id'     => $proyecto_id,
        'nombre_proyecto' => $nombre_proyecto,
        'pabellones'      => [
            'urgencia'          => $pabellones['pabellones_urgencia'],
            'electivo'          => $pabellones['pabellones_electivo'],
            'total'             => $pabellones['pabellones_total'],
            'fraccion_urgencia' => $pabellones['fraccion_urgencia'],
            'fraccion_electivo' => $pabellones['fraccion_electivo'],
            'detalle'           => $pabellones['detalle'],
            'pabellones_por_recinto' => $pabellones['pabellones_por_recinto'],
        ],
        'boxes' => [
            'total'       => $boxes['boxes_total'],
            'por_subarea' => $boxes['boxes_por_subarea'],
            'detalle'     => $boxes['detalle'],
            'por_recinto' => $boxes['boxes_por_recinto'],
        ],
        'equipamiento' => [
            'equipos' => array_map(function ($e) {
                return [
                    'equipo_id'        => $e['equipo_id'],
                    'nombre_equipo'    => $e['nombre_equipo'],
                    'cantidad'         => $e['cantidad'],
                    'cantidad_piso'    => $e['cantidad_piso'],
                    'cantidad_demanda' => $e['cantidad_demanda'],
                    'origenes'         => $e['origenes'],
                ];
            }, $equipamiento['equipos']),
            'por_recinto' => $vistas['por_recinto'],
            'demanda_compartida' => $vistas['demanda_compartida'],
        ],
        'urpa' => $urpa,
    ],
]);
