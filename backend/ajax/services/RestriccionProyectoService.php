<?php
declare(strict_types=1);

require_once __DIR__ . '/RestriccionRRHHService.php';

/**
 * Restriccion de RR.HH. aplicada a un proyecto (modelo v4).
 *
 * 1) Por recinto (servicio j) se calcula alpha con RestriccionRRHHService.
 * 2) Modulos (pabellones / boxes):  D* = techo(alpha_recinto * D_tec).
 * 3) Tipo 5:  D* = techo( SUMA alpha_recinto(i) * requerimiento_i ).
 * 4) Equipamiento COMPLETO (tipos 1 a 6): se recompone con la MISMA regla
 *    del agregador de /calculo, pero usando los modulos restringidos:
 *      kit     = cantidad_base * modulos_restringidos[recinto]   (cubre tipo 1)
 *      tipo 2  = modulos_restringidos[recinto]
 *      tipo 6  = cantidad_base * techo(camas_restringidas / divisor)
 *      tipo 5  = cantidad restringida del punto 3
 *    Por recinto: max entre fuentes; entre recintos: suma; la demanda
 *    compartida compite contra el piso total. Los tipos 3 y 4 salen de esa
 *    composicion, igual que en el calculo normal.
 * 5) URPA: camillas y salas con los pabellones restringidos.
 *
 * Con alpha = 1 en todos los recintos el resultado es identico al calculo
 * actual. /calculo NO se modifica: solo se llaman sus funciones y se leen
 * sus constantes.
 *
 * Entorno objetivo: PHP 7.4.
 */
