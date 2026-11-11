<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../ajax/services/PrestacionPerfilService.php';

/** Pruebas de la Planilla A (logica pura, sin base de datos). */
class PrestacionPerfilServiceTest extends TestCase
{
    /** Prestaciones de ejemplo: id => nombre y tiempo en minutos. */
    private function prestaciones(): array
    {
        return [
            10 => ['nombre' => 'Hemodiálisis', 't_min' => 240.0],
            20 => ['nombre' => 'Cirugía',      't_min' => 60.0],
            30 => ['nombre' => 'Día cama UCI', 't_min' => 1440.0],
            40 => ['nombre' => 'Sin tiempo',   't_min' => null],
        ];
    }

    private function fila(array $extra = []): array
    {
        return array_merge([
            'prestacion_id' => 10, 'tipo_rrhh' => 'Enfermera/o',
            'participacion' => 'puntual', 'e_minutos' => 10, 'f_veces_dia' => 3,
        ], $extra);
    }

    /* --- dedicacion ----------------------------------------------------- */

    public function testDedicacionHemodialisisDelDocumento(): void
    {
        // 10 min, 3 veces, sesion de 4 h: phi = 0,125 (seccion 2.1).
        $this->assertEqualsWithDelta(0.125, PrestacionPerfilService::dedicacion(10.0, 3.0, 240.0), 0.0001);
    }

    public function testDedicacionVentilacionDelDocumento(): void
    {
        // 30 min, 4 veces al dia, 120 h: phi = 0,083.
        $this->assertEqualsWithDelta(0.0833, PrestacionPerfilService::dedicacion(30.0, 4.0, 7200.0), 0.0001);
    }

    /* --- validarFilas: casos validos ------------------------------------ */

    public function testListaVaciaEsValida(): void
    {
        $this->assertNull(PrestacionPerfilService::validarFilas([], $this->prestaciones()));
    }

    public function testPuntualValido(): void
    {
        $this->assertNull(PrestacionPerfilService::validarFilas([$this->fila()], $this->prestaciones()));
    }

    public function testPermanenteNoNecesitaEyF(): void
    {
        $f = ['prestacion_id' => 20, 'tipo_rrhh' => 'Cirujano/a', 'participacion' => 'permanente'];
        $this->assertNull(PrestacionPerfilService::validarFilas([$f], $this->prestaciones()));
    }

    public function testPermanenteSinTiempoEsValido(): void
    {
        $f = ['prestacion_id' => 40, 'tipo_rrhh' => 'TENS', 'participacion' => 'permanente'];
        $this->assertNull(PrestacionPerfilService::validarFilas([$f], $this->prestaciones()));
    }

    public function testMismoPerfilEnPrestacionesDistintas(): void
    {
        $this->assertNull(PrestacionPerfilService::validarFilas(
            [$this->fila(), $this->fila(['prestacion_id' => 30, 'e_minutos' => 15, 'f_veces_dia' => 6])],
            $this->prestaciones()
        ));
    }

    public function testDedicacionExactamenteUnoEsValida(): void
    {
        // Cirugia de 60 min con una intervencion de 60 min: phi = 1.
        $this->assertNull(PrestacionPerfilService::validarFilas(
            [$this->fila(['prestacion_id' => 20, 'e_minutos' => 60, 'f_veces_dia' => 1])],
            $this->prestaciones()
        ));
    }

    /* --- validarFilas: errores ------------------------------------------ */

    public function testPrestacionAjenaAlProyecto(): void
    {
        $this->assertSame('Una de las prestaciones no pertenece a este proyecto.',
            PrestacionPerfilService::validarFilas([$this->fila(['prestacion_id' => 99])], $this->prestaciones()));
    }

    public function testPerfilRepetidoEnLaMismaPrestacion(): void
    {
        $this->assertSame('El perfil «Enfermera/o» está repetido en «Hemodiálisis».',
            PrestacionPerfilService::validarFilas([$this->fila(), $this->fila(['e_minutos' => 5])], $this->prestaciones()));
    }

    public function testParticipacionInvalida(): void
    {
        $this->assertSame('Datos inválidos en «Hemodiálisis».',
            PrestacionPerfilService::validarFilas([$this->fila(['participacion' => 'a veces'])], $this->prestaciones()));
    }

