<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function excel(Request $request, $proyecto)
    {
        $nombre = $request->query('nombre', 'Proyecto');
        $base   = env('PHP_LEGACY_URL', 'https://sigem-uv.cl/__v2/modulo_eph/ajax');
        $url    = "{$base}/generar_xls_cerrada.php?proyecto_id={$proyecto}&nombre=" . urlencode($nombre);

        return redirect($url);
    }

    public function pdf(Request $request, $proyecto)
    {
        $nombre = $request->query('nombre', 'Proyecto');
        $base   = env('PHP_LEGACY_URL', 'https://sigem-uv.cl/__v2/modulo_eph/ajax');
        $url    = "{$base}/generar_pdf_cerrada.php?proyecto_id={$proyecto}&nombre=" . urlencode($nombre);

        return redirect($url);
    }
}