<?php
declare(strict_types=1);

require_once __DIR__ . '/../calculo/calculo_equipamiento.php';

class EquipamientoAgregadorService
{
    private mysqli $conn;

    /**
     * $kitService, $tipo5Service y $tipo6Service ya no se usan:
     * SIGEM_EPHDEM_CERRADA_CalcularEquipamiento llama por su cuenta a las
     * funciones de calculo/ de cada fuente (kit, tipo 2, tipo 5 y tipo 6).
     * Se conservan en la firma para no cambiar DemandaController.
     */
    public function __construct(mysqli $conn, EquipamientoKitService $kitService, EquipamientoTipo5Service $tipo5Service, EquipamientoTipo6Service $tipo6Service)
    {
        $this->conn = $conn;
    }

    public function calcular(int $proyectoId): array
    {
        $resultado = SIGEM_EPHDEM_CERRADA_CalcularEquipamiento($this->conn, $proyectoId);

        // La funcion original agrega 'desglose' (copia de la fuente) a cada
        // entrada de 'detalle'. La respuesta actual de la API no lo incluye,
        // asi que se quita para no cambiar el JSON.
        foreach ($resultado['equipos'] as $i => $equipo) {
            foreach (array_keys($equipo['detalle']) as $j) {
                unset($resultado['equipos'][$i]['detalle'][$j]['desglose']);
            }
        }

        return $resultado;
    }
}
