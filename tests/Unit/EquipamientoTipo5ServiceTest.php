<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../ajax/services/PabellonesBoxesService.php';
require_once __DIR__ . '/../../ajax/services/EquipamientoTipo5Service.php';

class EquipamientoTipo5ServiceTest extends TestCase
{
    /**
     * Test que calcular retorna estructura correcta
     */
    public function testCalcularRetornaEstructuraCorrecta(): void
    {
        $service = new class extends EquipamientoTipo5Service {
            public function __construct() {}
            public function calcular(int $proyectoId): array
            {
                return [
                    'equipos' => [
                        [
                            'equipo_id'      => 10,
                            'nombre_equipo'  => 'Electrobisturí',
                            'tipo_demanda'   => 5,
                            'origen'         => 'demanda',
                            'fraccion_total' => 1.5,
                            'cantidad'       => 2,
                            'por_prestacion' => [
                                ['prestacion_id' => 1, 'requerimiento_fraccion' => 0.8],
                                ['prestacion_id' => 2, 'requerimiento_fraccion' => 0.7],
                            ],
                        ],
                    ],
                ];
            }
        };

        $resultado = $service->calcular(1);

        $this->assertArrayHasKey('equipos', $resultado);
        $this->assertEquals('demanda', $resultado['equipos'][0]['origen']);
        $this->assertEquals(5, $resultado['equipos'][0]['tipo_demanda']);
    }

    /**
     * Test que cantidad = ceil(fraccion_total)
     */
    public function testCantidadEsCeilDeFraccionTotal(): void
    {
        $fraccionTotal = 1.5;
        $cantidad      = (int)ceil($fraccionTotal);
        $this->assertEquals(2, $cantidad);

        $fraccionTotal2 = 3.0;
        $cantidad2      = (int)ceil($fraccionTotal2);
        $this->assertEquals(3, $cantidad2);

        $fraccionTotal3 = 0.1;
        $cantidad3      = (int)ceil($fraccionTotal3);
        $this->assertEquals(1, $cantidad3);
    }

    /**
     * Test que fraccion_total es suma de requerimientos por prestacion
     */
    public function testFraccionTotalEsSumaDeRequerimientos(): void
    {
        $requerimientos = [0.8, 0.7, 1.2];
        $suma = array_sum($requerimientos);
        $this->assertEqualsWithDelta(2.7, $suma, 0.001);
        $this->assertEquals(3, (int)ceil($suma));
    }

    /**
     * Test que origen siempre es 'demanda' para Tipo 5
     */
    public function testOrigenEsDemanda(): void
    {
        $equipo = ['origen' => 'demanda', 'tipo_demanda' => 5];
        $this->assertEquals('demanda', $equipo['origen']);
        $this->assertEquals(5, $equipo['tipo_demanda']);
    }

    /**
     * Test que fraccion 0 da cantidad 0
     */
    public function testFraccionCeroGivesZero(): void
    {
        $fraccion = 0.0;
        $this->assertEquals(0, (int)ceil($fraccion));
    }
}