<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'correo'     => 'required|email',
            'contrasena' => 'required|min:4',
        ]);

        $usuario = DB::table('SIGEM_UV_Usuarios')
            ->where('correo', $request->correo)
            ->first();

        if (!$usuario || !Hash::check($request->contrasena, $usuario->contrasena)) {
            return response()->json([
                'ok'    => false,
                'error' => 'Correo o contraseña incorrectos.',
            ], 401);
        }

        $token = bin2hex(random_bytes(32));

        return response()->json([
            'ok'    => true,
            'datos' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre'     => $usuario->nombre,
                'correo'     => $usuario->correo,
                'token'      => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }
}