<?php
declare(strict_types=1);

class PabellonesBoxesService
{
    const TBL_DEMANDA      = 'EPHAC_Proyecto_Demanda';
    const TBL_PRESTACIONES = 'EPHAC_Prestaciones';
    const AREA_UPC         = 'UPC';
    const TP_DIA_CAMA      = 1440;

    const IDS_URGENCIA = [
        4, 7, 8, 9, 10, 11, 16, 19, 20, 30, 32, 33, 34, 40, 41, 42, 44, 46,
        50, 51, 54, 57, 58, 59, 60, 61, 65, 67, 68, 70, 71, 73, 74, 76, 78,
        79, 80, 81, 82, 85, 86, 87, 88, 90, 91, 94, 95, 96, 97, 98, 99, 100,
        101, 102, 103, 104, 107, 108, 109, 110, 111, 112, 113, 115, 116, 117,
        118, 122, 128, 129, 130, 145, 147, 148,
    ];

    const IDS_DIA_CAMA = [149, 150];

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function formulaEEMM(float $demandaAnual, float $diasLaborales, float $tiempoProcedimiento, float $disponibilidad, float $jornada): float
    {
        if ($diasLaborales <= 0 || $tiempoProcedimiento <= 0 || $disponibilidad <= 0 || $jornada <= 0) return 0.0;
        $numerador   = $demandaAnual / $diasLaborales;
        $denominador = (60.0 / $tiempoProcedimiento) * $disponibilidad * $jornada;
        if ($denominador <= 0) return 0.0;
        return $numerador / $denominador;
    }