    public function testPerfilVacio(): void
    {
        $this->assertSame('Datos inválidos en «Hemodiálisis».',
            PrestacionPerfilService::validarFilas([$this->fila(['tipo_rrhh' => '  '])], $this->prestaciones()));
    }

    public function testPuntualSinMinutos(): void
    {
        $error = PrestacionPerfilService::validarFilas([$this->fila(['e_minutos' => null])], $this->prestaciones());
        $this->assertStringContainsString('minutos por intervención', (string)$error);
    }

    public function testPuntualConVecesCero(): void
    {
        $error = PrestacionPerfilService::validarFilas([$this->fila(['f_veces_dia' => 0])], $this->prestaciones());
        $this->assertStringContainsString('veces al día', (string)$error);
    }

    public function testPuntualEnPrestacionSinTiempo(): void
    {
        $error = PrestacionPerfilService::validarFilas([$this->fila(['prestacion_id' => 40])], $this->prestaciones());
        $this->assertSame('«Sin tiempo» no tiene tiempo de procedimiento; solo admite participación permanente.', $error);
    }

    public function testDedicacionMayorQueUnoSeRechaza(): void
    {
        // Cirugia de 60 min con 30 min, 3 veces: phi = 1,5.
        $error = PrestacionPerfilService::validarFilas(
            [$this->fila(['prestacion_id' => 20, 'e_minutos' => 30, 'f_veces_dia' => 3])],
            $this->prestaciones()
        );
        $this->assertStringContainsString('1,50 y no puede ser mayor que 1', (string)$error);
    }

    public function testFilaQueNoEsArreglo(): void
    {
        $this->assertSame('Datos inválidos.', PrestacionPerfilService::validarFilas(['texto'], $this->prestaciones()));
    }

    /* --- armarPlanilla -------------------------------------------------- */

    public function testArmarPlanillaAgrupaPorRecinto(): void
    {
        $prestaciones = [
            ['id' => 20, 'codigo' => '1801001', 'nombre' => 'Cirugía', 'recinto_id' => 3, 'recinto' => 'Pabellón menor', 't_min' => 60.0, 'demanda' => 300],
            ['id' => 21, 'codigo' => '1801002', 'nombre' => 'Biopsia', 'recinto_id' => 3, 'recinto' => 'Pabellón menor', 't_min' => 30.0, 'demanda' => 100],
            ['id' => 30, 'codigo' => null,      'nombre' => 'Día cama UCI', 'recinto_id' => 2, 'recinto' => 'Cubículo UCI', 't_min' => 1440.0, 'demanda' => null],
        ];
        $catalogo = [
            ['prestacion_id' => '20', 'tipo_rrhh' => 'Cirujano/a',  'participacion' => 'permanente', 'e_minutos' => null,    'f_veces_dia' => null],
            ['prestacion_id' => '20', 'tipo_rrhh' => 'Enfermera/o', 'participacion' => 'puntual',    'e_minutos' => '10.00', 'f_veces_dia' => '3.00'],
        ];
        $grupos = [
            ['recinto_id' => 3, 'tipo_rrhh' => 'Cirujano/a'],
            ['recinto_id' => 3, 'tipo_rrhh' => 'Enfermera/o'],
            ['recinto_id' => 1, 'tipo_rrhh' => 'TENS'],
        ];

        $r = PrestacionPerfilService::armarPlanilla($prestaciones, $catalogo, $grupos);

        $this->assertSame(2, count($r));
        $this->assertSame('Pabellón menor', $r[0]['nombre']);
        $this->assertSame(['Cirujano/a', 'Enfermera/o'], $r[0]['perfiles_rrhh']);
        $this->assertSame(2, count($r[0]['prestaciones']));
        $this->assertSame(2, count($r[0]['prestaciones'][0]['perfiles']));
        $this->assertSame(10.0, $r[0]['prestaciones'][0]['perfiles'][1]['e_minutos']);
        $this->assertNull($r[0]['prestaciones'][0]['perfiles'][0]['e_minutos']);
        $this->assertSame([], $r[0]['prestaciones'][1]['perfiles']);
        $this->assertSame('Cubículo UCI', $r[1]['nombre']);
        $this->assertSame([], $r[1]['perfiles_rrhh']);
        $this->assertNotFalse(json_encode($r));
    }
}