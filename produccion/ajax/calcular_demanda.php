<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * ajax/calcular_demanda.php
 *
 * Endpoint: POST
 * Recibe (JSON en el body, o form-encoded):
 *   proyecto_id : int
 *   filas       : array de objetos con
 *                   prestacion_id, demanda_anual, dias_laborales,
 *                   disponibilidad, jornada_efectiva
 *
 * Llama:
 *   SIGEM_EPHDEM_CERRADA_GuardarDemanda(...)
 *   SIGEM_EPHDEM_CERRADA_CalcularPabellones(...)
 *   SIGEM_EPHDEM_CERRADA_CalcularBoxes(...)
 *
 * Devuelve JSON unificado:
 *   { ok: true,
 *     datos: {
 *       proyecto_id, filas_guardadas,
 *       pabellones: { urgencia, electivo, total, fraccion_*, detalle },
 *       boxes:      { total, por_subarea, detalle }
 *     }
 *   }
 * Errores:
 *   { ok: false, error: "..." }   con HTTP 400/405/500.
 *
 * Conexion: igual patron que ajax/get_prestaciones.php
 * (placeholder USUARIO/PASSWORD que se reemplaza al desplegar).
 * =====================================================================
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

/**
 * Responde con JSON y termina.
 */
function ephdem_responder(int $http, array $payload): void
{
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/* --- Solo POST ----------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ephdem_responder(405, ['ok' => false, 'error' => 'Metodo no permitido. Usar POST.']);
}

/* --- Parseo del body ---------------------------------------------- */
$rawBody    = file_get_contents('php://input');
$contentTyp = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
$input      = [];

if (strpos($contentTyp, 'application/json') !== false && $rawBody !== '' && $rawBody !== false) {
    $decoded = json_decode($rawBody, true);
    if (!is_array($decoded)) {
        ephdem_responder(400, ['ok' => false, 'error' => 'JSON invalido en el body.']);
    }
    $input = $decoded;
} else {
    $input = $_POST;
}

$proyecto_id = isset($input['proyecto_id']) ? (int)$input['proyecto_id'] : 0;
$filasIn     = isset($input['filas']) && is_array($input['filas']) ? $input['filas'] : null;

if ($proyecto_id <= 0) {
    ephdem_responder(400, ['ok' => false, 'error' => 'proyecto_id es obligatorio y debe ser > 0.']);
}
if ($filasIn === null || count($filasIn) === 0) {
    ephdem_responder(400, ['ok' => false, 'error' => 'filas es obligatorio y no puede estar vacio.']);
}

/* --- Validacion y saneo fila por fila ----------------------------- */
$filasSane = [];
$errores   = [];
foreach ($filasIn as $i => $f) {
    if (!is_array($f)) {
        $errores[] = "fila[$i]: formato invalido";
        continue;
    }

    $pid  = isset($f['prestacion_id'])    ? (int)$f['prestacion_id']      : 0;
    $dem  = isset($f['demanda_anual'])    ? (int)$f['demanda_anual']      : -1;
    $dias = isset($f['dias_laborales'])   ? (int)$f['dias_laborales']     : 0;
    $disp = isset($f['disponibilidad'])   ? (float)$f['disponibilidad']   : -1.0;
    $jor  = isset($f['jornada_efectiva']) ? (float)$f['jornada_efectiva'] : 0.0;

    if ($pid <= 0)              { $errores[] = "fila[$i]: prestacion_id invalido"; continue; }
    if ($dem < 0)               { $errores[] = "fila[$i]: demanda_anual debe ser >= 0"; continue; }
    if ($dias <= 0)             { $errores[] = "fila[$i]: dias_laborales debe ser > 0"; continue; }
    if ($disp <= 0 || $disp > 1) { $errores[] = "fila[$i]: disponibilidad debe estar en (0,1]"; continue; }
    if ($jor <= 0 || $jor > 24) { $errores[] = "fila[$i]: jornada_efectiva debe estar en (0,24]"; continue; }

    $filasSane[] = [
        'prestacion_id'    => $pid,
        'demanda_anual'    => $dem,
        'dias_laborales'   => $dias,
        'disponibilidad'   => $disp,
        'jornada_efectiva' => $jor,
    ];
}

if (count($errores) > 0) {
    ephdem_responder(400, ['ok' => false, 'error' => 'Datos invalidos.', 'detalle' => $errores]);
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

/* --- Ejecucion ---------------------------------------------------- */
try {
    $guardadas  = SIGEM_EPHDEM_CERRADA_GuardarDemanda($conn, $proyecto_id, $filasSane);
    $pabellones = SIGEM_EPHDEM_CERRADA_CalcularPabellones($conn, $proyecto_id);
    $boxes      = SIGEM_EPHDEM_CERRADA_CalcularBoxes($conn, $proyecto_id);
    $equipamiento = SIGEM_EPHDEM_CERRADA_CalcularEquipamiento($conn, $proyecto_id);
    $vistas = SIGEM_EPHDEM_CERRADA_CalcularEquipamientoPorRecinto($conn, $equipamiento);
    $urpa = SIGEM_EPHDEM_CERRADA_CalcularURPA($conn, (int)$pabellones['pabellones_total']);
} catch (\Throwable $e) {
    mysqli_close($conn);
    ephdem_responder(500, ['ok' => false, 'error' => 'Error en el calculo: ' . $e->getMessage()]);
}

mysqli_close($conn);

ephdem_responder(200, [
    'ok'    => true,
    'datos' => [
        'proyecto_id'     => $proyecto_id,
        'filas_guardadas' => $guardadas,
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
                    'equipo_id'      => $e['equipo_id'],
                    'nombre_equipo'  => $e['nombre_equipo'],
                    'cantidad'       => $e['cantidad'],
                    'cantidad_piso'  => $e['cantidad_piso'],
                    'cantidad_demanda' => $e['cantidad_demanda'],
                    'origenes'       => $e['origenes'],
                ];
            }, $equipamiento['equipos']),
            'por_recinto' => $vistas['por_recinto'],
            'demanda_compartida' => $vistas['demanda_compartida'],
        ],
        'urpa' => $urpa,
    ],
]);
