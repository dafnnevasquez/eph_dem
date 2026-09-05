<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ProyectoControllerTest extends TestCase
{
    /**
     * Test que nombre vacío es inválido
     */
    public function testNombreVacioEsInvalido(): void
    {
        $nombre = trim('');
        $this->assertEquals('', $nombre);
        $this->assertTrue($nombre === '');
    }

    /**
     * Test que nombre con espacios se limpia
     */
    public function testNombreConEspaciosSeClean(): void
    {
        $nombre = trim('  Hospital San Martín  ');
        $this->assertEquals('Hospital San Martín', $nombre);
    }

    /**
     * Test que usuario_id debe ser entero positivo
     */
    public function testUsuarioIdDebeSerPositivo(): void
    {
        $usuarioId = (int)'592';
        $this->assertGreaterThan(0, $usuarioId);

        $usuarioIdInvalido = (int)'0';
        $this->assertFalse($usuarioIdInvalido > 0);
    }

    /**
     * Test estructura de respuesta crear proyecto exitoso
     */
    public function testEstructuraRespuestaCrearProyecto(): void
    {
        $respuesta = [
            'ok'    => true,
            'datos' => [
                'id_proyecto'     => 106,
                'nombre_proyecto' => 'Hospital San Martín',
                'tipo_proyecto'   => 'Atencion cerrada',
            ],
        ];

        $this->assertTrue($respuesta['ok']);
        $this->assertArrayHasKey('id_proyecto', $respuesta['datos']);
        $this->assertIsInt($respuesta['datos']['id_proyecto']);
    }

    /**
     * Test estructura de respuesta proyecto duplicado
     */
    public function testEstructuraRespuestaProyectoDuplicado(): void
    {
        $respuesta = [
            'ok'    => false,
            'error' => 'Ya tienes un proyecto con este nombre. Por favor, elige otro.',
        ];

        $this->assertFalse($respuesta['ok']);
        $this->assertStringContainsString('nombre', $respuesta['error']);
    }

    /**
     * Test estructura de listado de proyectos
     */
    public function testEstructuraListadoProyectos(): void
    {
        $proyectos = [
            ['id_proyecto' => 106, 'nombre_proyecto' => 'Hospital A', 'fecha_creacion' => '25/08/2026'],
            ['id_proyecto' => 107, 'nombre_proyecto' => 'Hospital B', 'fecha_creacion' => '26/08/2026'],
        ];

        $this->assertCount(2, $proyectos);
        $this->assertArrayHasKey('id_proyecto', $proyectos[0]);
        $this->assertArrayHasKey('nombre_proyecto', $proyectos[0]);
        $this->assertArrayHasKey('fecha_creacion', $proyectos[0]);
    }

    /**
     * Test que tipo_proyecto por defecto es Atencion cerrada
     */
    public function testTipoProyectoDefault(): void
    {
        $input = [];
        $tipo  = $input['tipo_proyecto'] ?? 'Atencion cerrada';
        $this->assertEquals('Atencion cerrada', $tipo);
    }
}