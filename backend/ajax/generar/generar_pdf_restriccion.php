<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

/**
 * =====================================================================
 * SIGEM-UV  ·  Modulo EPHDEM-Cerrada
 * ajax/generar/generar_pdf_restriccion.php
 *
 * Endpoint: GET
 * Params: proyecto_id (int), usuario_id (int)
 *
 * Informe PDF del resultado de la restriccion de RR.HH. (FPDF, mismo
 * estilo que generar_pdf_cerrada.php).
 * =====================================================================
 */

require_once __DIR__ . '/restriccion_datos.php';
require_once __DIR__ . '/../../lib/fpdf/fpdf.php';

$datos  = ephdem_restriccion_cargar();
$nombre = $datos['nombre'];
$res    = $datos['res'];
$t      = $res['totales'];

/** Texto UTF-8 -> Windows-1252 (lo que usan las fuentes de FPDF). */
function ephdem_txt(string $s): string
{
    if (function_exists('iconv')) {
        $r = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        if ($r !== false) {
            return $r;
        }
    }
    return utf8_decode($s);
}

/* --- Clase PDF con tabla de N columnas --------------------------------- */
class PDF_Restriccion extends FPDF
{
    public function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }

    public function Titulo(string $texto)
    {
        $this->CheckPageBreak(14);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 60, 88);
        $this->Cell(0, 8, ephdem_txt($texto), 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
    }

    public function Parrafo(string $texto, int $tam = 10)
    {
        $this->SetFont('Arial', '', $tam);
        $this->MultiCell(0, 5.5, ephdem_txt($texto));
    }

    private function Cabecera(array $w, array $head)
    {
        $this->SetFillColor(0, 60, 88);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        foreach ($head as $k => $h) {
            $this->Cell($w[$k], 8, ephdem_txt($h), 1, 0, $k === 0 ? 'L' : 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }

    /**
     * Tabla simple: la primera columna admite varias lineas; la cabecera se
     * repite si la tabla continua en otra pagina. $resaltar[i] = fila en negrita.
     */
    public function Tabla(array $w, array $head, array $rows, array $resaltar = [])
    {
        $this->CheckPageBreak(16);
        $this->Cabecera($w, $head);
        $lh = 6;
        foreach ($rows as $i => $row) {
            $estilo = !empty($resaltar[$i]) ? 'B' : '';
            $this->SetFont('Arial', $estilo, 8);
            $txt0 = ephdem_txt((string)$row[0]);
            $n = max(1, (int)ceil($this->GetStringWidth($txt0) / ($w[0] - 3)));
            $h = $lh * $n;
            if ($this->GetY() + $h > $this->PageBreakTrigger) {
                $this->AddPage();
                $this->Cabecera($w, $head);
                $this->SetFont('Arial', $estilo, 8);
            }
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w[0], $h);
            $this->MultiCell($w[0], $lh, $txt0, 0, 'L');
            $cx = $x + $w[0];
            for ($k = 1, $c = count($w); $k < $c; $k++) {
                $this->SetXY($cx, $y);
                $this->Cell($w[$k], $h, ephdem_txt((string)($row[$k] ?? '')), 1, 0, 'C');
                $cx += $w[$k];
            }
            $this->SetXY($x, $y + $h);
        }
        $this->Ln(3);
    }
}

