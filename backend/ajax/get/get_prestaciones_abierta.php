<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

Response::soloGet();

$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) Response::error('No se pudo conectar a la base de datos.', 500);
mysqli_set_charset($conn, 'utf8mb4');

$sql = "SELECT DISTINCT p.ID_PRESTACION, p.cod_prestacion, p.nombre_prestacion, p.area, p.subarea
        FROM EPHDEM_PREST_RECINTO_EQ rel
        INNER JOIN EPHDEM_PRESTACION p ON rel.ID_PRESTACION = p.ID_PRESTACION
        ORDER BY p.area, p.cod_prestacion";

$result = mysqli_query($conn, $sql);
if (!$result) Response::error(mysqli_error($conn), 500);

$prestaciones = [];
while ($row = mysqli_fetch_assoc($result)) {
    $prestaciones[] = [
        'ID_PRESTACION'     => (int)$row['ID_PRESTACION'],
        'cod_prestacion'    => $row['cod_prestacion'],
        'nombre_prestacion' => $row['nombre_prestacion'],
        'area'              => $row['area'],
        'subarea'           => $row['subarea'] ?? '',
    ];
}
mysqli_close($conn);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'datos' => $prestaciones], JSON_UNESCAPED_UNICODE);