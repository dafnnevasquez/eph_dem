<?php
declare(strict_types=1);
ini_set('display_errors', '0');

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../services/RRHHService.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

class RRHHController
{
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

        // Validacion de cada grupo (rangos, perfil repetido en el mismo recinto).
        $errorGrupos = RRHHService::validarGrupos($grupos);
        if ($errorGrupos !== null) Response::error($errorGrupos, 400);

        try {
            if (!$this->verificarPropietario($proyectoId, $usuarioId)) {
                Response::error('No tienes acceso a este proyecto.', 403);
                return;
            }

            // Cada perfil debe existir en el catalogo; se guarda con su escritura oficial.
            $canon = $this->service->canonizarPerfiles($grupos);
            if (count($canon['faltan']) > 0) {
                Response::error('El perfil «' . implode('», «', $canon['faltan']) . '» no existe en el catálogo.', 400);
                return;
            }
            // Se valida de nuevo: dos escrituras distintas pueden ser el mismo perfil.
            $errorGrupos = RRHHService::validarGrupos($canon['grupos']);
            if ($errorGrupos !== null) {
                Response::error($errorGrupos, 400);
                return;
            }

            $n = $this->service->guardar($proyectoId, $canon['grupos']);
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
            if (!$this->verificarPropietario($proyectoId, $usuarioId)) {
                Response::error('No tienes acceso a este proyecto.', 403);
                return;
            }
            Response::ok(['grupos' => $this->service->obtener($proyectoId)]);
        } catch (\Throwable $e) {
            error_log('RRHHController::obtener: ' . $e->getMessage());
            Response::error('No se pudo leer la dotación de RRHH.', 500);
        } finally {
            mysqli_close($this->conn);
        }
    }

    /** Lista del catalogo de perfiles (para el desplegable de la vista). */
    public function perfiles(): void
    {
        Response::soloGet();
        $usuarioId = (int)($_GET['usuario_id'] ?? 0);
        if ($usuarioId <= 0) Response::error('usuario_id es obligatorio.', 400);

        try {
            Response::ok(['perfiles' => $this->service->listarPerfiles()]);
        } catch (\Throwable $e) {
            error_log('RRHHController::perfiles: ' . $e->getMessage());
            Response::error('No se pudo leer la lista de perfiles.', 500);
        } finally {
            mysqli_close($this->conn);
        }
    }

    /** Agrega un perfil al catalogo; si ya existia devuelve el existente. */
    public function perfilAgregar(): void
    {
        Response::soloPost();
        $in = Response::input();
        $usuarioId = (int)($in['usuario_id'] ?? 0);
        if ($usuarioId <= 0) Response::error('usuario_id es obligatorio.', 400);

        list($nombre, $error) = RRHHService::normalizarPerfil($in['nombre'] ?? '');
        if ($error !== null) Response::error($error, 400);

        try {
            Response::ok($this->service->agregarPerfil($nombre));
        } catch (\Throwable $e) {
            error_log('RRHHController::perfilAgregar: ' . $e->getMessage());
            Response::error('No se pudo agregar el perfil.', 500);
        } finally {
            mysqli_close($this->conn);
        }
    }

    private function verificarPropietario(int $proyectoId, int $usuarioId): bool
    {
        $stmt = $this->conn->prepare(
            'SELECT 1 FROM EPHAC_Proyectos WHERE id_proyecto = ? AND usuario_id = ? LIMIT 1'
        );
        if (!$stmt) throw new \RuntimeException('prepare propietario');
        $stmt->bind_param('ii', $proyectoId, $usuarioId);
        $stmt->execute();
        $ok = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $ok;
    }
}

$controller = new RRHHController();
$action = $_GET['action'] ?? '';
if ($action === 'guardar') $controller->guardar();
elseif ($action === 'obtener') $controller->obtener();
elseif ($action === 'perfiles') $controller->perfiles();
elseif ($action === 'perfil_agregar') $controller->perfilAgregar();
else Response::error('Acción no válida.', 400);