<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

function ephdem_pdf_error_abierta(int $http, string $mensaje): void
{
    http_response_code($http);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    ephdem_pdf_error_abierta(405, 'Metodo no permitido. Usar GET.');
}

$ID_Usuario    = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
$id_proyeccion = isset($_GET['id'])         ? (int)$_GET['id']         : 0;

if (!$ID_Usuario || !$id_proyeccion) {
    ephdem_pdf_error_abierta(400, 'usuario_id e id son obligatorios.');
}

require_once __DIR__ . '/../../lib/fpdf/fpdf.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) ephdem_pdf_error_abierta(500, 'Error al conectar a la base de datos.');
mysqli_set_charset($conn, 'utf8mb4');

$sql  = "SELECT NOMBRE_PROYECCION, DATOS_JSON FROM EPHDEM_PROYECCIONES_GUARDADAS WHERE ID_PROYECCION = ? AND USER_ID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $id_proyeccion, $ID_Usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$row) ephdem_pdf_error_abierta(404, 'Proyecto no encontrado.');

$nombre_proyecto = $row['NOMBRE_PROYECCION'];
$datos_json      = json_decode($row['DATOS_JSON'], true) ?? [];
$equiposSummary  = $datos_json['equipos_summary'] ?? [];
$recintoSummary  = $datos_json['recinto_summary'] ?? [];
$prestaciones    = $datos_json['prestaciones']    ?? [];

class PDF_Abierta extends FPDF
{
    public function CheckPageBreak(float $h): void
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) $this->AddPage();
    }

    public function FilaTabla(string $nombre, string $cantidad, float $wNombre = 140, float $wCant = 30, float $lh = 6): void    {
        $nombre  = utf8_decode($nombre);
        $nLineas = max(1, ceil($this->GetStringWidth($nombre) / ($wNombre - 4)));
        $h       = $lh * $nLineas;
        $this->CheckPageBreak($h);
        $x = $this->GetX(); $y = $this->GetY();
        $this->Rect($x, $y, $wNombre, $h);
        $this->Rect($x + $wNombre, $y, $wCant, $h);
        $this->MultiCell($wNombre, $lh, $nombre, 0, 'L');
        $this->SetXY($x + $wNombre, $y);
        $this->Cell($wCant, $h, (string)$cantidad, 0, 0, 'C');
        $this->SetXY($x, $y + $h);
    }

    public function CabeceraTabla(string $labelIzq, string $labelDer, float $wNombre = 140, float $wCant = 30): void
    {
        $this->CheckPageBreak(8);
        $this->SetFillColor(0, 60, 88);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($wNombre, 8, utf8_decode($labelIzq), 1, 0, 'L', true);
        $this->Cell($wCant,   8, utf8_decode($labelDer), 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
    }
}

$pdf = new PDF_Abierta('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

$logoPath = '/home4/csi84990/public_html/_general/logos/LOGO SIGEM-UV HORIZONTAL.png';
if (file_exists($logoPath)) $pdf->Image($logoPath, 15, 10, 60);
$pdf->SetY(35);
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(0, 60, 88);
$pdf->Cell(0, 10, utf8_decode('INFORME DE EQUIPAMIENTO MÉDICO'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, utf8_decode('Estudio de Preinversión Hospitalaria - Atención Abierta'), 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, utf8_decode('Proyecto: ' . $nombre_proyecto), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Generado el ' . date('d-m-Y H:i'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(6);

$totalEquipos      = array_sum($equiposSummary);
$totalPrestaciones = count($prestaciones);
$resumenTexto = "Este informe presenta el resultado del calculo de equipamiento medico " .
    "para el proyecto \"$nombre_proyecto\", segun la metodologia EEMM (Estimacion de Equipos " .
    "Medicos segun Demanda). Se estimo un total de $totalEquipos unidades de equipamiento, " .
    "distribuidas en base a $totalPrestaciones prestaciones de atencion abierta seleccionadas.";
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('1) Resumen Ejecutivo'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 6, utf8_decode($resumenTexto));
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('2) Resumen de Equipos (Total)'), 0, 1, 'L');
arsort($equiposSummary);
$pdf->CabeceraTabla('Equipo', 'Cantidad');
if (empty($equiposSummary)) {
    $pdf->FilaTabla('(Sin equipamiento calculado)', '');
} else {
    foreach ($equiposSummary as $eq => $cant) {
        if ($cant <= 0) continue;
        $pdf->FilaTabla($eq, (string)$cant);
    }
}
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('3) Resumen por Recinto'), 0, 1, 'L');
$pdf->CabeceraTabla('Recinto', 'Requerimiento');
if (empty($recintoSummary)) {
    $pdf->FilaTabla('(Sin recintos calculados)', '');
} else {
    foreach ($recintoSummary as $rec => $val) {
        if ($rec === '') continue;
        $pdf->FilaTabla($rec, (string)$val);
    }
}
$pdf->Ln(6);

if (!empty($prestaciones)) {
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode('4) Desglose por Prestación'), 0, 1, 'L');
    foreach ($prestaciones as $p) {
        if (empty($p['EQUIPOS'])) continue;
        $pdf->CheckPageBreak(10);
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $titulo = ($p['COD_PRESTACION'] ?? '') . ' - ' . ($p['NOMBRE_PRESTACION'] ?? 'Prestacion');
        $pdf->Cell(0, 7, utf8_decode($titulo), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->CabeceraTabla('Equipo', 'Cantidad');
        foreach ($p['EQUIPOS'] as $eq) {
            if (($eq['CANTIDAD'] ?? 0) <= 0) continue;
            $pdf->FilaTabla($eq['EQUIPO'], (string)$eq['CANTIDAD']);
        }
        $pdf->Ln(4);
    }
}

$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('5) Fórmula de Cálculo (EEMM)'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$formula = "DEMANDA EEMM = (Demanda Anual / Dias Laborales) / [ N x (60 / Tiempo Procedimiento) x Disponibilidad x Jornada Efectiva ]\n\n" .
    "Donde:\n" .
    "- Demanda Anual: cantidad total de atenciones proyectadas.\n" .
    "- Dias Laborales: dias efectivos de operacion al ano.\n" .
    "- N: numero de prestaciones simultaneas.\n" .
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
    "3) Anexo 2 NTB de Autorizacion Sanitaria para Atencion Abierta.\n" .
    "4) Guias para el Diseno de Establecimientos Hospitalarios (MINSAL).";
$pdf->MultiCell(0, 5, utf8_decode($fuentes));

$filename = 'Informe_EPH_Abierta_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre_proyecto) . '_' . date('YmdHis') . '.pdf';
$pdf->Output('D', $filename);
exit;