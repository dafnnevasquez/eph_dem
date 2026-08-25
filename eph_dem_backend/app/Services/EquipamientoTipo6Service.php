<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EquipamientoTipo6Service
{
    const TBL_EQUIPOS   = 'EPHAC_Equipos';
    const RECINTO_UTI   = 1;
    const RECINTO_UCI   = 2;

    // Divisores por regla
    const UCI_DIV            = 6;
    const UTI_DIV            = 12;
    const ENFERMERIA_DIV_UCI = 6;
    const ENFERMERIA_DIV_UTI = 12;

    public function __construct(
        private PabellonesBoxesService $pabellonesService
    ) {}

    /**
     * Reglas UPC hardcodeadas (origen: Reglas_UPC.xlsx).
     */
    private function reglasUPC(): array
    {
        return [
            'uci' => [
                5=>1, 172=>1, 34=>1, 28=>1, 96=>1, 48=>1, 29=>1, 70=>1, 97=>1,
                118=>1, 21=>1, 9=>1, 83=>1, 11=>2, 117=>2, 33=>1, 111=>1, 109=>2,
                173=>1, 1=>1, 46=>1, 174=>2, 140=>2, 150=>2, 64=>2, 175=>1, 3=>1,
                57=>1, 67=>1, 55=>1, 147=>1, 82=>1, 127=>1, 10=>1, 31=>1, 71=>1,
                93=>1, 120=>1, 114=>1, 52=>1, 58=>1, 138=>1, 99=>1,
            ],
            'uti' => [
                174=>4, 46=>3, 1=>1, 70=>2, 140=>3, 67=>1, 55=>1, 71=>1, 111=>1,
                24=>1, 33=>1, 118=>1, 57=>1, 143=>2, 82=>1, 28=>1, 22=>3, 120=>2, 138=>1,
            ],
            'enfermeria' => [
                91=>1, 176=>1, 153=>1, 110=>1,
            ],
        ];
    }

    /**
     * Calcula equipamiento Tipo 6 (norma UPC).
     */
    public function calcular(int $proyectoId): array
    {
        $conteo   = $this->pabellonesService->conteoRecintosPorId($proyectoId);
        $camasUCI = $conteo[self::RECINTO_UCI] ?? 0;
        $camasUTI = $conteo[self::RECINTO_UTI] ?? 0;

        $dotacionesUCI    = $camasUCI > 0 ? (int)ceil($camasUCI / self::UCI_DIV) : 0;
        $dotacionesUTI    = $camasUTI > 0 ? (int)ceil($camasUTI / self::UTI_DIV) : 0;
        $dotacionesEnfUCI = $camasUCI > 0 ? (int)ceil($camasUCI / self::ENFERMERIA_DIV_UCI) : 0;
        $dotacionesEnfUTI = $camasUTI > 0 ? (int)ceil($camasUTI / self::ENFERMERIA_DIV_UTI) : 0;

        $reglas = $this->reglasUPC();
        $acum   = [];

        $aplicar = function (string $etiqueta, array $tabla, int $dotaciones) use (&$acum) {
            if ($dotaciones <= 0) return;
            foreach ($tabla as $eid => $cantBase) {
                $sub = (int)$cantBase * $dotaciones;
                if ($sub <= 0) continue;
                if (!isset($acum[$eid])) {
                    $acum[$eid] = ['cantidad' => 0, 'por_regla' => []];
                }
                $acum[$eid]['cantidad'] += $sub;
                $acum[$eid]['por_regla'][] = [
                    'regla'         => $etiqueta,
                    'cantidad_base' => (int)$cantBase,
                    'dotaciones'    => $dotaciones,
                    'subtotal'      => $sub,
                ];
            }
        };

        $aplicar('uci',            $reglas['uci'],        $dotacionesUCI);
        $aplicar('uti',            $reglas['uti'],        $dotacionesUTI);
        $aplicar('enfermeria_uci', $reglas['enfermeria'], $dotacionesEnfUCI);
        $aplicar('enfermeria_uti', $reglas['enfermeria'], $dotacionesEnfUTI);

        // Resolver nombres en una sola consulta
        $nombres = [];
        if (!empty($acum)) {
            $ids = implode(',', array_map('intval', array_keys($acum)));
            $res = DB::select("SELECT id_equipo, nombre_equipo FROM " . self::TBL_EQUIPOS . " WHERE id_equipo IN ($ids)");
            foreach ($res as $r) {
                $nombres[(int)$r->id_equipo] = $r->nombre_equipo;
            }
        }

        $equipos = [];
        foreach ($acum as $eid => $info) {
            $equipos[] = [
                'equipo_id'     => $eid,
                'nombre_equipo' => $nombres[$eid] ?? null,
                'origen'        => 'norma_upc',
                'cantidad'      => $info['cantidad'],
                'por_regla'     => $info['por_regla'],
            ];
        }

        return [
            'camas'      => ['uci' => $camasUCI, 'uti' => $camasUTI],
            'dotaciones' => [
                'uci'            => $dotacionesUCI,
                'uti'            => $dotacionesUTI,
                'enfermeria_uci' => $dotacionesEnfUCI,
                'enfermeria_uti' => $dotacionesEnfUTI,
            ],
            'equipos' => $equipos,
        ];
    }
}