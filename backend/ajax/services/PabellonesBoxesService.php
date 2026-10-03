<?php
declare(strict_types=1);

require_once __DIR__ . '/../calculo/calculo_pabellones_boxes.php';
require_once __DIR__ . '/../calculo/calculo_equipamiento_kit.php';

class PabellonesBoxesService
{
    const TBL_DEMANDA = 'EPHAC_Proyecto_Demanda';

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function formulaEEMM(float $demandaAnual, float $diasLaborales, float $tiempoProcedimiento, float $disponibilidad, float $jornada): float
    {
        return SIGEM_EPHDEM_CERRADA_FormulaEEMM($demandaAnual, $diasLaborales, $tiempoProcedimiento, $disponibilidad, $jornada);
    }

    /**
     * EXCEPCION: este metodo NO delega en SIGEM_EPHDEM_CERRADA_GuardarDemanda.
     * La funcion original (calculo/calculo_pabellones_boxes.php) no comprueba el
     * retorno de execute() del DELETE ni de los INSERT. El servidor corre PHP 7.4,
     * donde mysqli no lanza excepciones por defecto (eso empieza en PHP 8.1), asi
     * que un INSERT fallido pasaria inadvertido, se contaria en $n y se haria
     * commit. Aqui se comprueba execute() y se lanza RuntimeException para que el
     * catch haga rollback y devuelva 0, que es lo que DemandaController usa para
     * no continuar el calculo.
     */
    public function guardarDemanda(int $proyectoId, array $filas): int
    {
        $this->conn->begin_transaction();

        try {
            $del = $this->conn->prepare("DELETE FROM " . self::TBL_DEMANDA . " WHERE proyecto_id = ?");
            if ($del === false) {
                throw new \RuntimeException('prepare DELETE fallo');
            }
            $del->bind_param('i', $proyectoId);
            if (!$del->execute()) {
                throw new \RuntimeException('execute DELETE fallo');
            }
            $del->close();

            $stmt = $this->conn->prepare(
                "INSERT INTO " . self::TBL_DEMANDA . " (proyecto_id, prestacion_id, demanda_anual, dias_laborales, disponibilidad, jornada_efectiva)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if ($stmt === false) {
                throw new \RuntimeException('prepare INSERT fallo');
            }

            $pid = $prest = $dem = $dias = 0;
            $disp = $jorn = 0.0;
            $stmt->bind_param('iiiidd', $pid, $prest, $dem, $dias, $disp, $jorn);

            $n = 0;
            foreach ($filas as $f) {
                $pid   = $proyectoId;
                $prest = (int)($f['prestacion_id']      ?? 0);
                $dem   = (int)($f['demanda_anual']      ?? 0);
                $dias  = (int)($f['dias_laborales']     ?? 0);
                $disp  = (float)($f['disponibilidad']   ?? 1.0);
                $jorn  = (float)($f['jornada_efectiva'] ?? 0.0);
                if ($prest <= 0) {
                    continue;
                }
                if (!$stmt->execute()) {
                    throw new \RuntimeException('execute INSERT fallo');
                }
                $n++;
            }
            $stmt->close();

            $this->conn->commit();
            return $n;

        } catch (\Throwable $e) {
            $this->conn->rollback();
            return 0;
        }
    }

    public function calcularPabellones(int $proyectoId): array
    {
        return SIGEM_EPHDEM_CERRADA_CalcularPabellones($this->conn, $proyectoId);
    }

    public function calcularBoxes(int $proyectoId): array
    {
        return SIGEM_EPHDEM_CERRADA_CalcularBoxes($this->conn, $proyectoId);
    }

    public function conteoRecintosPorId(int $proyectoId): array
    {
        return SIGEM_EPHDEM_CERRADA_ConteoRecintosPorId($this->conn, $proyectoId);
    }
}
