<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * calculo_equipamiento_tipo6.php
 *
 * Calculo de EQUIPAMIENTO por REGLAS / GUIAS UPC (norma por camas).
 *
 * ---------------------------------------------------------------------
 * Modelo (confirmado en sesion de trabajo)
 * ---------------------------------------------------------------------
 * Las guias UPC son un MECANISMO DE CALCULO por norma que aplica a
 * CUALQUIER equipo listado en las reglas, SIN IMPORTAR su tipo_demanda.
 * (Las guias listan equipos de varios tipos; la fuente manda sobre el
 * tipo, no al reves.) Por eso los IDs se toman directo de las reglas,
 * no se filtra por tipo_demanda=6.
 *
 * Tres reglas independientes (no se comparten camas entre ellas):
 *   - UCI:        1 dotacion cada  6 camas UCI (recinto_base_id = 2)
 *   - UTI:        1 dotacion cada 12 camas UTI (recinto_base_id = 1)
 *   - Enfermeria: 1 estacion cada N camas, POR SEPARADO en UCI y UTI.
 *
 * Formula por equipo y regla:
 *   cantidad = ceil( nº_camas_del_recinto / cada_N_camas ) * cantidad_base
 *
 * Un mismo equipo puede estar en varias reglas (p.ej. UCI y UTI); sus
 * cantidades se SUMAN (independientes), porque puede haber prestaciones
 * exclusivas de UCI o de UTI.
 *
 * ---------------------------------------------------------------------
 * PENDIENTE DE CONFIRMAR (Enfermeria en UTI)
 * ---------------------------------------------------------------------
 * La estacion de enfermeria se dimensiona cada 6 camas en UCI. Para UTI
 * queda PROVISIONAL en 6 (segun indicacion inicial), pero podria ser 12
 * (igual que el resto del equipamiento UTI). Nico lo validara con su
 * companero. Para cambiarlo basta editar EPHDEM_UPC_ENFERMERIA_DIV_UTI.
 *
 * ---------------------------------------------------------------------
 * Composicion (se resuelve en el AGREGADOR, no aqui):
 *   La cantidad por norma UPC es un PISO MINIMO. Si el mismo equipo se
 *   calcula ademas por demanda (T5) y la demanda supera ese piso, gana
 *   la demanda: cantidad_final = max(norma_UPC, demanda). Kit y UPC,
 *   entre si, tambien se combinan con max() (piso vs piso).
 * =====================================================================
 */

/* ---------------------------------------------------------------------
 * CONFIG · Divisores de camas por regla (cuantas camas "comparte" cada
 * dotacion de equipamiento). Configurables por si cambian las guias.
 * ------------------------------------------------------------------- */
const EPHDEM_UPC_UCI_DIV            = 6;   // UCI: 1 dotacion cada 6 camas UCI
const EPHDEM_UPC_UTI_DIV            = 12;  // UTI: 1 dotacion cada 12 camas UTI
const EPHDEM_UPC_ENFERMERIA_DIV_UCI = 6;   // Enfermeria en UCI: cada 6 camas
const EPHDEM_UPC_ENFERMERIA_DIV_UTI = 12;  // Enfermeria en UTI: cada 12 camas (confirmado con compañero, 08-jul)

/* recinto_base_id de cada area critica (segun EPHAC_Recinto_Estandar) */
const EPHDEM_RECINTO_UTI = 1;
const EPHDEM_RECINTO_UCI = 2;

