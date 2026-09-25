<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * calculo_equipamiento_urpa.php
 *
 * URPA (Sala de Recuperacion Post-Anestesica).
 *
 * MODULO AISLADO: no participa del pipeline normal de prestaciones.
 * Ninguna prestacion tiene recinto_base_id apuntando a la URPA, asi que
 * ni CalcularPabellones, ni CalcularBoxes, ni el agregador la tocan.
 * Este archivo SOLO deriva la URPA a partir de un numero ya calculado:
 * el total de pabellones. Por eso no toca ni una linea del motor
 * existente y no puede introducir doble-conteo en Nivel A/B.
 *
 * ---------------------------------------------------------------------
 * ARITMETICA (decisiones confirmadas con Nico, 14-jul):
 *   - camillas = EPHDEM_URPA_CAMILLAS_POR_PABELLON * nro_pabellones
 *   - nro_pabellones = total de pabellones (urgencia + electivo,
 *     mayor + menor). Pool unico, sin separar urgencia/electivo.
 *   - salas = ceil(camillas / EPHDEM_URPA_MAX_CAMILLAS)
 *
 *   No hay problema de "sumar fracciones antes de redondear": se parte
 *   de un entero (nro_pabellones ya salio de su propio ceil()), asi que
 *   camillas es entero*entero y solo hay un ceil() final para las salas.
 *
 * ---------------------------------------------------------------------
 * DOS NIVELES DE EQUIPAMIENTO (multiplicadores distintos):
 *   - EQUIPOS DE SALA:    1 dotacion por SALA de URPA (no escalan con
 *                         camillas). cantidad = salas * cantidad_base.
 *   - EQUIPOS DE CAMILLA: escalan con el numero de camillas.
 *                         cantidad = camillas * cantidad_base.
 *
 * Las listas de IDs viven hardcodeadas abajo (no en EPHAC_Recinto_Equipo)
 * a proposito: mantiene la URPA fuera del flujo de kits y evita subir SQL
 * a produccion. Si en el futuro se quiere que sean editables sin tocar
 * codigo, migrar estos arrays a un par de recintos nuevos en la tabla de
 * kits; el resto de la logica de conteo seguiria igual.
 * =====================================================================
 */

/* ---------------------------------------------------------------------
 * CONFIG · Constantes de conteo
 * ------------------------------------------------------------------- */
const EPHDEM_URPA_CAMILLAS_POR_PABELLON = 2;
const EPHDEM_URPA_MAX_CAMILLAS          = 12;
const EPHDEM_URPA_NOMBRE_RECINTO        = 'URPA (Sala de recuperación post-anestésica)';

/* ---------------------------------------------------------------------
 * LISTAS DE EQUIPOS  ·  ⚠️ PROVISIONAL — REVISAR CON PROFESORA GUIA
 * ---------------------------------------------------------------------
 * Formato: equipo_id => cantidad_base (dotacion por unidad).
 *   - En SALA:    la unidad es "1 sala de URPA".
 *   - En CAMILLA: la unidad es "1 camilla de URPA".
 *
 * IDs tomados del catalogo real (mapa id->nombre confirmado en produccion
 * el 14-jul). El nombre de cada equipo se resuelve por SELECT, aqui solo
 * van los IDs y su cantidad base. Ajustar libremente estos dos arrays: el
 * resto del modulo no necesita cambios.
 *
 * Esta clasificacion (que equipo es "de sala" vs "de camilla") es una
 * DECISION CLINICA pendiente de validar. La de abajo es una propuesta de
 * arranque basada en el equipamiento de recuperacion post-anestesica ya
 * identificado en sesiones previas; NO es definitiva.
 * ------------------------------------------------------------------- */

// Equipos que se cargan 1 vez por SALA de URPA (independiente del nro de camillas).
// IDs y nombres confirmados contra EPHAC_Equipos (Heidi, 14-jul-2026).
const EPHDEM_URPA_EQUIPOS_SALA = [
    33  => 1,   // Carro de paro completo
    88  => 1,   // Lavamanos clínico
    53  => 1,   // Dispensador de jabón
    124 => 1,   // Porta toalla de papel
    48  => 1,   // Computador
    110 => 1,   // Monitor central
    107 => 1,   // Mesón de estación de enfermería
];

