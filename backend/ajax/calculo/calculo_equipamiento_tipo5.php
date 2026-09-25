<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * calculo_equipamiento_tipo5.php
 *
 * Calculo de EQUIPAMIENTO TIPO 5 (cantidad calculada exclusivamente
 * por demanda proyectada).
 *
 * Regla de negocio (confirmada en sesion de trabajo):
 *   - Para cada equipo Tipo 5, se identifican TODAS las prestaciones
 *     del proyecto relacionadas a el (via EPHAC_Prestacion_Equipo),
 *     sin importar area/subarea/pool (urgencia-electivo, UCI-UTI, etc).
 *   - Se SUMAN las fracciones de requerimiento_calculado de esas
 *     prestaciones (ya persistidas por CalcularPabellones/CalcularBoxes).
 *   - Se aplica ceil() UNA SOLA VEZ al final, sobre la suma total.
 *     Nunca se redondea por prestacion individual (misma regla EEMM
 *     que pabellones/boxes).
 *   - Verificado contra datos reales: ningun equipo Tipo 5 se usa a
 *     la vez en prestaciones de Gastroenterologia Intervencional y de
 *     UPC, por lo que la suma global no mezcla pools fisicamente
 *     incompatibles. Si en el futuro apareciera un equipo dedicado
 *     a una sola subarea (no compartido), esa seria una excepcion a
 *     tratar aparte; no se ha detectado ningun caso asi hasta ahora.
 *
 * IMPORTANTE — orden de ejecucion:
 *   Esta funcion LEE requerimiento_calculado desde EPHAC_Proyecto_Demanda.
 *   Debe ejecutarse DESPUES de SIGEM_EPHDEM_CERRADA_CalcularPabellones()
 *   y SIGEM_EPHDEM_CERRADA_CalcularBoxes(), que son las que calculan y
 *   persisten ese valor. Si una prestacion del proyecto aun no tiene
 *   requerimiento_calculado (NULL), sus filas se omiten silenciosamente
 *   (no se cuentan como 0 ni generan error).
 *
 * Constantes compartidas con los futuros calculadores de equipamiento
 * (Tipo 2 / kit de recintos y Tipo 6 / norma UPC): EPHDEM_TBL_EQUIPOS,
 * EPHDEM_TBL_PRESTACION_EQUIPO y las EPHDEM_TIPO_DEMANDA_T*. Se definen
 * aqui por ser el primer archivo de equipamiento cargado por el hub;
 * los archivos que se agreguen despues deben reutilizarlas, no
 * redefinirlas.
 * =====================================================================
 */

/* ---------------------------------------------------------------------
 * CONFIG · Nombres de tablas y tipos de demanda (CONFIRMAR contra la BD)
 *
 * Nota de nombre: la tabla de relaciones es 'EPHAC_Prestacion_Equipo'
 * (singular, SIN 's' final) — confirmado via SHOW CREATE TABLE. No usar
 * 'EPHAC_Prestacion_Equipos' (plural), no existe con ese nombre.
 * ------------------------------------------------------------------- */
const EPHDEM_TBL_EQUIPOS           = 'EPHAC_Equipos';
const EPHDEM_TBL_PRESTACION_EQUIPO = 'EPHAC_Prestacion_Equipo';

const EPHDEM_TIPO_DEMANDA_T2 = 2; // 1 por modulo (recinto/box/pabellon) — kit + relaciones
const EPHDEM_TIPO_DEMANDA_T5 = 5; // Calculado exclusivamente por demanda
const EPHDEM_TIPO_DEMANDA_T6 = 6; // Norma/guia (solo UPC: UCI/UTI)


/* ---------------------------------------------------------------------
 * 1) Calculo de equipamiento TIPO 5
 *    Retorna, por cada equipo Tipo 5 usado por el proyecto, la fraccion
 *    total acumulada, la cantidad final (ceil) y el desglose por
 *    prestacion que aporto a esa suma.
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo5(mysqli $conn, int $proyecto_id): array
{
    $sql = "SELECT eq.id_equipo,
                   eq.nombre_equipo,
                   pd.prestacion_id,
                   pd.requerimiento_calculado
            FROM " . EPHDEM_TBL_DEMANDA . " pd
            INNER JOIN " . EPHDEM_TBL_PRESTACION_EQUIPO . " pe
                    ON pe.prestacion_id = pd.prestacion_id
            INNER JOIN " . EPHDEM_TBL_EQUIPOS . " eq
                    ON eq.id_equipo = pe.equipo_id
            WHERE pd.proyecto_id = ?
              AND eq.tipo_demanda = " . EPHDEM_TIPO_DEMANDA_T5;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return ['equipos' => []];
    }
    $stmt->bind_param('i', $proyecto_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();

    $sumaPorEquipo   = []; // id_equipo => fraccion acumulada (float)
    $nombrePorEquipo = []; // id_equipo => nombre_equipo
    $porPrestacion   = []; // id_equipo => [ ['prestacion_id'=>.., 'requerimiento_fraccion'=>..], ... ]

    foreach ($rows as $row) {
        // Prestacion sin requerimiento_calculado aun (pabellones/boxes
        // no se han corrido para esta fila): se omite, no se suma como 0.
        if ($row['requerimiento_calculado'] === null) {
            continue;
        }

        $eid  = (int)$row['id_equipo'];
        $frac = (float)$row['requerimiento_calculado'];

        if (!isset($sumaPorEquipo[$eid])) {
            $sumaPorEquipo[$eid]   = 0.0;
            $nombrePorEquipo[$eid] = $row['nombre_equipo'];
            $porPrestacion[$eid]   = [];
        }

        $sumaPorEquipo[$eid] += $frac;
        $porPrestacion[$eid][] = [
            'prestacion_id'          => (int)$row['prestacion_id'],
            'requerimiento_fraccion' => round($frac, 4),
        ];
    }

    $equipos = [];
    foreach ($sumaPorEquipo as $eid => $fraccionTotal) {
        $equipos[] = [
            'equipo_id'      => $eid,
            'nombre_equipo'  => $nombrePorEquipo[$eid],
            'tipo_demanda'   => EPHDEM_TIPO_DEMANDA_T5,
            'origen'         => 'demanda',
            'fraccion_total' => round($fraccionTotal, 4),
            'cantidad'       => (int)ceil($fraccionTotal),
            'por_prestacion' => $porPrestacion[$eid],
        ];
    }

    return [
        'equipos' => $equipos,
    ];
}