/* ---------------------------------------------------------------------
 * TABLAS DE REGLAS · equipo_id => cantidad_base por dotacion.
 * Origen: Reglas_UPC.xlsx (hojas "UCI cada 6 camas", "Enfermeria cada 6
 * camas", "Regla uti cada 12 camas"). Si cambian las guias, actualizar
 * estas tablas (idealmente regenerandolas desde el Excel).
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_ReglasUPC(): array
{
    return [
        // UCI: cada 6 camas UCI (recinto 2)
        'uci' => [
        5 => 1,
        172 => 1,
        34 => 1,
        28 => 1,
        96 => 1,
        48 => 1,
        29 => 1,
        70 => 1,
        97 => 1,
        118 => 1,
        21 => 1,
        9 => 1,
        83 => 1,
        11 => 2,
        117 => 2,
        33 => 1,
        111 => 1,
        109 => 2,
        173 => 1,
        1 => 1,
        46 => 1,
        174 => 2,
        140 => 2,
        150 => 2,
        64 => 2,
        175 => 1,
        3 => 1,
        57 => 1,
        67 => 1,
        55 => 1,
        147 => 1,
        82 => 1,
        127 => 1,
        10 => 1,
        31 => 1,
        71 => 1,
        93 => 1,
        120 => 1,
        114 => 1,
        52 => 1,
        58 => 1,
        138 => 1,
        99 => 1,
        ],
        // UTI: cada 12 camas UTI (recinto 1)
        'uti' => [
        174 => 4,
        46 => 3,
        1 => 1,
        70 => 2,
        140 => 3,
        67 => 1,
        55 => 1,
        71 => 1,
        111 => 1,
        24 => 1,
        33 => 1,
        118 => 1,
        57 => 1,
        143 => 2,
        82 => 1,
        28 => 1,
        22 => 3,
        120 => 2,
        138 => 1,
        ],
        // Enfermeria: cada N camas, por separado en UCI y UTI
        'enfermeria' => [
        91 => 1,
        176 => 1,
        153 => 1,
        110 => 1,
        ],
    ];
}


/* ---------------------------------------------------------------------
 * Calculo de equipamiento TIPO 6 / reglas UPC.
 * Devuelve, por equipo, la cantidad total por norma y el desglose por
 * regla (uci / uti / enfermeria) que la origino.
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo6(mysqli $conn, int $proyecto_id): array
{
    // Conteo de camas por recinto (reutiliza el helper del modulo kit).
    $conteoPorRecinto = SIGEM_EPHDEM_CERRADA_ConteoRecintosPorId($conn, $proyecto_id);
    $camasUCI = $conteoPorRecinto[EPHDEM_RECINTO_UCI] ?? 0;
    $camasUTI = $conteoPorRecinto[EPHDEM_RECINTO_UTI] ?? 0;

    // Nº de dotaciones por regla (ceil de camas / divisor).
    $dotacionesUCI    = ($camasUCI > 0) ? (int)ceil($camasUCI / EPHDEM_UPC_UCI_DIV) : 0;
    $dotacionesUTI    = ($camasUTI > 0) ? (int)ceil($camasUTI / EPHDEM_UPC_UTI_DIV) : 0;
    $dotacionesEnfUCI = ($camasUCI > 0) ? (int)ceil($camasUCI / EPHDEM_UPC_ENFERMERIA_DIV_UCI) : 0;
    $dotacionesEnfUTI = ($camasUTI > 0) ? (int)ceil($camasUTI / EPHDEM_UPC_ENFERMERIA_DIV_UTI) : 0;

    $reglas = SIGEM_EPHDEM_CERRADA_ReglasUPC();

    $acum = []; // equipo_id => ['cantidad'=>int, 'por_regla'=>[...]]

    $aplicar = function (string $etiqueta, array $tabla, int $dotaciones) use (&$acum) {
        if ($dotaciones <= 0) {
            return;
        }
        foreach ($tabla as $eid => $cantBase) {
            $sub = (int)$cantBase * $dotaciones;
            if ($sub <= 0) {
                continue;
            }
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

    $aplicar('uci',        $reglas['uci'],        $dotacionesUCI);
    $aplicar('uti',        $reglas['uti'],        $dotacionesUTI);
    // Enfermeria: se dimensiona por separado en UCI y UTI, y se suma.
    $aplicar('enfermeria_uci', $reglas['enfermeria'], $dotacionesEnfUCI);
    $aplicar('enfermeria_uti', $reglas['enfermeria'], $dotacionesEnfUTI);

    // Nombres de equipos (una sola consulta).
    $nombres = [];
    if (!empty($acum)) {
        $ids = implode(',', array_map('intval', array_keys($acum)));
        $q = "SELECT id_equipo, nombre_equipo
                FROM " . EPHDEM_TBL_EQUIPOS . "
               WHERE id_equipo IN ($ids)";
        $res = $conn->query($q);
        if ($res !== false) {
            while ($r = $res->fetch_assoc()) {
                $nombres[(int)$r['id_equipo']] = $r['nombre_equipo'];
            }
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
        'camas' => [
            'uci' => $camasUCI,
            'uti' => $camasUTI,
        ],
        'dotaciones' => [
            'uci'            => $dotacionesUCI,
            'uti'            => $dotacionesUTI,
            'enfermeria_uci' => $dotacionesEnfUCI,
            'enfermeria_uti' => $dotacionesEnfUTI,
        ],
        'equipos' => $equipos,
    ];
}
