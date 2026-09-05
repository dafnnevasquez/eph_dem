<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// Incluir el service directamente (sin BD)
require_once __DIR__ . '/../../ajax/services/PabellonesBoxesService.php';

class PabellonesBoxesServiceTest extends TestCase
{
    private PabellonesBoxesService $service;

    protected function setUp(): void
    {
        // Creamos un mock de mysqli para no necesitar BD real
        $this->service = $this->getMockBuilder(PabellonesBoxesService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * Test fórmula EEMM con valores conocidos
     */
    public function testFormulaEEMMBasico(): void
    {
        $service = new class extends PabellonesBoxesService {
            public function __construct() {} // bypass constructor
        };

        // Demanda: 450, Días: 250, Tiempo: 60 min, Disp: 0.85, Jornada: 8 hrs
        $resultado = $service->formulaEEMM(450, 250, 60, 0.85, 8);

        // Demanda diaria = 450/250 = 1.8
        // Denominador = (60/60) * 0.85 * 8 = 6.8
        // EEMM = 1.8 / 6.8 = 0.2647...
        $this->assertEqualsWithDelta(0.2647, $resultado, 0.001);
    }

    /**
     * Test que EEMM retorna 0 si algún valor es 0
     */
    public function testFormulaEEMMConCeroRetornaCero(): void
    {
        $service = new class extends PabellonesBoxesService {
            public function __construct() {}
        };

        $this->assertEquals(0.0, $service->formulaEEMM(0, 250, 60, 0.85, 8));
        $this->assertEquals(0.0, $service->formulaEEMM(450, 0, 60, 0.85, 8));
        $this->assertEquals(0.0, $service->formulaEEMM(450, 250, 0, 0.85, 8));
        $this->assertEquals(0.0, $service->formulaEEMM(450, 250, 60, 0, 8));
        $this->assertEquals(0.0, $service->formulaEEMM(450, 250, 60, 0.85, 0));
    }

    /**
     * Test que mayor demanda = mayor requerimiento
     */
    public function testMayorDemandaMayorRequerimiento(): void
    {
        $service = new class extends PabellonesBoxesService {
            public function __construct() {}
        };

        $bajo  = $service->formulaEEMM(100, 250, 60, 0.85, 8);
        $alto  = $service->formulaEEMM(500, 250, 60, 0.85, 8);

        $this->assertGreaterThan($bajo, $alto);
    }

    /**
     * Test que mayor disponibilidad = menor requerimiento
     */
    public function testMayorDisponibilidadMenorRequerimiento(): void
    {
        $service = new class extends PabellonesBoxesService {
            public function __construct() {}
        };

        $dispBaja = $service->formulaEEMM(450, 250, 60, 0.5, 8);
        $dispAlta = $service->formulaEEMM(450, 250, 60, 1.0, 8);

        $this->assertGreaterThan($dispAlta, $dispBaja);
    }

    /**
     * Test que mayor jornada = menor requerimiento
     */
    public function testMayorJornadaMenorRequerimiento(): void
    {
        $service = new class extends PabellonesBoxesService {
            public function __construct() {}
        };

        $jornada8  = $service->formulaEEMM(450, 250, 60, 0.85, 8);
        $jornada24 = $service->formulaEEMM(450, 250, 60, 0.85, 24);

        $this->assertGreaterThan($jornada24, $jornada8);
    }

    /**
     * Test ceil — resultado siempre es entero hacia arriba
     */
    public function testCeilDeEEMM(): void
    {
        $service = new class extends PabellonesBoxesService {
            public function __construct() {}
        };

        $eemm = $service->formulaEEMM(450, 250, 60, 0.85, 8);
        // EEMM = 0.2647 → ceil = 1
        $this->assertEquals(1, (int)ceil($eemm));
    }
}