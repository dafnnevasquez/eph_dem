<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * ajax/generar/generar_xls_restriccion.php
 *
 * Endpoint: GET
 * Params: proyecto_id (int), usuario_id (int)
 *
 * Excel con el resultado de la restriccion de RR.HH. (SimpleXLSXGen,
 * igual que generar_xls_cerrada.php):
 *   Hoja 1 "Resumen"      : totales sin restriccion vs con restriccion
 *   Hoja 2 "Recintos"     : alpha, modulos y prestaciones no cubiertas
 *   Hoja 3 "Personal"     : horas requeridas/disponibles, minimo y brecha
 *   Hoja 4 "Equipamiento" : todos los equipos (tipos 1 a 6)
 *   Hoja 5 "URPA"         : camillas, salas y equipos
 * =====================================================================
 */

use Shuchkin\SimpleXLSXGen;

require_once __DIR__ . '/restriccion_datos.php';
require_once __DIR__ . '/../../lib/simplexlsxgen/SimpleXLSXGen.php';

$datos  = ephdem_restriccion_cargar();
$nombre = $datos['nombre'];
$res    = $datos['res'];
$t      = $res['totales'];

$b = static function (array $fila): array {
    return array_map(static function ($h) { return '<b>' . $h . '</b>'; }, $fila);
};

/* --- Hoja 1: Resumen ------------------------------------------------- */
$hoja1 = [];
$hoja1[] = ['<b>Resultado con restricción de RR.HH.</b>'];
$hoja1[] = ['Proyecto', $nombre];
$hoja1[] = ['Generado el', date('d-m-Y H:i')];
$hoja1[] = [''];
$hoja1[] = $b(['Indicador', 'Necesidad tecnológica (sin restricción)', 'Con el personal actual']);
$hoja1[] = ['Pabellones y boxes', $t['modulos_normal'], $t['modulos_restringido']];
if (isset($t['equipos_normal'])) {
    $hoja1[] = ['Equipamiento (todos los tipos)', $t['equipos_normal'], $t['equipos_restringido']];
}
if (!empty($res['urpa'])) {
    $hoja1[] = ['Camillas URPA', $res['urpa']['normal']['camillas'], $res['urpa']['restringido']['camillas']];
    $hoja1[] = ['Equipos URPA', $t['urpa_normal'] ?? '', $t['urpa_restringido'] ?? ''];
}
$hoja1[] = [''];
$hoja1[] = $b(['Personal', 'Valor']);
$hoja1[] = ['Personas que faltan para cubrir toda la demanda', ephdem_restriccion_faltan($res)];
$hoja1[] = ['Prestaciones no cubiertas al año', round(ephdem_restriccion_no_cubiertas($res))];

/* --- Hoja 2: Recintos ------------------------------------------------ */
$hoja2 = [];
$hoja2[] = $b(['Recinto', 'Con tiempos cargados', 'α (fracción de la demanda que cubre el personal)', 'Perfil que limita',
    'Módulos sin restricción', 'Módulos con restricción', 'Demanda anual', 'Prestaciones no cubiertas']);
foreach ($res['recintos'] as $r) {
    $hoja2[] = [
        $r['nombre'],
        $r['con_datos_planilla_a'] ? 'Sí' : 'No',
        round((float)$r['alpha'], 4),
        $r['perfil_limitante'] ?? '',
        $r['modulos'] ? $r['modulos']['normal'] : '',
        $r['modulos'] ? $r['modulos']['restringido'] : '',
        $r['demanda_total'],
        round((float)$r['prestaciones_no_cubiertas']),
    ];
}

/* --- Hoja 3: Personal ------------------------------------------------ */
$hoja3 = [];
$hoja3[] = $b(['Recinto', 'Perfil', 'Horas requeridas al año (Q)', 'Horas disponibles al año (A)',
    'Suficiencia (A/Q)', 'Personas', 'Mínimo necesario', 'Faltan']);
$hayPersonal = false;
foreach ($res['recintos'] as $r) {
    foreach ($r['por_perfil'] as $p) {
        $hayPersonal = true;
        $hoja3[] = [
            $r['nombre'],
            $p['perfil'],
            round((float)$p['q'], 1),
            round((float)$p['a'], 1),
            $p['rho'] === null ? '' : round((float)$p['rho'], 4),
            $p['n'],
            $p['n_min'] === null ? '' : $p['n_min'],
            $p['brecha'] === null ? '' : $p['brecha'],
        ];
    }
}
if (!$hayPersonal) {
    $hoja3[] = ['(Sin tiempos cargados en la Planilla A)'];
}

/* --- Hoja 4: Equipamiento -------------------------------------------- */
$hoja4 = [];
$hoja4[] = $b(['Equipo', 'Tipo', 'Sin restricción', 'Con restricción', 'Diferencia']);
$equipos = $res['equipamiento'] ?? [];
usort($equipos, static function ($a, $b) {
    $da = $a['normal'] - $a['restringido'];
    $db = $b['normal'] - $b['restringido'];
    if ($da !== $db) return $db <=> $da;           // primero los que más bajan
    return strcmp((string)$a['nombre_equipo'], (string)$b['nombre_equipo']);
});
foreach ($equipos as $e) {
    $hoja4[] = [
        $e['nombre_equipo'],
        $e['tipo'] ? 'Tipo ' . $e['tipo'] : '',
        $e['normal'],
        $e['restringido'],
        $e['restringido'] - $e['normal'],
    ];
}
if (empty($equipos)) {
    $hoja4[] = ['(Sin equipamiento calculado)'];
}

/* --- Hoja 5: URPA ---------------------------------------------------- */
$hoja5 = [];
if (!empty($res['urpa']) && $res['urpa']['normal']['pabellones'] > 0) {
    $u = $res['urpa'];
    $hoja5[] = $b(['URPA', 'Sin restricción', 'Con restricción']);
    $hoja5[] = ['Pabellones', $u['normal']['pabellones'], $u['restringido']['pabellones']];
    $hoja5[] = ['Camillas', $u['normal']['camillas'], $u['restringido']['camillas']];
    $hoja5[] = ['Salas', $u['normal']['salas'], $u['restringido']['salas']];
    $hoja5[] = [''];
    $hoja5[] = $b(['Equipo', 'Tipo', 'Sin restricción', 'Con restricción']);
    foreach ($u['equipos'] as $e) {
        $hoja5[] = [$e['nombre_equipo'], $e['tipo'] ? 'Tipo ' . $e['tipo'] : '', $e['normal'], $e['restringido']];
    }
} else {
    $hoja5[] = ['(El proyecto no tiene pabellones: no hay URPA)'];
}

/* --- Salida ---------------------------------------------------------- */
$xlsx = new SimpleXLSXGen();
$xlsx->addSheet($hoja1, 'Resumen');
$xlsx->addSheet($hoja2, 'Recintos');
$xlsx->addSheet($hoja3, 'Personal');
$xlsx->addSheet($hoja4, 'Equipamiento');
$xlsx->addSheet($hoja5, 'URPA');

$filename = ephdem_nombre_archivo('Restriccion_RRHH_', $nombre, 'xlsx');

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
if (ob_get_length()) {
    ob_end_clean();
}
$xlsx->download();
exit;