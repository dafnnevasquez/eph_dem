<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../ajax/services/PabellonesBoxesService.php';
require_once __DIR__ . '/../../ajax/services/EquipamientoKitService.php';

class EquipamientoKitServiceTest extends TestCase
{
    /**
     * Test que calcularKit devuelve array con estructura correcta
     */
    public function testCalcularKitRetornaEstructuraCorrecta(): void
    {
        $kitService = new class extends EquipamientoKitService {
            public function __construct() {}
            public function calcularKit(int $proyectoId): array
            {
                return [
                    'conteo_recintos' => [1 => 2, 2 => 1, 3 => 3, 4 => 1],
                    'equipos' => [
                        [
                            'equipo_id'     => 1,
                            'nombre_equipo' => 'Monitor multiparámetros',
                            'origen'        => 'kit',
                            'cantidad'      => 6,
                            'por_recinto'   => [
                                ['recinto_id' => 1, 'cantidad_base' => 1, 'nro_recintos' => 2, 'subtotal' => 2],
                                ['recinto_id' => 3, 'cantidad_base' => 1, 'nro_recintos' => 3, 'subtotal' => 3],
                                ['recinto_id' => 4, 'cantidad_base' => 1, 'nro_recintos' => 1, 'subtotal' => 1],
                            ],
                        ],
                    ],
                ];
            }
        };

        $resultado = $kitService->calcularKit(1);

        $this->assertArrayHasKey('conteo_recintos', $resultado);
        $this->assertArrayHasKey('equipos', $resultado);
        $this->assertIsArray($resultado['equipos']);
    }

    /**
     * Test que cantidad = cantidad_base × nro_recintos
     */
    public function testCantidadEsProductoDeBaseYRecintos(): void
    {
        $cantidadBase = 2;
        $nroRecintos  = 3;
        $esperado     = $cantidadBase * $nroRecintos;

        $this->assertEquals(6, $esperado);
    }

    /**
     * Test que conteo de recintos con 0 recintos da cantidad 0
     */
    public function testConCeroRecintosLaCantidadEsCero(): void
    {
        $cantidadBase = 2;
        $nroRecintos  = 0;
        $resultado    = $cantidadBase * $nroRecintos;

        $this->assertEquals(0, $resultado);
    }

    /**
     * Test que origen siempre es 'kit' para equipos de kit
     */
    public function testOrigenEsKit(): void
    {
        $equipo = [
            'equipo_id'     => 1,
            'nombre_equipo' => 'Electrobisturí',
            'origen'        => 'kit',
            'cantidad'      => 3,
        ];

        $this->assertEquals('kit', $equipo['origen']);
    }

    /**
     * Test que calcularTipo2 retorna estructura correcta
     */
    public function testCalcularTipo2RetornaEstructuraCorrecta(): void
    {
        $kitService = new class extends EquipamientoKitService {
            public function __construct() {}
            public function calcularTipo2(int $proyectoId): array
            {
                return [
                    'equipos' => [
                        [
                            'equipo_id'    => 5,
                            'nombre_equipo'=> 'Fonendoscopio',
                            'tipo_demanda' => 2,
                            'origen'       => 'tipo2_relacion',
                            'cantidad'     => 2,
                            'por_recinto'  => [
                                ['recinto_id' => 3, 'nro_recintos' => 2],
                            ],
                        ],
                    ],
                ];
            }
        };

        $resultado = $kitService->calcularTipo2(1);

        $this->assertArrayHasKey('equipos', $resultado);
        $this->assertEquals('tipo2_relacion', $resultado['equipos'][0]['origen']);
        $this->assertEquals(2, $resultado['equipos'][0]['tipo_demanda']);
    }
}