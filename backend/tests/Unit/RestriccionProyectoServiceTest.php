<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../ajax/services/RestriccionProyectoService.php';

/** Pruebas de la restriccion aplicada a un proyecto (logica pura, sin base de datos). */
class RestriccionProyectoServiceTest extends TestCase
{
    const TOL = 0.001;

    private function prestacion(int $id, int $recinto, string $nombreRecinto, $tMin, $demanda): array
    {
        return ['id' => $id, 'codigo' => null, 'nombre' => 'P' . $id, 'recinto_id' => $recinto,
                'recinto' => $nombreRecinto, 't_min' => $tMin, 'demanda' => $demanda];
    }

    private function grupo(int $recinto, string $perfil, int $n, int $jornada, float $semanas, float $fraccion): array
    {
        return ['recinto_id' => $recinto, 'tipo_rrhh' => $perfil, 'cantidad_personas' => $n,
                'jornada_semanal' => $jornada, 'semanas_efectivas' => $semanas, 'fraccion_jornada' => $fraccion];
    }

    private function recinto(array $r, int $id): array
    {
        foreach ($r['recintos'] as $x) {
            if ($x['id'] === $id) return $x;
        }
        $this->fail('No esta el recinto ' . $id);
        return [];
    }

    /* --- Sin datos de Planilla A: todo igual al calculo actual --------- */

    public function testSinPlanillaAEsIgualAlCalculoActual(): void
    {
        $r = RestriccionProyectoService::calcular(
            [$this->prestacion(20, 3, 'Pabellón menor', 60.0, 300), $this->prestacion(149, 2, 'Cubículo UCI', 1440.0, 300)],
            [],
            [$this->grupo(3, 'Cirujano/a', 1, 44, 48, 1.0)],
            [3 => ['tipo' => 'pabellones', 'fraccion' => 2.3, 'cantidad' => 3], 2 => ['tipo' => 'boxes', 'fraccion' => 0.8219, 'cantidad' => 1]],
            [['equipo_id' => 7, 'nombre_equipo' => 'Monitor', 'cantidad' => 4, 'por_prestacion' => [['prestacion_id' => 20, 'requerimiento_fraccion' => 3.2]]]]
        );
        $this->assertSame(3, $this->recinto($r, 3)['modulos']['restringido']);
        $this->assertSame(1, $this->recinto($r, 2)['modulos']['restringido']);
        $this->assertFalse($this->recinto($r, 3)['con_datos_planilla_a']);
        $this->assertSame(4, $r['tipo5'][0]['restringido']);
        $this->assertSame($r['totales']['modulos_normal'], $r['totales']['modulos_restringido']);
        $this->assertSame($r['totales']['tipo5_normal'], $r['totales']['tipo5_restringido']);
    }

    public function testConPersonalSuficienteNoCambiaNada(): void
    {
        // Q = 300 h; A = 2 * 44 * 48 = 4224 h -> alpha = 1.
        $r = RestriccionProyectoService::calcular(
            [$this->prestacion(20, 3, 'Pabellón menor', 60.0, 300)],
            [['prestacion_id' => 20, 'tipo_rrhh' => 'Cirujano/a', 'participacion' => 'permanente', 'e_minutos' => null, 'f_veces_dia' => null]],
            [$this->grupo(3, 'Cirujano/a', 2, 44, 48, 1.0)],
            [3 => ['tipo' => 'pabellones', 'fraccion' => 2.0000000004, 'cantidad' => 3]],
            []
        );
        $rec = $this->recinto($r, 3);
        $this->assertEqualsWithDelta(1.0, $rec['alpha'], self::TOL);
        // Con alpha = 1 se devuelve el numero del calculo actual tal cual.
        $this->assertSame(3, $rec['modulos']['restringido']);
    }

    /* --- Ejemplo 5.1 del documento: pabellon, delta = 0,36 ------------- */

    public function testPabellonDelDocumento(): void
    {
        // 2000 cirugias de 1 h; 1 equipo quirurgico 8 h x 5 dias = 40 h/sem, 52 sem, delta 0,36.
        $r = RestriccionProyectoService::calcular(
            [$this->prestacion(20, 4, 'Pabellón mayor', 60.0, 2000)],
            [['prestacion_id' => 20, 'tipo_rrhh' => 'Equipo quirúrgico', 'participacion' => 'permanente', 'e_minutos' => null, 'f_veces_dia' => null]],
            [$this->grupo(4, 'Equipo quirúrgico', 1, 40, 52, 0.36)],
            [4 => ['tipo' => 'pabellones', 'fraccion' => 0.9615, 'cantidad' => 1]],
            []
        );
        $rec = $this->recinto($r, 4);
        $this->assertEqualsWithDelta(0.3744, $rec['alpha'], self::TOL);
        $this->assertSame('rrhh', $rec['restriccion_activa']);
        $this->assertSame('Equipo quirúrgico', $rec['perfil_limitante']);
        $this->assertEqualsWithDelta(1251.2, $rec['prestaciones_no_cubiertas'], self::TOL);
        $this->assertSame(1, $rec['modulos']['restringido']);        // techo(0,36) = 1
        $this->assertSame(3, $rec['por_perfil'][0]['n_min']);
        $this->assertEqualsWithDelta(2.0, $rec['por_perfil'][0]['brecha'], self::TOL);
    }

