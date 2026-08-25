<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class UrpaService
{
    const TBL_EQUIPOS           = 'EPHAC_Equipos';
    const CAMILLAS_POR_PABELLON = 2;
    const MAX_CAMILLAS          = 12;
    const NOMBRE_RECINTO        = 'URPA (Sala de recuperación post-anestésica)';

    const EQUIPOS_SALA = [
        33  => 1,   // Carro de paro completo
        88  => 1,   // Lavamanos clínico
        53  => 1,   // Dispensador de jabón
        124 => 1,   // Porta toalla de papel
        48  => 1,   // Computador
        110 => 1,   // Monitor central
        107 => 1,   // Mesón de estación de enfermería
    ];

    const EQUIPOS_CAMILLA = [
        27  => 1,   // Camilla de recuperación con barandas
        113 => 1,   // Monitor multiparámetros
        17  => 1,   // Bomba de infusión volumétrica
        123 => 1,   // Porta sueros
        22  => 1,   // Botella de oxígeno
        68  => 1,   // Fonendoscopio
        166 => 1,   // Tomas de aspiración central
    ];

    /**
     * Calcula equipamiento URPA a partir del total de pabellones.
     */
    public function calcular(int $nroPabellones): array
    {
        $nroPabellones = max(0, $nroPabellones);
        $camillas      = self::CAMILLAS_POR_PABELLON * $nroPabellones;
        $salas         = $camillas > 0 ? (int)ceil($camillas / self::MAX_CAMILLAS) : 0;

        // Resolver nombres en una sola consulta
        $idsInvolucrados = array_unique(array_merge(
            array_keys(self::EQUIPOS_SALA),
            array_keys(self::EQUIPOS_CAMILLA)
        ));

        $nombres = [];
        if (!empty($idsInvolucrados)) {
            $ids = implode(',', array_map('intval', $idsInvolucrados));
            $res = DB::select("SELECT id_equipo, nombre_equipo FROM " . self::TBL_EQUIPOS . " WHERE id_equipo IN ($ids)");
            foreach ($res as $r) {
                $nombres[(int)$r->id_equipo] = $r->nombre_equipo;
            }
        }

        $construirLista = function (array $definicion, int $multiplicador) use ($nombres): array {
            $lista = [];
            foreach ($definicion as $eid => $cantidadBase) {
                $cantidad = $multiplicador * (int)$cantidadBase;
                if ($cantidad <= 0) continue;
                $lista[] = [
                    'equipo_id'     => (int)$eid,
                    'nombre_equipo' => $nombres[(int)$eid] ?? null,
                    'cantidad_base' => (int)$cantidadBase,
                    'cantidad'      => $cantidad,
                ];
            }
            usort($lista, fn($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);
            return $lista;
        };

        $equiposSala    = $construirLista(self::EQUIPOS_SALA,    $salas);
        $equiposCamilla = $construirLista(self::EQUIPOS_CAMILLA, $camillas);

        // Lista fusionada
        $merge = [];
        foreach (array_merge($equiposSala, $equiposCamilla) as $item) {
            $eid = $item['equipo_id'];
            if (!isset($merge[$eid])) {
                $merge[$eid] = ['equipo_id' => $eid, 'nombre_equipo' => $item['nombre_equipo'], 'cantidad' => 0];
            }
            $merge[$eid]['cantidad'] += $item['cantidad'];
        }
        $equipos = array_values($merge);
        usort($equipos, fn($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);

        return [
            'nombre_recinto'  => self::NOMBRE_RECINTO,
            'nro_pabellones'  => $nroPabellones,
            'nro_camillas'    => $camillas,
            'nro_salas'       => $salas,
            'equipos_sala'    => $equiposSala,
            'equipos_camilla' => $equiposCamilla,
            'equipos'         => $equipos,
        ];
    }
}