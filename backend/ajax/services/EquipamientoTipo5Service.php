<?php
declare(strict_types=1);

require_once __DIR__ . '/../calculo/calculo_pabellones_boxes.php';
require_once __DIR__ . '/../calculo/calculo_equipamiento_tipo5.php';

class EquipamientoTipo5Service
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function calcular(int $proyectoId): array
    {
        return SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo5($this->conn, $proyectoId);
    }
}
