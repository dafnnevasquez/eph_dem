<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EquipamientoAgregadorService
{
    const TBL_PRESTACIONES = 'EPHAC_Prestaciones';

    const MAPA_REGLA_RECINTO = [
        'uci'            => 2,
        'enfermeria_uci' => 2,
        'uti'            => 1,
        'enfermeria_uti' => 1,
    ];

    public function __construct(
        private EquipamientoKitService   $kitService,
        private EquipamientoTipo5Service $tipo5Service,
        private EquipamientoTipo6Service $tipo6Service,
    ) {}

    /**
     * Consolida las 4 fuentes con la regla max(piso, demanda).
     */
    public function calcular(int $proyectoId): array
    {
        // 1) Fuentes de piso (kit dispara pabellones+boxes internamente)
        $kit   = $this->kitService->calcularKit($proyectoId);
        $tipo2 = $this->kitService->calcularTipo2($proyectoId);
        $tipo6 = $this->tipo6Service->calcular($proyectoId);

        // 2) Fuente de demanda (requiere requerimiento_calculado ya persistido)
        $tipo5 = $this->tipo5Service->calcular($proyectoId);

        // 2.1) Resolver recinto por prestación para T5
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

        // 3) Consolidación
        $eq = [];

        $asegurar = function (int $id, ?string $nombre) use (&$eq) {
            if (!isset($eq[$id])) {
                $eq[$id] = ['nombre' => $nombre, 'piso_por_bucket' => [], 'demanda' => 0, 'origenes' => [], 'detalle' => []];
            } elseif ($eq[$id]['nombre'] === null && $nombre !== null) {
                $eq[$id]['nombre'] = $nombre;
            }
        };

        $registrarPiso = function (int $id, ?string $nombre, string $bucket, int $cant) use (&$eq, $asegurar) {
            $asegurar($id, $nombre);
            if (!isset($eq[$id]['piso_por_bucket'][$bucket]) || $cant > $eq[$id]['piso_por_bucket'][$bucket]) {
                $eq[$id]['piso_por_bucket'][$bucket] = $cant;
            }
        };

        // KIT
        foreach ($kit['equipos'] as $item) {
            $id = (int)$item['equipo_id'];
            $asegurar($id, $item['nombre_equipo'] ?? null);
            foreach ($item['por_recinto'] as $pr) {
                $registrarPiso($id, $item['nombre_equipo'] ?? null, 'recinto_' . (int)$pr['recinto_id'], (int)$pr['subtotal']);
            }
            $eq[$id]['origenes']['kit'] = (int)$item['cantidad'];
            $eq[$id]['detalle'][] = ['origen' => 'kit', 'cantidad' => (int)$item['cantidad']];
        }

        // TIPO2
        foreach ($tipo2['equipos'] as $item) {
            $id = (int)$item['equipo_id'];
            $asegurar($id, $item['nombre_equipo'] ?? null);
            foreach ($item['por_recinto'] as $pr) {
                $registrarPiso($id, $item['nombre_equipo'] ?? null, 'recinto_' . (int)$pr['recinto_id'], (int)$pr['nro_recintos']);
            }
            $eq[$id]['origenes']['tipo2_relacion'] = (int)$item['cantidad'];
            $eq[$id]['detalle'][] = ['origen' => 'tipo2_relacion', 'cantidad' => (int)$item['cantidad']];
        }

        // TIPO6 (norma UPC)
        foreach ($tipo6['equipos'] as $item) {
            $id = (int)$item['equipo_id'];
            $asegurar($id, $item['nombre_equipo'] ?? null);
            $porBucketTmp = [];
            foreach ($item['por_regla'] as $pr) {
                $recId = self::MAPA_REGLA_RECINTO[$pr['regla']] ?? null;
                if ($recId === null) continue;
                $bucket = 'recinto_' . $recId;
                $porBucketTmp[$bucket] = ($porBucketTmp[$bucket] ?? 0) + (int)$pr['subtotal'];
            }
            foreach ($porBucketTmp as $bucket => $cant) {
                $registrarPiso($id, $item['nombre_equipo'] ?? null, $bucket, $cant);
            }
            $eq[$id]['origenes']['norma_upc'] = (int)$item['cantidad'];
            $eq[$id]['detalle'][] = ['origen' => 'norma_upc', 'cantidad' => (int)$item['cantidad']];
        }

        // TIPO5 (demanda)
        foreach ($tipo5['equipos'] as $item) {
            $id   = (int)$item['equipo_id'];
            $cant = (int)$item['cantidad'];
            $asegurar($id, $item['nombre_equipo'] ?? null);

            $recintosSet = [];
            foreach ($item['por_prestacion'] as $pp) {
                $rec = $recintoPorPrestacion[(int)$pp['prestacion_id']] ?? null;
                if ($rec !== null) $recintosSet[$rec] = true;
            }
            $recintosInvolucrados = array_keys($recintosSet);

            if (count($recintosInvolucrados) === 1) {
                $registrarPiso($id, $item['nombre_equipo'] ?? null, 'recinto_' . $recintosInvolucrados[0], $cant);
            } else {
                $eq[$id]['demanda'] = $cant;
            }

            $eq[$id]['origenes']['demanda'] = $cant;
            $eq[$id]['detalle'][] = ['origen' => 'demanda', 'cantidad' => $cant];
        }

        // 4) Cantidad final = max(piso, demanda)
        $equipos = [];
        foreach ($eq as $id => $info) {
            $piso          = array_sum($info['piso_por_bucket']);
            $cantidadFinal = max($piso, $info['demanda']);
            $equipos[] = [
                'equipo_id'        => $id,
                'nombre_equipo'    => $info['nombre'],
                'cantidad'         => $cantidadFinal,
                'cantidad_piso'    => $piso,
                'cantidad_demanda' => $info['demanda'],
                'origenes'         => $info['origenes'],
                'detalle'          => $info['detalle'],
            ];
        }

        usort($equipos, fn($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);

        return [
            'proyecto_id' => $proyectoId,
            'equipos'     => $equipos,
            'fuentes'     => [
                'kit'   => $kit,
                'tipo2' => $tipo2,
                'tipo5' => $tipo5,
                'tipo6' => $tipo6,
            ],
        ];
    }
}