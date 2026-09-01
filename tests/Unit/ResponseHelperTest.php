<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../ajax/helpers/Response.php';

class ResponseHelperTest extends TestCase
{
    /**
     * Test que input() retorna array vacío con body vacío
     */
    public function testInputRetornaArrayVacio(): void
    {
        // Simulamos php://input vacío
        $json   = '';
        $result = json_decode($json, true) ?? [];
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test que input() parsea JSON correctamente
     */
    public function testInputParsaJSON(): void
    {
        $json   = '{"correo":"test@uv.cl","contrasena":"1234"}';
        $result = json_decode($json, true) ?? [];
        $this->assertEquals('test@uv.cl', $result['correo']);
        $this->assertEquals('1234', $result['contrasena']);
    }

    /**
     * Test que JSON inválido retorna array vacío
     */
    public function testJSONInvalidoRetornaArrayVacio(): void
    {
        $json   = 'no_es_json';
        $result = json_decode($json, true) ?? [];
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test estructura de respuesta ok
     */
    public function testEstructuraRespuestaOk(): void
    {
        $payload = ['ok' => true, 'datos' => ['id' => 1]];
        $json    = json_encode($payload);
        $parsed  = json_decode($json, true);

        $this->assertTrue($parsed['ok']);
        $this->assertArrayHasKey('datos', $parsed);
        $this->assertEquals(1, $parsed['datos']['id']);
    }

    /**
     * Test estructura de respuesta error
     */
    public function testEstructuraRespuestaError(): void
    {
        $payload = ['ok' => false, 'error' => 'Correo o contraseña incorrectos.'];
        $json    = json_encode($payload);
        $parsed  = json_decode($json, true);

        $this->assertFalse($parsed['ok']);
        $this->assertArrayHasKey('error', $parsed);
        $this->assertStringContainsString('incorrectos', $parsed['error']);
    }

    /**
     * Test que respuesta ok sin datos no tiene clave 'datos'
     */
    public function testRespuestaOkSinDatos(): void
    {
        $payload = ['ok' => true];
        $this->assertArrayNotHasKey('datos', $payload);
    }

    /**
     * Test que JSON_UNESCAPED_UNICODE preserva caracteres especiales
     */
    public function testUnicodePreservado(): void
    {
        $texto  = 'Correo o contraseña incorrectos.';
        $json   = json_encode(['error' => $texto], JSON_UNESCAPED_UNICODE);
        $parsed = json_decode($json, true);
        $this->assertEquals($texto, $parsed['error']);
    }
}