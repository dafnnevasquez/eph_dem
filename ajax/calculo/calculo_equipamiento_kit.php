<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * calculo_equipamiento_kit.php
 *
 * Calculo de EQUIPAMIENTO por KIT DE RECINTO y por TIPO 2 (via
 * relaciones Prestacion_Equipo).
 *
 * ---------------------------------------------------------------------
 * KIT (EPHAC_Recinto_Equipo)
 * ---------------------------------------------------------------------
 * Equipamiento minimo fijo que debe tener cada recinto (R1 UTI, R2 UCI,
 * R3 Pabellon menor, R4 Pabellon mayor). El tipo_demanda del equipo NO
 * importa aqui: la cantidad ya esta determinada por cantidad_base en la
 * tabla del kit. Formula:
 *
 *     total(equipo) = SUMA sobre cada recinto donde aparece
 *                      ( cantidad_base * nº de recintos de ese tipo )
 *
 * ---------------------------------------------------------------------
 * TIPO 2 via relaciones (Prestacion_Equipo, equipos con tipo_demanda=2)
 * ---------------------------------------------------------------------
 * "1 equipo por modulo de atencion" (box o pabellon). Se identifica a
 * que recinto(s) pertenece cada equipo Tipo 2 segun las prestaciones
 * del proyecto que lo usan (via recinto_base_id de esas prestaciones),
 * y se suma el nº de modulos de esos recintos. Verificado contra datos
 * reales: los equipos Tipo 2 usados en relaciones (Rx movil, Ventilador
 * mecanico invasivo) aparecen en prestaciones de UTI Y UCI a la vez, por
 * lo que su cantidad final es la SUMA de boxes de ambos recintos.
 *
 * ---------------------------------------------------------------------
 * Regla de composicion (aplica en el AGREGADOR, no aqui):
 *   Si un equipo aparece tanto en el KIT como en una fuente por demanda
 *   (Tipo 5), la cantidad final = max(cantidad_kit, cantidad_demanda).
 *   El kit es un piso minimo garantizado, no se suma con la demanda.
 * =====================================================================
 */

const EPHDEM_TBL_RECINTO_EQUIPO = 'EPHAC_Recinto_Equipo';


/* ---------------------------------------------------------------------
 * Helper privado · Conteo de recintos del proyecto, indexado por
 * recinto_base_id (1=UTI, 2=UCI, 3=Pabellon menor, 4=Pabellon mayor).
 *
 * Reutiliza CalcularPabellones() y CalcularBoxes(), que ya persisten
 * requerimiento_calculado; no repite la formula EEMM ni vuelve a tocar
 * la BD en modo escritura (solo lee lo que esas funciones devuelven).
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_ConteoRecintosPorId(mysqli $conn, int $proyecto_id): array
{
    $pabellones = SIGEM_EPHDEM_CERRADA_CalcularPabellones($conn, $proyecto_id);
    $boxes      = SIGEM_EPHDEM_CERRADA_CalcularBoxes($conn, $proyecto_id);

    $conteo = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

    foreach ($boxes['boxes_por_recinto'] as $recId => $info) {
        $conteo[(int)$recId] = $info['boxes'];
    }
    foreach ($pabellones['pabellones_por_recinto'] as $recId => $info) {
        $conteo[(int)$recId] = $info['pabellones'];
    }

    return $conteo;
}


/* ---------------------------------------------------------------------
 * 1) Calculo de equipamiento del KIT (por recinto)
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_CalcularEquipamientoKit(mysqli $conn, int $proyecto_id): array
{
    $conteoPorRecinto = SIGEM_EPHDEM_CERRADA_ConteoRecintosPorId($conn, $proyecto_id);

    $sql = "SELECT re.recinto_id,
                   re.equipo_id,
                   re.cantidad_base,
                   eq.nombre_equipo
            FROM " . EPHDEM_TBL_RECINTO_EQUIPO . " re
            INNER JOIN " . EPHDEM_TBL_EQUIPOS . " eq
                    ON eq.id_equipo = re.equipo_id";

    $res = $conn->query($sql);
    if ($res === false) {
        return ['equipos' => []];
    }

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    $totalPorEquipo   = []; // id_equipo => cantidad total acumulada (int)
    $nombrePorEquipo  = []; // id_equipo => nombre_equipo
    $porRecinto       = []; // id_equipo => [ ['recinto_id'=>.., 'cantidad_base'=>.., 'nro_recintos'=>.., 'subtotal'=>..], ... ]

    foreach ($rows as $row) {
        $eid  = (int)$row['equipo_id'];
        $rec  = (int)$row['recinto_id'];
        $cant = (int)$row['cantidad_base'];
        $nRec = $conteoPorRecinto[$rec] ?? 0;
        $subtotal = $cant * $nRec;

        if (!isset($totalPorEquipo[$eid])) {
            $totalPorEquipo[$eid]  = 0;
            $nombrePorEquipo[$eid] = $row['nombre_equipo'];
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


/* ---------------------------------------------------------------------
 * 2) Calculo de equipamiento TIPO 2 (via relaciones Prestacion_Equipo)
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo2(mysqli $conn, int $proyecto_id): array
{
    $conteoPorRecinto = SIGEM_EPHDEM_CERRADA_ConteoRecintosPorId($conn, $proyecto_id);

    $sql = "SELECT DISTINCT eq.id_equipo,
                   eq.nombre_equipo,
                   p.recinto_base_id
            FROM " . EPHDEM_TBL_DEMANDA . " pd
            INNER JOIN " . EPHDEM_TBL_PRESTACION_EQUIPO . " pe
                    ON pe.prestacion_id = pd.prestacion_id
            INNER JOIN " . EPHDEM_TBL_EQUIPOS . " eq
                    ON eq.id_equipo = pe.equipo_id
            INNER JOIN " . EPHDEM_TBL_PRESTACIONES . " p
                    ON p.id_prestacion = pd.prestacion_id
            WHERE pd.proyecto_id = ?
              AND eq.tipo_demanda = " . EPHDEM_TIPO_DEMANDA_T2;

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

    $recintosPorEquipo = []; // id_equipo => set de recinto_base_id (como claves de array)
    $nombrePorEquipo    = [];

    foreach ($rows as $row) {
        $eid = (int)$row['id_equipo'];
        $rec = (int)$row['recinto_base_id'];

        if (!isset($recintosPorEquipo[$eid])) {
            $recintosPorEquipo[$eid] = [];
            $nombrePorEquipo[$eid]   = $row['nombre_equipo'];
        }
        $recintosPorEquipo[$eid][$rec] = true; // dedup automatico por clave
    }

    $equipos = [];
    foreach ($recintosPorEquipo as $eid => $recintosSet) {
        $porRecinto = [];
        $total = 0;
        foreach (array_keys($recintosSet) as $rec) {
            $nRec = $conteoPorRecinto[$rec] ?? 0;
            $total += $nRec;
            $porRecinto[] = [
                'recinto_id'  => $rec,
                'nro_recintos' => $nRec,
            ];
        }

        $equipos[] = [
            'equipo_id'     => $eid,
            'nombre_equipo' => $nombrePorEquipo[$eid],
            'tipo_demanda'  => EPHDEM_TIPO_DEMANDA_T2,
            'origen'        => 'tipo2_relacion',
            'cantidad'      => $total,
            'por_recinto'   => $porRecinto,
        ];
    }

    return [
        'equipos' => $equipos,
    ];
}
