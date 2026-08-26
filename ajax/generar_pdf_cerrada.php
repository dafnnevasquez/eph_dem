<?php

declare(strict_types=1);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * ajax/generar_pdf_cerrada.php
 *
 * Endpoint: GET
 * Params:
 *   proyecto_id : int, obligatorio, > 0
 *   nombre      : string, opcional (cosmetico, nombre de archivo/encabezado)
 *
 * Recalcula el equipamiento desde proyecto_id (la demanda ya esta
 * persistida en BD), igual que generar_xls_cerrada.php. No recibe filas
 * ni llama a GuardarDemanda.
 *
 * Genera un .pdf con FPDF y fuerza la descarga.
 *
 * Errores tempranos: texto plano (NO JSON) + exit.
 * =====================================================================
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

/**
 * Responde con texto plano y termina.
 */
function ephdem_pdf_error(int $http, string $mensaje): void
{
    http_response_code($http);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

/* --- Solo GET -------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    ephdem_pdf_error(405, 'Metodo no permitido. Usar GET.');
}

/* --- Parametros -------------------------------------------------------- */
$proyecto_id = isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0;
$nombre      = isset($_GET['nombre']) && trim((string)$_GET['nombre']) !== ''
    ? (string)$_GET['nombre']
    : 'Proyecto';

if ($proyecto_id <= 0) {
    ephdem_pdf_error(400, 'proyecto_id es obligatorio y debe ser > 0.');
}

/* --- Conexion ---
 * Servidor: SIGEM ya inyecta su propio $conn.
 * Local: si existe config/database.local.php (gitignored) lo usa para
 * definir $conn; si no existe, cae al conector propio de SIGEM-UV
 * (funciones_sigemuv_C_BaseDatos.php / SIGEM_UV_C_Nueva_Conexion). */
if (!isset($conn) || !($conn instanceof \mysqli)) {
    $localConfig = __DIR__ . '/../config/database.local.php';
    if (file_exists($localConfig)) {
        require_once $localConfig;
    } else {
        require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';
        $conn = SIGEM_UV_C_Nueva_Conexion();
    }
}

mysqli_set_charset($conn, 'utf8mb4');

/* --- Carga de funciones del modulo y libreria PDF ----------------------- */
require_once __DIR__ . '/../calculo_equipamiento.php';
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

/* --- Recalculo ----------------------------------------------------- */
try {
    $pab    = SIGEM_EPHDEM_CERRADA_CalcularPabellones($conn, $proyecto_id);
    $box    = SIGEM_EPHDEM_CERRADA_CalcularBoxes($conn, $proyecto_id);
    $equip  = SIGEM_EPHDEM_CERRADA_CalcularEquipamiento($conn, $proyecto_id);
    $vistas = SIGEM_EPHDEM_CERRADA_CalcularEquipamientoPorRecinto($conn, $equip);
} catch (\Throwable $e) {
    mysqli_close($conn);
    ephdem_pdf_error(500, 'Error en el calculo: ' . $e->getMessage());
}

mysqli_close($conn);

/* --- Clase PDF con helpers de tabla --------------------------------- */
class PDF_Reporte extends FPDF
{
    public function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }

    public function FilaTabla($nombre, $cantidad, $wNombre = 140, $wCant = 30, $lh = 6)
    {
        $nombre = utf8_decode($nombre);
        $nLineas = max(1, ceil($this->GetStringWidth($nombre) / ($wNombre - 4)));
        $h = $lh * $nLineas;
        $this->CheckPageBreak($h);
        $x = $this->GetX();
        $y = $this->GetY();
        $this->Rect($x, $y, $wNombre, $h);
        $this->Rect($x + $wNombre, $y, $wCant, $h);
        $this->MultiCell($wNombre, $lh, $nombre, 0, 'L');
        $this->SetXY($x + $wNombre, $y);
        $this->Cell($wCant, $h, (string)$cantidad, 0, 0, 'C');
        $this->SetXY($x, $y + $h);
    }

    public function CabeceraTabla($labelIzq, $labelDer, $wNombre = 140, $wCant = 30)
    {
        $this->CheckPageBreak(8);
        $this->SetFillColor(0, 60, 88);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wNombre, 8, utf8_decode($labelIzq), 1, 0, 'L', true);
        $this->Cell($wCant, 8, utf8_decode($labelDer), 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
    }
}

