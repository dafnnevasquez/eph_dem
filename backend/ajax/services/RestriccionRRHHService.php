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
 * tiempos por prestacion y perfil, D_tec) es RestriccionProyectoService.
 *
 * Orden del archivo = orden del documento:
 *   R3a, R3b, R3c -> R4 -> R5 -> R6 -> R7 -> R8 -> R9 -> R10 -> R11
 *   y al final resolverServicio(), que las encadena para un servicio j.
 *
 * Entorno objetivo: PHP 7.4. No usar match, str_contains, ?->,
 * argumentos con nombre, fdiv ni comas finales en parametros.
 *
 * Correspondencia con el sistema actual:
 *   t = tiempo_procedimiento / 60 (horas)
 *   h * d = jornada_semanal   W = semanas_efectivas   delta = fraccion_jornada
 *
 * Supuestos:
 *   - El servicio j es el recinto (el llamador agrupa las prestaciones). CONFIRMADO
 *   - Perfil requerido sin dotacion: se aplica el modelo al pie de la
 *     letra (A = 0, rho = 0, alpha = 0) y se informa en 'perfiles_sin_datos'.
 *     PENDIENTE DE CONFIRMAR CON LA PROFESORA.
 */
class RestriccionRRHHService
{
    /* =====================================================================
     * Utilidad
     * ===================================================================== */

    /**
     * Techo seguro: round(..., 9) evita que 2.0000000000000004 suba a 3
     * por error de punto flotante.
     */
    public static function techo(float $x): int
    {
        return (int)ceil(round($x, 9));
    }

    /* =====================================================================
     * R3) Dedicacion del perfil a la prestacion
     * ===================================================================== */

    /** R3a) Dias de prestacion: Delta = max(1, t / 24). */
    public static function diasPrestacion(float $tHoras): float
    {
        return max(1.0, $tHoras / 24.0);
    }

    /**
     * R3b) Horas del perfil por prestacion, intervencion puntual:
     *   tau = e * f * Delta   (e en horas por vez, f veces al dia).
     */
    public static function horasPorPrestacion(float $eHorasVez, float $fVecesDia, float $tHoras): float
    {
        return $eHorasVez * $fVecesDia * self::diasPrestacion($tHoras);
    }

    /**
     * R3b) Operacion permanente: el perfil esta todo el tiempo, tau = t.
     *
     * Nota sobre el documento: dice "en operacion permanente e = t". Eso solo
     * es correcto si t <= 24 h; con t = 120 h, e * f * Delta daria phi = 5.
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
     * R3c) Consistencia del documento: 0 < phi <= 1. Si es falso hay un error
     * en los datos, o la prestacion requiere varias personas del mismo perfil.
     */
    public static function dedicacionValida(float $phi): bool
    {
        return $phi > 0 && $phi <= 1.0 + 1e-9;
    }

    /* =====================================================================
     * R4) Horas requeridas
     * ===================================================================== */

    /**
     * R4) Q = SUMA(Dda * tau).
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

    /* =====================================================================
     * R5) Horas disponibles
     * ===================================================================== */

    /**
     * R5) Horas anuales que aporta UNA persona: h * d * W * delta.
     * Se usa en R5 (A) y en R10 (n_min). Datos negativos = 0.
     */
    public static function horasPorPersona(float $h, float $d, float $w, float $delta): float
    {
        if ($h < 0 || $d < 0 || $w < 0 || $delta < 0) {
            return 0.0;
        }
        return $h * $d * $w * $delta;
    }

    /** R5) Horas disponibles del perfil: A = n * h * d * W * delta. */
    public static function horasDisponibles(float $n, float $h, float $d, float $w, float $delta): float
    {
        if ($n < 0) {
            return 0.0;
        }
        return $n * self::horasPorPersona($h, $d, $w, $delta);
    }

    /* =====================================================================
     * R6) Suficiencia
     * ===================================================================== */

    /**
     * R6) Razon de suficiencia del perfil: rho = A / Q. Si Q = 0 el perfil no
     * es requerido y no limita: devuelve null (no INF, para que sea JSON valido).
     */
    public static function suficiencia(float $a, float $q): ?float
    {
        if ($q <= 0) {
            return null;
        }
        return $a / $q;
    }

    /**
     * R6) Suficiencia del servicio: rho_j = min(rho de cada perfil).
     * En la misma pasada identifica el perfil limitante (cuello de botella).
     *
     * @param array $rhosPorPerfil perfil => rho (o null si el perfil no limita)
     * @return array ['rho' => ?float, 'perfil' => ?string]
     */
    public static function suficienciaServicio(array $rhosPorPerfil): array
    {
        $min       = null;
        $limitante = null;
        foreach ($rhosPorPerfil as $perfil => $rho) {
            if ($rho === null) {
                continue;
            }
            if ($min === null || (float)$rho < $min) {
                $min       = (float)$rho;
                $limitante = (string)$perfil;
            }
        }
        return ['rho' => $min, 'perfil' => $limitante];
    }

    /* =====================================================================
     * R7) Factor alcanzable
     * ===================================================================== */

    /**
     * R7) alpha = min(1, rho_j).
     * Sin perfiles requeridos (rho_j = null) no hay restriccion: alpha = 1.
     */
    public static function factorAlcanzable(?float $rhoServicio): float
    {
        if ($rhoServicio === null) {
            return 1.0;
        }
        return min(1.0, $rhoServicio);
    }

    /* =====================================================================
     * R8 y R9) Equipos con restriccion
     * ===================================================================== */

