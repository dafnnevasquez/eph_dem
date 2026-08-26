<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * calculo_equipamiento.php
 *
 * Hub de inclusion de las funciones de calculo del modulo.
 * Centraliza los require_once para que el resto del codigo
 * (funciones_sigemuv_*, endpoints AJAX, scripts CLI) solo
 * tenga que incluir este archivo.
 *
 * No declara funciones propias. Si en el futuro se agregan
 * calculadores adicionales (Tipo 4 / 5 / 6, etc.), basta con
 * sumarlos aqui.
 * =====================================================================
 */

/**
 * Mapeo de regla T6 (norma UPC) -> recinto_base_id al que pertenece.
 * Compartido entre calculo_equipamiento_agregador.php (calcula el piso
 * por equipo) y calculo_equipamiento_vistas.php (arma la vista por
 * recinto). Vive aqui, en el hub, para que ambos lean la MISMA fuente:
 * antes solo existia en vistas.php y el agregador tenia su propia
 * logica (incorrecta) que ignoraba a que recinto pertenecia cada
 * fuente de piso. Ver docs/decisiones.md.
 */
const EPHDEM_MAPA_REGLA_RECINTO = [
    'uci'            => 2,
    'enfermeria_uci' => 2,
    'uti'            => 1,
    'enfermeria_uti' => 1,
];

require_once __DIR__ . '/calculo_pabellones_boxes.php';
require_once __DIR__ . '/calculo_equipamiento_tipo5.php';
require_once __DIR__ . '/calculo_equipamiento_kit.php';
require_once __DIR__ . '/calculo_equipamiento_tipo6.php';
require_once __DIR__ . '/calculo_equipamiento_agregador.php';
require_once __DIR__ . '/calculo_equipamiento_vistas.php';
require_once __DIR__ . '/calculo_equipamiento_urpa.php';
