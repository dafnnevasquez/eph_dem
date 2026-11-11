<?php
declare(strict_types=1);

/*
 * Punto de entrada unico de RRHH.
 * La pagina indica la accion en la URL:  rrhh.php?action=...
 *
 *   perfiles        GET   lista de perfiles del catalogo
 *   perfil_agregar  POST  agrega un perfil nuevo al catalogo
 *   obtener         GET   dotacion de RRHH de un proyecto
 *   guardar         POST  guarda la dotacion de RRHH de un proyecto
 *   planilla_a          GET   prestaciones del proyecto con la participacion de cada perfil
 *   planilla_a_guardar  POST  guarda la Planilla A (catalogo compartido)
 *   calcular_restriccion GET  resultado con restriccion de RRHH (pabellones, boxes y tipo 5)
 *
 * Para agregar una accion nueva: sumarla a esta lista y al final de
 * controllers/RRHHController.php.
 */
require_once __DIR__ . '/helpers/Response.php';

$acciones = ['perfiles', 'perfil_agregar', 'obtener', 'guardar', 'planilla_a', 'planilla_a_guardar', 'calcular_restriccion'];

$accion = (string)($_GET['action'] ?? '');
if (!in_array($accion, $acciones, true)) {
    Response::error('Acción no válida.', 400);
    exit;
}

require_once __DIR__ . '/controllers/RRHHController.php';