// Equipos que escalan con el numero de CAMILLAS de URPA.
// IDs y nombres confirmados contra EPHAC_Equipos (Heidi, 14-jul-2026).
const EPHDEM_URPA_EQUIPOS_CAMILLA = [
    27  => 1,   // Camilla de recuperación con barandas
    113 => 1,   // Monitor multiparámetros
    17  => 1,   // Bomba de infusión volumétrica
    123 => 1,   // Porta sueros
    22  => 1,   // Botella de oxígeno
    68  => 1,   // Fonendoscopio
    166 => 1,   // Tomas de aspiración central
];


/* ---------------------------------------------------------------------
 * Funcion principal
 * ---------------------------------------------------------------------
 * @param mysqli $conn           conexion activa
 * @param int    $nroPabellones  total de pabellones del proyecto
 *                                (pabellones.total del contrato)
 *
 * @return array bloque URPA autocontenido con la misma forma "amigable"
 *               que un recinto, mas su metadata de conteo. Estructura:
 *   [
 *     'nombre_recinto'  => string,
 *     'nro_pabellones'  => int,
 *     'nro_camillas'    => int,
 *     'nro_salas'       => int,
 *     'equipos_sala'    => [ {equipo_id, nombre_equipo, cantidad_base, cantidad}, ... ],
 *     'equipos_camilla' => [ {equipo_id, nombre_equipo, cantidad_base, cantidad}, ... ],
 *     'equipos'         => [ {equipo_id, nombre_equipo, cantidad}, ... ]  // fusion (suma por id)
 *   ]
 * ------------------------------------------------------------------- */
function SIGEM_EPHDEM_CERRADA_CalcularURPA(mysqli $conn, int $nroPabellones): array
{
    $nroPabellones = max(0, $nroPabellones);

    $camillas = EPHDEM_URPA_CAMILLAS_POR_PABELLON * $nroPabellones;
    $salas    = $camillas > 0
        ? (int)ceil($camillas / EPHDEM_URPA_MAX_CAMILLAS)
        : 0;

    // --- Resolver nombres de todos los equipos involucrados en 1 consulta ---
    $idsInvolucrados = array_unique(array_merge(
        array_keys(EPHDEM_URPA_EQUIPOS_SALA),
        array_keys(EPHDEM_URPA_EQUIPOS_CAMILLA)
    ));

    $nombres = [];
    if (!empty($idsInvolucrados)) {
        $ids = implode(',', array_map('intval', $idsInvolucrados));
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

    // --- Construir listas por nivel (sala escala x salas, camilla x camillas) ---
    $construirLista = static function (array $definicion, int $multiplicador) use ($nombres): array {
        $lista = [];
        foreach ($definicion as $eid => $cantidadBase) {
            $eid = (int)$eid;
            $cantidad = $multiplicador * (int)$cantidadBase;
            if ($cantidad <= 0) {
                continue;
            }
            $lista[] = [
                'equipo_id'     => $eid,
                'nombre_equipo' => $nombres[$eid] ?? null,
                'cantidad_base' => (int)$cantidadBase,
                'cantidad'      => $cantidad,
            ];
        }
        usort($lista, static fn ($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);
        return $lista;
    };

    $equiposSala    = $construirLista(EPHDEM_URPA_EQUIPOS_SALA, $salas);
    $equiposCamilla = $construirLista(EPHDEM_URPA_EQUIPOS_CAMILLA, $camillas);

    // --- Lista fusionada (suma por equipo_id) para el frontend que quiera
    //     una sola tabla plana en vez de dos sub-secciones. ---
    $merge = [];
    foreach (array_merge($equiposSala, $equiposCamilla) as $item) {
        $eid = $item['equipo_id'];
        if (!isset($merge[$eid])) {
            $merge[$eid] = [
                'equipo_id'     => $eid,
                'nombre_equipo' => $item['nombre_equipo'],
                'cantidad'      => 0,
            ];
        }
        $merge[$eid]['cantidad'] += $item['cantidad'];
    }
    $equipos = array_values($merge);
    usort($equipos, static fn ($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);

    return [
        'nombre_recinto'  => EPHDEM_URPA_NOMBRE_RECINTO,
        'nro_pabellones'  => $nroPabellones,
        'nro_camillas'    => $camillas,
        'nro_salas'       => $salas,
        'equipos_sala'    => $equiposSala,
        'equipos_camilla' => $equiposCamilla,
        'equipos'         => $equipos,
    ];
}
