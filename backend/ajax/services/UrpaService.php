<?php
declare(strict_types=1);

require_once __DIR__ . '/../calculo/calculo_equipamiento_tipo5.php';
require_once __DIR__ . '/../calculo/calculo_equipamiento_urpa.php';

class UrpaService
{
    // Alias de las constantes de calculo/: los tests actuales las leen desde la clase.
    const CAMILLAS_POR_PABELLON = EPHDEM_URPA_CAMILLAS_POR_PABELLON;
    const MAX_CAMILLAS          = EPHDEM_URPA_MAX_CAMILLAS;
    const NOMBRE_RECINTO        = EPHDEM_URPA_NOMBRE_RECINTO;

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function calcular(int $nroPabellones): array
    {
        return SIGEM_EPHDEM_CERRADA_CalcularURPA($this->conn, $nroPabellones);
    }
}
