<?php
declare(strict_types=1);

require_once __DIR__ . '/RestriccionRRHHService.php';

/**
 * Planilla A del modelo v4: como participa cada perfil en cada prestacion.
 * Se guarda en el catalogo compartido EPHAC_Prestacion_Perfil (lo que llena
 * un proyecto queda disponible para todos los demas).
 *
 *   permanente: el perfil esta todo el tiempo de la prestacion (tau = t).
 *   puntual:    interviene f veces al dia, e minutos cada vez (tau = e * f * dias).
 */
class PrestacionPerfilService
{
    const TBL = 'EPHAC_Prestacion_Perfil';

    const PARTICIPACIONES = ['permanente', 'puntual'];

    /** Limites de captura: una intervencion no dura mas de un dia, ni ocurre mas de 1440 veces al dia. */
    const MAX_MINUTOS = 1440.0;
    const MAX_VECES   = 1440.0;

    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /* ================================================================
     * Logica pura (sin base de datos)
     * ================================================================ */

    /**
     * Valida las filas de la Planilla A antes de guardarlas.
     *
     * @param array $filas        [['prestacion_id', 'tipo_rrhh', 'participacion', 'e_minutos', 'f_veces_dia'], ...]
     * @param array $prestaciones id_prestacion => ['nombre' => ..., 't_min' => minutos|null]
     *                            (solo las del proyecto)
     * @return string|null        null si todo esta bien, o el mensaje de error (para un 400)
     */
    public static function validarFilas(array $filas, array $prestaciones): ?string
    {
        $vistos = [];
        foreach ($filas as $f) {
            if (!is_array($f)) {
                return 'Datos inválidos.';
            }
            $id   = (int)($f['prestacion_id'] ?? 0);
            $tipo = trim((string)($f['tipo_rrhh'] ?? ''));
            $part = (string)($f['participacion'] ?? '');

            if (!isset($prestaciones[$id])) {
                return 'Una de las prestaciones no pertenece a este proyecto.';
            }
            $nombre = (string)$prestaciones[$id]['nombre'];

            if ($tipo === '' || !in_array($part, self::PARTICIPACIONES, true)) {
                return 'Datos inválidos en «' . $nombre . '».';
            }

            $clave = $id . '|' . $tipo;
            if (isset($vistos[$clave])) {
                return 'El perfil «' . $tipo . '» está repetido en «' . $nombre . '».';
            }
            $vistos[$clave] = true;

            if ($part === 'permanente') {
                continue;
            }

            // Puntual: e y f obligatorios y la dedicacion debe quedar en (0, 1].
            $e = $f['e_minutos'] ?? null;
            $v = $f['f_veces_dia'] ?? null;
            if (!is_numeric($e) || (float)$e <= 0 || (float)$e > self::MAX_MINUTOS) {
                return 'En «' . $nombre . '», «' . $tipo . '»: los minutos por intervención deben ser mayores que 0 y como máximo 1440.';
            }
            if (!is_numeric($v) || (float)$v <= 0 || (float)$v > self::MAX_VECES) {
                return 'En «' . $nombre . '», «' . $tipo . '»: las veces al día deben ser mayores que 0.';
            }
            $tMin = $prestaciones[$id]['t_min'];
            if ($tMin === null || (float)$tMin <= 0) {
                return '«' . $nombre . '» no tiene tiempo de procedimiento; solo admite participación permanente.';
            }
            $phi = self::dedicacion((float)$e, (float)$v, (float)$tMin);
            if (!RestriccionRRHHService::dedicacionValida($phi)) {
                return 'En «' . $nombre . '», «' . $tipo . '»: la dedicación queda en '
                    . number_format($phi, 2, ',', '') . ' y no puede ser mayor que 1. Revisa los minutos y las veces al día.';
            }
        }
        return null;
    }

    /** phi para una intervencion puntual, con e en minutos y t en minutos (R3a a R3c). */
    public static function dedicacion(float $eMinutos, float $fVecesDia, float $tMinutos): float
    {
        $tHoras = $tMinutos / 60.0;
        $tau = RestriccionRRHHService::horasPorPrestacion($eMinutos / 60.0, $fVecesDia, $tHoras);
        return RestriccionRRHHService::dedicacion($tau, $tHoras);
    }

    /**
     * Arma la respuesta de la pantalla: recintos con sus prestaciones, los
     * perfiles que el proyecto declaro en la Planilla B y lo que dice el catalogo.
     *
     * @param array $prestaciones de prestacionesProyecto()
     * @param array $catalogo     de catalogoProyecto()
     * @param array $grupos       de RRHHService::obtener() (Planilla B)
     */
    public static function armarPlanilla(array $prestaciones, array $catalogo, array $grupos): array
    {
        $porPrestacion = [];
        foreach ($catalogo as $c) {
            $porPrestacion[(int)$c['prestacion_id']][] = [
                'tipo_rrhh'     => $c['tipo_rrhh'],
                'participacion' => $c['participacion'],
                'e_minutos'     => $c['e_minutos'] === null ? null : (float)$c['e_minutos'],
                'f_veces_dia'   => $c['f_veces_dia'] === null ? null : (float)$c['f_veces_dia'],
            ];
        }

        $perfilesRecinto = [];
        foreach ($grupos as $g) {
            $perfilesRecinto[(int)$g['recinto_id']][] = $g['tipo_rrhh'];
        }

        $recintos = [];
        foreach ($prestaciones as $p) {
            $rid = (int)$p['recinto_id'];
            if (!isset($recintos[$rid])) {
                $recintos[$rid] = [
                    'id'            => $rid,
                    'nombre'        => $p['recinto'],
                    'perfiles_rrhh' => $perfilesRecinto[$rid] ?? [],
                    'prestaciones'  => [],
                ];
            }
            $recintos[$rid]['prestaciones'][] = [
                'id'       => (int)$p['id'],
                'codigo'   => $p['codigo'],
                'nombre'   => $p['nombre'],
                't_min'    => $p['t_min'],
                'demanda'  => $p['demanda'],
                'perfiles' => $porPrestacion[(int)$p['id']] ?? [],
            ];
        }
        return array_values($recintos);
    }

