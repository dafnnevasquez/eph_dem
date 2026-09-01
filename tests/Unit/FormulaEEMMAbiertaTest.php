<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FormulaEEMMAbiertaTest extends TestCase
{
    /**
     * Implementación de la fórmula EEMM con N° simultáneas
     * (atención abierta)
     */
    private function calcularEEMM(
        float $demandaAnual,
        float $diasLaborales,
        float $tiempoProc,
        int   $nSimultaneas,
        float $disponibilidad,
        float $jornada
    ): ?float {
        if ($demandaAnual <= 0 || $diasLaborales <= 0 || $tiempoProc <= 0 ||
            $nSimultaneas <= 0 || $disponibilidad <= 0 || $jornada <= 0) {
            return null;
        }
        $demandaDiaria   = $demandaAnual / $diasLaborales;
        $procHora        = 60 / $tiempoProc;
        $capDiaria       = $procHora * $jornada;
        $capConDisp      = $capDiaria * ($disponibilidad / 100);
        return $demandaDiaria / ($capConDisp * $nSimultaneas);
    }

    /**
     * Test fórmula básica con N=1 (igual a atención cerrada)
     */
    public function testFormulaConNUno(): void
    {
        $resultado = $this->calcularEEMM(450, 250, 60, 1, 85, 7);
        $this->assertNotNull($resultado);
        $this->assertGreaterThan(0, $resultado);
    }

    /**
     * Test que N=2 da la mitad del resultado que N=1
     */
    public function testNDosEsMitadDeNUno(): void
    {
        $n1 = $this->calcularEEMM(450, 250, 60, 1, 85, 7);
        $n2 = $this->calcularEEMM(450, 250, 60, 2, 85, 7);

        $this->assertEqualsWithDelta($n1 / 2, $n2, 0.001);
    }

    /**
     * Test que mayor N = menor requerimiento
     */
    public function testMayorNMenorRequerimiento(): void
    {
        $n1 = $this->calcularEEMM(1000, 260, 30, 1, 100, 8);
        $n3 = $this->calcularEEMM(1000, 260, 30, 3, 100, 8);

        $this->assertGreaterThan($n3, $n1);
    }

    /**
     * Test con valores inválidos retorna null
     */
    public function testValoresInvalidosRetornaNull(): void
    {
        $this->assertNull($this->calcularEEMM(0, 260, 30, 1, 100, 8));
        $this->assertNull($this->calcularEEMM(450, 0, 30, 1, 100, 8));
        $this->assertNull($this->calcularEEMM(450, 260, 0, 1, 100, 8));
        $this->assertNull($this->calcularEEMM(450, 260, 30, 0, 100, 8));
        $this->assertNull($this->calcularEEMM(450, 260, 30, 1, 0, 8));
        $this->assertNull($this->calcularEEMM(450, 260, 30, 1, 100, 0));
    }

    /**
     * Test días laborales 260 vs 365
     */
    public function testDiasLaboralesAfectaResultado(): void
    {
        $r260 = $this->calcularEEMM(450, 260, 30, 1, 100, 8);
        $r365 = $this->calcularEEMM(450, 365, 30, 1, 100, 8);

        // Con más días laborales, la demanda diaria baja → menor requerimiento
        $this->assertGreaterThan($r365, $r260);
    }

    /**
     * Test resultado siempre positivo con valores válidos
     */
    public function testResultadoSiemprePositivo(): void
    {
        $resultado = $this->calcularEEMM(1000, 365, 15, 2, 90, 8);
        $this->assertGreaterThan(0, $resultado);
    }

    /**
     * Test ceil del resultado
     */
    public function testCeilDelResultado(): void
    {
        $resultado = $this->calcularEEMM(100, 260, 30, 1, 100, 7);
        $this->assertGreaterThanOrEqual(1, (int)ceil($resultado));
    }
}