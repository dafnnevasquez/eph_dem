<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EquipamientoTipo5Service
{
    const TBL_DEMANDA           = 'EPHAC_Proyecto_Demanda';
    const TBL_PRESTACION_EQUIPO = 'EPHAC_Prestacion_Equipo';
    const TBL_EQUIPOS           = 'EPHAC_Equipos';
    const TIPO_DEMANDA_T5       = 5;

    /**
     * Calcula equipamiento Tipo 5 (exclusivamente por demanda proyectada).
     */
    public function calcular(int $proyectoId): array
    {
        $rows = DB::table(self::TBL_DEMANDA . ' as pd')
            ->join(self::TBL_PRESTACION_EQUIPO . ' as pe', 'pe.prestacion_id', '=', 'pd.prestacion_id')
            ->join(self::TBL_EQUIPOS . ' as eq', 'eq.id_equipo', '=', 'pe.equipo_id')
            ->where('pd.proyecto_id', $proyectoId)
            ->where('eq.tipo_demanda', self::TIPO_DEMANDA_T5)
            ->whereNotNull('pd.requerimiento_calculado')
            ->select('eq.id_equipo', 'eq.nombre_equipo', 'pd.prestacion_id', 'pd.requerimiento_calculado')
            ->get();

        $sumaPorEquipo   = [];
        $nombrePorEquipo = [];
        $porPrestacion   = [];

        foreach ($rows as $row) {
            $eid  = (int)$row->id_equipo;
            $frac = (float)$row->requerimiento_calculado;

            if (!isset($sumaPorEquipo[$eid])) {
                $sumaPorEquipo[$eid]   = 0.0;
                $nombrePorEquipo[$eid] = $row->nombre_equipo;
                $porPrestacion[$eid]   = [];
            }

            $sumaPorEquipo[$eid] += $frac;
            $porPrestacion[$eid][] = [
                'prestacion_id'          => (int)$row->prestacion_id,
                'requerimiento_fraccion' => round($frac, 4),
            ];
        }

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