<?php
/**
 * login.php
 * Valida credenciales contra la tabla SIGEM_Usuarios.
 * POST { correo: string, contrasena: string }
 * Response { ok: bool, datos?: { id_usuario, nombre, correo }, error?: string }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// Leer body JSON
$body = file_get_contents('php://input');
$data = json_decode($body, true);

$correo    = trim($data['correo']    ?? '');
$contrasena = trim($data['contrasena'] ?? '');

if (!$correo || !$contrasena) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Correo y contraseña son requeridos.']);
    exit;
}

// Conexión a la base de datos
require_once __DIR__ . '/db_config.php'; // Ajusta la ruta según tu configuración

try {
    $stmt = $pdo->prepare(
        "SELECT ID_Usuario, Nombre, Correo, Contraseña 
         FROM SIGEM_Usuarios 
         WHERE Correo = :correo 
         LIMIT 1"
    );
    $stmt->execute([':correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Correo o contraseña incorrectos.']);
        exit;
    }

    // =========================================================
    // COMPARACIÓN DE CONTRASEÑA
    // Descomenta el bloque que corresponda según cómo está guardada:
    // =========================================================

    // Opción A: Texto plano (no recomendado en producción)
    $valida = ($usuario['Contraseña'] === $contrasena);

    // Opción B: MD5
    // $valida = ($usuario['Contraseña'] === md5($contrasena));

    // Opción C: bcrypt (password_hash)
    // $valida = password_verify($contrasena, $usuario['Contraseña']);

    if (!$valida) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Correo o contraseña incorrectos.']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'datos' => [
            'id_usuario' => (int) $usuario['ID_Usuario'],
            'nombre'     => $usuario['Nombre'] ?? $usuario['Correo'],
            'correo'     => $usuario['Correo'],
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor.']);
    error_log('login.php PDO error: ' . $e->getMessage());
}
