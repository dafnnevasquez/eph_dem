<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PabellonesBoxesService;
use App\Services\EquipamientoKitService;
use App\Services\EquipamientoTipo5Service;
use App\Services\EquipamientoTipo6Service;
use App\Services\EquipamientoAgregadorService;
use App\Services\EquipamientoVistasService;
use App\Services\UrpaService;

class DemandaController extends Controller
{
    public function __construct(
        private PabellonesBoxesService     $pabellonesService,
        private EquipamientoAgregadorService $agregadorService,
        private EquipamientoVistasService  $vistasService,
        private UrpaService                $urpaService,
    ) {}

    public function show(Request $request)
    {
        $request->validate(['proyecto_id' => 'required|integer|min:1']);

        $datos = \Illuminate\Support\Facades\DB::table('EPHAC_Proyecto_Demanda as pd')
            ->join('EPHAC_Prestaciones as p', 'p.id_prestacion', '=', 'pd.prestacion_id')
            ->where('pd.proyecto_id', $request->proyecto_id)
            ->select('p.id_prestacion', 'p.codigo_fonasa', 'p.nombre_prestacion',
                     'pd.demanda_anual', 'pd.dias_laborales', 'pd.disponibilidad', 'pd.jornada_efectiva')
            ->get();

        return response()->json(['ok' => true, 'datos' => $datos]);
    }

    public function calcular(Request $request)
    {
        $request->validate([
            'proyecto_id'                  => 'required|integer|min:1',
            'filas'                        => 'required|array|min:1',
            'filas.*.prestacion_id'        => 'required|integer|min:1',
            'filas.*.demanda_anual'        => 'required|numeric|min:0',
            'filas.*.dias_laborales'       => 'required|numeric|min:1|max:366',
            'filas.*.disponibilidad'       => 'required|numeric|min:0.01|max:1',
            'filas.*.jornada_efectiva'     => 'required|numeric|min:0.1|max:24',
        ]);

        $proyectoId = (int)$request->proyecto_id;

        // 1. Guardar demanda
        $guardadas = $this->pabellonesService->guardarDemanda($proyectoId, $request->filas);

        // 2. Calcular pabellones y boxes
        $pabellones = $this->pabellonesService->calcularPabellones($proyectoId);
        $boxes      = $this->pabellonesService->calcularBoxes($proyectoId);

        // 3. Calcular equipamiento consolidado
        $equipamiento = $this->agregadorService->calcular($proyectoId);

        // 4. Vistas por recinto
        $vistas = $this->vistasService->calcular($equipamiento);

        // 5. URPA
        $urpa = $this->urpaService->calcular((int)$pabellones['pabellones_total']);

        return response()->json([
            'ok'    => true,
            'datos' => [
                'proyecto_id'     => $proyectoId,
                'filas_guardadas' => $guardadas,
                'pabellones'      => [
                    'urgencia'               => $pabellones['pabellones_urgencia'],
                    'electivo'               => $pabellones['pabellones_electivo'],
                    'total'                  => $pabellones['pabellones_total'],
                    'fraccion_urgencia'      => $pabellones['fraccion_urgencia'],
                    'fraccion_electivo'      => $pabellones['fraccion_electivo'],
                    'detalle'                => $pabellones['detalle'],
                    'pabellones_por_recinto' => $pabellones['pabellones_por_recinto'],
                ],
                'boxes' => [
                    'total'       => $boxes['boxes_total'],
                    'por_subarea' => $boxes['boxes_por_subarea'],
                    'detalle'     => $boxes['detalle'],
                    'por_recinto' => $boxes['boxes_por_recinto'],
                ],
                'equipamiento' => [
                    'equipos'           => $equipamiento['equipos'],
                    'por_recinto'       => $vistas['por_recinto'],
                    'demanda_compartida'=> $vistas['demanda_compartida'],
                ],
                'urpa' => $urpa,
            ],
        ]);
    }

    public function resultados(Request $request, $proyecto)
    {
        $usuarioId = $request->query('usuario_id');
        $url = env('PHP_LEGACY_URL') . "/obtener_resultados_proyecto.php?proyecto_id={$proyecto}&usuario_id={$usuarioId}";
        $response = \Illuminate\Support\Facades\Http::get($url);
        if (!$response->successful()) {
            return response()->json(['ok' => false, 'error' => 'Error al obtener resultados.'], 500);
        }
        return response()->json($response->json());
    }
}