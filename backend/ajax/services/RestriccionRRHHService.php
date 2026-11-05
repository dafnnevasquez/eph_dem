<?php
declare(strict_types=1);

/**
 * Restriccion de RR.HH. sobre el equipamiento (modelo v4, ecuaciones R3a a R11).
 *
 * R1 y R2 (necesidad tecnologica D_tec) NO estan aqui: ya los calcula el
 * sistema actual (/calculo). Este service recibe D_tec ya calculado y le
 * aplica la restriccion de RR.HH.
 *
 * Logica PURA: no usa la base de datos ni depende de /calculo. Recibe
 * numeros y devuelve numeros. Quien lee los datos (demanda, dotacion,
 * tiempos por prestacion y perfil, D_tec) es el controller.
 *
 * Entorno objetivo: PHP 7.4. No usar match, str_contains, ?->,
 * argumentos con nombre, fdiv ni comas finales en parametros.
 *
 * Correspondencia con el sistema actual:
 *   t = tiempo_procedimiento / 60 (horas)
 *   h * d = jornada_semanal  W = semanas_efectivas     delta = fraccion_jornada
 *
 * SUPUESTOS PENDIENTES DE CONFIRMAR CON LA PROFESORA:
 *   - El servicio j es el recinto (el llamador agrupa las prestaciones).
 *   - Perfil requerido sin dotacion: se aplica el modelo al pie de la
 *     letra (A = 0, rho = 0, alpha = 0) y se informa en 'perfiles_sin_datos'.
 */
class RestriccionRRHHService
{
    /**
     * Techo seguro: round(..., 9) evita que 2.0000000000000004 suba a 3
     * por error de punto flotante.
     */
    public static function techo(float $x): int
    {
        return (int)ceil(round($x, 9));
    }

    /** R3a) Dias de prestacion: max(1, t / 24). */
    public static function diasPrestacion(float $tHoras): float
    {
        return max(1.0, $tHoras / 24.0);
    }

    /**
     * R3b) Horas del perfil por prestacion, intervencion puntual:
     *   tau = e * f * dias_prestacion   (e en horas por vez, f veces al dia).
     */
    public static function horasPorPrestacion(float $eHorasVez, float $fVecesDia, float $tHoras): float
    {
        return $eHorasVez * $fVecesDia * self::diasPrestacion($tHoras);
    }

    /**
     * R3b) Operacion permanente: el perfil esta todo el tiempo, tau = t.
     *
     * Nota sobre el documento: dice "en operacion permanente e = t". Eso solo
     * es correcto si t <= 24 h; con t = 120 h, e * f * delta daria phi = 5.
     * Por eso aqui se devuelve directamente tau = t.
     */
    public static function horasPorPrestacionPermanente(float $tHoras): float
    {
        return $tHoras;
    }

    /** R3c) Fraccion de dedicacion: phi = tau / t. */
    public static function dedicacion(float $tau, float $tHoras): float
    {
        if ($tHoras <= 0) {
            return 0.0;
        }
        return $tau / $tHoras;
    }

    /**
     * Consistencia del documento: 0 < phi <= 1. Si es falso hay un error en
     * los datos, o la prestacion requiere varias personas del mismo perfil.
     */
    public static function dedicacionValida(float $phi): bool
    {
        return $phi > 0 && $phi <= 1.0 + 1e-9;
    }

    /**
     * R4) Horas requeridas: Q = SUMA(Dda * tau).
     * @param array $filas lista de ['dda' => prestaciones/anio, 'tau' => h/prestacion]
     */
    public static function horasRequeridas(array $filas): float
    {
        $q = 0.0;
        foreach ($filas as $f) {
            $q += (float)$f['dda'] * (float)$f['tau'];
        }
        return $q;
    }

    /** R5) Horas disponibles del perfil: A = n * h * d * W * delta. */
    public static function horasDisponibles(float $n, float $h, float $d, float $w, float $delta): float
    {
        if ($n < 0 || $h < 0 || $d < 0 || $w < 0 || $delta < 0) {
            return 0.0;
        }
        return $n * $h * $d * $w * $delta;
    }

    /**
     * R6) Suficiencia del perfil: rho = A / Q. Si Q = 0 el perfil no es
     * requerido y no limita: devuelve null (no INF, para que sea JSON valido).
     */
    public static function suficiencia(float $a, float $q): ?float
    {
        if ($q <= 0) {
            return null;
        }
        return $a / $q;
    }

