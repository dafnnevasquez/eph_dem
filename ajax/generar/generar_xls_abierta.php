<?php
declare(strict_types=1);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';
require_once __DIR__ . '/../calculo/funciones_sigemuv_Modulo_EPHDEM.php';

use Shuchkin\SimpleXLSXGen;
require_once __DIR__ . '/../../lib/simplexlsxgen/SimpleXLSXGen.php';

$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) die("Error al conectar a la base de datos.");

$ID_Usuario    = $_SESSION['ID_Usuario'] ?? null;
$id_proyeccion = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$ID_Usuario || !$id_proyeccion) die("Error: No se pudo identificar el usuario o el proyecto.");

$sql = "SELECT NOMBRE_PROYECCION, DATOS_JSON FROM EPHDEM_PROYECCIONES_GUARDADAS WHERE ID_PROYECCION = ? AND USER_ID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id_proyeccion, $ID_Usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) die("No se encontraron datos para esta proyección.");

$nombre_proyecto = $row['NOMBRE_PROYECCION'];
$datos_json      = json_decode($row['DATOS_JSON'], true);
if (!$datos_json) die("Error al leer datos del JSON.");

$equiposSummary = $datos_json['equipos_summary'] ?? [];
$recintoSummary = $datos_json['recinto_summary'] ?? [];
$prestaciones   = $datos_json['prestaciones']    ?? [];

// Hoja 1: Resumen equipos
$sheet1 = [["Resumen de Equipos — Atención Abierta"], ["Equipo", "Total (unid)"]];
foreach ($equiposSummary as $eq => $cant) $sheet1[] = [$eq, $cant];

// Hoja 2: Resumen recintos
$sheet2 = [["Resumen de Recintos"], ["Recinto", "Requerimiento"]];
foreach ($recintoSummary as $rec => $val) {
    if ($rec !== '') $sheet2[] = [$rec, $val];
}

// Hoja 3: Detalle por prestación
$sheet3 = [["Detalle por Prestación"], ["Código", "Prestación", "Área", "EEMM", "Equipo", "Tipo", "Recinto", "Cantidad"]];
foreach ($prestaciones as $p) {
    foreach ($p['EQUIPOS'] as $eq) {
        $sheet3[] = [
            $p['COD_PRESTACION']    ?? '',
            $p['NOMBRE_PRESTACION'] ?? '',
            $p['AREA']              ?? '',
            $p['REQUERIMIENTO']     ?? '',
            $eq['EQUIPO']           ?? '',
            $eq['TIPO_EQUIPO']      ?? '',
            $eq['RECINTO']          ?? '',
            $eq['CANTIDAD']         ?? '',
        ];
    }
}

$xlsx = new SimpleXLSXGen();
$xlsx->addSheet($sheet1, "Resumen Equipos");
$xlsx->addSheet($sheet2, "Resumen Recintos");
$xlsx->addSheet($sheet3, "Detalle Prestaciones");

$filename = "Reporte_AbiertA_" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre_proyecto) . "_" . date("YmdHis") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');
$xlsx->download();
exit;