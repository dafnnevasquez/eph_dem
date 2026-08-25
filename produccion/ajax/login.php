<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// ===== LEER BODY =====
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$correo     = trim($input['correo']     ?? '');
$contrasena = trim($input['contrasena'] ?? '');

if ($correo === '' || $contrasena === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Correo y contraseña son requeridos.']);
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

// ===== BUSCAR USUARIO =====
$stmt = mysqli_prepare($conn, "SELECT ID_Usuario, Nombre, Correo, Contrasena FROM SIGEM_Usuarios WHERE Correo = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor.']);
    mysqli_close($conn);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $correo);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$usuario) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Correo o contraseña incorrectos.']);
    mysqli_close($conn);
    exit;
}

// ===== VALIDAR CONTRASEÑA =====
// Descomenta la opción que corresponda:

// Opción A: Texto plano
//$valida = ($usuario['Contrasena'] === $contrasena);

// Opción B: MD5
// $valida = ($usuario['Contrasena'] === md5($contrasena));

// Opción C: bcrypt (password_hash)
$valida = password_verify($contrasena, $usuario['Contrasena']);

if (!$valida) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Correo o contraseña incorrectos.']);
    mysqli_close($conn);
    exit;
}

mysqli_close($conn);

echo json_encode([
    'ok'    => true,
    'datos' => [
        'id_usuario' => (int) $usuario['ID_Usuario'],
        'nombre'     => $usuario['Nombre'] ?? $usuario['Correo'],
        'correo'     => $usuario['Correo'],
    ],
]);
