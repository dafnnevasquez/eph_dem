<?php
declare(strict_types=1);

class UrpaService
{
    const TBL_EQUIPOS           = 'EPHAC_Equipos';
    const CAMILLAS_POR_PABELLON = 2;
    const MAX_CAMILLAS          = 12;
    const NOMBRE_RECINTO        = 'URPA (Sala de recuperación post-anestésica)';

    const EQUIPOS_SALA = [
        33=>1, 88=>1, 53=>1, 124=>1, 48=>1, 110=>1, 107=>1,
    ];

    const EQUIPOS_CAMILLA = [
        27=>1, 113=>1, 17=>1, 123=>1, 22=>1, 68=>1, 166=>1,
    ];

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function calcular(int $nroPabellones): array
    {
        $nroPabellones = max(0, $nroPabellones);
        $camillas      = self::CAMILLAS_POR_PABELLON * $nroPabellones;
        $salas         = $camillas > 0 ? (int)ceil($camillas / self::MAX_CAMILLAS) : 0;

        $idsInvolucrados = array_unique(array_merge(array_keys(self::EQUIPOS_SALA), array_keys(self::EQUIPOS_CAMILLA)));
        $nombres = [];

        if (!empty($idsInvolucrados)) {
            $ids    = implode(',', array_map('intval', $idsInvolucrados));
            $result = mysqli_query($this->conn, "SELECT id_equipo, nombre_equipo FROM " . self::TBL_EQUIPOS . " WHERE id_equipo IN ($ids)");
            while ($row = mysqli_fetch_assoc($result)) $nombres[(int)$row['id_equipo']] = $row['nombre_equipo'];
        }

        $construirLista = function (array $definicion, int $multiplicador) use ($nombres): array {
            $lista = [];
            foreach ($definicion as $eid => $cantBase) {
                $cantidad = $multiplicador * (int)$cantBase;
                if ($cantidad <= 0) continue;
                $lista[] = ['equipo_id' => (int)$eid, 'nombre_equipo' => $nombres[(int)$eid] ?? null, 'cantidad_base' => (int)$cantBase, 'cantidad' => $cantidad];
            }
            usort($lista, fn($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);
            return $lista;
        };

        $equiposSala    = $construirLista(self::EQUIPOS_SALA,    $salas);
        $equiposCamilla = $construirLista(self::EQUIPOS_CAMILLA, $camillas);

        $merge = [];
        foreach (array_merge($equiposSala, $equiposCamilla) as $item) {
            $eid = $item['equipo_id'];
            if (!isset($merge[$eid])) $merge[$eid] = ['equipo_id' => $eid, 'nombre_equipo' => $item['nombre_equipo'], 'cantidad' => 0];
            $merge[$eid]['cantidad'] += $item['cantidad'];
        }
        $equipos = array_values($merge);
        usort($equipos, fn($a, $b) => $a['equipo_id'] <=> $b['equipo_id']);

        return [
            'nombre_recinto'  => self::NOMBRE_RECINTO,
            'nro_pabellones'  => $nroPabellones,
            'nro_camillas'    => $camillas,
            'nro_salas'       => $salas,
            'equipos_sala'    => $equiposSala,
            'equipos_camilla' => $equiposCamilla,
            'equipos'         => $equipos,
        ];
    }
}