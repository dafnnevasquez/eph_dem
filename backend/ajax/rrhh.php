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
 *
 * Para agregar una accion nueva: sumarla a esta lista y al final de
 * controllers/RRHHController.php.
 */
require_once __DIR__ . '/helpers/Response.php';

$acciones = ['perfiles', 'perfil_agregar', 'obtener', 'guardar'];

$accion = (string)($_GET['action'] ?? '');
if (!in_array($accion, $acciones, true)) {
    Response::error('Acción no válida.', 400);
    exit;
}

require_once __DIR__ . '/controllers/RRHHController.php';