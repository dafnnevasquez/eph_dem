<?php
declare(strict_types=1);

class RRHHService
{
    const TBL = 'EPHAC_Proyecto_RRHH';

    /** Jornadas semanales permitidas (horas por semana). */
    const JORNADAS = [11, 22, 33, 44];

    /** Valores por defecto del modelo de restriccion de RR.HH. (v4). */
    const SEMANAS_DEFECTO  = 48.0; // W: semanas efectivas al anio
    const FRACCION_DEFECTO = 1.0;  // delta: fraccion de la jornada asignada al equipo

    const TBL_PERFIL = 'EPHAC_Perfil_RRHH';

    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Valida la lista de grupos. No toca la base de datos.
     * Devuelve null si todo esta bien, o el mensaje de error (para un 400).
     * Los campos semanas_efectivas y fraccion_jornada son opcionales: si no
     * llegan se usan los valores por defecto.
     */
    public static function validarGrupos(array $grupos): ?string
    {
        $vistos = [];
        foreach ($grupos as $g) {
            if (!is_array($g)) {
                return 'Datos inválidos.';
            }
            $rec  = (int)($g['recinto_id'] ?? 0);
            $per  = $g['cantidad_personas'] ?? null;
            $jor  = (int)($g['jornada_semanal'] ?? 0);
            $tipo = trim((string)($g['tipo_rrhh'] ?? ''));
            $sem  = $g['semanas_efectivas'] ?? self::SEMANAS_DEFECTO;
            $fra  = $g['fraccion_jornada'] ?? self::FRACCION_DEFECTO;

            if ($rec < 1 || $rec > 4
                || !is_numeric($per) || (int)$per < 0
                || !in_array($jor, self::JORNADAS, true)
                || $tipo === '') {
                return 'Datos inválidos.';
            }
            if (!is_numeric($sem) || (float)$sem <= 0 || (float)$sem > 52) {
                return 'Las semanas efectivas deben ser mayores que 0 y como máximo 52.';
            }
            if (!is_numeric($fra) || (float)$fra <= 0 || (float)$fra > 1) {
                return 'La fracción de jornada debe ser mayor que 0 y como máximo 1.';
            }

            $clave = $rec . '|' . $tipo;
            if (isset($vistos[$clave])) {
                return 'No puede haber dos grupos del mismo perfil en el mismo recinto.';
            }
            $vistos[$clave] = true;
        }
        return null;
    }

