<?php
/**
 * get_proyectos.php
 * Devuelve los proyectos asociados a un usuario.
 * GET ?usuario_id=X
 * Response { ok: bool, datos?: [...proyectos], error?: string }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

$usuario_id = isset($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : 0;

if ($usuario_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'usuario_id requerido.']);
    exit;
}

require_once __DIR__ . '/db_config.php';

try {
    $stmt = $pdo->prepare(
        "SELECT 
            id_proyecto,
            nombre_proyecto,
            tipo_proyecto,
            DATE_FORMAT(fecha_creacion, '%d/%m/%Y') AS fecha_creacion
         FROM EPHAC_Proyectos
         WHERE usuario_id = :usuario_id
         ORDER BY fecha_creacion DESC"
    );
    $stmt->execute([':usuario_id' => $usuario_id]);
    $proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalizar ids como enteros
    $proyectos = array_map(function ($p) {
        $p['id'] = (int) $p['id_proyecto'];
        return $p;
    }, $proyectos);

    echo json_encode(['ok' => true, 'datos' => $proyectos]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor.']);
    error_log('get_proyectos.php PDO error: ' . $e->getMessage());
}
