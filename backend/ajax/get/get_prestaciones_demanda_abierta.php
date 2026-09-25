<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

Response::soloGet();

$proyectoId = (int)($_GET['proyecto_id'] ?? 0);
$usuarioId  = (int)($_GET['usuario_id']  ?? 0);

if ($proyectoId <= 0) Response::error('proyecto_id es requerido.', 400);

$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) Response::error('No se pudo conectar a la base de datos.', 500);
mysqli_set_charset($conn, 'utf8mb4');

$stmt = mysqli_prepare($conn,
    "SELECT DATOS_JSON FROM EPHDEM_PROYECCIONES_GUARDADAS
     WHERE ID_PROYECCION = ? AND USER_ID = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'ii', $proyectoId, $usuarioId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$row) Response::error('Proyecto no encontrado.', 404);

$datos = json_decode($row['DATOS_JSON'], true);
if (!$datos || empty($datos['prestaciones'])) {
    Response::ok([]);
}

// Devolver las prestaciones con sus parámetros
$prestaciones = array_map(fn($p) => [
    'ID_PRESTACION'     => $p['ID_PRESTACION'],
    'cod_prestacion'    => $p['COD_PRESTACION']    ?? '',
    'nombre_prestacion' => $p['NOMBRE_PRESTACION'] ?? '',
    'area'              => $p['AREA']              ?? '',
    'demanda'           => $p['DEMANDA_PROCEDIMIENTO'] ?? 0,
    'tiempo'            => $p['TIEMPO_PROCEDIMIENTO']  ?? 0,
    'diasLaborales'     => $p['DIAS_LABORALES']        ?? 260,
    'nSimultaneas'      => $p['N_SIMULTANEAS']          ?? 1,
    'disponibilidad'    => $p['DISPONIBILIDAD'] * 100  ?? 100,
    'jornada'           => $p['JORNADA']               ?? 7,
], $datos['prestaciones']);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'datos' => $prestaciones], JSON_UNESCAPED_UNICODE);