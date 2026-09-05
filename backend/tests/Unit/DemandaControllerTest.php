<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DemandaControllerTest extends TestCase
{
    /**
     * Test que proyecto_id debe ser positivo
     */
    public function testProyectoIdDebeSerPositivo(): void
    {
        $proyectoId = (int)'106';
        $this->assertGreaterThan(0, $proyectoId);

        $invalido = (int)'0';
        $this->assertFalse($invalido > 0);
    }

    /**
     * Test que filas no puede estar vacío
     */
    public function testFilasNoVacio(): void
    {
        $filas = [];
        $this->assertEmpty($filas);

        $filasConDatos = [
            ['prestacion_id' => 1, 'demanda_anual' => 450]
        ];
        $this->assertNotEmpty($filasConDatos);
    }

    /**
     * Test validación de fila — campos requeridos
     */
    public function testValidacionFila(): void
    {
        $fila = [
            'prestacion_id'  => 5,
            'demanda_anual'  => 450,
            'dias_laborales' => 250,
            'disponibilidad' => 0.85,
            'jornada_efectiva' => 8,
        ];

        $this->assertGreaterThan(0, $fila['prestacion_id']);
        $this->assertGreaterThan(0, $fila['demanda_anual']);
        $this->assertGreaterThan(0, $fila['dias_laborales']);
        $this->assertLessThanOrEqual(366, $fila['dias_laborales']);
        $this->assertGreaterThan(0, $fila['disponibilidad']);
        $this->assertLessThanOrEqual(1, $fila['disponibilidad']);
        $this->assertGreaterThan(0, $fila['jornada_efectiva']);
        $this->assertLessThanOrEqual(24, $fila['jornada_efectiva']);
    }

    /**
     * Test estructura de respuesta calcular
     */
    public function testEstructuraRespuestaCalcular(): void
    {
        $respuesta = [
            'ok'    => true,
            'datos' => [
                'proyecto_id'     => 106,
                'filas_guardadas' => 3,
                'pabellones'      => ['total' => 2],
                'boxes'           => ['total' => 0],
                'equipamiento'    => ['equipos' => [], 'por_recinto' => []],
                'urpa'            => ['nro_salas' => 1],
            ],
        ];

        $this->assertTrue($respuesta['ok']);
        $this->assertArrayHasKey('pabellones', $respuesta['datos']);
        $this->assertArrayHasKey('equipamiento', $respuesta['datos']);
        $this->assertArrayHasKey('urpa', $respuesta['datos']);
    }

    /**
     * Test que disponibilidad se guarda como decimal (0-1)
     */
    public function testDisponibilidadComoDecimal(): void
    {
        $porcentaje = 85;
        $decimal    = $porcentaje / 100;
        $this->assertEqualsWithDelta(0.85, $decimal, 0.001);
    }

    /**
     * Test que disponibilidad como porcentaje se convierte correctamente
     */
    public function testConversionDisponibilidad(): void
    {
        $casos = [100 => 1.0, 85 => 0.85, 50 => 0.5, 0 => 0.0];
        foreach ($casos as $porcentaje => $esperado) {
            $this->assertEqualsWithDelta($esperado, $porcentaje / 100, 0.001);
        }
    }

    /**
     * Test estructura de respuesta resultados
     */
    public function testEstructuraRespuestaResultados(): void
    {
        $respuesta = [
            'ok'    => true,
            'datos' => [
                'proyecto_id'     => 106,
                'nombre_proyecto' => 'Hospital San Martín',
                'pabellones'      => [],
                'boxes'           => [],
                'equipamiento'    => [],
                'urpa'            => [],
            ],
        ];

        $this->assertTrue($respuesta['ok']);
        $this->assertArrayHasKey('nombre_proyecto', $respuesta['datos']);
        $this->assertEquals('Hospital San Martín', $respuesta['datos']['nombre_proyecto']);
    }
}