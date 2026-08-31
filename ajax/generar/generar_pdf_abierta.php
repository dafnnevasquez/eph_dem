<?php
declare(strict_types=1);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once '/home4/csi84990/public_html/_general/fpdf/fpdf.php';
require_once __DIR__ . '/../../funciones_sigemuv_C_BaseDatos.php';

$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) die("Error al conectar a la base de datos.");

$ID_Usuario    = $_SESSION['ID_Usuario'] ?? null;
$id_proyeccion = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$ID_Usuario || !$id_proyeccion) die("Error: No se pudo identificar el usuario o el proyecto.");

$sql = "SELECT NOMBRE_PROYECCION, DATOS_JSON FROM EPHDEM_PROYECCIONES_GUARDADAS WHERE ID_PROYECCION = ? AND USER_ID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id_proyeccion, $ID_Usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) die("No se encontraron datos para esta proyección.");

$nombre_proyecto = $row['NOMBRE_PROYECCION'];
$datos_json      = json_decode($row['DATOS_JSON'], true);
if (!$datos_json) die("Error al leer datos del JSON.");

$equiposSummary = $datos_json['equipos_summary'] ?? [];
$recintoSummary = $datos_json['recinto_summary'] ?? [];

$colorPrincipal  = [0, 60, 88];
$colorBlanco     = [255, 255, 255];
$colorGris       = [230, 230, 230];
$colorNegro      = [0, 0, 0];

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Título
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(...$colorPrincipal);
$pdf->Cell(0, 10, utf8_decode("CÁLCULO DE EQUIPAMIENTO MÉDICO — ATENCIÓN ABIERTA"), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, utf8_decode("Proyecto: $nombre_proyecto"), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(...$colorNegro);
$pdf->Cell(0, 6, 'Generado el ' . date('d-m-Y H:i'), 0, 1, 'C');
$pdf->Ln(6);

// Resumen equipos
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(...$colorPrincipal);
$pdf->Cell(0, 8, utf8_decode("RESUMEN DE EQUIPOS"), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(...$colorPrincipal);
$pdf->SetTextColor(...$colorBlanco);
$pdf->Cell(130, 6, 'Equipo', 1, 0, 'C', true);
$pdf->Cell(50, 6, 'Total', 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 8);
$pdf->SetFillColor(...$colorGris);
$pdf->SetTextColor(...$colorNegro);
foreach ($equiposSummary as $eq => $cant) {
    $pdf->Cell(130, 6, utf8_decode($eq), 1, 0, 'L', true);
    $pdf->Cell(50, 6, (string)$cant, 1, 1, 'C', true);
}
$pdf->Ln(6);

// Resumen recintos
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(...$colorPrincipal);
$pdf->Cell(0, 8, utf8_decode("RESUMEN DE RECINTOS"), 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(...$colorPrincipal);
$pdf->SetTextColor(...$colorBlanco);
$pdf->Cell(130, 6, 'Recinto', 1, 0, 'C', true);
$pdf->Cell(50, 6, 'Requerimiento', 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 8);
$pdf->SetFillColor(...$colorGris);
$pdf->SetTextColor(...$colorNegro);
foreach ($recintoSummary as $rec => $val) {
    if ($rec === '') continue;
    $pdf->Cell(130, 6, utf8_decode($rec), 1, 0, 'L', true);
    $pdf->Cell(50, 6, (string)$val, 1, 1, 'C', true);
}

$pdf->Output('I', utf8_decode("Reporte_Abierta_$nombre_proyecto.pdf"));
exit;