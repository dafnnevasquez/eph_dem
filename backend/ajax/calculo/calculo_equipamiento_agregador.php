<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * calculo_equipamiento_agregador.php
 *
 * AGREGADOR FINAL de equipamiento. Une las cuatro fuentes de calculo
 * en una sola lista consolidada por equipo, aplicando la regla de
 * composicion acordada.
 *
 * ---------------------------------------------------------------------
 * Las cuatro fuentes
 * ---------------------------------------------------------------------
 *   1. KIT      (CalcularEquipamientoKit)   -> origen 'kit'
 *   2. TIPO 2   (CalcularEquipamientoTipo2)  -> origen 'tipo2_relacion'
 *   3. TIPO 5   (CalcularEquipamientoTipo5)  -> origen 'demanda'
 *   4. TIPO 6   (CalcularEquipamientoTipo6)  -> origen 'norma_upc'
 *
 * ---------------------------------------------------------------------
 * Regla de composicion (una sola, unificada) — CORREGIDA 28-07-2026
 * ---------------------------------------------------------------------
 * Hay dos clases de fuente:
 *   - PISO MINIMO (base fija que el recinto debe tener si o si):
 *       kit, tipo2_relacion y norma_upc.
 *   - CRECIMIENTO POR DEMANDA (puede empujar hacia arriba):
 *       demanda (T5).
 *
 * El piso se calcula POR RECINTO, no por equipo a secas:
 *   1. Cada fuente de piso (kit, tipo2_relacion, norma_upc) declara a
 *      que recinto pertenece cada cantidad que aporta (kit y tipo2 ya
 *      traen 'por_recinto'; norma_upc trae 'por_regla', que se mapea a
 *      recinto via EPHDEM_MAPA_REGLA_RECINTO, sumando entre si las
 *      reglas que caen en el mismo recinto, p.ej. uci + enfermeria_uci).
 *   2. DENTRO de un mismo recinto: si dos fuentes distintas (p.ej. kit
 *      y norma_upc) declaran cantidad para el MISMO equipo en el MISMO
 *      recinto, son estimaciones alternativas del mismo piso -> se
 *      toma el MAYOR (max), no se suman.
 *   3. ENTRE recintos distintos: cada recinto es una sala fisica
 *      separada que necesita su propia dotacion -> los pisos de cada
 *      recinto se SUMAN.
 *   piso   = SUMA sobre los recintos de [ MAX de las fuentes que
 *            aportan a ESE recinto ]
 *   demanda = cantidad calculada por T5 (si aplica).
 *   cantidad_final = max(piso, demanda)
 *
 * BUG CORREGIDO (detectado en validacion 28-07-2026): la version
 * anterior tomaba max() entre los TOTALES de kit/tipo2/norma_upc ya
 * sumados a nivel de todo el proyecto, sin distinguir a que recinto
 * pertenecia cada uno. Cuando un equipo aparecia en kit para un
 * recinto (p.ej. Negatoscopio: 1x en Pabellon mayor via kit) Y
 * ademas en norma_upc para OTRO recinto (Negatoscopio: 2x en UCI via
 * regla 'uci'), el max() entre ambos totales descartaba silenciosamente
 * el recinto con menor cantidad, subestimando el total del proyecto
 * (confirmado en las 5 pruebas de validacion: 9, 9, 14, 1 y 0 equipos
 * afectados respectivamente, siempre equipos en 2+ tipos de recinto).
 * Esta misma logica por-recinto ya la implementaba correctamente
 * calculo_equipamiento_vistas.php (hoja "Habilitacion"); el agregador
 * (Nivel A, la fuente de verdad segun el contrato) no la tenia, lo que
 * ademas hacia que Nivel A y Nivel B (por_recinto) pudieran divergir
 * entre si para el mismo proyecto. Ahora usan la misma logica.
 *
 * Nota: la SUMA DENTRO de una fuente (p.ej. T6 suma uci+uti+enfermeria
 * si caen en recintos distintos; el kit suma sus recintos; T2 suma sus
 * recintos) sigue resuelta por cada calculador via su 'cantidad' total
 * -> eso alimenta 'origenes' (informativo, sin cambios). El calculo de
 * 'piso' en cambio ahora reconstruye el detalle por recinto de cada
 * fuente para poder aplicar max()-por-recinto + suma-entre-recintos.
 *
 * SEGUNDA CORRECCION (28-07-2026, misma sesion): el mismo problema
 * existia entre PISO y DEMANDA. Un equipo con piso repartido en varios
 * recintos y demanda (T5) atribuible a UN SOLO recinto competia con el
 * PISO TOTAL de todo el proyecto en vez de con el piso de su propio
 * recinto, escondiendo la necesidad real de ese recinto detras del
 * piso de los otros. Fix: si todas las prestaciones que originan la
 * demanda de un equipo pertenecen al mismo recinto, esa demanda entra
 * al bucket de ESE recinto (compite ahi via max(), se suma con los
 * demas). Si la demanda es "compartida" (2+ recintos, o ninguno
 * resoluble) el comportamiento queda IGUAL que antes: demanda de
 * proyecto completo vs. piso total, tal como se confirmo con Nico el
 * 08-jul para demanda_compartida ("no se aproxima ni se reparte").
 *
 * ---------------------------------------------------------------------
 * ORDEN DE EJECUCION
 * ---------------------------------------------------------------------
 * Internamente cada calculador vuelve a leer/derivar lo que necesita
 * (los de kit/T2/T6 reusan ConteoRecintosPorId, que corre pabellones y
 * boxes y persiste requerimiento_calculado). T5 depende de que ese
 * requerimiento ya este persistido. Por eso el agregador corre primero
 * el KIT (que dispara pabellones+boxes) y despues T5, garantizando el
 * orden correcto sin recalcular de mas.
 * =====================================================================
 */

