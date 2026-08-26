<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

class ProyectoController
{
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = SIGEM_UV_C_Nueva_Conexion();
        if (!$this->conn) Response::error('No se pudo conectar a la base de datos.', 500);
        mysqli_set_charset($this->conn, 'utf8mb4');
    }

    public function index(): void
    {
        Response::soloGet();

        $usuarioId = (int)($_GET['usuario_id'] ?? 0);
        if ($usuarioId <= 0) Response::error('usuario_id es requerido.', 400);

        $stmt = mysqli_prepare($this->conn,
            "SELECT id_proyecto, nombre_proyecto, tipo_proyecto, fecha_creacion
             FROM EPHAC_Proyectos
             WHERE id_usuario = ?
             ORDER BY id_proyecto DESC"
        );
        mysqli_stmt_bind_param($stmt, 'i', $usuarioId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $proyectos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $proyectos[] = $row;
        }
        mysqli_stmt_close($stmt);
        mysqli_close($this->conn);

        Response::ok($proyectos);
    }

    public function store(): void
    {
        Response::soloPost();

        $input          = Response::input();
        $nombreProyecto = trim($input['nombre_proyecto'] ?? '');
        $tipoProyecto   = trim($input['tipo_proyecto']   ?? 'Atención cerrada');
        $usuarioId      = (int)($input['usuario_id']     ?? 0);

        if ($nombreProyecto === '') Response::error('El nombre del proyecto es requerido.', 400);
        if ($usuarioId <= 0)       Response::error('usuario_id es requerido.', 400);

        // Validar unicidad
        $stmt = mysqli_prepare($this->conn,
            "SELECT id_proyecto FROM EPHAC_Proyectos WHERE nombre_proyecto = ? AND id_usuario = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'si', $nombreProyecto, $usuarioId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($this->conn);
            Response::error('Ya existe un proyecto con ese nombre.', 422);
        }
        mysqli_stmt_close($stmt);

        // Insertar
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO EPHAC_Proyectos (nombre_proyecto, tipo_proyecto, id_usuario, fecha_creacion)
             VALUES (?, ?, ?, CURDATE())"
        );
        mysqli_stmt_bind_param($stmt, 'ssi', $nombreProyecto, $tipoProyecto, $usuarioId);
        mysqli_stmt_execute($stmt);
        $idProyecto = (int)mysqli_insert_id($this->conn);
        mysqli_stmt_close($stmt);
        mysqli_close($this->conn);

        Response::ok(['id_proyecto' => $idProyecto], 201);
    }
}

// Despachar
header('Content-Type: application/json; charset=utf-8');
$controller = new ProyectoController();
$method     = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET')  $controller->index();
if ($method === 'POST') $controller->store();

Response::error('Método no permitido.', 405);