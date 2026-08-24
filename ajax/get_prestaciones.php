<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// ===== CONEXIÓN =====
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';
$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo conectar a la base de datos']);
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

// ===== CONSULTA =====
function SIGEM_EPHDEM_CERRADA_GetPrestaciones(\mysqli $conn): ?array
{
    $sql = "SELECT p.id_prestacion, p.codigo_fonasa, p.nombre_prestacion,
                   p.area_hospitalaria, p.subarea_hospitalaria, p.recinto_base_id,
                   r.nombre_recinto,
               p.tiempo_procedimiento
            FROM EPHAC_Prestaciones p
            LEFT JOIN EPHAC_Recinto_Estandar r ON r.id_recinto = p.recinto_base_id
            ORDER BY p.nombre_prestacion ASC";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return null;
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'id_prestacion'        => (int) $row['id_prestacion'],
            'codigo_fonasa'        => $row['codigo_fonasa'],
            'nombre_prestacion'    => $row['nombre_prestacion'],
            'area_hospitalaria'    => $row['area_hospitalaria'],
            'subarea_hospitalaria' => $row['subarea_hospitalaria'],
            'recinto_base_id'      => $row['recinto_base_id'] !== null ? (int) $row['recinto_base_id'] : null,
            'nombre_recinto'       => $row['nombre_recinto'] ?? null,
            'tiempo_procedimiento' => $row['tiempo_procedimiento'] !== null ? (int) $row['tiempo_procedimiento'] : null,
        ];
    }
    return $data;
}

// ===== RESPUESTA =====
$prestaciones = SIGEM_EPHDEM_CERRADA_GetPrestaciones($conn);

if ($prestaciones === null) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

echo json_encode(['ok' => true, 'datos' => $prestaciones], JSON_UNESCAPED_UNICODE);
mysqli_close($conn);