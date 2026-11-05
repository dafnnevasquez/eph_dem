<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../ajax/services/RestriccionRRHHService.php';

/**
 * Pruebas de la restriccion de RR.HH. con los ejemplos del documento
 * (modelo v4, secciones 2.1, 2.4, 5.1 y 5.2).
 * D_tec se toma del documento: lo calcula el sistema actual, no este service.
 */
class RestriccionRRHHServiceTest extends TestCase
{
    const TOL = 0.001;

    /** D_tec de los ejemplos del documento (R1 + R2, calculo actual). */
    const DTEC_PABELLON     = 0.9615;
    const DTEC_HEMODIALISIS = 5.5556;

    private function pabellon(float $delta): array
    {
        return RestriccionRRHHService::resolverServicio(
            [['dda' => 2000.0, 'tau' => ['equipo_quirurgico' => 1.0]]],
            ['equipo_quirurgico' => ['n' => 1, 'h' => 8, 'd' => 5, 'W' => 52, 'delta' => $delta]]
        );
    }

    /* --- R3: dedicacion (tabla de la seccion 2.1) ----------------------- */

    public function testVentilacionMecanica(): void
    {
        $tau = RestriccionRRHHService::horasPorPrestacion(0.5, 4.0, 120.0);
        $this->assertEqualsWithDelta(10.0, $tau, self::TOL);
        $this->assertEqualsWithDelta(0.0833, RestriccionRRHHService::dedicacion($tau, 120.0), self::TOL);
    }

    public function testHemodialisis(): void
    {
        $tau = RestriccionRRHHService::horasPorPrestacion(10.0 / 60.0, 3.0, 4.0);
        $this->assertEqualsWithDelta(0.5, $tau, self::TOL);
        $this->assertEqualsWithDelta(0.125, RestriccionRRHHService::dedicacion($tau, 4.0), self::TOL);
    }

    public function testOperacionPermanenteDaPhiUno(): void
    {
        $this->assertEqualsWithDelta(1.0, RestriccionRRHHService::dedicacion(
            RestriccionRRHHService::horasPorPrestacionPermanente(1.0), 1.0), self::TOL);
        // Con t = 120 h sigue dando phi = 1 (el "e = t" literal del documento daria 5).
        $this->assertEqualsWithDelta(1.0, RestriccionRRHHService::dedicacion(
            RestriccionRRHHService::horasPorPrestacionPermanente(120.0), 120.0), self::TOL);
    }

    public function testDedicacionValida(): void
    {
        $this->assertFalse(RestriccionRRHHService::dedicacionValida(5.0));
        $this->assertTrue(RestriccionRRHHService::dedicacionValida(0.125));
        $this->assertTrue(RestriccionRRHHService::dedicacionValida(1.0));
        $this->assertFalse(RestriccionRRHHService::dedicacionValida(0.0));
    }

    /* --- Ejemplo 5.1 pabellon ------------------------------------------- */

    public function testPabellonEscenarioASinRestriccion(): void
    {
        $dtec = self::DTEC_PABELLON;
        $r = $this->pabellon(1.0);
        $p = $r['por_perfil']['equipo_quirurgico'];
        $this->assertEqualsWithDelta(2000.0, $p['q'], self::TOL);
        $this->assertEqualsWithDelta(2080.0, $p['a'], self::TOL);
        $this->assertEqualsWithDelta(1.04, $r['rho'], self::TOL);
        $this->assertEqualsWithDelta(1.0, $r['alpha'], self::TOL);
        $this->assertSame('equipo', $r['restriccion_activa']);
        $this->assertSame(1, $p['n_min']);
        $this->assertEqualsWithDelta(0.0, $p['brecha'], self::TOL);
        $this->assertEqualsWithDelta(0.0, $r['prestaciones_no_cubiertas'], self::TOL);
        $this->assertSame(1, RestriccionRRHHService::equiposRestringidos($dtec, $r['alpha'])['d_entero']);
    }

    public function testPabellonEscenarioBConRestriccion(): void
    {
        $dtec = self::DTEC_PABELLON;
        $r = $this->pabellon(0.36);
        $p = $r['por_perfil']['equipo_quirurgico'];
        $this->assertEqualsWithDelta(748.8, $p['a'], self::TOL);
        $this->assertEqualsWithDelta(0.3744, $r['rho'], self::TOL);
        $this->assertEqualsWithDelta(0.3744, $r['alpha'], self::TOL);
        $this->assertSame('rrhh', $r['restriccion_activa']);
        $this->assertSame(3, $p['n_min']);
        $this->assertEqualsWithDelta(2.0, $p['brecha'], self::TOL);
        $this->assertEqualsWithDelta(1251.2, $r['prestaciones_no_cubiertas'], self::TOL);

        $eq = RestriccionRRHHService::equiposRestringidos($dtec, $r['alpha']);
        $this->assertEqualsWithDelta(0.36, $eq['d'], self::TOL);
        $this->assertSame(1, $eq['d_entero']);
    }

