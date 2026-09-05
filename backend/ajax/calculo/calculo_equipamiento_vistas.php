<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * calculo_equipamiento_vistas.php
 *
 * VISTAS de presentacion sobre el resultado ya calculado por el
 * agregador (calculo_equipamiento_agregador.php). No recalcula nada:
 * reutiliza $equipamiento['fuentes'] (kit, tipo2, tipo5, tipo6) para
 * construir dos agrupaciones adicionales que necesita el frontend.
 *
 * ---------------------------------------------------------------------
 * 1) POR RECINTO
 * ---------------------------------------------------------------------
 * Para KIT, TIPO2 y TIPO6 la atribucion a un recinto es exacta (cada
 * fuente ya la trae). Para TIPO5 (demanda) solo es exacta cuando TODAS
 * las prestaciones que aportan a un equipo pertenecen al MISMO recinto
 * base; en ese caso se fusiona como piso adicional de ese recinto.
 *
 * ---------------------------------------------------------------------
 * 2) DEMANDA COMPARTIDA
 * ---------------------------------------------------------------------
 * Cuando un equipo T5 recibe fraccion de prestaciones de MAS DE UN
 * recinto base, esa cantidad final (ya redondeada con un solo ceil())
 * no se puede repartir limpiamente entre recintos sin inventar un
 * numero. Estos equipos se excluyen de por_recinto y se listan aca con
 * su cantidad total real y los recintos que contribuyen (cualitativo).
 *
 * Decision de diseño (confirmada con Nico, 08-jul): NO se aproxima ni
 * se reparte. Nivel A (equipamiento.equipos) sigue siendo la verdad
 * final para estos equipos.
 * =====================================================================
 */

const EPHDEM_NOMBRES_RECINTO = [
    1 => 'Cubículo UTI',
    2 => 'Cubículo UCI',
    3 => 'Pabellón menor',
    4 => 'Pabellón mayor',
];

// Mapeo de regla T6 -> recinto_base_id al que pertenece.
// (Constante EPHDEM_MAPA_REGLA_RECINTO movida al hub calculo_equipamiento.php
// para que agregador.php y esta vista usen la misma fuente. Ver ahi.)

