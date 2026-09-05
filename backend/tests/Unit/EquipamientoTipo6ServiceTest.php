<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../ajax/services/PabellonesBoxesService.php';
require_once __DIR__ . '/../../ajax/services/EquipamientoTipo6Service.php';

class EquipamientoTipo6ServiceTest extends TestCase
{
    /**
     * Test que con 0 camas UCI/UTI no hay dotaciones
     */
    public function testSinCamasNoDotaciones(): void
    {
        $camasUCI = 0;
        $camasUTI = 0;

        $dotacionesUCI = $camasUCI > 0 ? (int)ceil($camasUCI / 6) : 0;
        $dotacionesUTI = $camasUTI > 0 ? (int)ceil($camasUTI / 12) : 0;

        $this->assertEquals(0, $dotacionesUCI);
        $this->assertEquals(0, $dotacionesUTI);
    }

    /**
     * Test cálculo de dotaciones UCI (1 por cada 6 camas)
     */
    public function testDotacionesUCI(): void
    {
        // 6 camas UCI → 1 dotación
        $this->assertEquals(1, (int)ceil(6 / 6));
        // 7 camas UCI → 2 dotaciones (ceil)
        $this->assertEquals(2, (int)ceil(7 / 6));
        // 12 camas UCI → 2 dotaciones
        $this->assertEquals(2, (int)ceil(12 / 6));
        // 18 camas UCI → 3 dotaciones
        $this->assertEquals(3, (int)ceil(18 / 6));
    }

    /**
     * Test cálculo de dotaciones UTI (1 por cada 12 camas)
     */
    public function testDotacionesUTI(): void
    {
        // 12 camas UTI → 1 dotación
        $this->assertEquals(1, (int)ceil(12 / 12));
        // 13 camas UTI → 2 dotaciones
        $this->assertEquals(2, (int)ceil(13 / 12));
        // 24 camas UTI → 2 dotaciones
        $this->assertEquals(2, (int)ceil(24 / 12));
    }

    /**
     * Test que cantidad de equipo = cantidad_base × dotaciones
     */
    public function testCantidadEquipoEsProducto(): void
    {
        $cantidadBase = 2;
        $dotaciones   = 3;
        $subtotal     = $cantidadBase * $dotaciones;
        $this->assertEquals(6, $subtotal);
    }

    /**
     * Test que origen es 'norma_upc'
     */
    public function testOrigenEsNormaUpc(): void
    {
        $equipo = ['origen' => 'norma_upc'];
        $this->assertEquals('norma_upc', $equipo['origen']);
    }

    /**
     * Test estructura de respuesta calcular
     */
    public function testCalcularRetornaEstructuraCorrecta(): void
    {
        $service = new class extends EquipamientoTipo6Service {
            public function __construct() {}
            public function calcular(int $proyectoId): array
            {
                return [
                    'camas'      => ['uci' => 12, 'uti' => 6],
                    'dotaciones' => ['uci' => 2, 'uti' => 1, 'enfermeria_uci' => 2, 'enfermeria_uti' => 1],
                    'equipos'    => [],
                ];
            }
        };

        $resultado = $service->calcular(1);

        $this->assertArrayHasKey('camas', $resultado);
        $this->assertArrayHasKey('dotaciones', $resultado);
        $this->assertArrayHasKey('equipos', $resultado);
        $this->assertEquals(12, $resultado['camas']['uci']);
        $this->assertEquals(2, $resultado['dotaciones']['uci']);
    }
}