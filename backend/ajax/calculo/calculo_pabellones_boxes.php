<?php
/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * Calculo de PABELLONES y BOXES (UPC) mediante la formula EEMM
 * =====================================================================
 * Entorno: PHP 7.4 · mysqli (sin PDO, sin framework) · MySQL 5.7
 * Convencion: SIGEM_EPHDEM_CERRADA_FunctionName($conn)
 *
 * Reglas de atencion cerrada aplicadas en la formula:
 *   - N_prestaciones queda EXCLUIDO del numerador.
 *   - disponibilidad por defecto = 1 (100%).
 *
 * Regla clave de agregacion:
 *   La formula por prestacion entrega una FRACCION de recinto.
 *   Se SUMAN las fracciones dentro de cada pool (urgencia, electivo,
 *   o subarea UPC) y SOLO AL FINAL se aplica ceil(). Nunca se redondea
 *   por prestacion individual.
 *
 * Integrar estas funciones en calculo_equipamiento.php
 * (o incluir este archivo desde alli).
 * =====================================================================
 */

/* ---------------------------------------------------------------------
 * CONFIG · Nombres de tablas y etiquetas (CONFIRMAR contra la BD real)
 * ------------------------------------------------------------------- */
const EPHDEM_TBL_DEMANDA      = 'EPHAC_Proyecto_Demanda';        // variables del usuario
const EPHDEM_TBL_PRESTACIONES = 'EPHAC_Prestaciones';            // catalogo: tiempo_procedimiento, area
const EPHDEM_AREA_UPC         = 'UPC';                           // valor EXACTO de area_hospitalaria para boxes
const EPHDEM_TP_DIA_CAMA      = 1440;                            // 24 h en minutos (TP fijo para boxes)

// IDs de prestaciones clasificadas como pabellon de URGENCIA.
// Clasificacion hardcoded en el modulo. Si esta vacio, toda prestacion de
// pabellon cae en 'electivo'.
const EPHDEM_IDS_URGENCIA = [
    4, 7, 8, 9, 10, 11, 16, 19, 20, 30, 32, 33, 34, 40, 41, 42, 44, 46,
    50, 51, 54, 57, 58, 59, 60, 61, 65, 67, 68, 70, 71, 73, 74, 76, 78,
    79, 80, 81, 82, 85, 86, 87, 88, 90, 91, 94, 95, 96, 97, 98, 99, 100,
    101, 102, 103, 104, 107, 108, 109, 110, 111, 112, 113, 115, 116, 117,
    118, 122, 128, 129, 130, 145, 147, 148,
];

// IDs de prestaciones "Dia Cama" — las UNICAS que habilitan el
// dimensionamiento del pool de boxes UPC. "Dia Cama" ya ES la
// definicion de ocupacion de box (demanda de dias-cama proyectada /
// 365 = censo promedio diario). Las demas prestaciones UPC (cateteres,
// gasometrias, hemodialisis, etc.) se realizan sobre un paciente que
// YA esta contabilizado como Dia Cama: no son eventos independientes
// que requieran un box adicional. Siguen alimentando el calculo de
// equipamiento Tipo 5 (ver requerimiento_calculado mas abajo), pero no
// el conteo de boxes. Confirmado contra el catalogo EPHAC_Prestaciones
// exportado el 14-07-2026; validado clinicamente con Nico en sesion de
// trabajo (pendiente de ratificar con la profesora guia antes de
// defender en la tesis).
const EPHDEM_IDS_DIA_CAMA = [149, 150]; // 149 = Dia Cama UCI (201201), 150 = Dia Cama UTI (201301)