function SIGEM_EPHDEM_CERRADA_CalcularEquipamiento(mysqli $conn, int $proyecto_id): array
{
    /* 1) Fuentes de PISO que dependen del conteo de recintos.
     *    El KIT dispara internamente CalcularPabellones + CalcularBoxes
     *    (via ConteoRecintosPorId), que persisten requerimiento_calculado.
     *    Debe ir antes que T5. */
    $kit   = SIGEM_EPHDEM_CERRADA_CalcularEquipamientoKit($conn, $proyecto_id);
    $tipo2 = SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo2($conn, $proyecto_id);
    $tipo6 = SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo6($conn, $proyecto_id);

    /* 2) Fuente de DEMANDA (T5). Requiere requerimiento_calculado ya
     *    persistido por el paso anterior. */
    $tipo5 = SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo5($conn, $proyecto_id);

    /* 2.1) Resolver a que recinto pertenece cada prestacion que aporta
     *      demanda T5, para poder atribuir la demanda de un equipo a UN
     *      recinto especifico cuando corresponde (ver mas abajo). Misma
     *      consulta que ya hace calculo_equipamiento_vistas.php. */
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

    /* 3) Consolidacion por equipo.
     *    Estructura acumulada:
     *      $eq[id] = [
     *        'nombre'          => string,
     *        'piso_por_bucket' => [ bucket => int ],  // max por recinto/bucket
     *        'demanda'         => int,      // cantidad T5
     *        'origenes'        => [ 'kit'=>.., 'tipo2_relacion'=>.., 'norma_upc'=>.., 'demanda'=>.. ],
     *        'detalle'         => [ ...desglose por fuente... ],
     *      ]
     *    'bucket' identifica un recinto fisico: 'recinto_1'..'recinto_4'
     *    para kit/tipo2, o el recinto que EPHDEM_MAPA_REGLA_RECINTO
     *    asigna a cada regla de norma_upc (uci/enfermeria_uci -> recinto
     *    2, uti/enfermeria_uti -> recinto 1). Dos reglas de norma_upc
     *    que caen en el mismo recinto se SUMAN entre si antes de entrar
     *    al bucket (son dotaciones distintas del mismo recinto), igual
     *    que ya hace calculo_equipamiento_vistas.php. */
    $eq = [];

    $asegurar = function (int $id, ?string $nombre) use (&$eq) {
        if (!isset($eq[$id])) {
            $eq[$id] = [
                'nombre'          => $nombre,
                'piso_por_bucket' => [],
                'demanda'         => 0,
                'origenes'        => [],
                'detalle'         => [],
            ];
        } elseif ($eq[$id]['nombre'] === null && $nombre !== null) {
            $eq[$id]['nombre'] = $nombre;
        }
    };

    /* Registra un aporte de piso a un bucket (recinto) especifico,
     * tomando el MAXIMO si ya habia otra fuente aportando al mismo
     * bucket para el mismo equipo. */
    $registrarPiso = function (int $id, ?string $nombre, string $bucket, int $cant) use (&$eq, $asegurar) {
        $asegurar($id, $nombre);
        if (!isset($eq[$id]['piso_por_bucket'][$bucket]) || $cant > $eq[$id]['piso_por_bucket'][$bucket]) {
            $eq[$id]['piso_por_bucket'][$bucket] = $cant;
        }
    };

    /* --- KIT: cada 'por_recinto' ya trae recinto_id + subtotal --- */
    foreach ($kit['equipos'] as $item) {
        $id     = (int)$item['equipo_id'];
        $nombre = $item['nombre_equipo'] ?? null;
        $asegurar($id, $nombre);
        foreach ($item['por_recinto'] as $pr) {
            $registrarPiso($id, $nombre, 'recinto_' . (int)$pr['recinto_id'], (int)$pr['subtotal']);
        }
        $eq[$id]['origenes']['kit'] = (int)$item['cantidad'];
        $eq[$id]['detalle'][] = ['origen' => 'kit', 'cantidad' => (int)$item['cantidad'], 'desglose' => $item];
    }

    /* --- TIPO2: cada 'por_recinto' trae recinto_id + nro_recintos
     * (1 equipo por modulo, por lo que nro_recintos ES el subtotal) --- */
    foreach ($tipo2['equipos'] as $item) {
        $id     = (int)$item['equipo_id'];
        $nombre = $item['nombre_equipo'] ?? null;
        $asegurar($id, $nombre);
        foreach ($item['por_recinto'] as $pr) {
            $registrarPiso($id, $nombre, 'recinto_' . (int)$pr['recinto_id'], (int)$pr['nro_recintos']);
        }
        $eq[$id]['origenes']['tipo2_relacion'] = (int)$item['cantidad'];
        $eq[$id]['detalle'][] = ['origen' => 'tipo2_relacion', 'cantidad' => (int)$item['cantidad'], 'desglose' => $item];
    }

    /* --- NORMA_UPC (T6): 'por_regla' se mapea a recinto via
     * EPHDEM_MAPA_REGLA_RECINTO. Reglas distintas que caen en el mismo
     * recinto (p.ej. 'uci' + 'enfermeria_uci') se SUMAN antes de
     * competir por max() con kit/tipo2 en ese recinto. --- */
    foreach ($tipo6['equipos'] as $item) {
        $id     = (int)$item['equipo_id'];
        $nombre = $item['nombre_equipo'] ?? null;
        $asegurar($id, $nombre);

        $porBucketTmp = [];
        foreach ($item['por_regla'] as $pr) {
            $recId = EPHDEM_MAPA_REGLA_RECINTO[$pr['regla']] ?? null;
            if ($recId === null) {
                continue;
            }
            $bucket = 'recinto_' . $recId;
            $porBucketTmp[$bucket] = ($porBucketTmp[$bucket] ?? 0) + (int)$pr['subtotal'];
        }
        foreach ($porBucketTmp as $bucket => $cant) {
            $registrarPiso($id, $nombre, $bucket, $cant);
        }

        $eq[$id]['origenes']['norma_upc'] = (int)$item['cantidad'];
        $eq[$id]['detalle'][] = ['origen' => 'norma_upc', 'cantidad' => (int)$item['cantidad'], 'desglose' => $item];
    }

    /* --- Fuente de DEMANDA (T5) ---
     * BUG CORREGIDO (28-07-2026, segunda parte): igual que con piso vs
     * piso, la demanda de un equipo atribuible a UN SOLO recinto se
     * comparaba (max) contra el PISO TOTAL DE TODO EL PROYECTO, no solo
     * contra el piso de su propio recinto. Un equipo con piso repartido
     * en varios recintos (p.ej. Ecografo: 1 en UTI + 1 en UCI, via kit)
     * y demanda de 2 atribuible solo a Pabellon mayor terminaba en
     * max(piso_total=2, demanda=2)=2, perdiendo la necesidad real de
     * Pabellon (2+1+1=4). Fix: si TODAS las prestaciones que aportan a
     * la demanda de un equipo pertenecen al MISMO recinto, esa demanda
     * entra al bucket de ESE recinto (compite ahi via max(), igual que
     * kit/tipo2/norma_upc) y luego se SUMA con los demas recintos como
     * cualquier otro bucket.
     *
     * Si la demanda es "compartida" (prestaciones de 2+ recintos
     * distintos, o de ninguno resoluble) se mantiene el comportamiento
     * ORIGINAL sin cambios: demanda a nivel de proyecto completo,
     * comparada con max() contra el piso total al final. Esto replica
     * a proposito la decision de diseño ya confirmada con Nico (08-jul)
     * para demanda_compartida: "no se aproxima ni se reparte". */
    foreach ($tipo5['equipos'] as $item) {
        $id     = (int)$item['equipo_id'];
        $nombre = $item['nombre_equipo'] ?? null;
        $cant   = (int)$item['cantidad'];
        $asegurar($id, $nombre);

        $recintosSet = [];
        foreach ($item['por_prestacion'] as $pp) {
            $rec = $recintoPorPrestacion[(int)$pp['prestacion_id']] ?? null;
            if ($rec !== null) {
                $recintosSet[$rec] = true;
            }
        }
        $recintosInvolucrados = array_keys($recintosSet);

        if (count($recintosInvolucrados) === 1) {
            // Atribuible a un solo recinto: compite con el piso de ESE
            // recinto unicamente (no con el piso de todo el proyecto).
            $bucket = 'recinto_' . $recintosInvolucrados[0];
            $registrarPiso($id, $nombre, $bucket, $cant);
        } else {
            // Compartido (2+ recintos) o sin recinto resoluble (0):
            // comportamiento original, sin cambios.
            $eq[$id]['demanda'] = $cant;
        }

        $eq[$id]['origenes']['demanda'] = $cant;
        $eq[$id]['detalle'][] = [
            'origen'   => 'demanda',
            'cantidad' => $cant,
            'desglose' => $item,
        ];
    }

    /* 4) Piso final por equipo = SUMA de los buckets (cada bucket ya es
     *    el max entre las fuentes que aportaron a ESE recinto).
     *    Cantidad final = max(piso, demanda), igual que antes. */
    $equipos = [];
    foreach ($eq as $id => $info) {
        $piso          = array_sum($info['piso_por_bucket']);
        $cantidadFinal = max($piso, $info['demanda']);
        $equipos[] = [
            'equipo_id'      => $id,
            'nombre_equipo'  => $info['nombre'],
            'cantidad'       => $cantidadFinal,
            'cantidad_piso'  => $piso,
            'cantidad_demanda' => $info['demanda'],
            'origenes'       => $info['origenes'],
            'detalle'        => $info['detalle'],
        ];
    }

    // Orden estable por id de equipo (facilita lectura y diffs).
    usort($equipos, function ($a, $b) {
        return $a['equipo_id'] <=> $b['equipo_id'];
    });

    return [
        'proyecto_id' => $proyecto_id,
        'equipos'     => $equipos,
        'fuentes'     => [
            'kit'   => $kit,
            'tipo2' => $tipo2,
            'tipo5' => $tipo5,
            'tipo6' => $tipo6,
        ],
    ];
}
