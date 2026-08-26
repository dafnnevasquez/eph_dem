<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../../../funciones_sigemuv_C_BaseDatos.php';

class PrestacionController
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

        $sql = "SELECT p.id_prestacion, p.codigo_fonasa, p.nombre_prestacion,
                       p.area_hospitalaria, p.subarea_hospitalaria, p.recinto_base_id,
                       r.nombre_recinto, p.tiempo_procedimiento
                FROM EPHAC_Prestaciones p
                LEFT JOIN EPHAC_Recinto_Estandar r ON r.id_recinto = p.recinto_base_id
                ORDER BY p.nombre_prestacion ASC";

        $result = mysqli_query($this->conn, $sql);
        if (!$result) Response::error(mysqli_error($this->conn), 500);

        $prestaciones = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $prestaciones[] = [
                'id_prestacion'        => (int)$row['id_prestacion'],
                'codigo_fonasa'        => $row['codigo_fonasa'],
                'nombre_prestacion'    => $row['nombre_prestacion'],
                'area_hospitalaria'    => $row['area_hospitalaria'],
                'subarea_hospitalaria' => $row['subarea_hospitalaria'],
                'recinto_base_id'      => $row['recinto_base_id'] !== null ? (int)$row['recinto_base_id'] : null,
                'nombre_recinto'       => $row['nombre_recinto'] ?? null,
                'tiempo_procedimiento' => $row['tiempo_procedimiento'] !== null ? (int)$row['tiempo_procedimiento'] : null,
            ];
        }
        mysqli_close($this->conn);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'datos' => $prestaciones], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function demanda(): void
    {
        Response::soloGet();

        $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
        if ($proyectoId <= 0) Response::error('proyecto_id es requerido.', 400);

        $stmt = mysqli_prepare($this->conn,
            "SELECT
                p.id_prestacion,
                p.codigo_fonasa,
                p.nombre_prestacion,
                pd.demanda_anual,
                pd.dias_laborales,
                pd.disponibilidad,
                pd.jornada_efectiva
             FROM EPHAC_Proyecto_Demanda pd
             JOIN EPHAC_Prestaciones p ON p.id_prestacion = pd.prestacion_id
             WHERE pd.proyecto_id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $proyectoId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $datos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $datos[] = $row;
        }
        mysqli_stmt_close($stmt);
        mysqli_close($this->conn);

        Response::ok($datos);
    }
}

// Despachar
header('Content-Type: application/json; charset=utf-8');
$controller = new PrestacionController();

$action = $_GET['action'] ?? 'index';
if ($action === 'demanda') $controller->demanda();
else $controller->index();