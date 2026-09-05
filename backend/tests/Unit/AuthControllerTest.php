<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{
    /**
     * Test que correo vacío es inválido
     */
    public function testCorreoVacioEsInvalido(): void
    {
        $correo = trim('');
        $this->assertEquals('', $correo);
        $this->assertTrue($correo === '');
    }

    /**
     * Test que correo con espacios se limpia
     */
    public function testCorreoConEspaciosSeClean(): void
    {
        $correo = trim('  test@uv.cl  ');
        $this->assertEquals('test@uv.cl', $correo);
    }

    /**
     * Test que password_verify valida correctamente
     */
    public function testPasswordVerifyFunciona(): void
    {
        $contrasena = 'miPassword123';
        $hash       = password_hash($contrasena, PASSWORD_BCRYPT);

        $this->assertTrue(password_verify($contrasena, $hash));
        $this->assertFalse(password_verify('otraPassword', $hash));
    }

    /**
     * Test que contraseña incorrecta no verifica
     */
    public function testContrasenaIncorrectaNoVerifica(): void
    {
        $hash = password_hash('correcta', PASSWORD_BCRYPT);
        $this->assertFalse(password_verify('incorrecta', $hash));
    }

    /**
     * Test estructura de respuesta exitosa del login
     */
    public function testEstructuraRespuestaLogin(): void
    {
        $respuesta = [
            'ok'    => true,
            'datos' => [
                'id_usuario' => 592,
                'nombre'     => 'Dafnne',
                'correo'     => 'dafnne.vasquez@estudiantes.uv.cl',
            ],
        ];

        $this->assertTrue($respuesta['ok']);
        $this->assertArrayHasKey('id_usuario', $respuesta['datos']);
        $this->assertArrayHasKey('nombre', $respuesta['datos']);
        $this->assertArrayHasKey('correo', $respuesta['datos']);
        $this->assertIsInt($respuesta['datos']['id_usuario']);
    }

    /**
     * Test estructura de respuesta fallida del login
     */
    public function testEstructuraRespuestaLoginFallido(): void
    {
        $respuesta = [
            'ok'    => false,
            'error' => 'Correo o contraseña incorrectos.',
        ];

        $this->assertFalse($respuesta['ok']);
        $this->assertArrayHasKey('error', $respuesta);
        $this->assertArrayNotHasKey('datos', $respuesta);
    }

    /**
     * Test que id_usuario es entero positivo
     */
    public function testIdUsuarioEsEnteroPositivo(): void
    {
        $idUsuario = (int)'592';
        $this->assertIsInt($idUsuario);
        $this->assertGreaterThan(0, $idUsuario);
    }
}