function SIGEM_EPHDEM_CERRADA_CalcularEquipamientoPorRecinto(
    mysqli $conn,
    array $equipamiento
): array {
    $fuentes = $equipamiento['fuentes'];
    $kit   = $fuentes['kit'];
    $tipo2 = $fuentes['tipo2'];
    $tipo5 = $fuentes['tipo5'];
    $tipo6 = $fuentes['tipo6'];

    /* --- 1) Acumular piso por recinto (kit / tipo2 / norma_upc) ---
     * $piso[recinto_id][equipo_id] = ['nombre'=>.., 'kit'=>0,'tipo2'=>0,'norma_upc'=>0,'demanda'=>0]
     */
    $piso = [];

    $entrada = function (int $rec, int $eid, ?string $nombre) use (&$piso): void {
        if (!isset($piso[$rec][$eid])) {
            $piso[$rec][$eid] = ['nombre' => $nombre, 'kit' => 0, 'tipo2' => 0, 'norma_upc' => 0, 'demanda' => 0];
        } elseif ($nombre !== null) {
            $piso[$rec][$eid]['nombre'] = $nombre;
        }
    };

    foreach ($kit['equipos'] as $item) {
        $eid = (int)$item['equipo_id'];
        foreach ($item['por_recinto'] as $pr) {
            $rec = (int)$pr['recinto_id'];
            $entrada($rec, $eid, $item['nombre_equipo']);
            $piso[$rec][$eid]['kit'] = max($piso[$rec][$eid]['kit'], (int)$pr['subtotal']);
        }
    }

    foreach ($tipo2['equipos'] as $item) {
        $eid = (int)$item['equipo_id'];
        foreach ($item['por_recinto'] as $pr) {
            $rec = (int)$pr['recinto_id'];
            $entrada($rec, $eid, $item['nombre_equipo']);
            $piso[$rec][$eid]['tipo2'] = max($piso[$rec][$eid]['tipo2'], (int)$pr['nro_recintos']);
        }
    }

    foreach ($tipo6['equipos'] as $item) {
        $eid = (int)$item['equipo_id'];
        // Varias reglas (ej. uci + enfermeria_uci) pueden apuntar al mismo
        // recinto: se SUMAN entre si (son dotaciones distintas del mismo
        // recinto), pero el resultado sigue siendo piso vs kit/tipo2 (max).
        $porRecintoTmp = [];
        foreach ($item['por_regla'] as $pr) {
            $rec = EPHDEM_MAPA_REGLA_RECINTO[$pr['regla']] ?? null;
            if ($rec === null) {
                continue;
            }
            $porRecintoTmp[$rec] = ($porRecintoTmp[$rec] ?? 0) + (int)$pr['subtotal'];
        }
        foreach ($porRecintoTmp as $rec => $cant) {
            $entrada($rec, $eid, $item['nombre_equipo']);
            $piso[$rec][$eid]['norma_upc'] = max($piso[$rec][$eid]['norma_upc'], $cant);
        }
    }

    /* --- 2) Clasificar cada equipo TIPO 5 segun cuantos recintos --- */
    $prestIds = [];
    foreach ($tipo5['equipos'] as $item) {
        foreach ($item['por_prestacion'] as $pp) {
            $prestIds[(int)$pp['prestacion_id']] = true;
        }
    }

    $recintoPorPrestacion = [];
    if (!empty($prestIds)) {
        $ids = implode(',', array_map('intval', array_keys($prestIds)));
        $res = $conn->query(
            "SELECT id_prestacion, recinto_base_id FROM " . EPHDEM_TBL_PRESTACIONES . "
              WHERE id_prestacion IN ($ids)"
        );
        if ($res !== false) {
            while ($r = $res->fetch_assoc()) {
                $recintoPorPrestacion[(int)$r['id_prestacion']] = (int)$r['recinto_base_id'];
            }
        }
    }

    $demandaCompartida = [];

    foreach ($tipo5['equipos'] as $item) {
        $eid = (int)$item['equipo_id'];

        $recintosSet = [];
        foreach ($item['por_prestacion'] as $pp) {
            $rec = $recintoPorPrestacion[(int)$pp['prestacion_id']] ?? null;
            if ($rec !== null) {
                $recintosSet[$rec] = true;
            }
        }
        $recintosInvolucrados = array_keys($recintosSet);

        if (count($recintosInvolucrados) === 1) {
            // Atribuible sin ambiguedad: se fusiona como piso de ese recinto.
            $rec = $recintosInvolucrados[0];
            $entrada($rec, $eid, $item['nombre_equipo']);
            $piso[$rec][$eid]['demanda'] = (int)$item['cantidad'];
            continue;
        }

        if (count($recintosInvolucrados) > 1) {
            // No atribuible: se excluye de por_recinto, va a seccion aparte.
            $demandaCompartida[] = [
                'equipo_id'             => $eid,
                'nombre_equipo'         => $item['nombre_equipo'],
                'cantidad'              => (int)$item['cantidad'],
                'fraccion_total'        => $item['fraccion_total'],
                'recintos_involucrados' => array_map(
                    static fn ($r) => [
                        'recinto_id'     => $r,
                        'nombre_recinto' => EPHDEM_NOMBRES_RECINTO[$r] ?? null,
                    ],
                    $recintosInvolucrados
                ),
            ];
        }
        // count === 0: T5 sin prestacion con recinto_base_id resoluble
        // (dato inconsistente en catalogo). Se omite silenciosamente,
        // igual que hace CalcularEquipamientoTipo5 con requerimiento NULL.
    }

    /* --- 3) Armar salida por_recinto: cantidad = max(kit, tipo2, norma_upc, demanda) ---
     * Los equipos que pertenecen a la tabla 'enfermeria' de ReglasUPC() se
     * separan en su propia lista 'estacion_enfermeria' (no se duplican en
     * 'equipos'), para que el frontend los muestre en un sub-desplegable
     * dentro de UCI/UTI. */
    $idsEnfermeria = array_flip(
        array_map('intval', array_keys(SIGEM_EPHDEM_CERRADA_ReglasUPC()['enfermeria']))
    );
    // La regla de enfermeria solo aplica a UCI/UTI (recintos 1 y 2). En
    // pabellones (3/4) un equipo puede coincidir con esos IDs por otra
    // razon (ej. kit) y NO debe separarse ahi.
    $recintosConEnfermeria = [1, 2];

    $porRecinto = [];
    foreach ($piso as $rec => $equiposRec) {
        $listaGeneral = [];
        $listaEnfermeria = [];
        $separarEnfermeria = in_array($rec, $recintosConEnfermeria, true);
        foreach ($equiposRec as $eid => $info) {
            $pisoMax  = max($info['kit'], $info['tipo2'], $info['norma_upc']);
            $cantidad = max($pisoMax, $info['demanda']);
            if ($cantidad <= 0) {
                continue;
            }
            $item = [
                'equipo_id'     => $eid,
                'nombre_equipo' => $info['nombre'],
                'cantidad'      => $cantidad,
            ];
            if ($separarEnfermeria && isset($idsEnfermeria[$eid])) {
                $listaEnfermeria[] = $item;
            } else {
                $listaGeneral[] = $item;
            }
        }
        usort($listaGeneral, static fn ($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);
        usort($listaEnfermeria, static fn ($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);
        $porRecinto[$rec] = [
            'nombre_recinto'      => EPHDEM_NOMBRES_RECINTO[$rec] ?? null,
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
