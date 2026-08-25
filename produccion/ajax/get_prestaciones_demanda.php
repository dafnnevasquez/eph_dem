<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * ajax/get_prestaciones_demanda.php
 *
 * Acepta GET o POST. Parametros (cualquiera de los dos):
 *   proyecto_id      : int            (lee prestaciones ya cargadas en
 *                                      EPHAC_Proyecto_Demanda)
 *   prestacion_ids[] : int[]          (lista explicita; tiene prioridad
 *                                      sobre proyecto_id)
 *   En body application/json tambien aceptamos:
 *       {"proyecto_id": 1}
 *       {"prestacion_ids": [101, 102, 103]}
 *
 * Para cada prestacion devuelve:
 *   id_prestacion, nombre_prestacion, area_hospitalaria,
 *   subarea_hospitalaria,
 *   categoria  : 'urgencia' | 'electivo' | 'box'
 *                (box ← area_hospitalaria = EPHDEM_AREA_UPC,
 *                 urgencia ← presencia en EPHDEM_IDS_URGENCIA,
 *                 electivo ← resto)
 *   defaults   : SIGEM_EPHDEM_CERRADA_DefaultsPorCategoria(categoria)
 *   valores    : { demanda_anual, dias_laborales, disponibilidad,
 *                  jornada_efectiva } ya guardados en
 *                EPHAC_Proyecto_Demanda para (proyecto_id, prestacion_id),
 *                o null si no hay nada guardado (o si se llamo con
 *                prestacion_ids[] sin proyecto_id). Pensado para precargar
 *                el formulario de parametros al editar un proyecto ya
 *                calculado (tarea "editar y recalcular").
 *
 * Conexion: igual patron que ajax/obtener_resultados_proyecto.php
 * (soporta config/database.local.php en desarrollo, y el conector propio
 * de SIGEM-UV en produccion).
 * =====================================================================
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

function ephdem_responder(int $http, array $payload): void
{
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/* --- Recoleccion de inputs (query, form y JSON) ------------------- */
$input = array_merge($_GET, $_POST);

$rawBody    = file_get_contents('php://input');
$contentTyp = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
if (strpos($contentTyp, 'application/json') !== false && $rawBody !== '' && $rawBody !== false) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $input = array_merge($input, $decoded);
    }
}

$proyecto_id    = isset($input['proyecto_id']) ? (int)$input['proyecto_id'] : 0;
$prestacion_ids = [];
if (isset($input['prestacion_ids']) && is_array($input['prestacion_ids'])) {
    foreach ($input['prestacion_ids'] as $pid) {
        $pidInt = (int)$pid;
        if ($pidInt > 0) {
            $prestacion_ids[] = $pidInt;
        }
    }
    $prestacion_ids = array_values(array_unique($prestacion_ids));
}

if (count($prestacion_ids) === 0 && $proyecto_id <= 0) {
    ephdem_responder(400, [
        'ok'    => false,
        'error' => 'Debe enviarse proyecto_id (>0) o prestacion_ids[] no vacio.',
    ]);
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

if (!$conn) {
    ephdem_responder(500, ['ok' => false, 'error' => 'No se pudo conectar a la base de datos.']);
}
mysqli_set_charset($conn, 'utf8mb4');

/* --- Carga de constantes y funciones del modulo ------------------- */
require_once __DIR__ . '/../calculo_equipamiento.php';

/* --- Si no vino lista explicita, resolvemos desde el proyecto ----- */
if (count($prestacion_ids) === 0) {
    $sql  = 'SELECT DISTINCT prestacion_id
             FROM ' . EPHDEM_TBL_DEMANDA . '
             WHERE proyecto_id = ?';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        mysqli_close($conn);
        ephdem_responder(500, ['ok' => false, 'error' => 'No se pudo preparar la consulta de proyecto.']);
    }
    $stmt->bind_param('i', $proyecto_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $prestacion_ids[] = (int)$r['prestacion_id'];
    }
    $stmt->close();

    if (count($prestacion_ids) === 0) {
        mysqli_close($conn);
        ephdem_responder(200, ['ok' => true, 'datos' => []]);
    }
}

/* --- Valores de demanda ya guardados (solo si vino proyecto_id) ---
 * Se usa para precargar el formulario de parametros al editar un
 * proyecto existente. Si el endpoint se llamo con prestacion_ids[]
 * explicito (sin proyecto_id), no hay de donde sacar valores guardados:
 * 'valores' queda null para todas las filas. */
$valoresPorPrestacion = [];
if ($proyecto_id > 0) {
    $sqlVal = "SELECT prestacion_id, demanda_anual, dias_laborales, disponibilidad, jornada_efectiva
               FROM " . EPHDEM_TBL_DEMANDA . "
               WHERE proyecto_id = ?";
    $stmtVal = $conn->prepare($sqlVal);
    if ($stmtVal !== false) {
        $stmtVal->bind_param('i', $proyecto_id);
        $stmtVal->execute();
        $resVal = $stmtVal->get_result();
        while ($rowVal = $resVal->fetch_assoc()) {
            $valoresPorPrestacion[(int)$rowVal['prestacion_id']] = [
                'demanda_anual'    => (int)$rowVal['demanda_anual'],
                'dias_laborales'   => (int)$rowVal['dias_laborales'],
                'disponibilidad'   => (float)$rowVal['disponibilidad'],
                'jornada_efectiva' => (float)$rowVal['jornada_efectiva'],
            ];
        }
        $stmtVal->close();
    }
}

/* --- Consulta principal con IN dinamico --------------------------- */
/* La categoria ya no se calcula en SQL: la tabla de urgencia fue           */
/* reemplazada por la constante EPHDEM_IDS_URGENCIA (in_array en PHP).      */
$placeholders = implode(',', array_fill(0, count($prestacion_ids), '?'));
$sql = "SELECT p.id_prestacion,
               p.nombre_prestacion,
               p.area_hospitalaria,
               p.subarea_hospitalaria
        FROM " . EPHDEM_TBL_PRESTACIONES . " p
        WHERE p.id_prestacion IN ($placeholders)";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    mysqli_close($conn);
    ephdem_responder(500, ['ok' => false, 'error' => 'No se pudo preparar la consulta de prestaciones.']);
}
$tipos = str_repeat('i', count($prestacion_ids));
$stmt->bind_param($tipos, ...$prestacion_ids);
$stmt->execute();
$res = $stmt->get_result();

$datos = [];
while ($row = $res->fetch_assoc()) {
    $idPrestacion = (int)$row['id_prestacion'];
    if ($row['area_hospitalaria'] === EPHDEM_AREA_UPC) {
        $categoria = 'box';
    } elseif (in_array($idPrestacion, EPHDEM_IDS_URGENCIA, true)) {
        $categoria = 'urgencia';
    } else {
        $categoria = 'electivo';
    }
    $datos[] = [
        'id_prestacion'        => $idPrestacion,
        'nombre_prestacion'    => $row['nombre_prestacion'],
        'area_hospitalaria'    => $row['area_hospitalaria'],
        'subarea_hospitalaria' => $row['subarea_hospitalaria'],
        'categoria'            => $categoria,
        'defaults'             => SIGEM_EPHDEM_CERRADA_DefaultsPorCategoria($categoria),
        'valores'              => $valoresPorPrestacion[$idPrestacion] ?? null,
    ];
}
$stmt->close();
mysqli_close($conn);

ephdem_responder(200, ['ok' => true, 'datos' => $datos]);
