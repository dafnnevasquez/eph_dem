<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * ajax/generar_xls_cerrada.php
 *
 * Endpoint: GET
 * Params:
 *   proyecto_id : int, obligatorio, > 0
 *   nombre      : string, opcional (cosmetico, nombre de archivo/encabezado)
 *
 * Recalcula el equipamiento desde proyecto_id (la demanda ya esta
 * persistida en BD). No recibe filas ni llama a GuardarDemanda.
 *
 * Genera un .xlsx con SimpleXLSXGen y fuerza la descarga:
 *   Hoja 1 "Habilitación"    : formato estandar hospitalario (56 columnas)
 *   Hoja 2 "Compartidos"     : demanda_compartida
 *   Hoja 3 "Resumen equipos" : equipamiento['equipos']
 *
 * Errores tempranos: texto plano (NO JSON) + exit.
 * =====================================================================
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

use Shuchkin\SimpleXLSXGen;

/**
 * Responde con texto plano y termina.
 */
function ephdem_xls_error(int $http, string $mensaje): void
{
    http_response_code($http);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

/* --- Solo GET -------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    ephdem_xls_error(405, 'Metodo no permitido. Usar GET.');
}

/* --- Parametros -------------------------------------------------------- */
$proyecto_id = isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0;
$nombre      = isset($_GET['nombre']) && trim((string)$_GET['nombre']) !== ''
    ? (string)$_GET['nombre']
    : 'Proyecto';

if ($proyecto_id <= 0) {
    ephdem_xls_error(400, 'proyecto_id es obligatorio y debe ser > 0.');
}

/* --- Conexion ---
 * Servidor: SIGEM ya inyecta su propio $conn.
 * Local: si existe config/database.local.php (gitignored) lo usa para
 * definir $conn; si no existe, cae al conector propio de SIGEM-UV
 * (funciones_sigemuv_C_BaseDatos.php / SIGEM_UV_C_Nueva_Conexion). */
if (!isset($conn) || !($conn instanceof \mysqli)) {
    $localConfig = __DIR__ . '/../config/database.local.php';
    if (file_exists($localConfig)) {
        require_once $localConfig;
    } else {
        require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';
        $conn = SIGEM_UV_C_Nueva_Conexion();
    }
}

mysqli_set_charset($conn, 'utf8mb4');

/* --- Carga de funciones del modulo y libreria XLSX --------------------- */
require_once __DIR__ . '/calculo_equipamiento.php';
require_once __DIR__ . '/../lib/simplexlsxgen/SimpleXLSXGen.php';

/* --- Recalculo ----------------------------------------------------- */
try {
    $pab    = SIGEM_EPHDEM_CERRADA_CalcularPabellones($conn, $proyecto_id);
    $box    = SIGEM_EPHDEM_CERRADA_CalcularBoxes($conn, $proyecto_id);
    $equip  = SIGEM_EPHDEM_CERRADA_CalcularEquipamiento($conn, $proyecto_id);
    $vistas = SIGEM_EPHDEM_CERRADA_CalcularEquipamientoPorRecinto($conn, $equip);
} catch (\Throwable $e) {
    mysqli_close($conn);
    ephdem_xls_error(500, 'Error en el calculo: ' . $e->getMessage());
}

mysqli_close($conn);

/**
 * Fila en blanco de 56 columnas (formato "Habilitación").
 */
function filaBlanca(): array
{
    return array_fill(0, 56, '');
}

/* --- Hoja 1: Habilitación ------------------------------------------ */
$HEADERS = [
    'Nª 5 agosto 2020', 'Nº', 'Subdirección', 'Centro de Responsabilidad', 'Área',
    'Unidad', 'SubUnidad', 'RECINTO', 'Número de Personas', 'Cantidad Recintos', 'm²',
    'm² Totales', 'Clase', 'Subclase', 'Def. ley de presupuesto',
    'Equipo/ Equipamiento/ Instrumental', 'OBSERVACIONES ', 'OBSERVACIONES MINSAL',
    'RESPUESTA SS', 'OBSERVACIONES MINSAL 29-07-2020', 'RESPUESTA SS 29-07-2020',
    'Observaciones 3 Agosto', 'recinto con observaciones',
    'E&E con requerimiento de obra (sí/no)', 'REQUERIMIENTO TOTAL', 'Existencia',
    'Recuperable', 'Vida útil residual estandarizada', 'Reposición Equipo año 0',
    'Equipos Nuevos', 'Vida útil ', 'P.Unit. Equipo cofin M$', 'P.Unit. Equipo US$',
    'Cant. Req. equipos total AÑO 0', ' M$  Repos. Equipos año 0',
    'US$ Repos. equipos año 0', ' M$ Equipos Nuevos', 'US$ Equipos Nuevos ',
    'M$ Costo Reposición periodo 15 años', 'US$ Costo Reposición periodo 15 años ',
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15',
];

$hoja1 = [];

$fila1 = filaBlanca();
$fila1[2] = 'SERVICIO DE SALUD';
$fila1[3] = '';
$hoja1[] = $fila1;

$fila2 = filaBlanca();
$fila2[2] = 'PROYECTO';
$fila2[3] = $nombre;
$hoja1[] = $fila2;

$hoja1[] = filaBlanca();
$hoja1[] = filaBlanca();

$fila5 = filaBlanca();
$fila5[1] = 'DÓLAR';
$hoja1[] = $fila5;

$hoja1[] = array_map(static function ($h) {
    return '<b>' . $h . '</b>';
}, $HEADERS);

$n = 1;
foreach ($vistas['por_recinto'] as $rid => $rec) {
    foreach ($rec['equipos'] as $e) {
        $fila = filaBlanca();
        $fila[1]  = $n++;
        $fila[7]  = $rec['nombre_recinto'];
        $fila[15] = $e['nombre_equipo'];
        $fila[24] = $e['cantidad'];
        $hoja1[] = $fila;
    }
    foreach ($rec['estacion_enfermeria'] as $e) {
        $fila = filaBlanca();
        $fila[1]  = $n++;
        $fila[7]  = $rec['nombre_recinto'];
        $fila[15] = $e['nombre_equipo'];
        $fila[24] = $e['cantidad'];
        $fila[16] = 'Estación de Enfermería';
        $hoja1[] = $fila;
    }
}

if ($n === 1) {
    $fila = filaBlanca();
    $fila[15] = '(Sin equipamiento calculado)';
    $hoja1[] = $fila;
}

/* --- Hoja 2: Compartidos -------------------------------------------- */
$hoja2 = [];
$hoja2[] = array_map(static function ($h) {
    return '<b>' . $h . '</b>';
}, ['Nº', 'Equipo/ Equipamiento/ Instrumental', 'Cantidad (Total)', 'Fracción total', 'Recintos involucrados']);

if (count($vistas['demanda_compartida']) > 0) {
    $n2 = 1;
    foreach ($vistas['demanda_compartida'] as $d) {
        $recintos = implode(', ', array_map(static function ($r) {
            return $r['nombre_recinto'];
        }, $d['recintos_involucrados']));
        $hoja2[] = [$n2++, $d['nombre_equipo'], $d['cantidad'], $d['fraccion_total'], $recintos];
    }
} else {
    $hoja2[] = ['(Sin equipamiento compartido)', '', '', '', ''];
}

/* --- Hoja 3: Resumen equipos ---------------------------------------- */
$hoja3 = [];
$hoja3[] = array_map(static function ($h) {
    return '<b>' . $h . '</b>';
}, ['Equipo', 'Cantidad final', 'Cantidad piso (kit)', 'Cantidad demanda']);

$equipos = $equip['equipos'];
usort($equipos, static function ($a, $b) {
    return $b['cantidad'] <=> $a['cantidad'];
});
foreach ($equipos as $e) {
    $hoja3[] = [$e['nombre_equipo'], $e['cantidad'], $e['cantidad_piso'], $e['cantidad_demanda']];
}

/* --- Salida ---------------------------------------------------------- */
$xlsx = new SimpleXLSXGen();
$xlsx->addSheet($hoja1, 'Habilitación');
$xlsx->addSheet($hoja2, 'Compartidos');
$xlsx->addSheet($hoja3, 'Resumen equipos');

$filename = 'Reporte_EPH_Cerrada_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre) . '_' . date('YmdHis') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
if (ob_get_length()) {
    ob_end_clean();
}
$xlsx->download();
exit;