class RestriccionProyectoService
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /* ================================================================
     * Base de datos: resultados del calculo actual
     * ================================================================ */

    /**
     * Ejecuta el calculo actual completo (igual que Resultados) y devuelve:
     *   'modulos'      => recinto_id => ['tipo', 'fraccion', 'cantidad']
     *   'tipo5'        => equipos tipo 5 tal como los entrega /calculo
     *   'equipamiento' => lo necesario para recomponer todos los tipos
     *                     (ver calcular(), parametro $equipamiento)
     */
    public function datosCalculoActual(int $proyectoId): array
    {
        require_once __DIR__ . '/../calculo/calculo_equipamiento.php';

        // Agregador = resultado normal completo (kit, tipo 2, tipo 6, tipo 5).
        // Internamente corre pabellones y boxes y deja requerimiento_calculado al dia.
        $agr = SIGEM_EPHDEM_CERRADA_CalcularEquipamiento($this->conn, $proyectoId);

        // Fracciones por recinto (D_tec de los modulos).
        $pab = SIGEM_EPHDEM_CERRADA_CalcularPabellones($this->conn, $proyectoId);
        $box = SIGEM_EPHDEM_CERRADA_CalcularBoxes($this->conn, $proyectoId);

        $modulos = [];
        foreach ($pab['pabellones_por_recinto'] as $rec => $info) {
            $modulos[(int)$rec] = ['tipo' => 'pabellones', 'fraccion' => (float)$info['fraccion'], 'cantidad' => (int)$info['pabellones']];
        }
        foreach ($box['boxes_por_recinto'] as $rec => $info) {
            $modulos[(int)$rec] = ['tipo' => 'boxes', 'fraccion' => (float)$info['fraccion'], 'cantidad' => (int)$info['boxes']];
        }

        $urpa = SIGEM_EPHDEM_CERRADA_CalcularURPA($this->conn, (int)$pab['pabellones_total']);

        $ids = [];
        foreach ($agr['equipos'] as $e) {
            $ids[(int)$e['equipo_id']] = true;
        }
        foreach ($urpa['equipos'] as $e) {
            $ids[(int)$e['equipo_id']] = true;
        }

        return [
            'modulos'      => $modulos,
            'tipo5'        => $agr['fuentes']['tipo5']['equipos'],
            'equipamiento' => [
                'normal'  => $agr['equipos'],
                'fuentes' => $agr['fuentes'],
                'tipos'   => $this->tiposDemanda(array_keys($ids)),
                'urpa'    => $urpa,
                'config'  => [
                    'mapa_regla_recinto' => EPHDEM_MAPA_REGLA_RECINTO,
                    'divisores_upc'      => [
                        'uci'            => EPHDEM_UPC_UCI_DIV,
                        'uti'            => EPHDEM_UPC_UTI_DIV,
                        'enfermeria_uci' => EPHDEM_UPC_ENFERMERIA_DIV_UCI,
                        'enfermeria_uti' => EPHDEM_UPC_ENFERMERIA_DIV_UTI,
                    ],
                    'urpa_camillas_por_pabellon' => EPHDEM_URPA_CAMILLAS_POR_PABELLON,
                    'urpa_max_camillas'          => EPHDEM_URPA_MAX_CAMILLAS,
                ],
            ],
        ];
    }

    /** tipo_demanda (1 a 6) de cada equipo, en una sola consulta. */
    private function tiposDemanda(array $ids): array
    {
        if (count($ids) === 0) {
            return [];
        }
        $lista = implode(',', array_map('intval', $ids));
        $res = $this->conn->query(
            'SELECT id_equipo, tipo_demanda FROM ' . EPHDEM_TBL_EQUIPOS . ' WHERE id_equipo IN (' . $lista . ')'
        );
        $out = [];
        if ($res !== false) {
            while ($r = $res->fetch_assoc()) {
                $out[(int)$r['id_equipo']] = $r['tipo_demanda'] === null ? null : (int)$r['tipo_demanda'];
            }
        }
        return $out;
    }

    /* ================================================================
     * Logica pura (sin base de datos)
     * ================================================================ */

    /** tau (horas del perfil por prestacion) a partir de una fila de la Planilla A. */
    public static function tauDe(array $fila, $tMin): float
    {
        $tH = ($tMin === null) ? 0.0 : (float)$tMin / 60.0;
        if ($tH <= 0) {
            return 0.0;
        }
        if ((string)$fila['participacion'] === 'permanente') {
            return RestriccionRRHHService::horasPorPrestacionPermanente($tH);
        }
        return RestriccionRRHHService::horasPorPrestacion(
            (float)$fila['e_minutos'] / 60.0,
            (float)$fila['f_veces_dia'],
            $tH
        );
    }

    /**
     * @param array      $prestaciones de PrestacionPerfilService::prestacionesProyecto()
     * @param array      $catalogo     de PrestacionPerfilService::catalogoProyecto() (Planilla A)
     * @param array      $grupos       de RRHHService::obtener() (Planilla B)
     * @param array      $modulos      recinto_id => ['tipo', 'fraccion', 'cantidad'] (calculo actual)
     * @param array      $tipo5        equipos tipo 5 del calculo actual
     * @param array|null $equipamiento datosCalculoActual()['equipamiento']; si viene, se agrega
     *                                 el equipamiento completo y la URPA al resultado
     */
    public static function calcular(array $prestaciones, array $catalogo, array $grupos, array $modulos, array $tipo5, ?array $equipamiento = null): array
    {
        // Planilla A agrupada por prestacion.
        $filasPorPrestacion = [];
        foreach ($catalogo as $c) {
            $filasPorPrestacion[(int)$c['prestacion_id']][] = $c;
        }

        // Planilla B por recinto. jornada_semanal ya es h * d (horas por semana).
        $dotacion = [];
        foreach ($grupos as $g) {
            $dotacion[(int)$g['recinto_id']][(string)$g['tipo_rrhh']] = [
                'n'     => (float)$g['cantidad_personas'],
                'h'     => (float)$g['jornada_semanal'],
                'd'     => 1.0,
                'W'     => (float)$g['semanas_efectivas'],
                'delta' => (float)$g['fraccion_jornada'],
            ];
        }

        // Prestaciones por recinto, con su demanda y el tau de cada perfil.
        $porRecinto        = [];
        $nombreRecinto     = [];
        $recintoPrestacion = [];
        foreach ($prestaciones as $p) {
            $rid = (int)$p['recinto_id'];
            $nombreRecinto[$rid] = (string)$p['recinto'];
            $recintoPrestacion[(int)$p['id']] = $rid;
            $tau = [];
            foreach ($filasPorPrestacion[(int)$p['id']] ?? [] as $f) {
                $tau[(string)$f['tipo_rrhh']] = self::tauDe($f, $p['t_min']);
            }
            $porRecinto[$rid][] = ['dda' => (float)($p['demanda'] ?? 0), 'tau' => $tau];
        }

        $ids = array_unique(array_merge(array_keys($porRecinto), array_keys($modulos)));
        sort($ids);

        $recintos        = [];
        $alphaPorRecinto = [];
        $conteoRestr     = []; // recinto_id => modulos con restriccion
        $totalModNormal  = 0;
        $totalModRestr   = 0;

        foreach ($ids as $rid) {
            $lista = $porRecinto[$rid] ?? [];
            $r = RestriccionRRHHService::resolverServicio($lista, $dotacion[$rid] ?? []);
            $alpha = (float)$r['alpha'];
            $alphaPorRecinto[$rid] = $alpha;

            $conDatos = false;
            foreach ($lista as $x) {
                if (count($x['tau']) > 0) { $conDatos = true; break; }
            }

            $mod = null;
            if (isset($modulos[$rid])) {
                $m = $modulos[$rid];
                $restr = ($alpha >= 1.0)
                    ? (int)$m['cantidad']
                    : RestriccionRRHHService::equiposRestringidos((float)$m['fraccion'], $alpha)['d_entero'];
                $mod = [
                    'tipo'        => $m['tipo'],
                    'dtec'        => (float)$m['fraccion'],
                    'normal'      => (int)$m['cantidad'],
                    'restringido' => $restr,
                ];
                $conteoRestr[$rid] = $restr;
                $totalModNormal += (int)$m['cantidad'];
                $totalModRestr  += $restr;
            }

            $porPerfil = [];
            foreach ($r['por_perfil'] as $perfil => $info) {
                $porPerfil[] = array_merge(['perfil' => (string)$perfil], $info);
            }

            $recintos[] = [
                'id'                        => $rid,
                'nombre'                    => $nombreRecinto[$rid] ?? ('Recinto ' . $rid),
                'con_datos_planilla_a'      => $conDatos,
                'alpha'                     => $alpha,
                'rho'                       => $r['rho'],
                'restriccion_activa'        => $r['restriccion_activa'],
                'perfil_limitante'          => $r['perfil_limitante'] === null ? null : (string)$r['perfil_limitante'],
                'perfiles_sin_datos'        => array_map('strval', $r['perfiles_sin_datos']),
                'demanda_total'             => $r['demanda_total'],
                'prestaciones_no_cubiertas' => $r['prestaciones_no_cubiertas'],
                'por_perfil'                => $porPerfil,
                'modulos'                   => $mod,
            ];
        }

        // Tipo 5: cada prestacion se multiplica por el alpha de SU recinto antes de sumar.
        $equipos  = [];
        $t5Restr  = []; // equipo_id => cantidad con restriccion
        $totT5Nor = 0;
        $totT5Res = 0;
        foreach ($tipo5 as $e) {
            $normal   = (int)$e['cantidad'];
            $suma     = 0.0;
            $limitado = false;
            foreach ($e['por_prestacion'] as $pp) {
                $rid   = $recintoPrestacion[(int)$pp['prestacion_id']] ?? null;
                $alpha = ($rid !== null && isset($alphaPorRecinto[$rid])) ? $alphaPorRecinto[$rid] : 1.0;
                if ($alpha < 1.0) $limitado = true;
                $suma += $alpha * (float)$pp['requerimiento_fraccion'];
            }
            $restr = $limitado ? RestriccionRRHHService::techo($suma) : $normal;
            $t5Restr[(int)$e['equipo_id']] = $restr;
            $equipos[] = [
                'equipo_id'     => (int)$e['equipo_id'],
                'nombre_equipo' => $e['nombre_equipo'],
                'normal'        => $normal,
                'restringido'   => $restr,
            ];
            $totT5Nor += $normal;
            $totT5Res += $restr;
        }
        usort($equipos, function ($a, $b) { return $a['equipo_id'] <=> $b['equipo_id']; });

        $salida = [
            'recintos' => $recintos,
            'tipo5'    => $equipos,
            'totales'  => [
                'modulos_normal'      => $totalModNormal,
                'modulos_restringido' => $totalModRestr,
                'tipo5_normal'        => $totT5Nor,
                'tipo5_restringido'   => $totT5Res,
            ],
        ];

        if ($equipamiento !== null) {
            $completo = self::equipamientoRestringido($equipamiento, $modulos, $conteoRestr, $t5Restr, $recintoPrestacion);
            $salida['equipamiento'] = $completo['equipos'];
            $salida['urpa']         = $completo['urpa'];
            $salida['totales']      = array_merge($salida['totales'], $completo['totales']);
        }

        return $salida;
    }

    /**
     * Equipamiento completo (todos los tipos) y URPA con los modulos restringidos.
     *
     * @param array $equipamiento      datosCalculoActual()['equipamiento']
     * @param array $modulos           recinto_id => ['tipo', 'fraccion', 'cantidad']
     * @param array $conteoRestr       recinto_id => modulos con restriccion
     * @param array $t5Restr           equipo_id => cantidad tipo 5 con restriccion
     * @param array $recintoPrestacion prestacion_id => recinto_id
     */
    public static function equipamientoRestringido(array $equipamiento, array $modulos, array $conteoRestr, array $t5Restr, array $recintoPrestacion): array
    {
        $config = $equipamiento['config'];
        $tipos  = $equipamiento['tipos'] ?? [];

        $comp = self::componerEquipamiento($equipamiento['fuentes'], $conteoRestr, $t5Restr, $recintoPrestacion, $config);

        $lista = [];
        $totN  = 0;
        $totR  = 0;
        foreach ($equipamiento['normal'] as $e) {
            $id     = (int)$e['equipo_id'];
            $normal = (int)$e['cantidad'];
            $restr  = isset($comp[$id]) ? (int)$comp[$id]['cantidad'] : $normal;
            // Equipos que el proyecto no usa (kit de recintos con 0 modulos): igual que Resultados, no se muestran.
            if ($normal === 0 && $restr === 0) {
                continue;
            }
            $lista[] = [
                'equipo_id'     => $id,
                'nombre_equipo' => $e['nombre_equipo'],
                'tipo'          => $tipos[$id] ?? null,
                'normal'        => $normal,
                'restringido'   => $restr,
            ];
            $totN += $normal;
            $totR += $restr;
        }
        usort($lista, function ($a, $b) { return $a['equipo_id'] <=> $b['equipo_id']; });

        // URPA: depende del total de pabellones.
        $pabRestr = 0;
        foreach ($modulos as $rid => $m) {
            if ($m['tipo'] === 'pabellones') {
                $pabRestr += (int)($conteoRestr[$rid] ?? $m['cantidad']);
            }
        }
        $urpa = self::urpaRestringida($equipamiento['urpa'], $pabRestr, $config, $tipos);

        $urpaN = 0;
        $urpaR = 0;
        foreach ($urpa['equipos'] as $e) {
            $urpaN += $e['normal'];
            $urpaR += $e['restringido'];
        }

        return [
            'equipos' => $lista,
            'urpa'    => $urpa,
            'totales' => [
                'equipos_normal'      => $totN,
                'equipos_restringido' => $totR,
                'urpa_normal'         => $urpaN,
                'urpa_restringido'    => $urpaR,
            ],
        ];
    }

    /**
     * Misma composicion que SIGEM_EPHDEM_CERRADA_CalcularEquipamiento (agregador),
     * pero con el conteo de modulos y las cantidades tipo 5 que se le pasen.
     *
     * @param array $fuentes           agregador()['fuentes']: kit, tipo2, tipo5, tipo6
     * @param array $conteo            recinto_id => modulos (boxes o pabellones)
     * @param array $cantidadT5        equipo_id => cantidad tipo 5 (si falta, se usa la de la fuente)
     * @param array $recintoPrestacion prestacion_id => recinto_id
     * @param array $config            ['mapa_regla_recinto' => [...], 'divisores_upc' => [...]]
     * @return array equipo_id => ['nombre', 'cantidad', 'piso', 'demanda']
     */
    public static function componerEquipamiento(array $fuentes, array $conteo, array $cantidadT5, array $recintoPrestacion, array $config): array
    {
        $mapa = $config['mapa_regla_recinto'];
        $div  = $config['divisores_upc'];
        $eq   = [];

        $asegurar = function (int $id, $nombre) use (&$eq) {
            if (!isset($eq[$id])) {
                $eq[$id] = ['nombre' => $nombre, 'buckets' => [], 'demanda' => 0];
            } elseif ($eq[$id]['nombre'] === null && $nombre !== null) {
                $eq[$id]['nombre'] = $nombre;
            }
        };
        // Dentro de un recinto, varias fuentes son estimaciones del mismo piso: gana la mayor.
        $piso = function (int $id, $nombre, string $bucket, int $cant) use (&$eq, $asegurar) {
            $asegurar($id, $nombre);
            if (!isset($eq[$id]['buckets'][$bucket]) || $cant > $eq[$id]['buckets'][$bucket]) {
                $eq[$id]['buckets'][$bucket] = $cant;
            }
        };

        // KIT (cubre tipo 1): cantidad_base * modulos del recinto.
        foreach ($fuentes['kit']['equipos'] ?? [] as $item) {
            $id = (int)$item['equipo_id'];
            $nombre = $item['nombre_equipo'] ?? null;
            $asegurar($id, $nombre);
            foreach ($item['por_recinto'] as $pr) {
                $rec = (int)$pr['recinto_id'];
                $piso($id, $nombre, 'recinto_' . $rec, (int)$pr['cantidad_base'] * (int)($conteo[$rec] ?? 0));
            }
        }

        // TIPO 2: 1 por modulo.
        foreach ($fuentes['tipo2']['equipos'] ?? [] as $item) {
            $id = (int)$item['equipo_id'];
            $nombre = $item['nombre_equipo'] ?? null;
            $asegurar($id, $nombre);
            foreach ($item['por_recinto'] as $pr) {
                $rec = (int)$pr['recinto_id'];
                $piso($id, $nombre, 'recinto_' . $rec, (int)($conteo[$rec] ?? 0));
            }
        }

        // TIPO 6 (norma UPC): cantidad_base * techo(camas / divisor). Reglas del mismo recinto se suman.
        foreach ($fuentes['tipo6']['equipos'] ?? [] as $item) {
            $id = (int)$item['equipo_id'];
            $nombre = $item['nombre_equipo'] ?? null;
            $asegurar($id, $nombre);
            $tmp = [];
            foreach ($item['por_regla'] as $pr) {
                $regla = (string)$pr['regla'];
                if (!isset($mapa[$regla]) || !isset($div[$regla]) || (int)$div[$regla] <= 0) {
                    continue;
                }
                $rec   = (int)$mapa[$regla];
                $camas = (int)($conteo[$rec] ?? 0);
                $dot   = $camas > 0 ? (int)ceil($camas / (int)$div[$regla]) : 0;
                $bucket = 'recinto_' . $rec;
                $tmp[$bucket] = ($tmp[$bucket] ?? 0) + (int)$pr['cantidad_base'] * $dot;
            }
            foreach ($tmp as $bucket => $cant) {
                $piso($id, $nombre, $bucket, $cant);
            }
        }

        // TIPO 5: si toda su demanda viene de un solo recinto, compite en ese recinto;
        // si es compartida, compite al final contra el piso total.
        foreach ($fuentes['tipo5']['equipos'] ?? [] as $item) {
            $id = (int)$item['equipo_id'];
            $nombre = $item['nombre_equipo'] ?? null;
            $cant = isset($cantidadT5[$id]) ? (int)$cantidadT5[$id] : (int)$item['cantidad'];
            $asegurar($id, $nombre);

            $set = [];
            foreach ($item['por_prestacion'] as $pp) {
                $rec = $recintoPrestacion[(int)$pp['prestacion_id']] ?? null;
                if ($rec !== null) {
                    $set[(int)$rec] = true;
                }
            }
            $recs = array_keys($set);
            if (count($recs) === 1) {
                $piso($id, $nombre, 'recinto_' . $recs[0], $cant);
            } else {
                $eq[$id]['demanda'] = $cant;
            }
        }

        // Entre recintos se suma; luego max(piso, demanda compartida).
        $out = [];
        foreach ($eq as $id => $info) {
            $p = array_sum($info['buckets']);
            $out[$id] = [
                'nombre'   => $info['nombre'],
                'cantidad' => max($p, $info['demanda']),
                'piso'     => $p,
                'demanda'  => $info['demanda'],
            ];
        }
        return $out;
    }

    /**
     * URPA con los pabellones restringidos (misma aritmetica que CalcularURPA):
     *   camillas = camillas_por_pabellon * pabellones
     *   salas    = techo(camillas / max_camillas)
     *   equipos de sala x salas, equipos de camilla x camillas.
     */
    public static function urpaRestringida(array $urpa, int $pabellones, array $config, array $tipos = []): array
    {
        $pabellones = max(0, $pabellones);
        $camillas = (int)$config['urpa_camillas_por_pabellon'] * $pabellones;
        $max      = (int)$config['urpa_max_camillas'];
        $salas    = ($camillas > 0 && $max > 0) ? (int)ceil($camillas / $max) : 0;

        $acum = [];
        $sumar = function (array $e, int $restr) use (&$acum, $tipos) {
            $id = (int)$e['equipo_id'];
            if (!isset($acum[$id])) {
                $acum[$id] = [
                    'equipo_id'     => $id,
                    'nombre_equipo' => $e['nombre_equipo'] ?? null,
                    'tipo'          => $tipos[$id] ?? null,
                    'normal'        => 0,
                    'restringido'   => 0,
                ];
            }
            $acum[$id]['normal']      += (int)$e['cantidad'];
            $acum[$id]['restringido'] += $restr;
        };
        foreach ($urpa['equipos_sala'] ?? [] as $e) {
            $sumar($e, $salas * (int)$e['cantidad_base']);
        }
        foreach ($urpa['equipos_camilla'] ?? [] as $e) {
            $sumar($e, $camillas * (int)$e['cantidad_base']);
        }
        $equipos = array_values($acum);
        usort($equipos, function ($a, $b) { return $a['equipo_id'] <=> $b['equipo_id']; });

        return [
            'normal' => [
                'pabellones' => (int)($urpa['nro_pabellones'] ?? 0),
                'camillas'   => (int)($urpa['nro_camillas'] ?? 0),
                'salas'      => (int)($urpa['nro_salas'] ?? 0),
            ],
            'restringido' => [
                'pabellones' => $pabellones,
                'camillas'   => $camillas,
                'salas'      => $salas,
            ],
            'equipos' => $equipos,
        ];
    }
}