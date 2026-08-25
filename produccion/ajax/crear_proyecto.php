<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Leer los datos del POST (normalmente vienen como JSON en Axios/Fetch)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$nombre_proyecto = $input['nombre_proyecto'] ?? '';
$tipo_proyecto   = $input['tipo_proyecto']   ?? 'Atencion cerrada';
$usuario_id      = isset($input['usuario_id']) ? (int) $input['usuario_id'] : null;

if (trim($nombre_proyecto) === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El nombre del proyecto es requerido']);
    exit;
}

// ===== CONEXIÓN =====
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';
$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo conectar a la base de datos']);
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

// ===== VALIDACIÓN DE DISPONIBILIDAD =====
$stmt = mysqli_prepare($conn, "SELECT id_proyecto FROM EPHAC_Proyectos WHERE Nombre_proyecto = ? AND usuario_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "si", $nombre_proyecto, $usuario_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['ok' => false, 'error' => 'Ya tienes un proyecto con este nombre. Por favor, elige otro.']);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit;
    }
    mysqli_stmt_close($stmt);
}

// ===== INSERCIÓN =====
$stmtInsert = mysqli_prepare($conn, "INSERT INTO EPHAC_Proyectos (Nombre_proyecto, fecha_creacion, usuario_id, Datos_Json) VALUES (?, NOW(), ?, NULL)");
if ($stmtInsert) {
    mysqli_stmt_bind_param($stmtInsert, "si", $nombre_proyecto, $usuario_id);
    if (mysqli_stmt_execute($stmtInsert)) {
        $id = mysqli_insert_id($conn);
        echo json_encode([
            'ok' => true, 
            'datos' => [
                'id_proyecto' => $id,
                'nombre_proyecto' => $nombre_proyecto,
                'tipo_proyecto' => $tipo_proyecto
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al crear el proyecto: ' . mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmtInsert);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al preparar la consulta de inserción: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
