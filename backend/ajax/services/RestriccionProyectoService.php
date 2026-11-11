<?php
declare(strict_types=1);

require_once __DIR__ . '/RestriccionRRHHService.php';

/**
 * Restriccion de RR.HH. aplicada a un proyecto (modelo v4, opcion B).
 *
 * Toma del calculo actual (/calculo) lo que depende de la demanda:
 *   - pabellones y boxes por recinto (D_tec = suma de requerimiento_calculado)
 *   - equipamiento tipo 5 por equipo
 * y les aplica el factor alpha de cada recinto:
 *   modulos:  D* = techo(alpha_recinto * D_tec)
 *   tipo 5:   D* = techo( SUMA alpha_recinto(i) * requerimiento_i )
 *
 * Kit, tipo 2, tipo 6 (norma UPC) y URPA NO se recalculan (opcion B):
 * quedan como estan en el calculo actual. Pendiente de confirmar con la
 * profesora si deben arrastrarse con los conteos restringidos.
 *
 * Con alpha = 1 se devuelve exactamente el numero del calculo actual.
 * /calculo no se modifica: solo se llaman sus funciones, igual que lo
 * hace la pantalla de Resultados.
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
     * Ejecuta pabellones, boxes y tipo 5 de /calculo (igual que Resultados;
     * de paso deja requerimiento_calculado al dia) y devuelve:
     *   'modulos' => recinto_id => ['tipo', 'fraccion', 'cantidad']
     *   'tipo5'   => lista de equipos tipo 5 tal como los entrega /calculo
     */
    public function datosCalculoActual(int $proyectoId): array
    {
        require_once __DIR__ . '/../calculo/calculo_equipamiento.php';

        $pab = SIGEM_EPHDEM_CERRADA_CalcularPabellones($this->conn, $proyectoId);
        $box = SIGEM_EPHDEM_CERRADA_CalcularBoxes($this->conn, $proyectoId);
        $t5  = SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo5($this->conn, $proyectoId);

        $modulos = [];
        foreach ($pab['pabellones_por_recinto'] as $rec => $info) {
            $modulos[(int)$rec] = ['tipo' => 'pabellones', 'fraccion' => (float)$info['fraccion'], 'cantidad' => (int)$info['pabellones']];
        }
        foreach ($box['boxes_por_recinto'] as $rec => $info) {
            $modulos[(int)$rec] = ['tipo' => 'boxes', 'fraccion' => (float)$info['fraccion'], 'cantidad' => (int)$info['boxes']];
        }
        return ['modulos' => $modulos, 'tipo5' => $t5['equipos']];
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
     * @param array $prestaciones de PrestacionPerfilService::prestacionesProyecto()
     * @param array $catalogo     de PrestacionPerfilService::catalogoProyecto() (Planilla A)
     * @param array $grupos       de RRHHService::obtener() (Planilla B)
     * @param array $modulos      recinto_id => ['tipo', 'fraccion', 'cantidad'] (calculo actual)
     * @param array $tipo5        equipos tipo 5 del calculo actual
     */
    public static function calcular(array $prestaciones, array $catalogo, array $grupos, array $modulos, array $tipo5): array
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
        $porRecinto       = [];
        $nombreRecinto    = [];
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

        return [
            'recintos' => $recintos,
            'tipo5'    => $equipos,
            'totales'  => [
                'modulos_normal'      => $totalModNormal,
                'modulos_restringido' => $totalModRestr,
                'tipo5_normal'        => $totT5Nor,
                'tipo5_restringido'   => $totT5Res,
            ],
        ];
    }
}