    /* --- Ejemplo 5.2 hemodialisis --------------------------------------- */

    public function testHemodialisisCompleto(): void
    {
        $dtec = self::DTEC_HEMODIALISIS;
        $this->assertSame(6, RestriccionRRHHService::equiposRestringidos($dtec, 1.0)['d_entero']);

        $tau = RestriccionRRHHService::horasPorPrestacion(10.0 / 60.0, 3.0, 4.0);
        $r = RestriccionRRHHService::resolverServicio(
            [['dda' => 4680.0, 'tau' => ['enfermera' => $tau]]],
            ['enfermera' => ['n' => 1, 'h' => 9, 'd' => 5, 'W' => 48, 'delta' => 0.8]]
        );
        $p = $r['por_perfil']['enfermera'];
        $this->assertEqualsWithDelta(2340.0, $p['q'], self::TOL);
        $this->assertEqualsWithDelta(1728.0, $p['a'], self::TOL);
        $this->assertEqualsWithDelta(0.7385, $r['alpha'], self::TOL);
        $this->assertSame(2, $p['n_min']);
        $this->assertEqualsWithDelta(1.0, $p['brecha'], self::TOL);
        $this->assertEqualsWithDelta(1224.0, $r['prestaciones_no_cubiertas'], self::TOL);

        $eq = RestriccionRRHHService::equiposRestringidos($dtec, $r['alpha']);
        $this->assertEqualsWithDelta(4.1026, $eq['d'], self::TOL);
        $this->assertSame(5, $eq['d_entero']);
    }

    /* --- Seccion 2.4: varios perfiles, manda el minimo ------------------ */

    public function testCuelloDeBotella(): void
    {
        $r = RestriccionRRHHService::resolverServicio(
            [['dda' => 1000.0, 'tau' => ['cirujano' => 1.0, 'anestesiologo' => 1.0, 'arsenalera' => 1.0]]],
            [
                'cirujano'      => ['n' => 900,  'h' => 1, 'd' => 1, 'W' => 1, 'delta' => 1.0],
                'anestesiologo' => ['n' => 600,  'h' => 1, 'd' => 1, 'W' => 1, 'delta' => 1.0],
                'arsenalera'    => ['n' => 1200, 'h' => 1, 'd' => 1, 'W' => 1, 'delta' => 1.0],
            ]
        );
        $this->assertEqualsWithDelta(0.60, $r['alpha'], self::TOL);
        $this->assertSame('anestesiologo', $r['perfil_limitante']);
        $this->assertEqualsWithDelta(100.0, $r['por_perfil']['cirujano']['brecha'], self::TOL);
        $this->assertEqualsWithDelta(400.0, $r['por_perfil']['anestesiologo']['brecha'], self::TOL);
        $this->assertEqualsWithDelta(0.0, $r['por_perfil']['arsenalera']['brecha'], self::TOL);
        $this->assertEqualsWithDelta(400.0, $r['prestaciones_no_cubiertas'], self::TOL);
    }

    /* --- Casos limite --------------------------------------------------- */

    public function testPerfilSinDotacionDaAlphaCero(): void
    {
        $r = RestriccionRRHHService::resolverServicio([['dda' => 1000.0, 'tau' => ['enfermera' => 0.5]]], []);
        $this->assertEqualsWithDelta(0.0, $r['alpha'], self::TOL);
        $this->assertSame(['enfermera'], $r['perfiles_sin_datos']);
        $this->assertNull($r['por_perfil']['enfermera']['n_min']);
        $this->assertNotFalse(json_encode($r));
    }

    public function testSinPerfilesRequeridosNoHayRestriccion(): void
    {
        $r = RestriccionRRHHService::resolverServicio([['dda' => 1000.0, 'tau' => []]], []);
        $this->assertEqualsWithDelta(1.0, $r['alpha'], self::TOL);
        $this->assertNull($r['rho']);
    }

    public function testTechoSeguro(): void
    {
        $this->assertSame(2, RestriccionRRHHService::techo(2.0000000000000004));
        $this->assertSame(3, RestriccionRRHHService::techo(2.0000001));
        $this->assertSame(0, RestriccionRRHHService::techo(0.0));
    }

    public function testAlphaCeroDaCeroEquipos(): void
    {
        $this->assertSame(0, RestriccionRRHHService::equiposRestringidos(self::DTEC_HEMODIALISIS, 0.0)['d_entero']);
    }

    public function testAlphaUnoNoCambiaLosEquipos(): void
    {
        // Sin restriccion, D* debe ser igual al resultado actual (techo de D_tec).
        $eq = RestriccionRRHHService::equiposRestringidos(self::DTEC_HEMODIALISIS, 1.0);
        $this->assertSame($eq['dtec_entero'], $eq['d_entero']);
        $this->assertEqualsWithDelta(self::DTEC_HEMODIALISIS, $eq['d'], 1e-12);
    }
}