    /** R6 + R7) Factor alcanzable: alpha = min(1, min rho). Ignora los null. */
    public static function factorAlcanzable(array $rhos): float
    {
        $alpha = 1.0;
        foreach ($rhos as $rho) {
            if ($rho === null) {
                continue;
            }
            if ((float)$rho < $alpha) {
                $alpha = (float)$rho;
            }
        }
        return $alpha;
    }

    /** R10) Personal minimo: n_min = techo(Q / (h * d * W * delta)). */
    public static function personalMinimo(float $q, float $h, float $d, float $w, float $delta): int
    {
        $porPersona = $h * $d * $w * $delta;
        if ($q <= 0 || $porPersona <= 0) {
            return 0;
        }
        return self::techo($q / $porPersona);
    }

    /**
     * R8 + R9) Equipos con restriccion: D = alpha * D_tec, D* = techo(D).
     * D_tec viene del calculo actual de equipamiento.
     */
    public static function equiposRestringidos(float $dtec, float $alpha): array
    {
        $d = $alpha * $dtec;
        return [
            'dtec'        => $dtec,
            'dtec_entero' => self::techo($dtec),
            'd'           => $d,
            'd_entero'    => self::techo($d),
        ];
    }

    /**
     * Resuelve un servicio j (R4 a R7, R10, R11).
     *
     * @param array $prestaciones cada una:
     *   ['dda' => prestaciones/anio, 'tau' => [perfil => horas por prestacion]]
     * @param array $perfiles dotacion: perfil => ['n', 'h', 'd', 'W', 'delta']
     * @return array apto para json_encode (sin INF ni NAN)
     */
    public static function resolverServicio(array $prestaciones, array $perfiles): array
    {
        $ddaTotal       = 0.0;
        $filasPorPerfil = [];

        foreach ($prestaciones as $p) {
            $dda = (float)$p['dda'];
            $ddaTotal += $dda;
            foreach ($p['tau'] as $perfil => $tau) {
                $filasPorPerfil[$perfil][] = ['dda' => $dda, 'tau' => (float)$tau];
            }
        }

        $porPerfil   = [];
        $sinDatos    = [];
        $rhoServicio = null;
        $limitante   = null;

        foreach ($filasPorPerfil as $perfil => $filas) {
            $q = self::horasRequeridas($filas);

            if (isset($perfiles[$perfil])) {
                $cfg   = $perfiles[$perfil];
                $n     = (float)$cfg['n'];
                $h     = (float)$cfg['h'];
                $d     = (float)$cfg['d'];
                $w     = (float)$cfg['W'];
                $delta = (float)$cfg['delta'];
            } else {
                $sinDatos[] = $perfil;
                $n = $h = $d = $w = $delta = 0.0;
            }

            $a   = self::horasDisponibles($n, $h, $d, $w, $delta);
            $rho = self::suficiencia($a, $q);

            // n_min solo se puede calcular si hay horas por persona.
            $nMin   = null;
            $brecha = null;
            if ($h * $d * $w * $delta > 0) {
                $nMin   = self::personalMinimo($q, $h, $d, $w, $delta);
                $brecha = max(0.0, $nMin - $n);
            }

            if ($rho !== null && ($rhoServicio === null || $rho < $rhoServicio)) {
                $rhoServicio = $rho;
                $limitante   = $perfil;
            }

            $porPerfil[$perfil] = [
                'q'      => $q,
                'a'      => $a,
                'rho'    => $rho,
                'n'      => $n,
                'n_min'  => $nMin,
                'brecha' => $brecha,
            ];
        }

        // R7: alpha = min(1, rho_j). Sin perfiles requeridos no hay restriccion.
        $alpha = 1.0;
        if ($rhoServicio !== null && $rhoServicio < 1.0) {
            $alpha = $rhoServicio;
        }

        return [
            'demanda_total'             => $ddaTotal,
            'por_perfil'                => $porPerfil,
            'perfiles_sin_datos'        => $sinDatos,
            'rho'                       => $rhoServicio,
            'alpha'                     => $alpha,
            'restriccion_activa'        => ($alpha >= 1.0) ? 'equipo' : 'rrhh',
            'perfil_limitante'          => $limitante,
            // R11: prestaciones anuales no cubiertas = (1 - alpha) * suma(Dda)
            'prestaciones_no_cubiertas' => (1.0 - $alpha) * $ddaTotal,
        ];
    }
}