<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestacionController extends Controller
{
    public function index()
    {
        $prestaciones = DB::table('EPHAC_Prestaciones as p')
            ->join('EPHAC_Recinto_Estandar as r', 'p.id_recinto', '=', 'r.id_recinto')
            ->select(
                'p.id_prestacion',
                'p.codigo_fonasa',
                'p.nombre_prestacion',
                'p.area_hospitalaria as area',
                'p.subarea_hospitalaria as subarea',
                'r.nombre_recinto',
                'r.tiempo_procedimiento'
            )
            ->orderBy('p.nombre_prestacion')
            ->get();

        return response()->json([
            'ok'    => true,
            'datos' => $prestaciones,
        ]);
    }
}