    /* --- Ejemplo 5.2 del documento: hemodialisis (tipo 5) -------------- */

    public function testHemodialisisDelDocumento(): void
    {
        // 4680 sesiones de 4 h; enfermera 10 min x 3 veces; 9 h x 5 dias = 45 h/sem, 48 sem, delta 0,8.
        $r = RestriccionProyectoService::calcular(
            [$this->prestacion(30, 2, 'Cubículo UCI', 240.0, 4680)],
            [['prestacion_id' => 30, 'tipo_rrhh' => 'Enfermera/o', 'participacion' => 'puntual', 'e_minutos' => '10.00', 'f_veces_dia' => '3.00']],
            [$this->grupo(2, 'Enfermera/o', 1, 45, 48, 0.8)],
            [],
            [['equipo_id' => 90, 'nombre_equipo' => 'Máquina de hemodiálisis', 'cantidad' => 6,
              'por_prestacion' => [['prestacion_id' => 30, 'requerimiento_fraccion' => 5.5556]]]]
        );
        $rec = $this->recinto($r, 2);
        $this->assertEqualsWithDelta(2340.0, $rec['por_perfil'][0]['q'], self::TOL);
        $this->assertEqualsWithDelta(1728.0, $rec['por_perfil'][0]['a'], self::TOL);
        $this->assertEqualsWithDelta(0.7385, $rec['alpha'], self::TOL);
        $this->assertNull($rec['modulos']);
        $this->assertSame(6, $r['tipo5'][0]['normal']);
        $this->assertSame(5, $r['tipo5'][0]['restringido']);          // techo(4,10) = 5
    }

    /* --- Tipo 5 compartido entre recintos con distinto alpha ----------- */

    public function testTipo5CompartidoUsaElAlphaDeCadaRecinto(): void
    {
        // Recinto 3 con alpha 0,5 y recinto 4 sin restriccion.
        $r = RestriccionProyectoService::calcular(
            [$this->prestacion(20, 3, 'Pabellón menor', 60.0, 1000), $this->prestacion(21, 4, 'Pabellón mayor', 60.0, 100)],
            [['prestacion_id' => 20, 'tipo_rrhh' => 'TENS', 'participacion' => 'permanente', 'e_minutos' => null, 'f_veces_dia' => null]],
            [$this->grupo(3, 'TENS', 1, 10, 50, 1.0)],              // A = 500, Q = 1000 -> alpha 0,5
            [],
            [['equipo_id' => 5, 'nombre_equipo' => 'Aspirador', 'cantidad' => 3,
              'por_prestacion' => [['prestacion_id' => 20, 'requerimiento_fraccion' => 1.2], ['prestacion_id' => 21, 'requerimiento_fraccion' => 0.9]]]]
        );
        $this->assertEqualsWithDelta(0.5, $this->recinto($r, 3)['alpha'], self::TOL);
        $this->assertEqualsWithDelta(1.0, $this->recinto($r, 4)['alpha'], self::TOL);
        // techo(0,5 * 1,2 + 1 * 0,9) = techo(1,5) = 2
        $this->assertSame(2, $r['tipo5'][0]['restringido']);
        $this->assertSame(1, $r['totales']['tipo5_normal'] - $r['totales']['tipo5_restringido']);
    }

    /* --- Perfil usado en Planilla A pero sin personal en Planilla B ---- */

    public function testPerfilSinPersonalDaAlphaCero(): void
    {
        $r = RestriccionProyectoService::calcular(
            [$this->prestacion(20, 3, 'Pabellón menor', 60.0, 300)],
            [['prestacion_id' => 20, 'tipo_rrhh' => 'Arsenalera/o', 'participacion' => 'permanente', 'e_minutos' => null, 'f_veces_dia' => null]],
            [],
            [3 => ['tipo' => 'pabellones', 'fraccion' => 0.4, 'cantidad' => 1]],
            []
        );
        $rec = $this->recinto($r, 3);
        $this->assertEqualsWithDelta(0.0, $rec['alpha'], self::TOL);
        $this->assertSame(['Arsenalera/o'], $rec['perfiles_sin_datos']);
        $this->assertSame(0, $rec['modulos']['restringido']);
        $this->assertNotFalse(json_encode($r));
    }

    /* --- tau ------------------------------------------------------------ */

    public function testTauSinTiempoEsCero(): void
    {
        $f = ['participacion' => 'permanente', 'e_minutos' => null, 'f_veces_dia' => null];
        $this->assertSame(0.0, RestriccionProyectoService::tauDe($f, null));
    }

    public function testTauPermanenteEsElTiempoEnHoras(): void
    {
        $f = ['participacion' => 'permanente', 'e_minutos' => null, 'f_veces_dia' => null];
        $this->assertEqualsWithDelta(1.5, RestriccionProyectoService::tauDe($f, 90.0), self::TOL);
    }
}