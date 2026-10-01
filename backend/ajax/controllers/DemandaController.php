<?php
declare(strict_types=1);
ini_set('display_errors', '0');
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

    private function cerrarConexion(): void
    {
        try {
            mysqli_close($this->conn);
        } catch (\Throwable $ignorada) {
            // la conexion ya estaba cerrada
        }
    }

    private function responderDatosInvalidos(array $errores): void
    {
        $this->cerrarConexion();
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode(['ok' => false, 'error' => 'Datos invalidos.', 'detalle' => $errores], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function calcular(): void
    {
        Response::soloPost();

        $input      = Response::input();
        $proyectoId = (int)($input['proyecto_id'] ?? 0);
        $filas      = $input['filas'] ?? [];

        if ($proyectoId <= 0)                   Response::error('proyecto_id es requerido.', 400);
        if (!is_array($filas) || empty($filas)) Response::error('Se requiere al menos una prestación.', 400);

        // Validación y saneo fila por fila (igual que produccion/ajax/calcular_demanda.php)
        $filasSane = [];
        $errores   = [];
        foreach ($filas as $i => $f) {
            if (!is_array($f)) {
                $errores[] = "fila[$i]: formato invalido";
                continue;
            }

            $pid  = isset($f['prestacion_id'])    ? (int)$f['prestacion_id']      : 0;
            $dem  = isset($f['demanda_anual'])    ? (int)$f['demanda_anual']      : -1;
            $dias = isset($f['dias_laborales'])   ? (int)$f['dias_laborales']     : 0;
            $disp = isset($f['disponibilidad'])   ? (float)$f['disponibilidad']   : -1.0;
            $jor  = isset($f['jornada_efectiva']) ? (float)$f['jornada_efectiva'] : 0.0;

            if ($pid <= 0)               { $errores[] = "fila[$i]: prestacion_id invalido"; continue; }
            if ($dem < 0)                { $errores[] = "fila[$i]: demanda_anual debe ser >= 0"; continue; }
            if ($dias <= 0)              { $errores[] = "fila[$i]: dias_laborales debe ser > 0"; continue; }
            if ($disp <= 0 || $disp > 1) { $errores[] = "fila[$i]: disponibilidad debe estar en (0,1]"; continue; }
            if ($jor <= 0 || $jor > 24)  { $errores[] = "fila[$i]: jornada_efectiva debe estar en (0,24]"; continue; }

            $filasSane[] = [
                'prestacion_id'    => $pid,
                'demanda_anual'    => $dem,
                'dias_laborales'   => $dias,
                'disponibilidad'   => $disp,
                'jornada_efectiva' => $jor,
            ];
        }

        if (count($errores) > 0) {
            $this->responderDatosInvalidos($errores);
        }

        try {
            // 1. Guardar demanda
            $guardadas = $this->pabellonesService->guardarDemanda($proyectoId, $filasSane);
            if ($guardadas <= 0) {
                $this->cerrarConexion();
                Response::error('No se pudo guardar la demanda del proyecto. No se realizó el cálculo.', 500);
            }

            // 2. Calcular pabellones y boxes
            $pabellones = $this->pabellonesService->calcularPabellones($proyectoId);
            $boxes      = $this->pabellonesService->calcularBoxes($proyectoId);

            // 3. Equipamiento consolidado
            $equipamiento = $this->agregadorService->calcular($proyectoId);

            // 4. Vistas por recinto
            $vistas = $this->vistasService->calcular($equipamiento);

            // 5. URPA
            $urpa = $this->urpaService->calcular((int)$pabellones['pabellones_total']);
        } catch (\Throwable $e) {
            error_log('DemandaController::calcular: ' . $e->getMessage());
            $this->cerrarConexion();
            Response::error('Error en el cálculo.', 500);
        }

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
            "SELECT nombre_proyecto FROM EPHAC_Proyectos WHERE id_proyecto = ? AND usuario_id = ? LIMIT 1"
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