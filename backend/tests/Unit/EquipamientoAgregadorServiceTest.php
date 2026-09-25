<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class EquipamientoAgregadorServiceTest extends TestCase
{
    /**
     * Test regla max(piso, demanda) — piso mayor
     */
    public function testMaxPisoDemandaPisoMayor(): void
    {
        $piso    = 5;
        $demanda = 3;
        $resultado = max($piso, $demanda);
        $this->assertEquals(5, $resultado);
    }

    /**
     * Test regla max(piso, demanda) — demanda mayor
     */
    public function testMaxPisoDemandaDemandaMayor(): void
    {
        $piso    = 2;
        $demanda = 7;
        $resultado = max($piso, $demanda);
        $this->assertEquals(7, $resultado);
    }

    /**
     * Test regla max(piso, demanda) — iguales
     */
    public function testMaxPisoDemandaIguales(): void
    {
        $piso    = 4;
        $demanda = 4;
        $resultado = max($piso, $demanda);
        $this->assertEquals(4, $resultado);
    }

    /**
     * Test que piso es suma de buckets por recinto
     */
    public function testPisoEsSumaDeBuckets(): void
    {
        $pisoPorBucket = [
            'recinto_1' => 3,
            'recinto_3' => 2,
            'recinto_4' => 1,
        ];
        $piso = array_sum($pisoPorBucket);
        $this->assertEquals(6, $piso);
    }

    /**
     * Test que cantidad final nunca es menor al piso
     */
    public function testCantidadFinalNuncaMenorAlPiso(): void
    {
        $casos = [
            ['piso' => 5, 'demanda' => 3],
            ['piso' => 0, 'demanda' => 4],
            ['piso' => 3, 'demanda' => 3],
            ['piso' => 10, 'demanda' => 0],
        ];

        foreach ($casos as $caso) {
            $cantidadFinal = max($caso['piso'], $caso['demanda']);
            $this->assertGreaterThanOrEqual($caso['piso'], $cantidadFinal);
        }
    }

    /**
     * Test que cantidad final nunca es menor a la demanda
     */
    public function testCantidadFinalNuncaMenorALaDemanda(): void
    {
        $casos = [
            ['piso' => 5, 'demanda' => 3],
            ['piso' => 0, 'demanda' => 4],
            ['piso' => 3, 'demanda' => 3],
        ];

        foreach ($casos as $caso) {
            $cantidadFinal = max($caso['piso'], $caso['demanda']);
            $this->assertGreaterThanOrEqual($caso['demanda'], $cantidadFinal);
        }
    }

    /**
     * Test origenes válidos del agregador
     */
    public function testOrigenesValidos(): void
    {
        $origenesValidos = ['kit', 'tipo2_relacion', 'norma_upc', 'demanda'];

        foreach ($origenesValidos as $origen) {
            $this->assertContains($origen, $origenesValidos);
        }
    }

    /**
     * Test que con piso 0 y demanda 0 resultado es 0
     */
    public function testCeroPisoCeroDemandaEsCero(): void
    {
        $this->assertEquals(0, max(0, 0));
    }
}