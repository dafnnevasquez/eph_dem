<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../../../funciones_sigemuv_C_BaseDatos.php';

class AuthController
{
    public function login(): void
    {
        Response::soloPost();

        $input     = Response::input();
        $correo    = trim($input['correo']     ?? '');
        $contrasena = trim($input['contrasena'] ?? '');

        if ($correo === '' || $contrasena === '') {
            Response::error('Correo y contraseña son requeridos.', 400);
        }

        $conn = SIGEM_UV_C_Nueva_Conexion();
        if (!$conn) Response::error('No se pudo conectar a la base de datos.', 500);

        mysqli_set_charset($conn, 'utf8mb4');

        $stmt = mysqli_prepare($conn, "SELECT ID_Usuario, Nombre, Correo, Contrasena FROM SIGEM_Usuarios WHERE Correo = ? LIMIT 1");
        if (!$stmt) Response::error('Error interno del servidor.', 500);

        mysqli_stmt_bind_param($stmt, 's', $correo);
        mysqli_stmt_execute($stmt);
        $result  = mysqli_stmt_get_result($stmt);
        $usuario = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$usuario) Response::error('Correo o contraseña incorrectos.', 401);

        $valida = password_verify($contrasena, $usuario['Contrasena']);
        if (!$valida) Response::error('Correo o contraseña incorrectos.', 401);

        mysqli_close($conn);

        Response::ok([
            'id_usuario' => (int)$usuario['ID_Usuario'],
            'nombre'     => $usuario['Nombre'] ?? $usuario['Correo'],
            'correo'     => $usuario['Correo'],
        ]);
    }
}

// Despachar
header('Content-Type: application/json; charset=utf-8');
(new AuthController())->login();