/* ---------------------------------------------------------------------
 * 1) Formula maestra EEMM
 *    Devuelve el requerimiento FRACCIONARIO (sin redondear).
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_FormulaEEMM(
    float $demanda_anual,
    float $dias_laborales,
    float $tiempo_procedimiento,
    float $disponibilidad,
    float $jornada
): float {
    if ($dias_laborales <= 0 || $tiempo_procedimiento <= 0
        || $disponibilidad <= 0 || $jornada <= 0) {
        return 0.0;
    }
    $numerador   = $demanda_anual / $dias_laborales;
    $denominador = (60.0 / $tiempo_procedimiento) * $disponibilidad * $jornada;
    if ($denominador <= 0) {
        return 0.0;
    }
    return $numerador / $denominador;
}


/* ---------------------------------------------------------------------
 * 2) Defaults recomendados por categoria (para prellenar el formulario)
 *    El front consume esto al armar la pantalla de ingreso de variables.
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_DefaultsPorCategoria(string $categoria): array {
    switch ($categoria) {
        case 'urgencia':
            return ['dias_laborales' => 365, 'jornada_efectiva' => 24, 'disponibilidad' => 1.0];
        case 'box':
            return ['dias_laborales' => 365, 'jornada_efectiva' => 24, 'disponibilidad' => 1.0];
        case 'electivo':
        default:
            return ['dias_laborales' => 250, 'jornada_efectiva' => 8,  'disponibilidad' => 1.0];
    }
}


/* ---------------------------------------------------------------------
 * 3) Guardado de variables del usuario en Proyecto_Demanda (upsert)
 *    $filas: lista de arrays con claves:
 *      prestacion_id, demanda_anual, dias_laborales,
 *      disponibilidad, jornada_efectiva
 *    Requiere la clave unica (proyecto_id, prestacion_id).
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_GuardarDemanda(mysqli $conn, int $proyecto_id, array $filas): int {
    $conn->begin_transaction();

    try {
        // 1) Limpieza: borrar TODA la demanda previa de este proyecto.
        //    El front envia siempre la seleccion COMPLETA actual, asi que
        //    reemplazamos por completo para no arrastrar prestaciones que
        //    el usuario deselecciono (bug de mezcla pabellon/UPC).
        $del = $conn->prepare(
            "DELETE FROM " . EPHDEM_TBL_DEMANDA . " WHERE proyecto_id = ?"
        );
        if ($del === false) {
            throw new \RuntimeException('prepare DELETE fallo');
        }
        $del->bind_param('i', $proyecto_id);
        $del->execute();
        $del->close();

        // 2) Insertar las filas nuevas.
        $sql = "INSERT INTO " . EPHDEM_TBL_DEMANDA . "
                    (proyecto_id, prestacion_id, demanda_anual, dias_laborales, disponibilidad, jornada_efectiva)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    demanda_anual    = VALUES(demanda_anual),
                    dias_laborales   = VALUES(dias_laborales),
                    disponibilidad   = VALUES(disponibilidad),
                    jornada_efectiva = VALUES(jornada_efectiva)";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new \RuntimeException('prepare INSERT fallo');
        }

        $pid = $prest = $dem = $dias = 0;
        $disp = $jor = 0.0;
        $stmt->bind_param('iiiidd', $pid, $prest, $dem, $dias, $disp, $jor);

        $n = 0;
        foreach ($filas as $f) {
            $pid   = $proyecto_id;
            $prest = (int)($f['prestacion_id']    ?? 0);
            $dem   = (int)($f['demanda_anual']     ?? 0);
            $dias  = (int)($f['dias_laborales']    ?? 0);
            $disp  = (float)($f['disponibilidad']  ?? 1.0);
            $jor   = (float)($f['jornada_efectiva'] ?? 0.0);
            if ($prest <= 0) {
                continue;
            }
            $stmt->execute();
            $n++;
        }
        $stmt->close();

        $conn->commit();
        return $n;

    } catch (\Throwable $e) {
        $conn->rollback();
        return 0;
    }
}


/* ---------------------------------------------------------------------
 * 4) Calculo de PABELLONES (urgencia + electivo)
 *    Lee la demanda guardada, aplica EEMM por prestacion, persiste la
 *    fraccion en requerimiento_calculado, suma por pool y redondea.
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_CalcularPabellones(mysqli $conn, int $proyecto_id): array {
    $sql = "SELECT pd.id_registro,
                   pd.prestacion_id,
                   pd.demanda_anual,
                   pd.dias_laborales,
                   pd.disponibilidad,
                   pd.jornada_efectiva,
                   p.tiempo_procedimiento, p.recinto_base_id
            FROM " . EPHDEM_TBL_DEMANDA . " pd
            INNER JOIN " . EPHDEM_TBL_PRESTACIONES . " p
                    ON p.id_prestacion = pd.prestacion_id
            WHERE pd.proyecto_id = ?
              AND p.area_hospitalaria <> '" . EPHDEM_AREA_UPC . "'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $proyecto_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();

    $detalle = [];
    $suma = ['urgencia' => 0.0, 'electivo' => 0.0];
    $sumaPorRecinto = []; // recinto_base_id (menor=3/mayor=4) => fraccion acumulada

    $up = $conn->prepare(
        "UPDATE " . EPHDEM_TBL_DEMANDA . "
            SET requerimiento_calculado = ?
          WHERE id_registro = ?"
    );
    $bReq = 0.0;
    $bId  = 0;
    $up->bind_param('di', $bReq, $bId);

    foreach ($rows as $row) {
        $req = SIGEM_EPHDEM_CERRADA_FormulaEEMM(
            (float)$row['demanda_anual'],
            (float)$row['dias_laborales'],
            (float)$row['tiempo_procedimiento'],
            (float)$row['disponibilidad'],
            (float)$row['jornada_efectiva']
        );
        $pid = (int)$row['prestacion_id'];
        $cat = in_array($pid, EPHDEM_IDS_URGENCIA, true) ? 'urgencia' : 'electivo';
        $suma[$cat] += $req;

        $rec = (int)$row['recinto_base_id'];
        if (!isset($sumaPorRecinto[$rec])) {
            $sumaPorRecinto[$rec] = 0.0;
        }
        $sumaPorRecinto[$rec] += $req;

        $bReq = $req;
        $bId  = (int)$row['id_registro'];
        $up->execute();

        $detalle[] = [
            'prestacion_id'          => $pid,
            'categoria'              => $cat,
            'requerimiento_fraccion' => round($req, 4),
        ];
    }
    $up->close();

    $urg = (int)ceil($suma['urgencia']);
    $ele = (int)ceil($suma['electivo']);

    $pabellonesPorRecinto = [];
    foreach ($sumaPorRecinto as $rec => $frac) {
        $pabellonesPorRecinto[$rec] = [
            'fraccion'   => round($frac, 4),
            'pabellones' => (int)ceil($frac),
        ];
    }

    return [
        'detalle'             => $detalle,
        'pabellones_urgencia' => $urg,
        'pabellones_electivo' => $ele,
        // Antes: $urg + $ele (ceil() por categoria urgencia/electivo).
        // Como una prestacion NO se reparte igual entre categoria y
        // recinto fisico (menor/mayor), redondear dos veces por
        // separado podia "ganar" un pabellon fantasma (ver Prueba 5
        // de la validacion del 28-07-2026: 6 reales, 7 en el titular).
        // pabellones_por_recinto ya es la fuente fisica correcta (un
        // solo ceil() por recinto), y es la que ya consumen el kit y
        // el equipamiento: pabellones_total ahora se deriva de ahi
        // para que ambos numeros sean siempre consistentes.
        'pabellones_total'    => array_sum(array_column($pabellonesPorRecinto, 'pabellones')),
        'fraccion_urgencia'   => round($suma['urgencia'], 4),
        'fraccion_electivo'   => round($suma['electivo'], 4),
        'pabellones_por_recinto'  => $pabellonesPorRecinto,
    ];
}


/* ---------------------------------------------------------------------
 * 5) Calculo de BOXES (UPC)
 *    TP forzado a 1440 min (dia cama). Se agrupa por subarea para no
 *    mezclar pools fisicos distintos (p. ej. UCI vs UTI).
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_CalcularBoxes(mysqli $conn, int $proyecto_id): array {
    $sql = "SELECT pd.id_registro,
                   pd.prestacion_id,
                   pd.demanda_anual,
                   pd.dias_laborales,
                   pd.disponibilidad,
                   pd.jornada_efectiva,
                   p.subarea_hospitalaria, p.recinto_base_id
            FROM " . EPHDEM_TBL_DEMANDA . " pd
            INNER JOIN " . EPHDEM_TBL_PRESTACIONES . " p
                    ON p.id_prestacion = pd.prestacion_id
            WHERE pd.proyecto_id = ?
              AND p.area_hospitalaria = '" . EPHDEM_AREA_UPC . "'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $proyecto_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();

    $detalle = [];
    $suma = []; // fracciones acumuladas por subarea
    $sumaPorRecinto = []; // recinto_base_id (UTI=1/UCI=2) => fraccion acumulada

    $up = $conn->prepare(
        "UPDATE " . EPHDEM_TBL_DEMANDA . "
            SET requerimiento_calculado = ?
          WHERE id_registro = ?"
    );
    $bReq = 0.0;
    $bId  = 0;
    $up->bind_param('di', $bReq, $bId);

    foreach ($rows as $row) {
        $req = SIGEM_EPHDEM_CERRADA_FormulaEEMM(
            (float)$row['demanda_anual'],
            (float)$row['dias_laborales'],
            (float)EPHDEM_TP_DIA_CAMA,        // TP fijo 1440, ignora el del catalogo
            (float)$row['disponibilidad'],
            (float)$row['jornada_efectiva']
        );
        $sub = ($row['subarea_hospitalaria'] !== null && $row['subarea_hospitalaria'] !== '')
             ? $row['subarea_hospitalaria']
             : 'UPC';

        // El pool que determina la CANTIDAD DE BOXES se alimenta
        // exclusivamente de prestaciones "Dia Cama" (ver
        // EPHDEM_IDS_DIA_CAMA). Los demas procedimientos UPC quedan
        // fuera de esta suma: se calculan y persisten igual (bloque de
        // abajo, sin cambios) porque calculo_equipamiento_tipo5.php
        // depende de esa columna, pero no deben inflar el pool de boxes.
        if (in_array((int)$row['prestacion_id'], EPHDEM_IDS_DIA_CAMA, true)) {
            if (!isset($suma[$sub])) {
                $suma[$sub] = 0.0;
            }
            $suma[$sub] += $req;

            $rec = (int)$row['recinto_base_id'];
            if (!isset($sumaPorRecinto[$rec])) {
                $sumaPorRecinto[$rec] = 0.0;
            }
            $sumaPorRecinto[$rec] += $req;
        }

        $bReq = $req;
        $bId  = (int)$row['id_registro'];
        $up->execute();

        $detalle[] = [
            'prestacion_id'          => (int)$row['prestacion_id'],
            'subarea'                => $sub,
            'requerimiento_fraccion' => round($req, 4),
        ];
    }
    $up->close();

    $boxes_por_subarea = [];
    $total = 0;
    foreach ($suma as $sub => $frac) {
        $b = (int)ceil($frac);
        $boxes_por_subarea[$sub] = [
            'fraccion' => round($frac, 4),
            'boxes'    => $b,
        ];
        $total += $b;
    }

    $boxes_por_recinto = [];
    foreach ($sumaPorRecinto as $rec => $frac) {
        $boxes_por_recinto[$rec] = [
            'fraccion' => round($frac, 4),
            'boxes'    => (int)ceil($frac),
        ];
    }

    return [
        'detalle'           => $detalle,
        'boxes_por_subarea' => $boxes_por_subarea,
        'boxes_total'       => $total,
        'boxes_por_recinto' => $boxes_por_recinto,
    ];
}
