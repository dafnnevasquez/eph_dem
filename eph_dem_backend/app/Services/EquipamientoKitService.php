<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EquipamientoKitService
{
    const TBL_RECINTO_EQUIPO   = 'EPHAC_Recinto_Equipo';
    const TBL_EQUIPOS          = 'EPHAC_Equipos';
    const TBL_DEMANDA          = 'EPHAC_Proyecto_Demanda';
    const TBL_PRESTACIONES     = 'EPHAC_Prestaciones';
    const TBL_PRESTACION_EQUIPO = 'EPHAC_Prestacion_Equipo';
    const TIPO_DEMANDA_T2      = 2;

    public function __construct(
        private PabellonesBoxesService $pabellonesService
    ) {}

    /**
     * Calcula equipamiento por kit de recinto.
     */
    public function calcularKit(int $proyectoId): array
    {
        $conteoPorRecinto = $this->pabellonesService->conteoRecintosPorId($proyectoId);

        $rows = DB::table(self::TBL_RECINTO_EQUIPO . ' as re')
            ->join(self::TBL_EQUIPOS . ' as eq', 'eq.id_equipo', '=', 're.equipo_id')
            ->select('re.recinto_id', 're.equipo_id', 're.cantidad_base', 'eq.nombre_equipo')
            ->get();

        $totalPorEquipo  = [];
        $nombrePorEquipo = [];
        $porRecinto      = [];

        foreach ($rows as $row) {
            $eid      = (int)$row->equipo_id;
            $rec      = (int)$row->recinto_id;
            $cant     = (int)$row->cantidad_base;
            $nRec     = $conteoPorRecinto[$rec] ?? 0;
            $subtotal = $cant * $nRec;

            if (!isset($totalPorEquipo[$eid])) {
                $totalPorEquipo[$eid]  = 0;
                $nombrePorEquipo[$eid] = $row->nombre_equipo;
                $porRecinto[$eid]      = [];
            }

            $totalPorEquipo[$eid] += $subtotal;
            $porRecinto[$eid][] = [
                'recinto_id'    => $rec,
                'cantidad_base' => $cant,
                'nro_recintos'  => $nRec,
                'subtotal'      => $subtotal,
            ];
        }

        $equipos = [];
        foreach ($totalPorEquipo as $eid => $total) {
            $equipos[] = [
                'equipo_id'     => $eid,
                'nombre_equipo' => $nombrePorEquipo[$eid],
                'origen'        => 'kit',
                'cantidad'      => $total,
                'por_recinto'   => $porRecinto[$eid],
            ];
        }

        return [
            'conteo_recintos' => $conteoPorRecinto,
            'equipos'         => $equipos,
        ];
    }

    /**
     * Calcula equipamiento Tipo 2 (1 equipo por módulo).
     */
    public function calcularTipo2(int $proyectoId): array
    {
        $conteoPorRecinto = $this->pabellonesService->conteoRecintosPorId($proyectoId);

        $rows = DB::table(self::TBL_DEMANDA . ' as pd')
            ->join(self::TBL_PRESTACION_EQUIPO . ' as pe', 'pe.prestacion_id', '=', 'pd.prestacion_id')
            ->join(self::TBL_EQUIPOS . ' as eq', 'eq.id_equipo', '=', 'pe.equipo_id')
            ->join(self::TBL_PRESTACIONES . ' as p', 'p.id_prestacion', '=', 'pd.prestacion_id')
            ->where('pd.proyecto_id', $proyectoId)
            ->where('eq.tipo_demanda', self::TIPO_DEMANDA_T2)
            ->select('eq.id_equipo', 'eq.nombre_equipo', 'p.recinto_base_id')
            ->distinct()
            ->get();

        $recintosPorEquipo = [];
        $nombrePorEquipo   = [];

        foreach ($rows as $row) {
            $eid = (int)$row->id_equipo;
            $rec = (int)$row->recinto_base_id;

            if (!isset($recintosPorEquipo[$eid])) {
                $recintosPorEquipo[$eid] = [];
                $nombrePorEquipo[$eid]   = $row->nombre_equipo;
            }
            $recintosPorEquipo[$eid][$rec] = true;
        }

        $equipos = [];
        foreach ($recintosPorEquipo as $eid => $recintosSet) {
            $porRecinto = [];
            $total = 0;
            foreach (array_keys($recintosSet) as $rec) {
                $nRec = $conteoPorRecinto[$rec] ?? 0;
                $total += $nRec;
                $porRecinto[] = ['recinto_id' => $rec, 'nro_recintos' => $nRec];
            }
            $equipos[] = [
                'equipo_id'     => $eid,
                'nombre_equipo' => $nombrePorEquipo[$eid],
                'tipo_demanda'  => self::TIPO_DEMANDA_T2,
                'origen'        => 'tipo2_relacion',
                'cantidad'      => $total,
                'por_recinto'   => $porRecinto,
            ];
        }

        return ['equipos' => $equipos];
    }
}