<?php
declare(strict_types=1);

require_once __DIR__ . '/../calculo/calculo_equipamiento.php';

class EquipamientoVistasService
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function calcular(array $equipamiento): array
    {
        return SIGEM_EPHDEM_CERRADA_CalcularEquipamientoPorRecinto($this->conn, $equipamiento);
    }
}
