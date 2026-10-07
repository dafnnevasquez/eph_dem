<?php
declare(strict_types=1);
ini_set('display_errors', '0');

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../services/RRHHService.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

class RRHHController
{
    const JORNADAS = [11, 22, 33, 44];

    private $conn;
    private $service;

    public function __construct()
    {
        $this->conn = SIGEM_UV_C_Nueva_Conexion();
        if (!$this->conn) Response::error('No se pudo conectar a la base de datos.', 500);
        mysqli_set_charset($this->conn, 'utf8mb4');
        $this->service = new RRHHService($this->conn);
    }

    public function guardar(): void
    {
        Response::soloPost();
        $in = Response::input();
        $proyectoId = (int)($in['proyecto_id'] ?? 0);
        $usuarioId  = (int)($in['usuario_id'] ?? 0);
        $grupos     = $in['grupos'] ?? [];

        if ($proyectoId <= 0 || $usuarioId <= 0) Response::error('proyecto_id y usuario_id son obligatorios.', 400);
        if (!is_array($grupos)) Response::error('Formato de grupos inválido.', 400);

        foreach ($grupos as $g) {
            $rec = (int)($g['recinto_id'] ?? 0);
            $per = $g['personas'] ?? null;
            $jor = (int)($g['jornada_semanal'] ?? 0);
            $min = $g['min_interaccion'] ?? null;
            if ($rec < 1 || $rec > 4
                || !is_numeric($per) || (int)$per < 0
                || !in_array($jor, self::JORNADAS, true)
                || !is_numeric($min) || (int)$min < 0
                || trim((string)($g['tipo_rrhh'] ?? '')) === '') {
                Response::error('Datos inválidos.', 400);
            }
        }

        try {
            $this->verificarPropietario($proyectoId, $usuarioId);
            $n = $this->service->guardar($proyectoId, $grupos);
            Response::ok(['guardados' => $n]);
        } catch (\Throwable $e) {
            error_log('RRHHController::guardar: ' . $e->getMessage());
            Response::error('No se pudo guardar la dotación de RRHH.', 500);
        } finally {
            mysqli_close($this->conn);
        }
    }

    public function obtener(): void
    {
        Response::soloGet();
        $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
        $usuarioId  = (int)($_GET['usuario_id'] ?? 0);
        if ($proyectoId <= 0 || $usuarioId <= 0) Response::error('proyecto_id y usuario_id son obligatorios.', 400);

        try {
            $this->verificarPropietario($proyectoId, $usuarioId);
            Response::ok(['grupos' => $this->service->obtener($proyectoId)]);
        } catch (\Throwable $e) {
            error_log('RRHHController::obtener: ' . $e->getMessage());
            Response::error('No se pudo leer la dotación de RRHH.', 500);
        } finally {
            mysqli_close($this->conn);
        }
    }

    private function verificarPropietario(int $proyectoId, int $usuarioId): void
    {
        // PENDIENTE: nombre real de la columna del dueño en EPHAC_Proyectos.
        // Reemplazar COLUMNA_USUARIO tras ver SHOW CREATE TABLE EPHAC_Proyectos.
        $stmt = $this->conn->prepare(
            'SELECT 1 FROM EPHAC_Proyectos WHERE id_proyecto = ? AND COLUMNA_USUARIO = ? LIMIT 1'
        );
        if (!$stmt) throw new RuntimeException('prepare propietario');
        $stmt->bind_param('ii', $proyectoId, $usuarioId);
        $stmt->execute();
        $ok = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$ok) throw new RuntimeException('Proyecto no pertenece al usuario.');
    }
}

$controller = new RRHHController();
$action = $_GET['action'] ?? '';
if ($action === 'guardar') $controller->guardar();
elseif ($action === 'obtener') $controller->obtener();
else Response::error('Acción no válida.', 400);