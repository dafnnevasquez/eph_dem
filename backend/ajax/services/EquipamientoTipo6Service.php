<?php
declare(strict_types=1);

require_once __DIR__ . '/../calculo/calculo_pabellones_boxes.php';
require_once __DIR__ . '/../calculo/calculo_equipamiento_tipo5.php';
require_once __DIR__ . '/../calculo/calculo_equipamiento_kit.php';
require_once __DIR__ . '/../calculo/calculo_equipamiento_tipo6.php';

class EquipamientoTipo6Service
{
    private mysqli $conn;

    /**
     * $pabellonesService ya no se usa: las funciones de calculo/ obtienen el
     * conteo de camas por su cuenta (SIGEM_EPHDEM_CERRADA_ConteoRecintosPorId).
     * Se conserva en la firma para no cambiar DemandaController ni los tests.
     */
    public function __construct(mysqli $conn, PabellonesBoxesService $pabellonesService)
    {
        $this->conn = $conn;
    }

    public function calcular(int $proyectoId): array
    {
        return SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo6($this->conn, $proyectoId);
    }
}
