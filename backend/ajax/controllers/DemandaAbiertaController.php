<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

class DemandaAbiertaController
{
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = SIGEM_UV_C_Nueva_Conexion();
        if (!$this->conn) Response::error('No se pudo conectar a la base de datos.', 500);
        mysqli_set_charset($this->conn, 'utf8mb4');
    }

    public function calcular(): void
    {
        Response::soloPost();

        $input      = Response::input();
        $proyectoId = (int)($input['proyecto_id'] ?? 0);
        $usuarioId  = (int)($input['usuario_id']  ?? 0);
        $filas      = $input['filas'] ?? [];

        if ($proyectoId <= 0) Response::error('proyecto_id es requerido.', 400);
        if (empty($filas))    Response::error('Se requiere al menos una prestación.', 400);

        // Obtener equipos por prestación desde la BD
        $prestacionIds = array_map(fn($f) => (int)$f['prestacion_id'], $filas);
        $ids = implode(',', $prestacionIds);

        $sql = "SELECT rel.ID_PRESTACION, eq.equipo, eq.tipo_equipo,
                       r.recinto, rel.Cantidad_equipo
                FROM EPHDEM_PREST_RECINTO_EQ rel
                INNER JOIN EPHDEM_EQUIPOS eq ON rel.ID_EQUIPO = eq.ID_EQUIPO
                INNER JOIN EPHDEM_RECINTOS r ON rel.ID_RECINTO = r.ID_RECINTO
                WHERE rel.ID_PRESTACION IN ($ids)";

        $result = mysqli_query($this->conn, $sql);
        if (!$result) Response::error(mysqli_error($this->conn), 500);

        $equiposPorPrestacion = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $pid = (int)$row['ID_PRESTACION'];
            if (!isset($equiposPorPrestacion[$pid])) $equiposPorPrestacion[$pid] = [];
            $equiposPorPrestacion[$pid][] = [
                'EQUIPO'      => $row['equipo'],
                'TIPO_EQUIPO' => $row['tipo_equipo'],
                'RECINTO'     => $row['recinto'],
                'CANTIDAD'    => (int)$row['Cantidad_equipo'],
            ];
        }

        // Equipos Tipo 1 por área
        $sqlTipo1 = "SELECT equipo FROM EPHDEM_EQUIPOS WHERE tipo_equipo = 'Tipo 1'";
        $resTipo1 = mysqli_query($this->conn, $sqlTipo1);
        $equiposTipo1 = [];
        while ($row = mysqli_fetch_assoc($resTipo1)) {
            $equiposTipo1[] = $row['equipo'];
        }

        // Obtener datos de las prestaciones (código, nombre, área)
        $sqlPrest = "SELECT ID_PRESTACION, cod_prestacion, nombre_prestacion, area
                     FROM EPHDEM_PRESTACION
                     WHERE ID_PRESTACION IN ($ids)";
        $resPrest = mysqli_query($this->conn, $sqlPrest);
        $datosPrestacion = [];
        while ($row = mysqli_fetch_assoc($resPrest)) {
            $datosPrestacion[(int)$row['ID_PRESTACION']] = $row;
        }

        // Construir prestaciones detalladas
        $prestacionesDetalle = [];
        $totalTipo1 = count($equiposTipo1);

        foreach ($filas as $f) {
            $pid  = (int)$f['prestacion_id'];
            $req  = (float)($f['requerimiento'] ?? 0);
            $info = $datosPrestacion[$pid] ?? [];

            $prestacionesDetalle[] = [
                'ID_PRESTACION'        => $pid,
                'COD_PRESTACION'       => $info['cod_prestacion']    ?? '',
                'NOMBRE_PRESTACION'    => $info['nombre_prestacion'] ?? '',
                'AREA'                 => $info['area']              ?? '',
                'REQUERIMIENTO'        => number_format($req, 3, '.', ''),
                'DEMANDA_PROCEDIMIENTO'=> (float)$f['demanda_anual'],
                'TIEMPO_PROCEDIMIENTO' => (float)$f['tiempo_proc'],
                'DIAS_LABORALES'       => (int)$f['dias_laborales'],
                'N_SIMULTANEAS'        => (int)$f['n_simultaneas'],
                'DISPONIBILIDAD'       => (float)$f['disponibilidad'],
                'JORNADA'              => (float)$f['jornada'],
                'EQUIPOS'              => $equiposPorPrestacion[$pid] ?? [],
            ];
        }

        // Calcular resúmenes
        $equiposSummary = [];
        $recintoSummary = [];

        foreach ($prestacionesDetalle as $p) {
            $reqVal = floatval($p['REQUERIMIENTO']);

            // Resumen recintos
            $hasTipo2o5      = false;
            $recintosTipo2o5 = [];
            foreach ($p['EQUIPOS'] as $eq) {
                $tipoNum = (int)str_replace('Tipo ', '', $eq['TIPO_EQUIPO'] ?? '');
                if (in_array($tipoNum, [2, 5])) {
                    $hasTipo2o5 = true;
                    if ($eq['RECINTO'] !== '') $recintosTipo2o5[] = $eq['RECINTO'];
                }
            }
            if ($hasTipo2o5) {
                foreach (array_unique($recintosTipo2o5) as $r) {
                    if (!isset($recintoSummary[$r])) $recintoSummary[$r] = 0;
                    $recintoSummary[$r] += (int)ceil($reqVal);
                }
            } elseif (!empty($p['EQUIPOS'])) {
                $primerRec = $p['EQUIPOS'][0]['RECINTO'] ?? '';
                if ($primerRec !== '') {
                    if (!isset($recintoSummary[$primerRec])) $recintoSummary[$primerRec] = 0;
                    $recintoSummary[$primerRec] += 1;
                }
            }

            // Resumen equipos
            foreach ($p['EQUIPOS'] as $eq) {
                $tipoNum  = (int)str_replace('Tipo ', '', $eq['TIPO_EQUIPO'] ?? '');
                $nombreEq = $eq['EQUIPO'];
                $cantNorm = (int)$eq['CANTIDAD'];

                if (in_array($tipoNum, [1, 2, 6])) {
                    $cantFinal = (int)ceil($cantNorm);
                } else {
                    $cantFinal = (int)max(ceil($cantNorm), ceil($reqVal));
                }

                if (!isset($equiposSummary[$nombreEq])) $equiposSummary[$nombreEq] = 0;
                $equiposSummary[$nombreEq] += $cantFinal;
            }
        }

        // Guardar JSON en BD
        $jsonData = json_encode([
            'prestaciones'    => $prestacionesDetalle,
            'equipos_summary' => $equiposSummary,
            'recinto_summary' => $recintoSummary,
        ], JSON_UNESCAPED_UNICODE);

        $stmt = mysqli_prepare($this->conn,
            "UPDATE EPHDEM_PROYECCIONES_GUARDADAS
             SET DATOS_JSON = ?
             WHERE ID_PROYECCION = ? AND USER_ID = ?"
        );
        mysqli_stmt_bind_param($stmt, 'sii', $jsonData, $proyectoId, $usuarioId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($this->conn);

        Response::ok([
            'proyecto_id'     => $proyectoId,
            'equipos_summary' => $equiposSummary,
            'recinto_summary' => $recintoSummary,
            'prestaciones'    => $prestacionesDetalle,
        ]);
    }

    public function resultados(): void
    {
        Response::soloGet();

        $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
        $usuarioId  = (int)($_GET['usuario_id']  ?? 0);

        if ($proyectoId <= 0) Response::error('proyecto_id es requerido.', 400);

        $stmt = mysqli_prepare($this->conn,
            "SELECT NOMBRE_PROYECCION, DATOS_JSON
             FROM EPHDEM_PROYECCIONES_GUARDADAS
             WHERE ID_PROYECCION = ? AND USER_ID = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $proyectoId, $usuarioId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row    = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        mysqli_close($this->conn);

        if (!$row) Response::error('Proyecto no encontrado.', 404);

        $datos = json_decode($row['DATOS_JSON'], true);
        if (!$datos) Response::error('Error al leer los datos del proyecto.', 500);

        Response::ok([
            'proyecto_id'     => $proyectoId,
            'nombre_proyecto' => $row['NOMBRE_PROYECCION'],
            'equipos_summary' => $datos['equipos_summary'] ?? [],
            'recinto_summary' => $datos['recinto_summary'] ?? [],
            'prestaciones'    => $datos['prestaciones']    ?? [],
        ]);
    }
}

// Despachar
header('Content-Type: application/json; charset=utf-8');
$controller = new DemandaAbiertaController();
$action     = $_GET['action'] ?? '';

if ($action === 'resultados') $controller->resultados();
else $controller->calcular();