$pdf = new PDF_Restriccion('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

/* --- Membrete -------------------------------------------------------- */
$logoPath = '/home4/csi84990/public_html/_general/logos/LOGO SIGEM-UV HORIZONTAL.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 10, 60);
}
$pdf->SetY(35);
$pdf->SetFont('Arial', 'B', 15);
$pdf->SetTextColor(0, 60, 88);
$pdf->Cell(0, 9, ephdem_txt('RESULTADO CON RESTRICCIÓN DE RR.HH.'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 7, ephdem_txt('Estudio de Preinversión Hospitalaria - Atención Cerrada'), 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, ephdem_txt('Proyecto: ' . $nombre), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Generado el ' . date('d-m-Y H:i'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(6);

/* --- 1) Resumen ------------------------------------------------------ */
$faltan = ephdem_restriccion_faltan($res);
$noCub  = ephdem_restriccion_no_cubiertas($res);

$pdf->Titulo('1) Resumen');
$pdf->Parrafo(
    'Este informe compara el equipamiento que exige la demanda (necesidad tecnológica) con el que se puede ' .
    'usar con el personal disponible. Para cada recinto se calcula alfa, la fracción de la demanda que el personal ' .
    'alcanza a cubrir; con ella se ajustan los pabellones, los boxes y todo el equipamiento.'
);
$pdf->Ln(2);
$filas = [['Pabellones y boxes', $t['modulos_normal'], $t['modulos_restringido']]];
if (isset($t['equipos_normal'])) {
    $filas[] = ['Equipamiento (todos los tipos)', $t['equipos_normal'], $t['equipos_restringido']];
}
if (!empty($res['urpa']) && $res['urpa']['normal']['camillas'] > 0) {
    $filas[] = ['Camillas URPA', $res['urpa']['normal']['camillas'], $res['urpa']['restringido']['camillas']];
}
$pdf->Tabla([90, 45, 45], ['Indicador', 'Necesidad tecnológica', 'Con el personal actual'], $filas);
$pdf->Tabla([135, 45], ['Personal', 'Valor'], [
    ['Personas que faltan para cubrir toda la demanda', ephdem_num($faltan)],
    ['Prestaciones no cubiertas al año (mientras falte personal)', ephdem_num($noCub)],
]);

/* --- 2) Por recinto -------------------------------------------------- */
$pdf->Titulo('2) Resultado por recinto');
$filas = [];
foreach ($res['recintos'] as $r) {
    if (!$r['con_datos_planilla_a']) {
        $cobertura = 'Sin tiempos cargados';
    } elseif ($r['alpha'] >= 1) {
        $cobertura = 'Alcanza';
    } else {
        $cobertura = ephdem_pct($r['alpha']);
    }
    $filas[] = [
        $r['nombre'],
        $cobertura,
        $r['perfil_limitante'] && $r['alpha'] < 1 ? $r['perfil_limitante'] : '-',
        $r['modulos'] ? $r['modulos']['normal'] . ' -> ' . $r['modulos']['restringido'] : '-',
        ephdem_num($r['prestaciones_no_cubiertas']),
    ];
}
$pdf->Tabla([46, 30, 40, 32, 32], ['Recinto', 'Cobertura', 'Perfil que limita', 'Módulos', 'No cubiertas'], $filas);

/* --- 3) Personal ----------------------------------------------------- */
$filas = [];
foreach ($res['recintos'] as $r) {
    foreach ($r['por_perfil'] as $p) {
        $filas[] = [
            $r['nombre'] . ' - ' . $p['perfil'],
            ephdem_num($p['q']),
            ephdem_num($p['a']),
            $p['rho'] === null ? '-' : ($p['rho'] >= 1 ? 'Alcanza' : ephdem_pct($p['rho'])),
            ephdem_num($p['n']),
            $p['n_min'] === null ? '-' : ephdem_num($p['n_min']),
            $p['brecha'] === null ? '-' : ephdem_num($p['brecha']),
        ];
    }
}
if ($filas) {
    $pdf->Titulo('3) Personal por recinto y perfil');
    $pdf->Tabla([52, 24, 24, 22, 18, 22, 18],
        ['Recinto - Perfil', 'Horas req./año', 'Horas disp./año', 'Suficiencia', 'Personas', 'Mínimo', 'Faltan'],
        $filas);
}

/* --- 4) Equipamiento ------------------------------------------------- */
$equipos = $res['equipamiento'] ?? [];
if ($equipos) {
    usort($equipos, static function ($a, $b) {
        $da = $a['normal'] - $a['restringido'];
        $db = $b['normal'] - $b['restringido'];
        if ($da !== $db) return $db <=> $da;
        return strcmp((string)$a['nombre_equipo'], (string)$b['nombre_equipo']);
    });
    $pdf->AddPage();
    $pdf->Titulo('4) Equipamiento (todos los tipos)');
    $cambian = count(array_filter($equipos, static function ($e) { return $e['normal'] !== $e['restringido']; }));
    $pdf->Parrafo(
        "Cambian $cambian de " . count($equipos) . ' equipos (en negrita, al inicio de la tabla). ' .
        'Los demás quedan igual: son de recintos sin restricción o el redondeo absorbe la diferencia.', 9
    );
    $pdf->Ln(2);
    $filas = [];
    $resaltar = [];
    foreach ($equipos as $i => $e) {
        $filas[] = [$e['nombre_equipo'], $e['tipo'] ? 'Tipo ' . $e['tipo'] : '-', $e['normal'], $e['restringido']];
        $resaltar[$i] = $e['normal'] !== $e['restringido'];
    }
    $pdf->Tabla([100, 25, 28, 27], ['Equipo', 'Tipo', 'Sin restricción', 'Con restricción'], $filas, $resaltar);
}

/* --- 5) URPA --------------------------------------------------------- */
if (!empty($res['urpa']) && $res['urpa']['normal']['pabellones'] > 0) {
    $u = $res['urpa'];
    $pdf->Titulo('5) URPA (recuperación post-anestésica)');
    $pdf->Tabla([90, 45, 45], ['URPA', 'Sin restricción', 'Con restricción'], [
        ['Pabellones', $u['normal']['pabellones'], $u['restringido']['pabellones']],
        ['Camillas', $u['normal']['camillas'], $u['restringido']['camillas']],
        ['Salas', $u['normal']['salas'], $u['restringido']['salas']],
    ]);
    $filas = [];
    $resaltar = [];
    foreach ($u['equipos'] as $i => $e) {
        $filas[] = [$e['nombre_equipo'], $e['normal'], $e['restringido']];
        $resaltar[$i] = $e['normal'] !== $e['restringido'];
    }
    if ($filas) {
        $pdf->Tabla([110, 35, 35], ['Equipo URPA', 'Sin restricción', 'Con restricción'], $filas, $resaltar);
    }
}

/* --- 6) Metodologia -------------------------------------------------- */
$pdf->AddPage();
$pdf->Titulo('6) Cómo se calcula (modelo de restricción de RR.HH.)');
$pdf->Parrafo(
    "Para cada recinto j y cada perfil de personal r:\n\n" .
    "- Dedicación por prestación (tau): permanente = duración t de la prestación; puntual = e x f x max(1, t/24), " .
    "con e = horas de cada intervención y f = veces al día. phi = tau / t debe estar entre 0 y 1.\n" .
    "- Horas requeridas al año: Q = suma(Demanda anual x tau).\n" .
    "- Horas disponibles al año: A = personas x jornada semanal x semanas efectivas x fracción de jornada.\n" .
    "- Suficiencia del perfil: rho = A / Q. El recinto queda limitado por su perfil más escaso.\n" .
    "- Factor alcanzable: alpha = min(1, rho mínimo del recinto).\n" .
    "- Equipos con restricción: D* = techo(alpha x D_tec), donde D_tec es la necesidad tecnológica (fórmula EEMM).\n" .
    "- Personal mínimo: techo(Q / horas de una persona al año). Faltan = mínimo - personas actuales.\n" .
    "- Prestaciones no cubiertas: (1 - alpha) x demanda anual del recinto.\n\n" .
    'Los pabellones y boxes se ajustan con alpha; el kit de recinto (tipo 1), los equipos por módulo (tipo 2), ' .
    'la norma UPC (tipo 6) y la URPA se recalculan con los módulos ajustados, y los equipos por demanda (tipo 5) ' .
    'con alpha. Los tipos 3 y 4 resultan de la misma regla de composición del cálculo sin restricción.',
    9
);

/* --- Salida ---------------------------------------------------------- */
$pdf->Output('D', ephdem_nombre_archivo('Restriccion_RRHH_', $nombre, 'pdf'));
exit;