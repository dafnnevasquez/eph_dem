<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// ===== LEER PARÁMETRO =====
$usuario_id = isset($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : 0;

if ($usuario_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'usuario_id requerido.']);
    exit;
}

// ===== CONEXIÓN =====
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';
$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo conectar a la base de datos.']);
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

// ===== CONSULTA =====
$stmt = mysqli_prepare($conn, 
    "SELECT 
        id_proyecto,
        Nombre_proyecto AS nombre_proyecto,
        tipo_proyecto,
        DATE_FORMAT(fecha_creacion, '%d/%m/%Y') AS fecha_creacion, Datos_Json
     FROM EPHAC_Proyectos
     WHERE usuario_id = ?
     ORDER BY fecha_creacion DESC"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor.']);
    mysqli_close($conn);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $usuario_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$proyectos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['id'] = (int) $row['id_proyecto'];
    $proyectos[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode(['ok' => true, 'datos' => $proyectos]);