    /** R8) Equipos con restriccion (continuo): D = alpha * D_tec. */
    public static function equiposConRestriccion(float $dtec, float $alpha): float
    {
        return $alpha * $dtec;
    }

    /** R9) Equipos enteros: D* = techo(D). */
    public static function equiposEnteros(float $d): int
    {
        return self::techo($d);
    }

    /**
     * R8 + R9 juntos, para el llamador. D_tec viene del calculo actual.
     * Devuelve tambien techo(D_tec) para comparar con el resultado sin restriccion.
     */
    public static function equiposRestringidos(float $dtec, float $alpha): array
    {
        $d = self::equiposConRestriccion($dtec, $alpha);
        return [
            'dtec'        => $dtec,
            'dtec_entero' => self::equiposEnteros($dtec),
            'd'           => $d,
            'd_entero'    => self::equiposEnteros($d),
        ];
    }

    /* =====================================================================
     * R10) Personal minimo y brecha
     * ===================================================================== */

    /** R10) Personal minimo: n_min = techo(Q / (h * d * W * delta)). */
    public static function personalMinimo(float $q, float $h, float $d, float $w, float $delta): int
    {
        $porPersona = self::horasPorPersona($h, $d, $w, $delta);
        if ($q <= 0 || $porPersona <= 0) {
            return 0;
        }
        return self::techo($q / $porPersona);
    }

    /** R10) Brecha de personal: max(0, n_min - n). */
    public static function brechaPersonal(int $nMin, float $n): float
    {
        return max(0.0, $nMin - $n);
    }

    /* =====================================================================
     * R11) Prestaciones no cubiertas
     * ===================================================================== */

    /** R11) Prestaciones anuales no cubiertas: P_nc = (1 - alpha) * SUMA(Dda). */
    public static function prestacionesNoCubiertas(float $alpha, float $ddaTotal): float
    {
        return (1.0 - $alpha) * $ddaTotal;
    }

    /* =====================================================================
     * Orquestacion: un servicio j completo
     * ===================================================================== */

    /**
     * Resuelve un servicio j encadenando R4 a R7, R10 y R11.
     *
     * @param array $prestaciones cada una:
     *   ['dda' => prestaciones/anio, 'tau' => [perfil => horas por prestacion]]
     * @param array $perfiles dotacion: perfil => ['n', 'h', 'd', 'W', 'delta']
     * @return array apto para json_encode (sin INF ni NAN)
     */
    public static function resolverServicio(array $prestaciones, array $perfiles): array
    {
        // 1) Una pasada: demanda total y filas (dda, tau) agrupadas por perfil.
        $ddaTotal       = 0.0;
        $filasPorPerfil = [];
        foreach ($prestaciones as $p) {
            $dda = (float)$p['dda'];
            $ddaTotal += $dda;
            foreach ($p['tau'] as $perfil => $tau) {
                $filasPorPerfil[$perfil][] = ['dda' => $dda, 'tau' => (float)$tau];
            }
        }

        // 2) Cada perfil: R4, R5, R6 (rho del perfil) y R10.
        $porPerfil = [];
        $sinDatos  = [];
        $rhos      = [];
        foreach ($filasPorPerfil as $perfil => $filas) {
            $cfg = isset($perfiles[$perfil]) ? $perfiles[$perfil] : null;
            if ($cfg === null) {
                $sinDatos[] = $perfil;
            }
            $porPerfil[$perfil] = self::resolverPerfil($filas, $cfg);
            $rhos[$perfil]      = $porPerfil[$perfil]['rho'];
        }

        // 3) R6 del servicio y R7.
        $suf   = self::suficienciaServicio($rhos);
        $alpha = self::factorAlcanzable($suf['rho']);

        return [
            'demanda_total'             => $ddaTotal,
            'por_perfil'                => $porPerfil,
            'perfiles_sin_datos'        => $sinDatos,
            'rho'                       => $suf['rho'],
            'alpha'                     => $alpha,
            'restriccion_activa'        => ($alpha >= 1.0) ? 'equipo' : 'rrhh',
            'perfil_limitante'          => $suf['perfil'],
            'prestaciones_no_cubiertas' => self::prestacionesNoCubiertas($alpha, $ddaTotal),
        ];
    }

    /**
     * Un perfil dentro del servicio: R4, R5, R6 (rho del perfil) y R10.
     * $cfg = null significa que el proyecto no tiene dotacion para ese perfil.
     */
    private static function resolverPerfil(array $filas, ?array $cfg): array
    {
        $n = $h = $d = $w = $delta = 0.0;
        if ($cfg !== null) {
            $n     = (float)$cfg['n'];
            $h     = (float)$cfg['h'];
            $d     = (float)$cfg['d'];
            $w     = (float)$cfg['W'];
            $delta = (float)$cfg['delta'];
        }

        $q   = self::horasRequeridas($filas);                  // R4
        $a   = self::horasDisponibles($n, $h, $d, $w, $delta); // R5
        $rho = self::suficiencia($a, $q);                      // R6

        // R10: solo se puede calcular si una persona aporta horas.
        $nMin   = null;
        $brecha = null;
        if (self::horasPorPersona($h, $d, $w, $delta) > 0) {
            $nMin   = self::personalMinimo($q, $h, $d, $w, $delta);
            $brecha = self::brechaPersonal($nMin, $n);
        }

        return [
            'q'      => $q,
            'a'      => $a,
            'rho'    => $rho,
            'n'      => $n,
            'n_min'  => $nMin,
            'brecha' => $brecha,
        ];
    }
}