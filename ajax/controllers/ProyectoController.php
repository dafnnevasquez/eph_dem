<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../../../funciones_sigemuv_C_BaseDatos.php';

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
            "SELECT 
                id_proyecto,
                Nombre_proyecto AS nombre_proyecto,
                tipo_proyecto,
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') AS fecha_creacion
             FROM EPHAC_Proyectos
             WHERE usuario_id = ?
             ORDER BY fecha_creacion DESC"
        );
        mysqli_stmt_bind_param($stmt, 'i', $usuarioId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $proyectos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['id'] = (int)$row['id_proyecto'];
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
        $tipoProyecto   = $input['tipo_proyecto']        ?? 'Atencion cerrada';
        $usuarioId      = isset($input['usuario_id']) ? (int)$input['usuario_id'] : null;

        if ($nombreProyecto === '') Response::error('El nombre del proyecto es requerido', 400);

        // Validar unicidad
        $stmt = mysqli_prepare($this->conn,
            "SELECT id_proyecto FROM EPHAC_Proyectos WHERE Nombre_proyecto = ? AND usuario_id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'si', $nombreProyecto, $usuarioId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($this->conn);
            Response::error('Ya tienes un proyecto con este nombre. Por favor, elige otro.', 409);
        }
        mysqli_stmt_close($stmt);

        // Insertar
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO EPHAC_Proyectos (Nombre_proyecto, fecha_creacion, usuario_id, Datos_Json) VALUES (?, NOW(), ?, NULL)"
        );
        mysqli_stmt_bind_param($stmt, 'si', $nombreProyecto, $usuarioId);

        if (mysqli_stmt_execute($stmt)) {
            $id = (int)mysqli_insert_id($this->conn);
            mysqli_stmt_close($stmt);
            mysqli_close($this->conn);
            Response::ok([
                'id_proyecto'     => $id,
                'nombre_proyecto' => $nombreProyecto,
                'tipo_proyecto'   => $tipoProyecto,
            ], 201);
        } else {
            mysqli_stmt_close($stmt);
            mysqli_close($this->conn);
            Response::error('Error al crear el proyecto.', 500);
        }
    }
}

// Despachar
header('Content-Type: application/json; charset=utf-8');
$controller = new ProyectoController();
$method     = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET')  $controller->index();
if ($method === 'POST') $controller->store();

Response::error('Método no permitido.', 405);