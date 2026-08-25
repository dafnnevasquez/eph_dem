<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProyectoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|integer',
        ]);

        $proyectos = DB::table('EPHAC_Proyectos')
            ->where('id_usuario', $request->usuario_id)
            ->orderBy('id_proyecto', 'desc')
            ->get();

        return response()->json([
            'ok'    => true,
            'datos' => $proyectos,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_proyecto' => 'required|string|max:255',
            'tipo_proyecto'   => 'required|string',
            'usuario_id'      => 'required|integer',
        ]);

        // Validar unicidad por nombre + usuario
        $existe = DB::table('EPHAC_Proyectos')
            ->where('nombre_proyecto', $request->nombre_proyecto)
            ->where('id_usuario', $request->usuario_id)
            ->exists();

        if ($existe) {
            return response()->json([
                'ok'    => false,
                'error' => 'Ya existe un proyecto con ese nombre.',
            ], 422);
        }

        $id = DB::table('EPHAC_Proyectos')->insertGetId([
            'nombre_proyecto' => $request->nombre_proyecto,
            'tipo_proyecto'   => $request->tipo_proyecto,
            'id_usuario'      => $request->usuario_id,
            'fecha_creacion'  => now()->toDateString(),
        ]);

        return response()->json([
            'ok'    => true,
            'datos' => ['id_proyecto' => $id],
        ]);
    }
}