<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../services/PabellonesBoxesService.php';
require_once __DIR__ . '/../services/EquipamientoKitService.php';
require_once __DIR__ . '/../services/EquipamientoTipo5Service.php';
require_once __DIR__ . '/../services/EquipamientoTipo6Service.php';
require_once __DIR__ . '/../services/EquipamientoAgregadorService.php';
require_once __DIR__ . '/../services/EquipamientoVistasService.php';
require_once __DIR__ . '/../services/UrpaService.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

class DemandaController
{
    private mysqli $conn;
    private PabellonesBoxesService     $pabellonesService;
    private EquipamientoAgregadorService $agregadorService;
    private EquipamientoVistasService  $vistasService;
    private UrpaService                $urpaService;

    public function __construct()
    {
        $this->conn = SIGEM_UV_C_Nueva_Conexion();
        if (!$this->conn) Response::error('No se pudo conectar a la base de datos.', 500);
        mysqli_set_charset($this->conn, 'utf8mb4');

        $this->pabellonesService = new PabellonesBoxesService($this->conn);
        $kitService              = new EquipamientoKitService($this->conn, $this->pabellonesService);
        $tipo5Service            = new EquipamientoTipo5Service($this->conn);
        $tipo6Service            = new EquipamientoTipo6Service($this->conn, $this->pabellonesService);
        $this->agregadorService  = new EquipamientoAgregadorService($this->conn, $kitService, $tipo5Service, $tipo6Service);
        $this->vistasService     = new EquipamientoVistasService($this->conn);
        $this->urpaService       = new UrpaService($this->conn);
    }

    public function calcular(): void
    {
        Response::soloPost();

        $input      = Response::input();
        $proyectoId = (int)($input['proyecto_id'] ?? 0);
        $filas      = $input['filas'] ?? [];

        if ($proyectoId <= 0)  Response::error('proyecto_id es requerido.', 400);
        if (empty($filas))     Response::error('Se requiere al menos una prestación.', 400);

        // 1. Guardar demanda
        $guardadas = $this->pabellonesService->guardarDemanda($proyectoId, $filas);

        // 2. Calcular pabellones y boxes
        $pabellones = $this->pabellonesService->calcularPabellones($proyectoId);
        $boxes      = $this->pabellonesService->calcularBoxes($proyectoId);

        // 3. Equipamiento consolidado
        $equipamiento = $this->agregadorService->calcular($proyectoId);

        // 4. Vistas por recinto
        $vistas = $this->vistasService->calcular($equipamiento);

        // 5. URPA
        $urpa = $this->urpaService->calcular((int)$pabellones['pabellones_total']);

        mysqli_close($this->conn);

        Response::ok([
            'proyecto_id'     => $proyectoId,
            'filas_guardadas' => $guardadas,
            'pabellones'      => $pabellones,
            'boxes'           => $boxes,
            'equipamiento'    => [
                'equipos'            => $equipamiento['equipos'],
                'por_recinto'        => $vistas['por_recinto'],
                'demanda_compartida' => $vistas['demanda_compartida'],
            ],
            'urpa' => $urpa,
        ]);
    }

    public function resultados(): void
    {
        Response::soloGet();

        $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
        $usuarioId  = (int)($_GET['usuario_id']  ?? 0);

        if ($proyectoId <= 0) Response::error('proyecto_id es requerido.', 400);
        if ($usuarioId  <= 0) Response::error('usuario_id es requerido.', 400);

        // Verificar que el proyecto pertenece al usuario
        $stmt = mysqli_prepare($this->conn,
            "SELECT nombre_proyecto FROM EPHAC_Proyectos WHERE id_proyecto = ? AND id_usuario = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $proyectoId, $usuarioId);
        mysqli_stmt_execute($stmt);
        $result  = mysqli_stmt_get_result($stmt);
        $proyecto = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$proyecto) Response::error('Proyecto no encontrado.', 404);

        // Recalcular
        $pabellones   = $this->pabellonesService->calcularPabellones($proyectoId);
        $boxes        = $this->pabellonesService->calcularBoxes($proyectoId);
        $equipamiento = $this->agregadorService->calcular($proyectoId);
        $vistas       = $this->vistasService->calcular($equipamiento);
        $urpa         = $this->urpaService->calcular((int)$pabellones['pabellones_total']);

        mysqli_close($this->conn);

        Response::ok([
            'proyecto_id'    => $proyectoId,
            'nombre_proyecto'=> $proyecto['nombre_proyecto'],
            'pabellones'     => $pabellones,
            'boxes'          => $boxes,
            'equipamiento'   => [
                'equipos'            => $equipamiento['equipos'],
                'por_recinto'        => $vistas['por_recinto'],
                'demanda_compartida' => $vistas['demanda_compartida'],
            ],
            'urpa' => $urpa,
        ]);
    }
}

// Despachar
header('Content-Type: application/json; charset=utf-8');
$controller = new DemandaController();
$action     = $_GET['action'] ?? '';

if ($action === 'resultados') $controller->resultados();
else $controller->calcular();