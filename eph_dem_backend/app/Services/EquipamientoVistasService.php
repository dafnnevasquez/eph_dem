<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EquipamientoVistasService
{
    const TBL_PRESTACIONES = 'EPHAC_Prestaciones';

    const NOMBRES_RECINTO = [
        1 => 'Cubículo UTI',
        2 => 'Cubículo UCI',
        3 => 'Pabellón menor',
        4 => 'Pabellón mayor',
    ];

    const MAPA_REGLA_RECINTO = [
        'uci'            => 2,
        'enfermeria_uci' => 2,
        'uti'            => 1,
        'enfermeria_uti' => 1,
    ];

    /**
     * Construye la vista por recinto y demanda compartida.
     */
    public function calcular(array $equipamiento): array
    {
        $fuentes = $equipamiento['fuentes'];
        $kit     = $fuentes['kit'];
        $tipo2   = $fuentes['tipo2'];
        $tipo5   = $fuentes['tipo5'];
        $tipo6   = $fuentes['tipo6'];

        $piso = [];

        $entrada = function (int $rec, int $eid, ?string $nombre) use (&$piso) {
            if (!isset($piso[$rec][$eid])) {
                $piso[$rec][$eid] = ['nombre' => $nombre, 'kit' => 0, 'tipo2' => 0, 'norma_upc' => 0, 'demanda' => 0];
            } elseif ($nombre !== null) {
                $piso[$rec][$eid]['nombre'] = $nombre;
            }
        };

        // KIT
        foreach ($kit['equipos'] as $item) {
            $eid = (int)$item['equipo_id'];
            foreach ($item['por_recinto'] as $pr) {
                $rec = (int)$pr['recinto_id'];
                $entrada($rec, $eid, $item['nombre_equipo'] ?? null);
                $piso[$rec][$eid]['kit'] = max($piso[$rec][$eid]['kit'], (int)$pr['subtotal']);
            }
        }

        // TIPO2
        foreach ($tipo2['equipos'] as $item) {
            $eid = (int)$item['equipo_id'];
            foreach ($item['por_recinto'] as $pr) {
                $rec = (int)$pr['recinto_id'];
                $entrada($rec, $eid, $item['nombre_equipo'] ?? null);
                $piso[$rec][$eid]['tipo2'] = max($piso[$rec][$eid]['tipo2'], (int)$pr['nro_recintos']);
            }
        }

        // TIPO6
        foreach ($tipo6['equipos'] as $item) {
            $eid = (int)$item['equipo_id'];
            $porRecintoTmp = [];
            foreach ($item['por_regla'] as $pr) {
                $rec = self::MAPA_REGLA_RECINTO[$pr['regla']] ?? null;
                if ($rec === null) continue;
                $porRecintoTmp[$rec] = ($porRecintoTmp[$rec] ?? 0) + (int)$pr['subtotal'];
            }
            foreach ($porRecintoTmp as $rec => $cant) {
                $entrada($rec, $eid, $item['nombre_equipo'] ?? null);
                $piso[$rec][$eid]['norma_upc'] = max($piso[$rec][$eid]['norma_upc'], $cant);
            }
        }

        // TIPO5 — clasificar por número de recintos
        $prestIds = [];
        foreach ($tipo5['equipos'] as $item) {
            foreach ($item['por_prestacion'] as $pp) {
                $prestIds[(int)$pp['prestacion_id']] = true;
            }
        }

        $recintoPorPrestacion = [];
        if (!empty($prestIds)) {
            $ids = implode(',', array_map('intval', array_keys($prestIds)));
            $res = DB::select("SELECT id_prestacion, recinto_base_id FROM " . self::TBL_PRESTACIONES . " WHERE id_prestacion IN ($ids)");
            foreach ($res as $r) {
                $recintoPorPrestacion[(int)$r->id_prestacion] = (int)$r->recinto_base_id;
            }
        }

        $demandaCompartida = [];

        foreach ($tipo5['equipos'] as $item) {
            $eid = (int)$item['equipo_id'];

            $recintosSet = [];
            foreach ($item['por_prestacion'] as $pp) {
                $rec = $recintoPorPrestacion[(int)$pp['prestacion_id']] ?? null;
                if ($rec !== null) $recintosSet[$rec] = true;
            }
            $recintosInvolucrados = array_keys($recintosSet);

            if (count($recintosInvolucrados) === 1) {
                $rec = $recintosInvolucrados[0];
                $entrada($rec, $eid, $item['nombre_equipo'] ?? null);
                $piso[$rec][$eid]['demanda'] = (int)$item['cantidad'];
                continue;
            }

            if (count($recintosInvolucrados) > 1) {
                $demandaCompartida[] = [
                    'equipo_id'             => $eid,
                    'nombre_equipo'         => $item['nombre_equipo'],
                    'cantidad'              => (int)$item['cantidad'],
                    'fraccion_total'        => $item['fraccion_total'],
                    'recintos_involucrados' => array_map(
                        fn($r) => ['recinto_id' => $r, 'nombre_recinto' => self::NOMBRES_RECINTO[$r] ?? null],
                        $recintosInvolucrados
                    ),
                ];
            }
        }

        // Armar por_recinto con separación de enfermería
        $reglasEnfermeria = array_flip(array_map('intval', array_keys([91=>1, 176=>1, 153=>1, 110=>1])));
        $recintosConEnfermeria = [1, 2];

        $porRecinto = [];
        foreach ($piso as $rec => $equiposRec) {
            $listaGeneral    = [];
            $listaEnfermeria = [];
            $separarEnf = in_array($rec, $recintosConEnfermeria, true);

            foreach ($equiposRec as $eid => $info) {
                $pisoMax  = max($info['kit'], $info['tipo2'], $info['norma_upc']);
                $cantidad = max($pisoMax, $info['demanda']);
                if ($cantidad <= 0) continue;

                $item = ['equipo_id' => $eid, 'nombre_equipo' => $info['nombre'], 'cantidad' => $cantidad];

                if ($separarEnf && isset($reglasEnfermeria[$eid])) {
                    $listaEnfermeria[] = $item;
                } else {
                    $listaGeneral[] = $item;
                }
            }

            usort($listaGeneral,    fn($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);
            usort($listaEnfermeria, fn($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);

            $porRecinto[$rec] = [
                'nombre_recinto'      => self::NOMBRES_RECINTO[$rec] ?? null,
                'equipos'             => $listaGeneral,
                'estacion_enfermeria' => $listaEnfermeria,
            ];
        }

        ksort($porRecinto);

        return [
            'por_recinto'        => $porRecinto,
            'demanda_compartida' => $demandaCompartida,
        ];
    }
}