    /** Reemplaza todo el RRHH del proyecto. Devuelve filas guardadas. */
    public function guardar(int $proyectoId, array $grupos): int
    {
        $this->conn->begin_transaction();
        try {
            $del = $this->conn->prepare('DELETE FROM ' . self::TBL . ' WHERE proyecto_id = ?');
            if (!$del) throw new RuntimeException('prepare delete');
            $del->bind_param('i', $proyectoId);
            if (!$del->execute()) throw new RuntimeException('execute delete');
            $del->close();

            $ins = $this->conn->prepare(
                'INSERT INTO ' . self::TBL .
                ' (proyecto_id, recinto_id, tipo_rrhh, cantidad_personas, jornada_semanal,
                   semanas_efectivas, fraccion_jornada)
                  VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            if (!$ins) throw new RuntimeException('prepare insert');

            $n = 0;
            foreach ($grupos as $g) {
                $rec  = (int)$g['recinto_id'];
                $tipo = trim((string)$g['tipo_rrhh']);
                $per  = (int)$g['cantidad_personas'];
                $jor  = (int)$g['jornada_semanal'];
                $sem  = isset($g['semanas_efectivas']) ? (float)$g['semanas_efectivas'] : self::SEMANAS_DEFECTO;
                $fra  = isset($g['fraccion_jornada'])  ? (float)$g['fraccion_jornada']  : self::FRACCION_DEFECTO;
                $ins->bind_param('iisiidd', $proyectoId, $rec, $tipo, $per, $jor, $sem, $fra);
                if (!$ins->execute()) throw new RuntimeException('execute insert');
                $n++;
            }
            $ins->close();
            $this->conn->commit();
            return $n;
        } catch (\Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /** Devuelve los grupos del proyecto con sus minutos semanales. */
    public function obtener(int $proyectoId): array
    {
        $stmt = $this->conn->prepare(
            'SELECT recinto_id, tipo_rrhh, cantidad_personas, jornada_semanal,
                    semanas_efectivas, fraccion_jornada
             FROM ' . self::TBL . ' WHERE proyecto_id = ? ORDER BY id_rrhh'
        );
        if (!$stmt) throw new RuntimeException('prepare select');
        $stmt->bind_param('i', $proyectoId);
        $stmt->execute();
        $res = $stmt->get_result();
        $grupos = [];
        while ($r = $res->fetch_assoc()) {
            $r['semanas_efectivas'] = (float)$r['semanas_efectivas'];
            $r['fraccion_jornada']  = (float)$r['fraccion_jornada'];
            $r['minutos_semanales'] = (int)$r['cantidad_personas'] * (int)$r['jornada_semanal'] * 60;
            $grupos[] = $r;
        }
        $stmt->close();
        return $grupos;
    }

    /**
     * Limpia y valida el nombre de un perfil nuevo. No toca la base de datos.
     * Devuelve [nombre_limpio, null] o [null, mensaje_de_error].
     */
    public static function normalizarPerfil($texto): array
    {
        $n = preg_replace('/\s+/u', ' ', trim((string)$texto));
        if ($n === null) {
            return [null, 'El nombre del perfil no es válido.'];
        }
        $largo = preg_match_all('/./us', $n);
        if ($largo < 2) {
            return [null, 'El nombre del perfil debe tener al menos 2 caracteres.'];
        }
        if ($largo > 100) {
            return [null, 'El nombre del perfil puede tener como máximo 100 caracteres.'];
        }
        if (preg_match('/[\x00-\x1F\x7F<>]/', $n)) {
            return [null, 'El nombre del perfil contiene caracteres no permitidos.'];
        }
        return [$n, null];
    }

    /** Lista del catalogo de perfiles: [ ['id'=>, 'nombre'=>], ... ]. */
    public function listarPerfiles(): array
    {
        $res = $this->conn->query('SELECT id_perfil, nombre FROM ' . self::TBL_PERFIL . ' ORDER BY id_perfil');
        if (!$res) throw new RuntimeException('select perfiles');
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[] = ['id' => (int)$r['id_perfil'], 'nombre' => $r['nombre']];
        }
        $res->free();
        return $out;
    }

    /** Busca un perfil por nombre (MySQL ignora mayusculas y tildes). */
    private function buscarPerfil(string $nombre): ?array
    {
        $st = $this->conn->prepare('SELECT id_perfil, nombre FROM ' . self::TBL_PERFIL . ' WHERE nombre = ? LIMIT 1');
        if (!$st) throw new RuntimeException('prepare buscar perfil');
        $st->bind_param('s', $nombre);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        $st->close();
        return $r ? ['id' => (int)$r['id_perfil'], 'nombre' => $r['nombre']] : null;
    }

    /**
     * Agrega un perfil al catalogo si no existe. Si ya existia devuelve el
     * existente con 'creado' => false (no es un error).
     */
    public function agregarPerfil(string $nombre): array
    {
        $ya = $this->buscarPerfil($nombre);
        if ($ya) return ['perfil' => $ya, 'creado' => false];

        $st = $this->conn->prepare('INSERT INTO ' . self::TBL_PERFIL . ' (nombre) VALUES (?)');
        if (!$st) throw new RuntimeException('prepare insert perfil');
        $st->bind_param('s', $nombre);
        $ok    = $st->execute();
        $errno = $st->errno;
        $id    = (int)$st->insert_id;
        $st->close();

        if (!$ok) {
            // Otro usuario lo creo justo antes (clave unica): se devuelve ese.
            if ($errno === 1062) {
                $ya = $this->buscarPerfil($nombre);
                if ($ya) return ['perfil' => $ya, 'creado' => false];
            }
            throw new RuntimeException('insert perfil');
        }
        return ['perfil' => ['id' => $id, 'nombre' => $nombre], 'creado' => true];
    }

    /**
     * Reemplaza el nombre de cada grupo por el del catalogo (asi se guarda
     * siempre con la misma escritura) y lista los perfiles que no existen.
     * Devuelve ['grupos' => [...], 'faltan' => [nombres]].
     */
    public function canonizarPerfiles(array $grupos): array
    {
        $cache  = [];
        $faltan = [];
        foreach ($grupos as $i => $g) {
            $t = trim((string)($g['tipo_rrhh'] ?? ''));
            if (!array_key_exists($t, $cache)) {
                $cache[$t] = $this->buscarPerfil($t);
            }
            if ($cache[$t] === null) {
                if (!in_array($t, $faltan, true)) $faltan[] = $t;
                continue;
            }
            $grupos[$i]['tipo_rrhh'] = $cache[$t]['nombre'];
        }
        return ['grupos' => $grupos, 'faltan' => $faltan];
    }
}