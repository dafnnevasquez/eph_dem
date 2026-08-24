<?php
require_once __DIR__ . '/../funciones_sigemuv_C_BaseDatos.php';
$conn = SIGEM_UV_C_Nueva_Conexion();
if (!$conn) die("Error de conexion");

$res = mysqli_query($conn, "SHOW TABLES LIKE '%Proy%'");
while($row = mysqli_fetch_row($res)) {
    echo "Tabla: " . $row[0] . "\n";
    $res2 = mysqli_query($conn, "DESCRIBE " . $row[0]);
    while($col = mysqli_fetch_assoc($res2)) {
        echo "  " . $col['Field'] . " - " . $col['Type'] . "\n";
    }
}
echo "Tablas con EPHAC:\n";
$res = mysqli_query($conn, "SHOW TABLES LIKE '%EPHAC%'");
while($row = mysqli_fetch_row($res)) {
    echo "Tabla: " . $row[0] . "\n";
}
mysqli_close($conn);