    /* ================================================================
     * Base de datos
     * ================================================================ */

    /** Prestaciones del proyecto con su recinto, tiempo (minutos) y demanda anual. */
    public function prestacionesProyecto(int $proyectoId): array
    {
        $st = $this->conn->prepare(
            'SELECT p.id_prestacion, p.codigo_fonasa, p.nombre_prestacion, p.recinto_base_id,
                    r.nombre_recinto, p.tiempo_procedimiento, d.demanda_anual
             FROM EPHAC_Proyecto_Demanda d
             JOIN EPHAC_Prestaciones p ON p.id_prestacion = d.prestacion_id
             JOIN EPHAC_Recinto_Estandar r ON r.id_recinto = p.recinto_base_id
             WHERE d.proyecto_id = ?
             ORDER BY p.recinto_base_id, p.nombre_prestacion'
        );
        if (!$st) throw new RuntimeException('prepare prestaciones proyecto');
        $st->bind_param('i', $proyectoId);
        $st->execute();
        $res = $st->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[] = [
                'id'         => (int)$r['id_prestacion'],
                'codigo'     => $r['codigo_fonasa'],
                'nombre'     => $r['nombre_prestacion'],
                'recinto_id' => (int)$r['recinto_base_id'],
                'recinto'    => $r['nombre_recinto'],
                't_min'      => $r['tiempo_procedimiento'] === null ? null : (float)$r['tiempo_procedimiento'],
                'demanda'    => $r['demanda_anual'] === null ? null : (int)$r['demanda_anual'],
            ];
        }
        $st->close();
        return $out;
    }

    /** Filas del catalogo para las prestaciones del proyecto. */
    public function catalogoProyecto(int $proyectoId): array
    {
        $st = $this->conn->prepare(
            'SELECT pp.prestacion_id, pp.tipo_rrhh, pp.participacion, pp.e_minutos, pp.f_veces_dia
             FROM ' . self::TBL . ' pp
             JOIN EPHAC_Proyecto_Demanda d ON d.prestacion_id = pp.prestacion_id
             WHERE d.proyecto_id = ?
             ORDER BY pp.prestacion_id, pp.id_prestacion_perfil'
        );
        if (!$st) throw new RuntimeException('prepare catalogo proyecto');
        $st->bind_param('i', $proyectoId);
        $st->execute();
        $res = $st->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[] = $r;
        }
        $st->close();
        return $out;
    }

    /**
     * Reemplaza en el catalogo las filas de las prestaciones del proyecto por
     * las que llegan (ya validadas). No toca prestaciones de otros proyectos.
     * Devuelve cuantas filas se guardaron.
     */
    public function guardar(int $proyectoId, array $filas): int
    {
        $this->conn->begin_transaction();
        try {
            $del = $this->conn->prepare(
                'DELETE FROM ' . self::TBL . ' WHERE prestacion_id IN
                   (SELECT prestacion_id FROM EPHAC_Proyecto_Demanda WHERE proyecto_id = ?)'
            );
            if (!$del) throw new RuntimeException('prepare delete planilla a');
            $del->bind_param('i', $proyectoId);
            if (!$del->execute()) throw new RuntimeException('execute delete planilla a');
            $del->close();

            $ins = $this->conn->prepare(
                'INSERT INTO ' . self::TBL . ' (prestacion_id, tipo_rrhh, participacion, e_minutos, f_veces_dia)
                 VALUES (?, ?, ?, ?, ?)'
            );
            if (!$ins) throw new RuntimeException('prepare insert planilla a');

            $n = 0;
            foreach ($filas as $f) {
                $id   = (int)$f['prestacion_id'];
                $tipo = trim((string)$f['tipo_rrhh']);
                $part = (string)$f['participacion'];
                $e    = null;
                $v    = null;
                if ($part === 'puntual') {
                    $e = (float)$f['e_minutos'];
                    $v = (float)$f['f_veces_dia'];
                }
                $ins->bind_param('issdd', $id, $tipo, $part, $e, $v);
                if (!$ins->execute()) throw new RuntimeException('execute insert planilla a');
                $n++;
            }
            $ins->close();
            $this->conn->commit();
            return $n;
        } catch (\Throwable $ex) {
            $this->conn->rollback();
            throw $ex;
        }
    }
}