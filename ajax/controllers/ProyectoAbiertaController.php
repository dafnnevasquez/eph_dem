<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

class ProyectoAbiertaController
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
            "SELECT ID_PROYECCION, NOMBRE_PROYECCION, FECHA_CREACION
             FROM EPHDEM_PROYECCIONES_GUARDADAS
             WHERE USER_ID = ?
             ORDER BY FECHA_CREACION DESC"
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
        $usuarioId      = (int)($input['usuario_id'] ?? 0);

        if ($nombreProyecto === '') Response::error('El nombre del proyecto es requerido.', 400);
        if ($usuarioId <= 0) Response::error('usuario_id es requerido.', 400);

        // Validar unicidad
        $stmt = mysqli_prepare($this->conn,
            "SELECT ID_PROYECCION FROM EPHDEM_PROYECCIONES_GUARDADAS
             WHERE NOMBRE_PROYECCION = ? AND USER_ID = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'si', $nombreProyecto, $usuarioId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($this->conn);
            Response::error('Ya tienes un proyecto con este nombre. Por favor elige otro.', 409);
        }
        mysqli_stmt_close($stmt);

        // Insertar
        $fecha    = date('Y-m-d H:i:s');
        $jsonVacio = '{}';
        $stmt = mysqli_prepare($this->conn,
        "INSERT INTO EPHDEM_PROYECCIONES_GUARDADAS (USER_ID, NOMBRE_PROYECCION, FECHA_CREACION, DATOS_JSON)
         VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, 'isss', $usuarioId, $nombreProyecto, $fecha, $jsonVacio);

        if (mysqli_stmt_execute($stmt)) {
        $id = (int)mysqli_insert_id($this->conn);
        mysqli_stmt_close($stmt);
        mysqli_close($this->conn);
        Response::ok(['id_proyeccion' => $id], 201);
        } else {
        $error = mysqli_error($this->conn);
        mysqli_stmt_close($stmt);
        mysqli_close($this->conn);
        Response::error('Error al crear el proyecto: ' . $error, 500);
        }
    }
}

// Despachar
header('Content-Type: application/json; charset=utf-8');
$controller = new ProyectoAbiertaController();
$method     = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET')  $controller->index();
if ($method === 'POST') $controller->store();

Response::error('Método no permitido.', 405);