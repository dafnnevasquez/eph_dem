<?php
declare(strict_types=1);

class EquipamientoTipo5Service
{
    const TBL_DEMANDA           = 'EPHAC_Proyecto_Demanda';
    const TBL_PRESTACION_EQUIPO = 'EPHAC_Prestacion_Equipo';
    const TBL_EQUIPOS           = 'EPHAC_Equipos';
    const TIPO_DEMANDA_T5       = 5;

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function calcular(int $proyectoId): array
    {
        $sql = "SELECT eq.id_equipo, eq.nombre_equipo, pd.prestacion_id, pd.requerimiento_calculado
                FROM " . self::TBL_DEMANDA . " pd
                JOIN " . self::TBL_PRESTACION_EQUIPO . " pe ON pe.prestacion_id = pd.prestacion_id
                JOIN " . self::TBL_EQUIPOS . " eq ON eq.id_equipo = pe.equipo_id
                WHERE pd.proyecto_id = ?
                AND eq.tipo_demanda = ?
                AND pd.requerimiento_calculado IS NOT NULL";

        $stmt = mysqli_prepare($this->conn, $sql);
        $t5   = self::TIPO_DEMANDA_T5;
        mysqli_stmt_bind_param($stmt, 'ii', $proyectoId, $t5);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $sumaPorEquipo = []; $nombrePorEquipo = []; $porPrestacion = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $eid  = (int)$row['id_equipo'];
            $frac = (float)$row['requerimiento_calculado'];

            if (!isset($sumaPorEquipo[$eid])) {
                $sumaPorEquipo[$eid]   = 0.0;
                $nombrePorEquipo[$eid] = $row['nombre_equipo'];
                $porPrestacion[$eid]   = [];
            }
            $sumaPorEquipo[$eid] += $frac;
            $porPrestacion[$eid][] = ['prestacion_id' => (int)$row['prestacion_id'], 'requerimiento_fraccion' => round($frac, 4)];
        }
        mysqli_stmt_close($stmt);

        $equipos = [];
        foreach ($sumaPorEquipo as $eid => $fraccionTotal) {
            $equipos[] = [
                'equipo_id'      => $eid,
                'nombre_equipo'  => $nombrePorEquipo[$eid],
                'tipo_demanda'   => self::TIPO_DEMANDA_T5,
                'origen'         => 'demanda',
                'fraccion_total' => round($fraccionTotal, 4),
                'cantidad'       => (int)ceil($fraccionTotal),
                'por_prestacion' => $porPrestacion[$eid],
            ];
        }

        return ['equipos' => $equipos];
    }
}