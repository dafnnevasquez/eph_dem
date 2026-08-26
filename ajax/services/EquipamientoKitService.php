<?php
declare(strict_types=1);

class EquipamientoKitService
{
    const TBL_RECINTO_EQUIPO    = 'EPHAC_Recinto_Equipo';
    const TBL_EQUIPOS           = 'EPHAC_Equipos';
    const TBL_DEMANDA           = 'EPHAC_Proyecto_Demanda';
    const TBL_PRESTACIONES      = 'EPHAC_Prestaciones';
    const TBL_PRESTACION_EQUIPO = 'EPHAC_Prestacion_Equipo';
    const TIPO_DEMANDA_T2       = 2;

    private mysqli $conn;
    private PabellonesBoxesService $pabellonesService;

    public function __construct(mysqli $conn, PabellonesBoxesService $pabellonesService)
    {
        $this->conn              = $conn;
        $this->pabellonesService = $pabellonesService;
    }

    public function calcularKit(int $proyectoId): array
    {
        $conteoPorRecinto = $this->pabellonesService->conteoRecintosPorId($proyectoId);

        $sql = "SELECT re.recinto_id, re.equipo_id, re.cantidad_base, eq.nombre_equipo
                FROM " . self::TBL_RECINTO_EQUIPO . " re
                JOIN " . self::TBL_EQUIPOS . " eq ON eq.id_equipo = re.equipo_id";

        $result = mysqli_query($this->conn, $sql);
        $totalPorEquipo = []; $nombrePorEquipo = []; $porRecinto = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $eid  = (int)$row['equipo_id'];
            $rec  = (int)$row['recinto_id'];
            $cant = (int)$row['cantidad_base'];
            $nRec = $conteoPorRecinto[$rec] ?? 0;
            $sub  = $cant * $nRec;

            if (!isset($totalPorEquipo[$eid])) {
                $totalPorEquipo[$eid]  = 0;
                $nombrePorEquipo[$eid] = $row['nombre_equipo'];
                $porRecinto[$eid]      = [];
            }
            $totalPorEquipo[$eid] += $sub;
            $porRecinto[$eid][]    = ['recinto_id' => $rec, 'cantidad_base' => $cant, 'nro_recintos' => $nRec, 'subtotal' => $sub];
        }

        $equipos = [];
        foreach ($totalPorEquipo as $eid => $total) {
            $equipos[] = ['equipo_id' => $eid, 'nombre_equipo' => $nombrePorEquipo[$eid], 'origen' => 'kit', 'cantidad' => $total, 'por_recinto' => $porRecinto[$eid]];
        }

        return ['conteo_recintos' => $conteoPorRecinto, 'equipos' => $equipos];
    }

    public function calcularTipo2(int $proyectoId): array
    {
        $conteoPorRecinto = $this->pabellonesService->conteoRecintosPorId($proyectoId);

        $sql = "SELECT DISTINCT eq.id_equipo, eq.nombre_equipo, p.recinto_base_id
                FROM " . self::TBL_DEMANDA . " pd
                JOIN " . self::TBL_PRESTACION_EQUIPO . " pe ON pe.prestacion_id = pd.prestacion_id
                JOIN " . self::TBL_EQUIPOS . " eq ON eq.id_equipo = pe.equipo_id
                JOIN " . self::TBL_PRESTACIONES . " p ON p.id_prestacion = pd.prestacion_id
                WHERE pd.proyecto_id = ? AND eq.tipo_demanda = ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        $t2   = self::TIPO_DEMANDA_T2;
        mysqli_stmt_bind_param($stmt, 'ii', $proyectoId, $t2);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $recintosPorEquipo = []; $nombrePorEquipo = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $eid = (int)$row['id_equipo'];
            $rec = (int)$row['recinto_base_id'];
            if (!isset($recintosPorEquipo[$eid])) { $recintosPorEquipo[$eid] = []; $nombrePorEquipo[$eid] = $row['nombre_equipo']; }
            $recintosPorEquipo[$eid][$rec] = true;
        }
        mysqli_stmt_close($stmt);

        $equipos = [];
        foreach ($recintosPorEquipo as $eid => $recintosSet) {
            $porRecinto = []; $total = 0;
            foreach (array_keys($recintosSet) as $rec) {
                $nRec = $conteoPorRecinto[$rec] ?? 0;
                $total += $nRec;
                $porRecinto[] = ['recinto_id' => $rec, 'nro_recintos' => $nRec];
            }
            $equipos[] = ['equipo_id' => $eid, 'nombre_equipo' => $nombrePorEquipo[$eid], 'tipo_demanda' => self::TIPO_DEMANDA_T2, 'origen' => 'tipo2_relacion', 'cantidad' => $total, 'por_recinto' => $porRecinto];
        }

        return ['equipos' => $equipos];
    }
}