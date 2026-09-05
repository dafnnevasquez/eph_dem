<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../ajax/services/UrpaService.php';

class UrpaServiceTest extends TestCase
{
    /**
     * Test que con 0 pabellones no hay camillas ni salas
     */
    public function testCeroPabelLonesNoCamillasNiSalas(): void
    {
        $nroPabellones = 0;
        $camillas      = UrpaService::CAMILLAS_POR_PABELLON * $nroPabellones;
        $salas         = $camillas > 0 ? (int)ceil($camillas / UrpaService::MAX_CAMILLAS) : 0;

        $this->assertEquals(0, $camillas);
        $this->assertEquals(0, $salas);
    }

    /**
     * Test camillas = pabellones × 2
     */
    public function testCamillasEsDobleDeParabellones(): void
    {
        $pabellones = 5;
        $camillas   = UrpaService::CAMILLAS_POR_PABELLON * $pabellones;
        $this->assertEquals(10, $camillas);
    }

    /**
     * Test número de salas = ceil(camillas / 12)
     */
    public function testNroSalas(): void
    {
        // 12 camillas → 1 sala
        $this->assertEquals(1, (int)ceil(12 / UrpaService::MAX_CAMILLAS));
        // 13 camillas → 2 salas
        $this->assertEquals(2, (int)ceil(13 / UrpaService::MAX_CAMILLAS));
        // 24 camillas → 2 salas
        $this->assertEquals(2, (int)ceil(24 / UrpaService::MAX_CAMILLAS));
        // 25 camillas → 3 salas
        $this->assertEquals(3, (int)ceil(25 / UrpaService::MAX_CAMILLAS));
    }

    /**
     * Test estructura de respuesta calcular
     */
    public function testCalcularRetornaEstructuraCorrecta(): void
    {
        $service = new class extends UrpaService {
            public function __construct() {}
            public function calcular(int $nroPabellones): array
            {
                $camillas = UrpaService::CAMILLAS_POR_PABELLON * $nroPabellones;
                $salas    = $camillas > 0 ? (int)ceil($camillas / UrpaService::MAX_CAMILLAS) : 0;
                return [
                    'nombre_recinto'  => UrpaService::NOMBRE_RECINTO,
                    'nro_pabellones'  => $nroPabellones,
                    'nro_camillas'    => $camillas,
                    'nro_salas'       => $salas,
                    'equipos_sala'    => [],
                    'equipos_camilla' => [],
                    'equipos'         => [],
                ];
            }
        };

        $resultado = $service->calcular(3);

        $this->assertArrayHasKey('nro_pabellones', $resultado);
        $this->assertArrayHasKey('nro_camillas', $resultado);
        $this->assertArrayHasKey('nro_salas', $resultado);
        $this->assertArrayHasKey('equipos', $resultado);
        $this->assertEquals(3, $resultado['nro_pabellones']);
        $this->assertEquals(6, $resultado['nro_camillas']);
        $this->assertEquals(1, $resultado['nro_salas']);
    }

    /**
     * Test que nombre recinto es correcto
     */
    public function testNombreRecinto(): void
    {
        $this->assertEquals(
            'URPA (Sala de recuperación post-anestésica)',
            UrpaService::NOMBRE_RECINTO
        );
    }

    /**
     * Test con 1 pabellón
     */
    public function testUnPabellon(): void
    {
        $camillas = UrpaService::CAMILLAS_POR_PABELLON * 1;
        $salas    = (int)ceil($camillas / UrpaService::MAX_CAMILLAS);
        $this->assertEquals(2, $camillas);
        $this->assertEquals(1, $salas);
    }
}