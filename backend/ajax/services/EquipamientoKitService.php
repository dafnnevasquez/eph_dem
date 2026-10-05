<?php
declare(strict_types=1);

require_once __DIR__ . '/../calculo/calculo_pabellones_boxes.php';
require_once __DIR__ . '/../calculo/calculo_equipamiento_tipo5.php';
require_once __DIR__ . '/../calculo/calculo_equipamiento_kit.php';

class EquipamientoKitService
{
    private mysqli $conn;

    /**
     * $pabellonesService ya no se usa: las funciones de calculo/ obtienen el
     * conteo de recintos por su cuenta (SIGEM_EPHDEM_CERRADA_ConteoRecintosPorId).
     * Se conserva en la firma para no cambiar DemandaController ni los tests.
     */
    public function __construct(mysqli $conn, PabellonesBoxesService $pabellonesService)
    {
        $this->conn = $conn;
    }

    public function calcularKit(int $proyectoId): array
    {
        return SIGEM_EPHDEM_CERRADA_CalcularEquipamientoKit($this->conn, $proyectoId);
    }

    public function calcularTipo2(int $proyectoId): array
    {
        return SIGEM_EPHDEM_CERRADA_CalcularEquipamientoTipo2($this->conn, $proyectoId);
    }
}