    public function guardarDemanda(int $proyectoId, array $filas): int
    {
        $stmt = mysqli_prepare($this->conn, "DELETE FROM " . self::TBL_DEMANDA . " WHERE proyecto_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $proyectoId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $n = 0;
        foreach ($filas as $f) {
            $pid  = (int)($f['prestacion_id'] ?? 0);
            if ($pid <= 0) continue;
            $dem  = (int)($f['demanda_anual']    ?? 0);
            $dias = (int)($f['dias_laborales']   ?? 0);
            $disp = (float)($f['disponibilidad'] ?? 1.0);
            $jorn = (float)($f['jornada_efectiva'] ?? 0.0);

            $stmt = mysqli_prepare($this->conn,
                "INSERT INTO " . self::TBL_DEMANDA . " (proyecto_id, prestacion_id, demanda_anual, dias_laborales, disponibilidad, jornada_efectiva)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'iiiidd', $proyectoId, $pid, $dem, $dias, $disp, $jorn);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $n++;
        }
        return $n;
    }

    public function calcularPabellones(int $proyectoId): array
    {
        $sql = "SELECT pd.id_registro, pd.prestacion_id, pd.demanda_anual, pd.dias_laborales,
                       pd.disponibilidad, pd.jornada_efectiva, p.tiempo_procedimiento, p.recinto_base_id
                FROM " . self::TBL_DEMANDA . " pd
                JOIN " . self::TBL_PRESTACIONES . " p ON p.id_prestacion = pd.prestacion_id
                WHERE pd.proyecto_id = ? AND p.area_hospitalaria <> ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'is', $proyectoId, self::AREA_UPC);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $detalle = []; $suma = ['urgencia' => 0.0, 'electivo' => 0.0]; $sumaPorRecinto = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $req = $this->formulaEEMM((float)$row['demanda_anual'], (float)$row['dias_laborales'],
                (float)$row['tiempo_procedimiento'], (float)$row['disponibilidad'], (float)$row['jornada_efectiva']);

            $pid = (int)$row['prestacion_id'];
            $cat = in_array($pid, self::IDS_URGENCIA, true) ? 'urgencia' : 'electivo';
            $suma[$cat] += $req;
            $rec = (int)$row['recinto_base_id'];
            $sumaPorRecinto[$rec] = ($sumaPorRecinto[$rec] ?? 0.0) + $req;

            $upd = mysqli_prepare($this->conn, "UPDATE " . self::TBL_DEMANDA . " SET requerimiento_calculado = ? WHERE id_registro = ?");
            mysqli_stmt_bind_param($upd, 'di', $req, $row['id_registro']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $detalle[] = ['prestacion_id' => $pid, 'categoria' => $cat, 'requerimiento_fraccion' => round($req, 4)];
        }
        mysqli_stmt_close($stmt);

        $pabellonesPorRecinto = [];
        foreach ($sumaPorRecinto as $rec => $frac) {
            $pabellonesPorRecinto[$rec] = ['fraccion' => round($frac, 4), 'pabellones' => (int)ceil($frac)];
        }

        return [
            'detalle'                => $detalle,
            'pabellones_urgencia'    => (int)ceil($suma['urgencia']),
            'pabellones_electivo'    => (int)ceil($suma['electivo']),
            'pabellones_total'       => array_sum(array_column($pabellonesPorRecinto, 'pabellones')),
            'fraccion_urgencia'      => round($suma['urgencia'], 4),
            'fraccion_electivo'      => round($suma['electivo'], 4),
            'pabellones_por_recinto' => $pabellonesPorRecinto,
        ];
    }

    public function calcularBoxes(int $proyectoId): array
    {
        $sql = "SELECT pd.id_registro, pd.prestacion_id, pd.demanda_anual, pd.dias_laborales,
                       pd.disponibilidad, pd.jornada_efectiva, p.subarea_hospitalaria, p.recinto_base_id
                FROM " . self::TBL_DEMANDA . " pd
                JOIN " . self::TBL_PRESTACIONES . " p ON p.id_prestacion = pd.prestacion_id
                WHERE pd.proyecto_id = ? AND p.area_hospitalaria = ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'is', $proyectoId, self::AREA_UPC);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $detalle = []; $suma = []; $sumaPorRecinto = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $req = $this->formulaEEMM((float)$row['demanda_anual'], (float)$row['dias_laborales'],
                (float)self::TP_DIA_CAMA, (float)$row['disponibilidad'], (float)$row['jornada_efectiva']);

            $sub = ($row['subarea_hospitalaria'] !== null && $row['subarea_hospitalaria'] !== '') ? $row['subarea_hospitalaria'] : 'UPC';

            if (in_array((int)$row['prestacion_id'], self::IDS_DIA_CAMA, true)) {
                $suma[$sub] = ($suma[$sub] ?? 0.0) + $req;
                $rec = (int)$row['recinto_base_id'];
                $sumaPorRecinto[$rec] = ($sumaPorRecinto[$rec] ?? 0.0) + $req;
            }

            $upd = mysqli_prepare($this->conn, "UPDATE " . self::TBL_DEMANDA . " SET requerimiento_calculado = ? WHERE id_registro = ?");
            mysqli_stmt_bind_param($upd, 'di', $req, $row['id_registro']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $detalle[] = ['prestacion_id' => (int)$row['prestacion_id'], 'subarea' => $sub, 'requerimiento_fraccion' => round($req, 4)];
        }
        mysqli_stmt_close($stmt);

        $boxesPorSubarea = []; $total = 0;
        foreach ($suma as $sub => $frac) {
            $b = (int)ceil($frac);
            $boxesPorSubarea[$sub] = ['fraccion' => round($frac, 4), 'boxes' => $b];
            $total += $b;
        }

        $boxesPorRecinto = [];
        foreach ($sumaPorRecinto as $rec => $frac) {
            $boxesPorRecinto[$rec] = ['fraccion' => round($frac, 4), 'boxes' => (int)ceil($frac)];
        }

        return ['detalle' => $detalle, 'boxes_por_subarea' => $boxesPorSubarea, 'boxes_total' => $total, 'boxes_por_recinto' => $boxesPorRecinto];
    }

    public function conteoRecintosPorId(int $proyectoId): array
    {
        $pabellones = $this->calcularPabellones($proyectoId);
        $boxes      = $this->calcularBoxes($proyectoId);
        $conteo     = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($boxes['boxes_por_recinto'] as $recId => $info) $conteo[(int)$recId] = $info['boxes'];
        foreach ($pabellones['pabellones_por_recinto'] as $recId => $info) $conteo[(int)$recId] = $info['pabellones'];

        return $conteo;
    }
}