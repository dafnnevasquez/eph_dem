<?php
function SIGEM_EPHDEM_Acercade($conn) {
    // Obtener el nombre del usuario desde la sesión
    $nombre_usuario = $_SESSION['Nombre'] ?? $_SESSION['Correo'] ?? 'Usuario';
    $primer_apellido_usuario = $_SESSION['Primer_Apellido'] ?? $_SESSION['Correo'] ?? 'Usuario';

    // Obtener el ID_USUARIO
    $ID_Usuario = $_SESSION['ID_Usuario'] ?? null;
    if (!$ID_Usuario) {
        $correo = $_SESSION["Correo"] ?? '';
        if ($correo !== '') {
            $sql_user = "SELECT ID_Usuario FROM SIGEM_Usuarios WHERE Correo = ?";
            if ($stmt = mysqli_prepare($conn, $sql_user)) {
                mysqli_stmt_bind_param($stmt, "s", $correo);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $ID_Usuario = $row['ID_Usuario'];
                    $_SESSION['ID_Usuario'] = $ID_Usuario;
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    // -------------------------------------------------------------
    // Manejo de eliminación de un proyecto (no afecta el autoincrement)
    // -------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' 
        && isset($_POST['accion']) 
        && $_POST['accion'] === 'eliminar' 
        && !empty($_POST['id_proyeccion'])
    ) {
        $id_proy_a_eliminar = intval($_POST['id_proyeccion']);

        // Eliminación segura
        $sql_del = "DELETE FROM EPHDEM_PROYECCIONES_GUARDADAS 
                    WHERE ID_PROYECCION = ? AND USER_ID = ?";
        if ($stmt_del = mysqli_prepare($conn, $sql_del)) {
            mysqli_stmt_bind_param($stmt_del, "ii", $id_proy_a_eliminar, $ID_Usuario);
            mysqli_stmt_execute($stmt_del);
            mysqli_stmt_close($stmt_del);

            echo "<script>alert('Proyecto eliminado correctamente.');</script>";
            // Recargar la página para actualizar la lista
            echo "<script>window.location.href = '?cmdquery=EPHDEM|Acercade';</script>";
            exit();
        }
    }

    // -------------------------------------------------------------
    // Procesar formulario de creación de nuevo proyecto
    // -------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' 
        && isset($_POST['nombre_proyecto']) 
        && $_POST['nombre_proyecto'] !== '' 
        && empty($_POST['accion'])  // Para que no choque con 'eliminar'
    ) {
        $nombre_proyecto = trim($_POST['nombre_proyecto']);
        $sql_verificar = "
            SELECT COUNT(*) AS total
            FROM EPHDEM_PROYECCIONES_GUARDADAS
            WHERE NOMBRE_PROYECCION = ?
              AND USER_ID = ?
        ";
        if ($stmt = mysqli_prepare($conn, $sql_verificar)) {
            mysqli_stmt_bind_param($stmt, "si", $nombre_proyecto, $ID_Usuario);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            if ($row['total'] > 0) {
                echo "<script>alert('Ya tiene un proyecto con este nombre. Elija otro.');</script>";
            } else {
                $_SESSION['nombre_proyecto'] = $nombre_proyecto;
                echo "<script>window.location.href = '?cmdquery=EPHDEM|TablaPrestaciones';</script>";
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    }

    // -------------------------------------------------------------
    // Obtener la lista de proyectos creados por el usuario
    // -------------------------------------------------------------
    $proyectos = [];
    if ($ID_Usuario) {
        $sql_proyectos = "
            SELECT ID_PROYECCION, NOMBRE_PROYECCION, FECHA_CREACION
            FROM EPHDEM_PROYECCIONES_GUARDADAS
            WHERE USER_ID = ?
            ORDER BY FECHA_CREACION DESC
        ";
        if ($stmt = mysqli_prepare($conn, $sql_proyectos)) {
            mysqli_stmt_bind_param($stmt, "i", $ID_Usuario);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $proyectos[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    }
    ?>

    <!-- Banner Principal -->
    <div class="section hero text-center background-dark dark-bg descripcion-sistema" style="border-radius: 10px;">
        <div class="background-image" 
             style="border-radius: 10px; 
                    background: url(https://sigem-uv.cl/loc/imagenes/Banner.png) no-repeat center center; 
                    background-size: cover; 
                    opacity: .15; 
                    filter: blur(2px);">
        </div>
        <div class="container">
            <div class="row">
                <div class="col-sm-4">
                    <img src="./img/img_ephdem/Logo-Claro-EphDEM.png" 
                         alt="Logo SIGEM-UV" 
                         class="img-responsive center-block shadow mb-3" />
                </div>
                <div class="col-sm-4">
                    <p class="lead"><b>Módulo de Estudio de Preinversión Hospitalaria</b></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Descripción del Sistema -->
    <div class="container my-4">
        <div class="row g-4">
            <!-- Primera columna -->
            <div class="col-md-6 p-3 descripcion-sistema" style="background-color: rgba(10, 104, 148, 0.1);">
                <h4>¿Qué es EPHDEM y cuál es su objetivo?</h4>
                <p style="font-size: 1.1em;">
                    <b>EPHDEM</b> es un módulo de <b>SIGEM-UV</b> que centraliza información técnica para el <b>cálculo de equipamiento médico</b> en centros de atención abierta (alcance actual). Permite la selección de prestaciones MAI de FONASA, y luego cálcular la demanda de Equipamiento utilizando variables como la <b>demanda de procedimientos</b>, <b>días anuales laborales</b>, <b>tiempo de procedimiento</b>, etc., logrando un resultado <b>objetivo y eficiente</b>.
                </p>
            </div>
            <!-- Segunda columna -->
            <div class="col-md-6 p-3 descripcion-sistema" style="background-color: rgba(10, 104, 148, 0.1);">
                <h4>¿Cómo funciona y en qué contribuye EPHDEM?</h4>
                <p style="font-size: 1.1em;">
                    EPHDEM usa datos del <b>Plan de Inversiones del Minsal</b>, la <b>base instalada nacional</b>(Proyecto SIGEM-UV) y el anexo 2 de <b>Normas Técnicas Básicas del ISP</b>. Los equipos se seleccionan según <b>criterios de clasificación de demanda</b> de la guía metodológica para EPH, asegurando <b>cálculos objetivos</b>. Esta herramienta ayuda en la <b>toma de decisiones</b>, respondiendo a las necesidades reales del personal clínico y administrativo.
                </p>
            </div>
        </div>
    </div>

    <!-- Estilo adicional para sombra dinámica y diseño -->
    <style>
        .descripcion-sistema {
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        .descripcion-sistema:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-5px);
        }

        .row.g-4 > [class*='col-'] {
            margin-bottom: 1.5rem; /* Espacio entre columnas */
        }
    </style>



    <div class="container my-5 descripcion-sistema">
        <!-- Bienvenida -->
        <h2>Bienvenido(a): <?= htmlspecialchars($nombre_usuario) . ' ' . htmlspecialchars($primer_apellido_usuario) ?></h2>

        <!-- Sección para descargar manual de usuario -->
        <div style="padding: 5px; margin-top: 20px; border: 0px solid rgb(10, 104, 148);">
            <h4>¿Desea descargar el manual de usuario?</h4>
            <a href="https://sigem-uv.cl/eph/Manual_de_Usuario_EphDEM.pdf" target="_blank">
                <button class="btn btn-danger">
                    Descargar Manual de Usuario
                </button>
            </a>
        </div>




        <hr>

        <!-- Crear Nuevo Proyecto -->
            <h4>¿Desea Crear un Nuevo Proyecto?</h4>
            <form method="POST" id="proyectoForm" class="mb-4" style="max-width: 400px;">
                <div class="mb-3">
                    <label for="nombre_proyecto" class="form-label">Nombre del Proyecto:</label>
                    <input type="text" 
                        name="nombre_proyecto" 
                        id="nombre_proyecto" 
                        class="form-control" 
                        placeholder="Ej: Proyecto Hospital X" 
                        required>
                    <div id="mensaje_error" class="form-text text-danger"></div>
                </div>
                <button type="submit" id="btn_crear" class="btn btn-primary">
                    Crear Nuevo Proyecto
                </button>
                <br></br>
            </form>
  
        <!-- Proyectos Existentes -->
        <h4>Proyectos Creados</h4>

        <?php if (empty($proyectos)): ?>
            <p>No tienes proyectos guardados aún.</p>
        <?php else: ?>
            <!-- Estilos de tabla solicitados -->
            <style>
                /* Sólo el separador superior e inferior en la fila de columnas */
                .tabla-proyectos thead th {
                    border-top: 3px solid #003c58 !important; 
                    border-bottom: 3px solid #003c58 !important;
                }
                /* Borde inferior al final de la tabla */
                .tabla-proyectos tbody tr:last-child td {
                    border-bottom: 3px solid #003c58 !important;
                }
                /* No cambiar color de letra, etc. (respetamos los estilos por defecto) */
            </style>

            <table class="table tabla-proyectos" style="border-collapse: collapse; width:100%;">
                <thead>
                    <tr>
                        <th>ID Proyecto</th>
                        <th>Nombre Proyecto</th>
                        <th>Fecha Creación</th>
                        <th>Acción</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proyectos as $proj): ?>
                        <tr>
                            <td><?= htmlspecialchars($proj['ID_PROYECCION']) ?></td>
                            <td><?= htmlspecialchars($proj['NOMBRE_PROYECCION']) ?></td>
                            <td><?= htmlspecialchars($proj['FECHA_CREACION']) ?></td>
                            <td>
                                <a href="?cmdquery=EPHDEM|TablaRecintos&id=<?= $proj['ID_PROYECCION'] ?>" 
                                   class="btn btn-sm btn-secondary">
                                    Ver Detalles
                                </a>
                            </td>
                            <td>
                                <!-- Form para eliminar proyecto -->
                                <form method="POST" onsubmit="return confirm('¿Seguro que desea eliminar este proyecto?')">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_proyeccion" 
                                           value="<?= htmlspecialchars($proj['ID_PROYECCION']) ?>">
                                    <button type="submit" 
                                            class="btn btn-sm btn-danger">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Script para la validación AJAX del nombre del proyecto -->
    <script>
        const inputNombre = document.getElementById('nombre_proyecto');
        const mensajeError = document.getElementById('mensaje_error');
        const btnCrear = document.getElementById('btn_crear');

        inputNombre.addEventListener('input', () => {
            const nombre = inputNombre.value.trim();
            if (nombre.length > 0) {
                const formData = new FormData();
                formData.append('verificar_nombre', nombre);
                formData.append('ID_Usuario', <?= json_encode($ID_Usuario) ?>);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.existe) {
                        inputNombre.style.borderColor = 'red';
                        mensajeError.textContent = 'Ya tiene un proyecto con este nombre.';
                        btnCrear.disabled = true;
                    } else {
                        inputNombre.style.borderColor = 'green';
                        mensajeError.textContent = '';
                        btnCrear.disabled = false;
                    }
                });
            } else {
                inputNombre.style.borderColor = '';
                mensajeError.textContent = '';
                btnCrear.disabled = true;
            }
        });
    </script>

    <?php
    // Validación AJAX (verificar existencia del proyecto)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' 
        && isset($_POST['verificar_nombre'], $_POST['ID_Usuario']) 
        && !isset($_POST['accion'])
    ) {
        $nombre_verificar = trim($_POST['verificar_nombre']);
        $id_usuario = intval($_POST['ID_Usuario']);

        $sql_verificar = "
            SELECT COUNT(*) AS total
            FROM EPHDEM_PROYECCIONES_GUARDADAS
            WHERE NOMBRE_PROYECCION = ?
              AND USER_ID = ?
        ";
        $stmt = mysqli_prepare($conn, $sql_verificar);
        mysqli_stmt_bind_param($stmt, "si", $nombre_verificar, $id_usuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        header('Content-Type: application/json');
        echo json_encode(['existe' => $row['total'] > 0]);
        exit();
    }
}




function SIGEM_EPHDEM_SeleccionadorPrestaciones($conn) {
    // Obtener prestaciones relacionadas de la base de datos
    $sql_prestaciones = "
        SELECT DISTINCT p.ID_PRESTACION, p.cod_prestacion, p.nombre_prestacion, p.area
        FROM EPHDEM_PREST_RECINTO_EQ rel
        INNER JOIN EPHDEM_PRESTACION p ON rel.ID_PRESTACION = p.ID_PRESTACION
        ORDER BY p.area, p.cod_prestacion;
    ";
    $prestaciones = SIGEM_UV_C_Carga_Tabla_SIGEM($conn, $sql_prestaciones);

    // Obtener las áreas relacionadas
    $sql_areas = "
        SELECT DISTINCT p.area
        FROM EPHDEM_PREST_RECINTO_EQ rel
        INNER JOIN EPHDEM_PRESTACION p ON rel.ID_PRESTACION = p.ID_PRESTACION
        ORDER BY p.area;
    ";
    $areas = SIGEM_UV_C_Carga_Tabla_SIGEM($conn, $sql_areas);

    // Guardar prestaciones seleccionadas en sesión al enviar el formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prestaciones_seleccionadas'])) {
        $_SESSION['prestaciones_seleccionadas'] = json_decode($_POST['prestaciones_seleccionadas'], true);
        echo "<script>window.location.href = '?cmdquery=EPHDEM|Prueba';</script>";
        exit();
    }
    ?>

    <style>
        .container { margin: 20px auto; max-width: 90%; }
        .list-container { display: flex; justify-content: space-between; margin-top: 20px; gap: 20px; }
        .list { width: 48%; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
        .list-title {
            background-color: #003c58; 
            color: white; 
            text-align: center; 
            padding: 10px; 
            font-weight: bold; 
            border-top-left-radius: 5px; 
            border-top-right-radius: 5px;
        }
        .list-content { max-height: 400px; overflow-y: auto; padding: 10px; }
        .list-item {
            padding: 8px; 
            margin-bottom: 5px; 
            background-color: #fff; 
            border: 1px solid #ddd; 
            border-radius: 3px; 
            cursor: pointer; 
            transition: background-color 0.2s;
        }
        .list-item:hover { background-color: #e6f7ff; }
        .separador { border-top: 2px solid #ccc; margin: 10px 0; }
        .boton-guardar { text-align: center; margin-top: 20px; }
    </style>

    <div class="container">
        <h2 class="text-center">Seleccionador de Prestaciones</h2>

        <!-- Filtro de Área -->
        <div>
            <label for="areaSelect">Filtrar por Área:</label>
            <select id="areaSelect" onchange="filtrarPrestaciones()">
                <option value="">Todas las Áreas</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?= htmlspecialchars($area['area']) ?>"><?= htmlspecialchars($area['area']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="searchInput" placeholder="Buscar prestación..." oninput="buscarPrestacion()">
        </div>

        <!-- Separador Visual -->
        <div class="separador"></div>

        <!-- Listas -->
        <div class="list-container">
            <!-- Lista de prestaciones disponibles -->
            <div class="list">
                <div class="list-title">Prestaciones Disponibles</div>
                <div class="list-content" id="listaDisponibles">
                    <?php foreach ($prestaciones as $p): ?>
                        <div class="list-item" 
                             data-id="<?= $p['ID_PRESTACION'] ?>" 
                             data-area="<?= htmlspecialchars($p['area']) ?>">
                            <?= htmlspecialchars($p['cod_prestacion']) ?> - <?= htmlspecialchars($p['nombre_prestacion']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Lista de prestaciones seleccionadas -->
            <div class="list">
                <div class="list-title">Prestaciones Seleccionadas</div>
                <div class="list-content" id="listaSeleccionadas"></div>
            </div>
        </div>

        <!-- Botón para guardar prestaciones seleccionadas -->
        <form method="POST">
            <input type="hidden" name="prestaciones_seleccionadas" id="prestacionesInput">
            <div class="boton-guardar">
                <button type="submit" class="btn btn-primary">Guardar y Calcular</button>
            </div>
        </form>
    </div>

    <script>
        const listaDisponibles = document.getElementById('listaDisponibles');
        const listaSeleccionadas = document.getElementById('listaSeleccionadas');
        const prestacionesInput = document.getElementById('prestacionesInput');

        listaDisponibles.addEventListener('click', (e) => {
            if (e.target.classList.contains('list-item')) {
                listaSeleccionadas.appendChild(e.target);
                actualizarPrestaciones();
            }
        });

        listaSeleccionadas.addEventListener('click', (e) => {
            if (e.target.classList.contains('list-item')) {
                listaDisponibles.appendChild(e.target);
                actualizarPrestaciones();
            }
        });

        function actualizarPrestaciones() {
            const seleccionadas = [];
            listaSeleccionadas.querySelectorAll('.list-item').forEach(item => {
                seleccionadas.push(item.getAttribute('data-id'));
            });
            prestacionesInput.value = JSON.stringify(seleccionadas);
        }

        function filtrarPrestaciones() {
            const areaSeleccionada = document.getElementById('areaSelect').value.toLowerCase();
            document.querySelectorAll('#listaDisponibles .list-item').forEach(item => {
                const area = item.getAttribute('data-area').toLowerCase();
                item.style.display = areaSeleccionada === '' || area === areaSeleccionada ? '' : 'none';
            });
        }

        function buscarPrestacion() {
            const filtro = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('#listaDisponibles .list-item').forEach(item => {
                const texto = item.textContent.toLowerCase();
                item.style.display = texto.includes(filtro) ? '' : 'none';
            });
        }
    </script>
    <?php
}

function SIGEM_EPHDEM_Proyeccion_Equipamiento($conn) {
    // ============================================
    // 1) Manejo del POST: Guardar en BD y redirigir
    // ============================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proyeccion_data'])) {
        $data = json_decode($_POST['proyeccion_data'], true);
        if (is_array($data)) {
            // Guardamos en sesión por si se requiere
            $_SESSION['datos_proyeccion'] = $data;

            // =========================
            // Construir el JSON Detallado
            // =========================

            // Obtener las prestaciones seleccionadas
            $prestaciones_ids = array_map('intval', $data['prestaciones']);
            if (empty($prestaciones_ids)) {
                echo "<script>alert('No hay prestaciones seleccionadas.');</script>";
                return;
            }

            // Consulta para obtener detalles de las prestaciones
            $sql_prestaciones = "
                SELECT 
                    p.ID_PRESTACION, 
                    p.cod_prestacion, 
                    p.nombre_prestacion, 
                    p.area
                FROM EPHDEM_PRESTACION p
                WHERE p.ID_PRESTACION IN (" . implode(",", $prestaciones_ids) . ")
                ORDER BY p.area, p.cod_prestacion;
            ";
            $prestaciones_detalle = SIGEM_UV_C_Carga_Tabla_SIGEM($conn, $sql_prestaciones);

            // Inicializar el array para el JSON detallado
            $json_detallado = ["prestaciones" => []];

            foreach ($prestaciones_detalle as $prestacion) {
                $id_prest = $prestacion['ID_PRESTACION'];

                // Obtener los equipos asociados a esta prestación
                $sql_equipos = "
                    SELECT 
                        eq.equipo, 
                        r.recinto, 
                        rel.Cantidad_equipo,
                        eq.tipo_equipo
                    FROM EPHDEM_PREST_RECINTO_EQ rel
                    INNER JOIN EPHDEM_EQUIPOS eq ON rel.ID_EQUIPO = eq.ID_EQUIPO
                    INNER JOIN EPHDEM_RECINTOS r ON rel.ID_RECINTO = r.ID_RECINTO
                    WHERE rel.ID_PRESTACION = ?
                ";
                $stmt_equipos = mysqli_prepare($conn, $sql_equipos);
                mysqli_stmt_bind_param($stmt_equipos, "i", $id_prest);
                mysqli_stmt_execute($stmt_equipos);
                $result_equipos = mysqli_stmt_get_result($stmt_equipos);
                $equipos = [];
                while ($equipo = mysqli_fetch_assoc($result_equipos)) {
                    $equipos[] = [
                        "EQUIPO"      => $equipo['equipo'],
                        "RECINTO"     => $equipo['recinto'],
                        "CANTIDAD"    => (int)$equipo['Cantidad_equipo'],
                        "TIPO_EQUIPO" => $equipo['tipo_equipo']
                    ];
                }
                mysqli_stmt_close($stmt_equipos);

                // Obtener los valores ingresados por el usuario para esta prestación
                $demanda         = isset($data['demanda'][$id_prest])         ? (float)$data['demanda'][$id_prest]         : 0;
                $tiempo          = isset($data['tiempo'][$id_prest])          ? (float)$data['tiempo'][$id_prest]          : 0;
                $n               = isset($data['n'][$id_prest])               ? (int)$data['n'][$id_prest]                 : 1;
                $disponibilidad  = isset($data['disponibilidad'][$id_prest])  ? (float)$data['disponibilidad'][$id_prest]  : 100;
                $jornada         = isset($data['jornada'][$id_prest])         ? (float)$data['jornada'][$id_prest]         : 7;
                $dias_laborales  = isset($data['dias_laborales'][$id_prest])  ? (float)$data['dias_laborales'][$id_prest]  : 260;
                $requerimiento   = isset($data['requerimiento'][$id_prest]) 
                                   ? number_format((float)$data['requerimiento'][$id_prest], 3, '.', '') 
                                   : "0.000";

                // Construir la estructura de la prestación
                $json_detallado["prestaciones"][] = [
                    "ID_PRESTACION"         => $prestacion['ID_PRESTACION'],
                    "COD_PRESTACION"        => $prestacion['cod_prestacion'],
                    "NOMBRE_PRESTACION"     => $prestacion['nombre_prestacion'],
                    "AREA"                  => $prestacion['area'],
                    "TIEMPO_PROCEDIMIENTO"  => $tiempo,
                    "DEMANDA_PROCEDIMIENTO" => $demanda,
                    "DISPONIBILIDAD"        => $disponibilidad,
                    "JORNADA"               => $jornada,
                    "DIAS_LABORALES"        => $dias_laborales,
                    "REQUERIMIENTO"         => $requerimiento,
                    "EQUIPOS"               => $equipos
                ];
            }

            // =========================
            // NUEVO: Agregar Equipos Tipo 1 por Área
            // =========================

            // 1. Obtener todas las áreas únicas de las prestaciones
            $areas = [];
            foreach ($json_detallado["prestaciones"] as $prestacion) {
                $area = $prestacion["AREA"];
                if (!in_array($area, $areas)) {
                    $areas[] = $area;
                }
            }

            // 2. Obtener todos los equipos de Tipo 1 desde la tabla EPHDEM_EQUIPOS
            $sql_tipo1 = "SELECT equipo FROM EPHDEM_EQUIPOS WHERE tipo_equipo = 'Tipo 1'";
            $result_tipo1 = SIGEM_UV_C_Carga_Tabla_SIGEM($conn, $sql_tipo1);
            $equipos_tipo1 = [];
            foreach ($result_tipo1 as $row_tipo1) {
                $equipos_tipo1[] = $row_tipo1['equipo'];
            }

            // Verificar que hay equipos de Tipo 1 disponibles
            if (empty($equipos_tipo1)) {
                echo "<script>alert('No hay equipos de Tipo 1 disponibles en la base de datos.');</script>";
                return;
            }

            // 3. Asignar equipos de Tipo 1 a cada área (rotando si es necesario)
            $total_tipo1 = count($equipos_tipo1);
            foreach ($areas as $index => $area) {
                // Seleccionar un equipo de Tipo 1 (rotando si es necesario)
                $equipo_tipo1 = $equipos_tipo1[$index % $total_tipo1];

                // Buscar la primera prestación que pertenezca a esta área
                foreach ($json_detallado["prestaciones"] as &$prestacion) {
                    if ($prestacion["AREA"] === $area) {
                        // Agregar el equipo de Tipo 1 al final de la lista de equipos
                        $prestacion["EQUIPOS"][] = [
                            "EQUIPO"      => $equipo_tipo1,
                            "RECINTO"     => "", // Sin recinto
                            "CANTIDAD"    => 1,
                            "TIPO_EQUIPO" => "Tipo 1"
                        ];
                        // Solo agregar una vez por área
                        break;
                    }
                }
                unset($prestacion); // Romper la referencia
            }

            // =========================
            // Convertir a JSON
            // =========================
            $json_str = json_encode($json_detallado, JSON_UNESCAPED_UNICODE);

            // =========================
            // Guardar en la Base de Datos
            // =========================
            $ID_Usuario = $_SESSION['ID_Usuario'] ?? null;
            if (!$ID_Usuario) {
                echo "<script>alert('No se ha identificado un usuario.');</script>";
                return;
            }

            // Obtener/asegurar nombre_proyecto
            $nombre_proyecto = $_SESSION['nombre_proyecto'] ?? 'Proyecto sin nombre';
            $fecha = date('Y-m-d H:i:s');

            // Insertar en BD
            $insert_sql = "
                INSERT INTO EPHDEM_PROYECCIONES_GUARDADAS (USER_ID, NOMBRE_PROYECCION, FECHA_CREACION, DATOS_JSON)
                VALUES (?, ?, ?, ?)";
            if ($stmt_insert = mysqli_prepare($conn, $insert_sql)) {
                mysqli_stmt_bind_param($stmt_insert, "isss", $ID_Usuario, $nombre_proyecto, $fecha, $json_str);
                if (mysqli_stmt_execute($stmt_insert)) { 
                    // Obtener el ID de la proyección recién guardada
                    $id_proyeccion = mysqli_insert_id($conn);

                    // Redirigir a la pantalla de visualización con ?id=xxx
                    echo "<script>window.location.href = '?cmdquery=EPHDEM|TablaRecintos&id={$id_proyeccion}';</script>";
                    exit();
                } else {
                    echo "<script>alert('Error al guardar el proyecto: " . htmlspecialchars(mysqli_error($conn)) . "');</script>";
                    return;
                }
            }

            // Si falla
            echo "<script>alert('Error al guardar el proyecto.');</script>";
            return;
        }
    }

    // ============================================
    // 2) Cargar prestaciones seleccionadas
    // ============================================
    $seleccionadas = $_SESSION['prestaciones_seleccionadas'] ?? [];
    if (empty($seleccionadas)) {
        echo "<div class='container'><h3>No hay prestaciones seleccionadas para proyectar.</h3></div>";
        return;
    }

    // Consulta para obtener prestaciones
    $sql_prestaciones = "
        SELECT 
            p.ID_PRESTACION, 
            p.cod_prestacion, 
            p.nombre_prestacion, 
            p.area,
            p.tiempo_min_anexo5
        FROM EPHDEM_PRESTACION p
        WHERE p.ID_PRESTACION IN (" . implode(",", array_map('intval', $seleccionadas)) . ")
        ORDER BY p.area, p.cod_prestacion;
    ";
    $prestaciones = SIGEM_UV_C_Carga_Tabla_SIGEM($conn, $sql_prestaciones);
    ?>

    <!-- Estilos para la tabla y los tooltips -->
    <style>
        .table thead th {
            background-color: #003c58; 
            color: white; 
            text-align: center;
            vertical-align: middle;
        }
        .container {
            max-width: 90%;
            margin: 20px auto;
        }

        /* Quitar los spinners de inputs type=number en navegadores WebKit */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        /* Para Firefox (opcional): */
        input[type=number] {
            -moz-appearance: textfield;
        }

        /* Tooltip estilo hover personalizado */
        .tooltip-icon {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-left: 5px;
            color: #0d6efd; /* color del icono */
        }
        .tooltip-icon .tooltip-text {
            visibility: hidden;
            width: 270px;
            background-color: #f8f9fa;
            color: #000;
            text-align: left;
            border-radius: 5px;
            border: 1px solid #ccc;
            padding: 8px 10px;
            position: absolute;
            z-index: 999;
            bottom: 125%;
            left: 50%;
            margin-left: -135px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .tooltip-icon:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>

    <div class="container my-5">
        <h1 class="text-center mb-4">Ingreso de Variables de Demanda de Equipamiento</h1>

        <!-- Botones en una misma línea -->
        <div class="mb-3" style="text-align: right;">
            <!-- Botón Días Hábiles (260) -->
            <button type="button" class="btn btn-info" id="btnDiasHabiles">
                Días Hábiles (260)
            </button>
            <!-- Botón Todo el Año (365) -->
            <button type="button" class="btn btn-info" id="btnTodoAnio" style="margin-left: 5px;">
                Todo el Año (365)
            </button>
            <!-- Botón Tiempos recomendados (ya existente) -->
            <button type="button" class="btn btn-secondary" id="autofillButton" style="margin-left: 5px;">
                Tiempos recomendados (Consultas especialidad)
            </button>
        </div>

        <!-- Formulario del cálculo de EEMM -->
        <!-- Usamos onsubmit="return validarFormulario()" para bloquear envío si hay valores inválidos -->
        <form method="POST" onsubmit="return validarFormulario()">
            <!-- Campo oculto para enviar datos consolidados en JSON -->
            <input type="hidden" name="proyeccion_data" id="proyeccionDataInput">

            <table class="table table-bordered table-striped" id="prestacionesTable">
                <thead>
                    <tr>
                        <th>
                            Código
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Código de Prestación MAI 2024 de FONASA
                                </span>
                            </span>
                        </th>
                        <th>
                            Nombre
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Nombre de la Prestación de Modalidad Atención Institucional 2024 de FONASA
                                </span>
                            </span>
                        </th>
                        <th>
                            Área
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Definida por FONASA; corresponde a especialidades, servicios y unidades
                                </span>
                            </span>
                        </th>
                        <th>
                            Demanda Anual
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Producción de la prestación en un año calendario
                                </span>
                            </span>
                        </th>
                        <th>
                            Tiempo Proced. (min)
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Duración de la consulta, procedimiento o examen en minutos
                                </span>
                            </span>
                        </th>
                        <!-- Nueva columna: Días Laborales Anual -->
                        <th>
                            Días Laborales Anual
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Cantidad de días laborables en un año calendario para la prestación
                                </span>
                            </span>
                        </th>
                        <!-- NUEVO: N -->
                        <th>
                            N (Prest. simultáneas)
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Número (N) de prestaciones simultáneas (por defecto 1, puede variar), como ejemplo los exámenes de laboratorio
                                </span>
                            </span>
                        </th>
                        <!-- NUEVO: %Disponibilidad -->
                        <th>
                            %Disponibilidad
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Porcentaje de disponibilidad real del equipo (0 a 100%)
                                </span>
                            </span>
                        </th>
                        <!-- NUEVO: Jornada Efectiva -->
                        <th>
                            Jornada (hrs)
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Horas efectivas por jornada (hasta 12 horas)
                                </span>
                            </span>
                        </th>
                        <!-- OCULTO: Requerimiento (no se muestra, pero lo calculamos) -->
                        <th style="display: none;">
                            Requerimiento Interno
                            <span class="tooltip-icon">?
                                <span class="tooltip-text">
                                    Aquí se muestra el requerimiento calculado
                                </span>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestaciones as $prestacion): ?>
                        <tr data-id="<?= $prestacion['ID_PRESTACION'] ?>"
                            data-tiempo-anexo5="<?= htmlspecialchars($prestacion['tiempo_min_anexo5']) ?>">
                            <td><?= htmlspecialchars($prestacion['cod_prestacion']) ?></td>
                            <td><?= htmlspecialchars($prestacion['nombre_prestacion']) ?></td>
                            <td class="area-cell"><?= htmlspecialchars($prestacion['area']) ?></td>
                            <td>
                                <input type="number"
                                       class="form-control demanda-input"
                                       placeholder="Ingrese demanda anual"
                                       data-id="<?= $prestacion['ID_PRESTACION'] ?>"
                                       value=""
                                       min="0">
                            </td>
                            <td>
                                <input type="number"
                                       class="form-control tiempo-input"
                                       placeholder="Tiempo en minutos"
                                       data-id="<?= $prestacion['ID_PRESTACION'] ?>"
                                       value=""
                                       min="0">
                            </td>
                            <!-- Nueva columna: Días Laborales Anual (por defecto 260) -->
                            <td>
                                <input type="number"
                                       class="form-control dias-laborales-input"
                                       data-id="<?= $prestacion['ID_PRESTACION'] ?>"
                                       value="260"
                                       min="0" max="366">
                            </td>
                            <!-- N: default 1 -->
                            <td>
                                <input type="number"
                                       class="form-control n-input"
                                       min="0"
                                       placeholder="N"
                                       data-id="<?= $prestacion['ID_PRESTACION'] ?>"
                                       value="1">
                            </td>
                            <!-- %Disponibilidad: default 100, editable, 0..100 -->
                            <td>
                                <input type="number"
                                       class="form-control disponibilidad-input"
                                       data-id="<?= $prestacion['ID_PRESTACION'] ?>"
                                       value="100"
                                       min="0" max="100">
                            </td>
                            <!-- Jornada Efectiva: default 7, min 1, max 12 -->
                            <td>
                                <select class="form-select jornada-select"
                                        data-id="<?= $prestacion['ID_PRESTACION'] ?>">
                                    <?php
                                    for ($j=1; $j<=12; $j++){
                                        $selected = ($j===7) ? "selected" : "";
                                        echo "<option value='{$j}' {$selected}>{$j}</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <!-- Requerimiento oculto -->
                            <td style="display: none;" id="requerimiento-<?= $prestacion['ID_PRESTACION'] ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="text-center mt-3">
                <!-- Al hacer submit => se guarda proyecto y se redirige (si valida bien) -->
                <button type="submit" class="btn btn-primary">Guardar Planificación</button>
            </div>
        </form>
    </div>

    <script>
        // Obtenemos referencias a los botones
        const btnDiasHabiles = document.getElementById('btnDiasHabiles');
        const btnTodoAnio = document.getElementById('btnTodoAnio');
        const btnAutofill = document.getElementById('autofillButton');

        // Al hacer clic en "Días Hábiles (260)", se rellenan todos los "dias-laborales-input" con 260
        btnDiasHabiles.addEventListener('click', function() {
            if (!verificarValoresPrevios()) return;
            document.querySelectorAll('.dias-laborales-input').forEach(input => {
                input.value = 260;
            });
            actualizarRequerimientos();
        });

        // Al hacer clic en "Todo el Año (365)", se rellenan todos los "dias-laborales-input" con 365
        btnTodoAnio.addEventListener('click', function() {
            if (!verificarValoresPrevios()) return;
            document.querySelectorAll('.dias-laborales-input').forEach(input => {
                input.value = 365;
            });
            actualizarRequerimientos();
        });

        // Autorellenar Tiempos recomendados
        btnAutofill.addEventListener('click', function() {
            if (!verificarValoresPrevios()) return;
            document.querySelectorAll('#prestacionesTable tbody tr').forEach(row => {
                const tiempoAnexo5 = parseFloat(row.getAttribute('data-tiempo-anexo5')) || 0;
                if (tiempoAnexo5 > 0) {
                    const inputTiempo = row.querySelector('.tiempo-input');
                    if (inputTiempo) {
                        inputTiempo.value = tiempoAnexo5;
                    }
                }
            });
            actualizarRequerimientos();
        });

        // Disparamos actualizar cada vez que cambian los inputs
        document.querySelectorAll(
          '.demanda-input, .tiempo-input, .n-input, .disponibilidad-input, .dias-laborales-input, .jornada-select'
        ).forEach(el => {
            el.addEventListener('input', actualizarRequerimientos);
            el.addEventListener('change', actualizarRequerimientos);
        });

        // Función para validar formulario al hacer submit
        function validarFormulario() {
            // 1) Verificar valores en cada fila
            if (!verificarValoresPrevios()) {
                return false; // no hace submit
            }
            // 2) Si está todo bien, consolidar y permitir el envío
            actualizarRequerimientos();
            return true;
        }

        // Verifica si hay algún valor fuera de rango o negativo y muestra alerta
        function verificarValoresPrevios() {
            let todoOk = true;
            let mensajeError = "";

            // Recorremos cada fila
            document.querySelectorAll('#prestacionesTable tbody tr').forEach(row => {
                const demandaVal  = parseFloat(row.querySelector('.demanda-input')?.value || "0");
                const tiempoVal   = parseFloat(row.querySelector('.tiempo-input')?.value || "0");
                const diasVal     = parseFloat(row.querySelector('.dias-laborales-input')?.value || "0");
                const nVal        = parseFloat(row.querySelector('.n-input')?.value || "0");
                const dispVal     = parseFloat(row.querySelector('.disponibilidad-input')?.value || "100");

                // Validaciones
                if (demandaVal < 0) {
                    todoOk = false;
                    mensajeError = "Hay Demanda Anual con valor negativo.";
                }
                if (tiempoVal < 0) {
                    todoOk = false;
                    mensajeError = "Hay Tiempo Proced. (min) con valor negativo.";
                }
                if (diasVal < 0 || diasVal > 366) {
                    todoOk = false;
                    mensajeError = "Días Laborales Anual debe estar entre 0 y 366.";
                }
                if (nVal < 0) {
                    todoOk = false;
                    mensajeError = "El valor de N no puede ser negativo.";
                }
                if (dispVal < 0 || dispVal > 100) {
                    todoOk = false;
                    mensajeError = "%Disponibilidad debe estar entre 0 y 100.";
                }
            });

            if (!todoOk) {
                alert(mensajeError);
            }
            return todoOk;
        }

        // Función para actualizar el cálculo de "requerimientos"
        function actualizarRequerimientos() {
            const dataDemanda       = {};
            const dataTiempo        = {};
            const dataN             = {};
            const dataDispon        = {};
            const dataJornada       = {};
            const dataDiasLaborales = {};
            const dataRequerimiento = {};

            // Recorremos cada fila
            document.querySelectorAll('#prestacionesTable tbody tr').forEach(row => {
                const idPrest = row.getAttribute('data-id');

                // Obtenemos valores
                const demandaAnual       = parseFloat(row.querySelector('.demanda-input')?.value)         || 0;
                const tiempoProc         = parseFloat(row.querySelector('.tiempo-input')?.value)          || 0;
                const nSimultaneas       = parseFloat(row.querySelector('.n-input')?.value)               || 0;
                const dispVal            = parseFloat(row.querySelector('.disponibilidad-input')?.value)  || 0;
                const diasLaboralesAnual = parseFloat(row.querySelector('.dias-laborales-input')?.value)  || 0;
                const jornadaSelect      = row.querySelector('.jornada-select');
                const jornadaEfectiva    = jornadaSelect ? parseFloat(jornadaSelect.value) : 7;

                // Guardamos en data
                dataDemanda[idPrest]        = demandaAnual;
                dataTiempo[idPrest]         = tiempoProc;
                dataN[idPrest]              = nSimultaneas;
                dataDispon[idPrest]         = dispVal;
                dataDiasLaborales[idPrest]  = diasLaboralesAnual;
                dataJornada[idPrest]        = jornadaEfectiva;

                // Cálculo del requerimiento
                let req = null;
                if (demandaAnual > 0 && tiempoProc > 0 && jornadaEfectiva > 0 && nSimultaneas > 0 && dispVal > 0 && diasLaboralesAnual > 0) {
                    const demandaDiaria     = demandaAnual / diasLaboralesAnual;
                    const procHora          = 60 / tiempoProc;
                    const capDiariaTeorica  = procHora * jornadaEfectiva;
                    const capConDisp        = capDiariaTeorica * (dispVal / 100);
                    req = demandaDiaria / (capConDisp * nSimultaneas);

                    // Lo guardamos con 3 decimales
                    req = parseFloat(req.toFixed(3));
                }

                // Asignamos en la celda (oculta)
                const reqCell = row.querySelector(`#requerimiento-${idPrest}`);
                if (req === null) {
                    if (reqCell) reqCell.textContent = '';
                    dataRequerimiento[idPrest] = null;
                } else {
                    if (reqCell) reqCell.textContent = req;
                    dataRequerimiento[idPrest] = req;
                }
            });

            // Empaquetar datos
            const proyeccionData = {
                demanda:        dataDemanda,
                tiempo:         dataTiempo,
                n:              dataN,
                disponibilidad: dataDispon,
                jornada:        dataJornada,
                dias_laborales: dataDiasLaborales,
                requerimiento:  dataRequerimiento,
                prestaciones:   <?= json_encode($seleccionadas) ?>
            };
            document.getElementById('proyeccionDataInput').value = JSON.stringify(proyeccionData);
        }

        // Inicializamos los requerimientos
        actualizarRequerimientos();
    </script>
    <?php
}





function SIGEM_EPHDEM_Visualizar_Proyeccion($conn) {
    // Obtener el ID de la proyección desde los parámetros GET
    $id_proyeccion = isset($_GET['id']) ? intval($_GET['id']) : null;
    // Obtener el ID del usuario desde la sesión
    $ID_Usuario = $_SESSION['ID_Usuario'] ?? null;

    if (!$ID_Usuario) {
        echo "<div class='container'><h3>No se ha identificado el usuario.</h3></div>";
        return;
    }

    // Inicializar variables
    $nombre_proyecto = 'Proyecto sin nombre';
    $json_data = null; 
    $proyecto_guardado = false;

    // Si hay ID_PROYECCION, cargar desde la BD
    if ($id_proyeccion) {
        $sql = "SELECT NOMBRE_PROYECCION, DATOS_JSON 
                FROM EPHDEM_PROYECCIONES_GUARDADAS 
                WHERE ID_PROYECCION = ? 
                  AND USER_ID = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $id_proyeccion, $ID_Usuario);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $nombre_proyecto   = $row['NOMBRE_PROYECCION'];
                    $json_data         = json_decode($row['DATOS_JSON'], true);
                    $proyecto_guardado = true;
                } else {
                    echo "<div class='container'><h3>No se encontró la proyecto especificada.</h3></div>";
                    mysqli_stmt_close($stmt);
                    return;
                }
            } else {
                echo "<div class='container'><h3>Error al ejecutar la consulta de proyecto: " . htmlspecialchars(mysqli_error($conn)) . "</h3></div>";
                mysqli_stmt_close($stmt);
                return;
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "<div class='container'><h3>Error al preparar la consulta de proyecto: " . htmlspecialchars(mysqli_error($conn)) . "</h3></div>";
            return;
        }
    } else {
        echo "<div class='container'><h3>ID de proyecto no proporcionado.</h3></div>";
        return;
    }

    // Validar la estructura del JSON
    if (!is_array($json_data) 
        || !isset($json_data['prestaciones']) 
        || !is_array($json_data['prestaciones'])) {
        echo "<div class='container'><h3>No hay datos del proyecto para visualizar (JSON inválido).</h3></div>";
        return;
    }

    // ====================
    // Resumen de Recintos y Resumen de Equipos
    // (Recintos vuelve a la lógica original, Equipos mantiene la "Cantidad Final")
    // ====================

    $recintoSummary = [];
    $equiposSummary = [];

    if (!empty($json_data['prestaciones'])) {
        foreach ($json_data['prestaciones'] as $p) {
            $reqVal = floatval($p['REQUERIMIENTO']) ?: 0;

            // ================
            // 1) Resumen de RECINTOS (LÓGICA ORIGINAL)
            // ================
            // Se determina si la prestación tiene equipos tipo 2 o 5
            $prestacionHasType2or5 = false;
            $recintosType2or5 = [];

            foreach ($p['EQUIPOS'] as $eq) {
                $tipoEquipo = $eq['TIPO_EQUIPO'] ?? '';
                $tipoNumber = (int) str_replace('Tipo ', '', $tipoEquipo);

                if (in_array($tipoNumber, [2, 5])) {
                    $prestacionHasType2or5 = true;
                    $recinto = $eq['RECINTO'] ?? '';
                    if ($recinto !== '') {
                        $recintosType2or5[] = $recinto;
                    }
                }
            }

            if ($prestacionHasType2or5) {
                // Eliminar duplicados de recintos
                $recintosType2or5 = array_unique($recintosType2or5);
                foreach ($recintosType2or5 as $rec) {
                    if (!isset($recintoSummary[$rec])) {
                        $recintoSummary[$rec] = 0;
                    }
                    // Se suma ceil($reqVal)
                    $recintoSummary[$rec] += ceil($reqVal);
                }
            } else {
                // Prestación sin equipos tipo 2 o 5
                // => Se asigna 1 recinto (el primero que aparezca en p['EQUIPOS'])
                $recinto = '';
                if (!empty($p['EQUIPOS'])) {
                    $recinto = $p['EQUIPOS'][0]['RECINTO'] ?? '';
                }
                if ($recinto !== '') {
                    if (!isset($recintoSummary[$recinto])) {
                        $recintoSummary[$recinto] = 0;
                    }
                    $recintoSummary[$recinto] += 1;
                }
            }

            // ================
            // 2) Resumen de EQUIPOS (MANTENIENDO la LÓGICA DE CANT. FINAL)
            // ================
            // Para cada equipo, calculamos la "Cantidad Final" y la sumamos

            foreach ($p['EQUIPOS'] as $eq) {
                $tipoEquipo  = $eq['TIPO_EQUIPO'] ?? '';
                $tipoNumber  = (int) str_replace('Tipo ', '', $tipoEquipo);
                $nombreEq    = $eq['EQUIPO'] ?? 'Equipo desconocido';
                $cantNorm    = isset($eq['CANTIDAD']) ? (int)$eq['CANTIDAD'] : 0;

                // Mismo cálculo de la columna "Cantidad Final" de la tabla
                $cantidadFinal = 0;
                if (in_array($tipoNumber, [1,2,6])) {
                    // Cant. Final = ceil(cantNorm)
                    $cantidadFinal = ceil($cantNorm);
                } 
                else if (in_array($tipoNumber, [3,4,5])) {
                    // Cant. Final = max( ceil(cantNorm), ceil(reqVal) )
                    $cantidadFinal = max( ceil($cantNorm), ceil($reqVal) );
                } else {
                    // Cualquier otro tipo => similar a 3,4,5
                    $cantidadFinal = max( ceil($cantNorm), ceil($reqVal) );
                }

                if (!isset($equiposSummary[$nombreEq])) {
                    $equiposSummary[$nombreEq] = 0;
                }
                $equiposSummary[$nombreEq] += $cantidadFinal;
            }
        }
    }

    // ====================
    // Mostrar en pantalla
    // ====================
    echo "<div class='container my-5'>
            <h1 class='text-center mb-4'>Visualización del Proyecto</h1>
            <h3>Nombre del Proyecto: " . htmlspecialchars($nombre_proyecto) . "</h3>";

    if ($proyecto_guardado) {
        echo "<div class='alert alert-success'>Proyecto Guardado.</div>";
    }

    // Enlaces a PDF / Excel (si ya está guardado o se tiene ID)
    if ($proyecto_guardado || $id_proyeccion) {
        echo "<a style='margin-bottom: 20px; margin-right: 10px;' 
                  href='/eph/generar_pdf_eph.php?id={$id_proyeccion}' 
                  class='btn btn-danger' 
                  target='_blank'>
                  Descargar Proyecto en PDF
              </a>";
        
        echo "<a style='margin-bottom: 20px;' 
                  href='/eph/generar_xls_eph.php?id={$id_proyeccion}' 
                  class='btn btn-success' 
                  target='_blank'>
                  Descargar Proyecto en Excel
              </a>";
    }

    // Botones para ver "resumen de recintos" y "resumen de equipos"
    echo "
        <button type='button' 
                class='btn btn-info' 
                style='margin-left: 10px; margin-bottom: 20px;'
                onclick='abrirModalRecintos()'>
            Ver Resumen de Recintos
        </button>
    ";

    echo "
        <button type='button' 
                class='btn btn-secondary' 
                style='margin-left: 10px; margin-bottom: 20px;'
                onclick='abrirModalEquipos()'>
            Ver Resumen de Equipos
        </button>
    ";

    // ============================================================================================
    // TABLA PRINCIPAL
    // ============================================================================================
    echo "<table class='table table-bordered'>
            <thead>
                <tr>
                    <th>Área</th>
                    <th>Código</th>
                    <th>Prestación</th>
                    <th>Equipo</th>
                    <th>Tipo de Equipo</th>
                    <th>Recinto</th>
                    <th>Cantidad Equipo (Norma)</th>
                    <th>Tiempo Procedimiento (Minutos)</th>
                    <th>Demanda Anual Procedimiento</th>
                    <th>Cálculo de demanda del EEMM</th>
                    <th>Cantidad Final</th>
                </tr>
            </thead>
            <tbody>";

    if (!empty($json_data['prestaciones'])) {
        foreach ($json_data['prestaciones'] as $p) {
            $reqValBase = floatval($p['REQUERIMIENTO']) ?: 0;

            foreach ($p['EQUIPOS'] as $eq) {
                $eqName     = htmlspecialchars($eq['EQUIPO']);
                $recName    = htmlspecialchars($eq['RECINTO']);
                $cantNorm   = isset($eq['CANTIDAD']) ? (int)$eq['CANTIDAD'] : 0;
                $tipoEquipo = htmlspecialchars($eq['TIPO_EQUIPO'] ?? '');
                $tipoNumber = (int) str_replace('Tipo ', '', $eq['TIPO_EQUIPO'] ?? '');

                // Cálculo de la columna “Cálculo de demanda del EEMM”
                $calcDemandaStr = '';
                if (in_array($tipoNumber, [1,2,6])) {
                    $calcDemandaStr = "No aplica";
                } 
                elseif (in_array($tipoNumber, [3,4])) {
                    if ($reqValBase <= 1) {
                        $calcDemandaStr = "1";
                    } else {
                        $calcDemandaStr = number_format($reqValBase, 2, '.', '');
                    }
                }
                elseif ($tipoNumber === 5) {
                    $calcDemandaStr = number_format($reqValBase, 2, '.', '');
                }
                else {
                    $calcDemandaStr = number_format($reqValBase, 2, '.', '');
                }

                // Cálculo “Cantidad Final”
                $cantidadFinal = 0;
                if (in_array($tipoNumber, [1,2,6])) {
                    $cantidadFinal = ceil($cantNorm);
                } else {
                    $cantidadFinal = max(ceil($cantNorm), ceil($reqValBase));
                }

                // Para equipos Tipo 1, se muestran solo 4 columnas + Cant. Final
                if ($tipoNumber === 1) {
                    echo "<tr>
                            <td>".htmlspecialchars($p['AREA'])."</td>
                            <td>-</td>
                            <td>-</td>
                            <td>{$eqName}</td>
                            <td>{$tipoEquipo}</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>{$cantidadFinal}</td>
                          </tr>";
                }
                else {
                    // Para el resto, las 11 columnas
                    echo "<tr>
                            <td>".htmlspecialchars($p['AREA'])."</td>
                            <td>".htmlspecialchars($p['COD_PRESTACION'])."</td>
                            <td>".htmlspecialchars($p['NOMBRE_PRESTACION'])."</td>
                            <td>{$eqName}</td>
                            <td>{$tipoEquipo}</td>
                            <td>{$recName}</td>
                            <td>{$cantNorm}</td>
                            <td>".htmlspecialchars($p['TIEMPO_PROCEDIMIENTO'])."</td>
                            <td>".htmlspecialchars($p['DEMANDA_PROCEDIMIENTO'])."</td>
                            <td>{$calcDemandaStr}</td>
                            <td>{$cantidadFinal}</td>
                          </tr>";
                }
            }
        }
    }

    echo "  </tbody>
          </table>
          </div>";

    // ===========================
    // MODAL RESUMEN DE RECINTOS
    // ===========================
    ?>
    <style>
        /* Modal JS Puro */
        .modal-custom {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        .modal-content-custom {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin: 50px auto;
            position: relative;
        }
        .modal-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            color: #fff;
            background: #d9534f;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
        }
        .modal-close-btn:hover {
            opacity: 0.8;
        }
        .table thead th { background-color: #003c58; color: white; text-align: center; }

        /* Para que el resumen de EQUIPOS tenga scroll si es grande */
        #modalEquipos .modal-content-custom {
            max-height: 80vh; 
            overflow-y: auto;
        }
    </style>

    <!-- Modal Recintos -->
    <div id="modalRecintos" class="modal-custom">
        <div class="modal-content-custom">
            <button class="modal-close-btn" onclick="cerrarModalRecintos()">Cerrar</button>
            <h4>Resumen de Recintos</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Recinto</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($recintoSummary)) {
                        foreach ($recintoSummary as $r => $val) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($r) . "</td>
                                    <td>" . number_format($val, 0, '.', '') . "</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No hay datos de Recintos.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Equipos -->
    <div id="modalEquipos" class="modal-custom">
        <div class="modal-content-custom">
            <button class="modal-close-btn" onclick="cerrarModalEquipos()">Cerrar</button>
            <h4>Resumen de Equipos</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Total (Cantidad Final)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($equiposSummary)) {
                        foreach ($equiposSummary as $eqName => $totalEq) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($eqName) . "</td>
                                    <td>" . number_format($totalEq, 0, '.', '') . "</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No hay datos de Equipos.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Funciones para abrir y cerrar los modales
        function abrirModalRecintos() {
            document.getElementById('modalRecintos').style.display = 'block';
        }
        function cerrarModalRecintos() {
            document.getElementById('modalRecintos').style.display = 'none';
        }

        function abrirModalEquipos() {
            document.getElementById('modalEquipos').style.display = 'block';
        }
        function cerrarModalEquipos() {
            document.getElementById('modalEquipos').style.display = 'none';
        }

        // Cerrar los modales al hacer clic fuera de su contenido
        window.onclick = function(event) {
            const modalRecintos = document.getElementById('modalRecintos');
            const modalEquipos = document.getElementById('modalEquipos');
            if (event.target == modalRecintos) {
                modalRecintos.style.display = "none";
            }
            if (event.target == modalEquipos) {
                modalEquipos.style.display = "none";
            }
        }
    </script>
    <?php
}