/* --- Construccion del PDF -------------------------------------------- */
$pdf = new PDF_Reporte('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

/* --- Portada / membrete --- */
$logoPath = '/home4/csi84990/public_html/_general/logos/LOGO SIGEM-UV HORIZONTAL.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 10, 60);
}
$pdf->SetY(35);
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(0, 60, 88);
$pdf->Cell(0, 10, utf8_decode('INFORME DE EQUIPAMIENTO MÉDICO'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, utf8_decode('Estudio de Preinversión Hospitalaria - Atención Cerrada'), 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, utf8_decode('Proyecto: ' . $nombre), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Generado el ' . date('d-m-Y H:i'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(6);

/* --- Resumen ejecutivo --- */
$totalEquipos = array_sum(array_column($equip['equipos'], 'cantidad'));
$resumenTexto = "Este informe presenta el resultado del calculo de equipamiento medico " .
    "para el proyecto \"$nombre\", segun la metodologia EEMM (Estimacion de Equipos " .
    "Medicos segun Demanda). Se estimo un total de $totalEquipos unidades de equipamiento, " .
    "distribuidas en {$pab['pabellones_total']} pabellones quirurgicos y {$box['boxes_total']} boxes/cubiculos " .
    "de cuidados criticos (UCI/UTI).";
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('1) Resumen Ejecutivo'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 6, utf8_decode($resumenTexto));
$pdf->Ln(4);

/* --- Tabla resumen de equipos (total) --- */
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('2) Resumen de Equipos (Total)'), 0, 1, 'L');
$equiposOrdenados = $equip['equipos'];
usort($equiposOrdenados, static function ($a, $b) {
    return $b['cantidad'] <=> $a['cantidad'];
});
$pdf->CabeceraTabla('Equipo', 'Cantidad');
if (empty($equiposOrdenados)) {
    $pdf->FilaTabla('(Sin equipamiento calculado)', '');
} else {
    foreach ($equiposOrdenados as $e) {
        $pdf->FilaTabla($e['nombre_equipo'], $e['cantidad']);
    }
}
$pdf->Ln(6);

/* --- Desglose por recinto --- */
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('3) Desglose por Recinto'), 0, 1, 'L');
foreach ($vistas['por_recinto'] as $rid => $rec) {
    if (empty($rec['equipos']) && empty($rec['estacion_enfermeria'])) {
        continue;
    }
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->CheckPageBreak(10);
    $pdf->Ln(2);
    $pdf->Cell(0, 7, utf8_decode($rec['nombre_recinto']), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 9);
    if (!empty($rec['equipos'])) {
        $pdf->CabeceraTabla('Equipo', 'Cantidad');
        foreach ($rec['equipos'] as $e) {
            $pdf->FilaTabla($e['nombre_equipo'], $e['cantidad']);
        }
    }
    if (!empty($rec['estacion_enfermeria'])) {
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'BI', 9);
        $pdf->Cell(0, 6, utf8_decode('Estacion de Enfermeria (Norma UPC)'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->CabeceraTabla('Equipo', 'Cantidad');
        foreach ($rec['estacion_enfermeria'] as $e) {
            $pdf->FilaTabla($e['nombre_equipo'], $e['cantidad']);
        }
    }
    $pdf->Ln(4);
}

/* --- Demanda compartida --- */
if (!empty($vistas['demanda_compartida'])) {
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode('4) Equipos de Demanda Compartida (multi-recinto)'), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->MultiCell(0, 5, utf8_decode(
        'Estos equipos reciben demanda de mas de un recinto y no se atribuyen a uno solo; ' .
        'su cantidad es el total real.'
    ));
    $pdf->Ln(2);
    $pdf->CabeceraTabla('Equipo / Recintos involucrados', 'Cantidad', 140, 30);
    foreach ($vistas['demanda_compartida'] as $item) {
        $recintosStr = implode(', ', array_map(static function ($r) {
            return $r['nombre_recinto'];
        }, $item['recintos_involucrados']));
        $texto = $item['nombre_equipo'] . ' (' . $recintosStr . ')';
        $pdf->FilaTabla($texto, $item['cantidad']);
    }
}

/* --- Formula EEMM y fuentes --- */
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('5) Formula de Calculo (EEMM)'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$formula = "DEMANDA EEMM = (Demanda Anual / Dias Laborales) / [ (60 / Tiempo Procedimiento) x Disponibilidad x Jornada Efectiva ]\n\n" .
    "Donde:\n" .
    "- Demanda Anual: cantidad total de atenciones proyectadas.\n" .
    "- Dias Laborales: dias efectivos de operacion al ano.\n" .
    "- Tiempo Procedimiento: duracion en minutos del procedimiento.\n" .
    "- Disponibilidad: porcentaje de disponibilidad real del equipo (decimal).\n" .
    "- Jornada Efectiva: horas de jornada efectiva de trabajo.\n\n" .
    "Formula propuesta por la Ing. Clinica Eyleen Spencer Yates.";
$pdf->MultiCell(0, 5, utf8_decode($formula));
$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, utf8_decode('Fuentes'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 8);
$fuentes = "1) Guia Metodologica para Estudios Preinversionales Hospitalarios (MINSAL).\n" .
    "2) Decreto 58 - Normas Tecnicas Basicas.\n" .
    "3) Anexo 1 NTB de Autorizacion Sanitaria para Atencion Cerrada.\n" .
    "4) Guias para el Diseno de Establecimientos Hospitalarios (MINSAL).";
$pdf->MultiCell(0, 5, utf8_decode($fuentes));

/* --- Salida ---------------------------------------------------------- */
$filename = 'Informe_EPH_Cerrada_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre) . '_' . date('YmdHis') . '.pdf';
$pdf->Output('D', $filename);
exit;
