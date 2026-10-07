<?php
declare(strict_types=1);

class RRHHService
{
    const TBL = 'EPHAC_Proyecto_RRHH';

    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
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
                ' (proyecto_id, recinto_id, tipo_rrhh, cantidad_personas, jornada_semanal, min_interaccion)
                  VALUES (?, ?, ?, ?, ?, ?)'
            );
            if (!$ins) throw new RuntimeException('prepare insert');

            $n = 0;
            foreach ($grupos as $g) {
                $rec = (int)$g['recinto_id'];
                $tipo = (string)$g['tipo_rrhh'];
                $per = (int)$g['cantidad_personas'];
                $jor = (int)$g['jornada_semanal'];
                $min = (int)$g['min_interaccion'];
                $ins->bind_param('iisiii', $proyectoId, $rec, $tipo, $per, $jor, $min);
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
            'SELECT recinto_id, tipo_rrhh, cantidad_personas, jornada_semanal, min_interaccion
             FROM ' . self::TBL . ' WHERE proyecto_id = ? ORDER BY id_rrhh'
        );
        if (!$stmt) throw new RuntimeException('prepare select');
        $stmt->bind_param('i', $proyectoId);
        $stmt->execute();
        $res = $stmt->get_result();
        $grupos = [];
        while ($r = $res->fetch_assoc()) {
            $r['minutos_semanales'] = (int)$r['cantidad_personas'] * (int)$r['jornada_semanal'] * 60;
            $grupos[] = $r;
        }
        $stmt->close();
        return $grupos;
    }
}