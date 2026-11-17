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

    /* --- Equipamiento completo (tipos 1 a 6) y URPA ------------------- */

    private function configCalculo(): array
    {
        return [
            'mapa_regla_recinto' => ['uci' => 2, 'enfermeria_uci' => 2, 'uti' => 1, 'enfermeria_uti' => 1],
            'divisores_upc'      => ['uci' => 6, 'uti' => 12, 'enfermeria_uci' => 6, 'enfermeria_uti' => 12],
            'urpa_camillas_por_pabellon' => 2,
            'urpa_max_camillas'          => 12,
        ];
    }

    /** Fuentes como las entrega el agregador para: UTI 2 boxes, UCI 7, P. menor 2, P. mayor 3. */
    private function fuentesEjemplo(): array
    {
        return [
            'kit' => ['equipos' => [
                ['equipo_id' => 10, 'nombre_equipo' => 'Monitor', 'cantidad' => 13, 'por_recinto' => [
                    ['recinto_id' => 2, 'cantidad_base' => 1, 'nro_recintos' => 7, 'subtotal' => 7],
                    ['recinto_id' => 4, 'cantidad_base' => 2, 'nro_recintos' => 3, 'subtotal' => 6],
                ]],
            ]],
            'tipo2' => ['equipos' => [
                ['equipo_id' => 20, 'nombre_equipo' => 'Ventilador', 'cantidad' => 9, 'por_recinto' => [
                    ['recinto_id' => 1, 'nro_recintos' => 2], ['recinto_id' => 2, 'nro_recintos' => 7],
                ]],
            ]],
            'tipo6' => ['equipos' => [
                ['equipo_id' => 10, 'nombre_equipo' => 'Monitor', 'cantidad' => 2, 'por_regla' => [
                    ['regla' => 'uci', 'cantidad_base' => 1, 'dotaciones' => 2, 'subtotal' => 2],
                ]],
                ['equipo_id' => 30, 'nombre_equipo' => 'Bomba', 'cantidad' => 4, 'por_regla' => [
                    ['regla' => 'uti', 'cantidad_base' => 3, 'dotaciones' => 1, 'subtotal' => 3],
                    ['regla' => 'enfermeria_uti', 'cantidad_base' => 1, 'dotaciones' => 1, 'subtotal' => 1],
                ]],
            ]],
            'tipo5' => ['equipos' => [
                ['equipo_id' => 10, 'nombre_equipo' => 'Monitor', 'cantidad' => 9, 'por_prestacion' => [['prestacion_id' => 40, 'requerimiento_fraccion' => 8.5]]],
                ['equipo_id' => 40, 'nombre_equipo' => 'Ecógrafo', 'cantidad' => 5, 'por_prestacion' => [
                    ['prestacion_id' => 40, 'requerimiento_fraccion' => 3.0], ['prestacion_id' => 41, 'requerimiento_fraccion' => 1.5],
                ]],
            ]],
        ];
    }

    public function testComponerConConteoNormalReproduceElAgregador(): void
    {
        $c = RestriccionProyectoService::componerEquipamiento(
            $this->fuentesEjemplo(), [1 => 2, 2 => 7, 3 => 2, 4 => 3], [], [40 => 4, 41 => 3], $this->configCalculo()
        );
        // Monitor: UCI max(kit 7, norma 2) = 7 + P. mayor max(kit 6, demanda 9) = 9 -> 16
        $this->assertSame(16, $c[10]['cantidad']);
        $this->assertSame(9, $c[20]['cantidad']);   // tipo 2: 2 + 7
        $this->assertSame(4, $c[30]['cantidad']);   // norma UTI 3 + enfermeria 1
        $this->assertSame(5, $c[40]['cantidad']);   // demanda compartida
    }

    public function testComponerConModulosRestringidosBajaTodosLosTipos(): void
    {
        $c = RestriccionProyectoService::componerEquipamiento(
            $this->fuentesEjemplo(), [1 => 1, 2 => 4, 3 => 2, 4 => 2], [10 => 6, 40 => 4], [40 => 4, 41 => 3], $this->configCalculo()
        );
        // Monitor: UCI max(kit 4, norma techo(4/6)=1) = 4 + P. mayor max(kit 4, demanda 6) = 6 -> 10
        $this->assertSame(10, $c[10]['cantidad']);
        $this->assertSame(5, $c[20]['cantidad']);   // 1 + 4
        $this->assertSame(4, $c[30]['cantidad']);   // techo(1/12) = 1 dotacion: 3 + 1
        $this->assertSame(4, $c[40]['cantidad']);
    }

    public function testRecintoEnCeroQuitaSusEquiposFijos(): void
    {
        $c = RestriccionProyectoService::componerEquipamiento(
            $this->fuentesEjemplo(), [1 => 0, 2 => 0, 3 => 0, 4 => 0], [10 => 0, 40 => 0], [40 => 4, 41 => 3], $this->configCalculo()
        );
        foreach ([10, 20, 30, 40] as $id) {
            $this->assertSame(0, $c[$id]['cantidad']);
        }
    }

    public function testUrpaConPabellonesRestringidos(): void
    {
        $urpa = ['nro_pabellones' => 5, 'nro_camillas' => 10, 'nro_salas' => 1,
                 'equipos_sala'    => [['equipo_id' => 33, 'nombre_equipo' => 'Carro de paro', 'cantidad_base' => 1, 'cantidad' => 1]],
                 'equipos_camilla' => [['equipo_id' => 27, 'nombre_equipo' => 'Camilla', 'cantidad_base' => 1, 'cantidad' => 10]]];
        $u = RestriccionProyectoService::urpaRestringida($urpa, 3, $this->configCalculo());
        $this->assertSame(6, $u['restringido']['camillas']);
        $this->assertSame(1, $u['restringido']['salas']);
        $this->assertSame(6, $u['equipos'][0]['restringido']);   // 27 camilla
        $this->assertSame(1, $u['equipos'][1]['restringido']);   // 33 sala
        $cero = RestriccionProyectoService::urpaRestringida($urpa, 0, $this->configCalculo());
        $this->assertSame(0, $cero['restringido']['salas']);
        $this->assertSame(0, $cero['equipos'][1]['restringido']);
    }

    public function testCalcularIncluyeEquipamientoCompletoYUrpa(): void
    {
        // Pabellon menor: Q = 1000 h, A = 500 h -> alpha 0,5; 3 pabellones (2,3) -> techo(1,15) = 2.
        $equipamiento = [
            'normal'  => [
                ['equipo_id' => 10, 'nombre_equipo' => 'Lámpara', 'cantidad' => 6],
                ['equipo_id' => 50, 'nombre_equipo' => 'Electrobisturí', 'cantidad' => 3],
            ],
            'fuentes' => [
                'kit'   => ['equipos' => [['equipo_id' => 10, 'nombre_equipo' => 'Lámpara', 'cantidad' => 6,
                            'por_recinto' => [['recinto_id' => 3, 'cantidad_base' => 2, 'nro_recintos' => 3, 'subtotal' => 6]]]]],
                'tipo2' => ['equipos' => []],
                'tipo6' => ['equipos' => []],
                'tipo5' => ['equipos' => [['equipo_id' => 50, 'nombre_equipo' => 'Electrobisturí', 'cantidad' => 3,
                            'por_prestacion' => [['prestacion_id' => 20, 'requerimiento_fraccion' => 2.3]]]]],
            ],
            'tipos'  => [10 => 1, 50 => 5],
            'urpa'   => ['nro_pabellones' => 3, 'nro_camillas' => 6, 'nro_salas' => 1,
                         'equipos_sala'    => [['equipo_id' => 33, 'nombre_equipo' => 'Carro de paro', 'cantidad_base' => 1, 'cantidad' => 1]],
                         'equipos_camilla' => [['equipo_id' => 27, 'nombre_equipo' => 'Camilla', 'cantidad_base' => 1, 'cantidad' => 6]]],
            'config' => $this->configCalculo(),
        ];
        $r = RestriccionProyectoService::calcular(
            [$this->prestacion(20, 3, 'Pabellón menor', 60.0, 1000)],
            [['prestacion_id' => 20, 'tipo_rrhh' => 'TENS', 'participacion' => 'permanente', 'e_minutos' => null, 'f_veces_dia' => null]],
            [$this->grupo(3, 'TENS', 1, 10, 50, 1.0)],
            [3 => ['tipo' => 'pabellones', 'fraccion' => 2.3, 'cantidad' => 3]],
            $equipamiento['fuentes']['tipo5']['equipos'],
            $equipamiento
        );
        $this->assertSame(2, $this->recinto($r, 3)['modulos']['restringido']);
        $this->assertSame(4, $r['equipamiento'][0]['restringido']);   // kit tipo 1: 2 x 2 pabellones
        $this->assertSame(1, $r['equipamiento'][0]['tipo']);
        $this->assertSame(2, $r['equipamiento'][1]['restringido']);   // tipo 5: techo(0,5 x 2,3)
        $this->assertSame(9, $r['totales']['equipos_normal']);
        $this->assertSame(6, $r['totales']['equipos_restringido']);
        $this->assertSame(4, $r['urpa']['restringido']['camillas']);  // 2 x 2 pabellones
        $this->assertNotFalse(json_encode($r));
    }

    public function testSinRestriccionElEquipamientoQuedaIgual(): void
    {
        $f = $this->fuentesEjemplo();
        $normal = [];
        $c = RestriccionProyectoService::componerEquipamiento($f, [1 => 2, 2 => 7, 3 => 2, 4 => 3], [], [40 => 4, 41 => 3], $this->configCalculo());
        foreach ($c as $id => $x) {
            $normal[] = ['equipo_id' => $id, 'nombre_equipo' => $x['nombre'], 'cantidad' => $x['cantidad']];
        }
        $eq = ['normal' => $normal, 'fuentes' => $f, 'tipos' => [],
               'urpa' => ['nro_pabellones' => 5, 'nro_camillas' => 10, 'nro_salas' => 1, 'equipos_sala' => [], 'equipos_camilla' => []],
               'config' => $this->configCalculo()];
        $modulos = [1 => ['tipo' => 'boxes', 'fraccion' => 1.5, 'cantidad' => 2], 2 => ['tipo' => 'boxes', 'fraccion' => 6.2, 'cantidad' => 7],
                    3 => ['tipo' => 'pabellones', 'fraccion' => 1.4, 'cantidad' => 2], 4 => ['tipo' => 'pabellones', 'fraccion' => 2.6, 'cantidad' => 3]];
        $r = RestriccionProyectoService::calcular(
            [$this->prestacion(40, 4, 'Pabellón mayor', 60.0, 100), $this->prestacion(41, 3, 'Pabellón menor', 60.0, 100)],
            [], [], $modulos, $f['tipo5']['equipos'], $eq
        );
        foreach ($r['equipamiento'] as $e) {
            $this->assertSame($e['normal'], $e['restringido']);
        }
        $this->assertSame($r['totales']['equipos_normal'], $r['totales']['equipos_restringido']);
        $this->assertSame(10, $r['urpa']['restringido']['camillas']);
    }
}