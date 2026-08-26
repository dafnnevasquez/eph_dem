<?php
declare(strict_types=1);

/**
 * Helper para respuestas JSON estándar.
 */
class Response
{
    public static function ok($datos = null, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['ok' => true];
        if ($datos !== null) $payload['datos'] = $datos;
        echo json_encode($payload);
        exit;
    }

    public static function error(string $mensaje, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $mensaje]);
        exit;
    }

    public static function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::error('Método no permitido.', 405);
        }
    }

    public static function soloGet(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            self::error('Método no permitido.', 405);
        }
    }

    public static function input(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
}