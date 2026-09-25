<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

use Shuchkin\SimpleXLSXGen;

function ephdem_xls_error_abierta(int $http, string $mensaje): void
{
    http_response_code($http);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    ephdem_xls_error_abierta(405, 'Metodo no permitido. Usar GET.');
}

$ID_Usuario    = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
$id_proyeccion = isset($_GET['id'])         ? (int)$_GET['id']         : 0;

if (!$ID_Usuario || !$id_proyeccion) {
    ephdem_xls_error_abierta(400, 'usuario_id e id son obligatorios.');
}

require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';
require_once __DIR__ . '/../../lib/simplexlsxgen/SimpleXLSXGen.php';

$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) ephdem_xls_error_abierta(500, 'Error al conectar a la base de datos.');
mysqli_set_charset($conn, 'utf8mb4');

$sql  = "SELECT NOMBRE_PROYECCION, DATOS_JSON FROM EPHDEM_PROYECCIONES_GUARDADAS WHERE ID_PROYECCION = ? AND USER_ID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $id_proyeccion, $ID_Usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$row) ephdem_xls_error_abierta(404, 'Proyecto no encontrado.');

$nombre_proyecto = $row['NOMBRE_PROYECCION'];
$datos_json      = json_decode($row['DATOS_JSON'], true) ?? [];
$equiposSummary  = $datos_json['equipos_summary'] ?? [];
$recintoSummary  = $datos_json['recinto_summary'] ?? [];
$prestaciones    = $datos_json['prestaciones']    ?? [];

/* --- Headers 56 columnas ------------------------------------------- */
$HEADERS = [
    'Nª 5 agosto 2020', 'Nº', 'Subdirección', 'Centro de Responsabilidad', 'Área',
    'Unidad', 'SubUnidad', 'RECINTO', 'Número de Personas', 'Cantidad Recintos', 'm²',
    'm² Totales', 'Clase', 'Subclase', 'Def. ley de presupuesto',
    'Equipo/ Equipamiento/ Instrumental', 'OBSERVACIONES ', 'OBSERVACIONES MINSAL',
    'RESPUESTA SS', 'OBSERVACIONES MINSAL 29-07-2020', 'RESPUESTA SS 29-07-2020',
    'Observaciones 3 Agosto', 'recinto con observaciones',
    'E&E con requerimiento de obra (sí/no)', 'REQUERIMIENTO TOTAL', 'Existencia',
    'Recuperable', 'Vida útil residual estandarizada', 'Reposición Equipo año 0',
    'Equipos Nuevos', 'Vida útil ', 'P.Unit. Equipo cofin M$', 'P.Unit. Equipo US$',
    'Cant. Req. equipos total AÑO 0', ' M$  Repos. Equipos año 0',
    'US$ Repos. equipos año 0', ' M$ Equipos Nuevos', 'US$ Equipos Nuevos ',
    'M$ Costo Reposición periodo 15 años', 'US$ Costo Reposición periodo 15 años ',
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15',
];

function filaBlancaAbierta(): array
{
    return array_fill(0, 56, '');
}

/* --- Hoja 1: Habilitación ------------------------------------------ */
$hoja1 = [];

$fila1 = filaBlancaAbierta();
$fila1[2] = 'SERVICIO DE SALUD';
$hoja1[] = $fila1;

$fila2 = filaBlancaAbierta();
$fila2[2] = 'PROYECTO';
$fila2[3] = $nombre_proyecto;
$hoja1[] = $fila2;

$hoja1[] = filaBlancaAbierta();
$hoja1[] = filaBlancaAbierta();

$fila5 = filaBlancaAbierta();
$fila5[1] = 'DÓLAR';
$hoja1[] = $fila5;

$hoja1[] = array_map(static function ($h) {
    return '<b>' . $h . '</b>';
}, $HEADERS);

$n = 1;
foreach ($prestaciones as $p) {
    foreach ($p['EQUIPOS'] as $eq) {
        if (($eq['CANTIDAD'] ?? 0) <= 0) continue;
        $fila = filaBlancaAbierta();
        $fila[1]  = $n++;
        $fila[4]  = $p['AREA']              ?? '';
        $fila[7]  = $eq['RECINTO']          ?? '';
        $fila[15] = $eq['EQUIPO']           ?? '';
        $fila[16] = $p['NOMBRE_PRESTACION'] ?? '';
        $fila[24] = $eq['CANTIDAD']         ?? 0;
        $hoja1[] = $fila;
    }
}

if ($n === 1) {
    $fila = filaBlancaAbierta();
    $fila[15] = '(Sin equipamiento calculado)';
    $hoja1[] = $fila;
}

/* --- Hoja 2: Resumen equipos --------------------------------------- */
$hoja2 = [];
$hoja2[] = array_map(static function ($h) {
    return '<b>' . $h . '</b>';
}, ['Equipo', 'Cantidad total']);

arsort($equiposSummary);
if (empty($equiposSummary)) {
    $hoja2[] = ['(Sin equipamiento calculado)', ''];
} else {
    foreach ($equiposSummary as $eq => $cant) {
        if ($cant <= 0) continue;
        $hoja2[] = [$eq, $cant];
    }
}

/* --- Hoja 3: Resumen recintos -------------------------------------- */
$hoja3 = [];
$hoja3[] = array_map(static function ($h) {
    return '<b>' . $h . '</b>';
}, ['Recinto', 'Requerimiento']);

if (empty($recintoSummary)) {
    $hoja3[] = ['(Sin recintos calculados)', ''];
} else {
    foreach ($recintoSummary as $rec => $val) {
        if ($rec === '') continue;
        $hoja3[] = [$rec, $val];
    }
}

/* --- Salida -------------------------------------------------------- */
$xlsx = new SimpleXLSXGen();
$xlsx->addSheet($hoja1, 'Habilitación');
$xlsx->addSheet($hoja2, 'Resumen equipos');
$xlsx->addSheet($hoja3, 'Resumen recintos');

$filename = 'Reporte_EPH_Abierta_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre_proyecto) . '_' . date('YmdHis') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
if (ob_get_length()) ob_end_clean();
$xlsx->download();
exit;