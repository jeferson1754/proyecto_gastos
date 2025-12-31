<?php

include('bd.php');
require_once('funciones.php');


$cantidad_meses_balance = isset($_GET['cantidad_meses']) ? $_GET['cantidad_meses'] : 6;

$minRepeticiones = 5;

$fecha_inicio = date('Y-m-01');

$fecha_fin = date('Y-m-t');

// Crear un arreglo para manejar las categorías y sus where correspondientes
$modulos = [
    'Gastos' => $where_gastos,
    'Ocio' => $where_ocio,
    'Ahorros' => $where_ahorros
];

// Crear un arreglo para almacenar los resultados
$resultados = [];

foreach ($modulos as $nombre_categoria => $where_clause) {
    // Llamar a la función con cada where y almacenar los resultados
    $resultados[$nombre_categoria] = obtener_datos($conexion, $where_clause, $current_month, $current_year, $previous_month, $previous_year);
}

// Acceder a los resultados de cada categoría
$total_gastos = $resultados['Gastos']['total_general'];
$result_detalles_gastos = $resultados['Gastos']['detalles'];
$anterior_total_gastos = $resultados['Gastos']['anterior_sistema'];

$total_ocio = $resultados['Ocio']['total_general'];
$result_detalles_ocio = $resultados['Ocio']['detalles'];
$anterior_total_ocio = $resultados['Ocio']['anterior_sistema'];

$total_ahorros = $resultados['Ahorros']['total_general'];
$result_detalles_ahorros = $resultados['Ahorros']['detalles'];
$anterior_total_ahorros = $resultados['Ahorros']['anterior_sistema'];

// --- ACCESO A RESULTADOS DE GASTOS ---
$total_gastos_externo  = $resultados['Gastos']['total_externo']; // El informativo
$total_gastos_real     = $resultados['Gastos']['total_sistema']; // La suma de ambos

// --- ACCESO A RESULTADOS DE OCIO ---
$total_ocio_externo    = $resultados['Ocio']['total_externo'];
$total_ocio_real       = $resultados['Ocio']['total_sistema'];

// --- ACCESO A RESULTADOS DE AHORROS ---
$total_ahorros_externo = $resultados['Ahorros']['total_externo'];
$total_ahorros_real    = $resultados['Ahorros']['total_sistema'];


// --- TOTALES GLOBALES PARA EL PROMPT ---
$total_sistema = $total_ahorros_real + $total_ocio_real + $total_ahorros_real;
$total_externo = $total_gastos_externo + $total_ocio_externo + $total_ahorros_externo;
$gasto_total_general = $total_gastos + $total_ocio + $total_ahorros;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://fastly.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <title>Resumen Financiero</title>
    <link rel="stylesheet" href="styles.css?<?php echo time() ?>">
</head>

<body>
    <div class="container">
        <div class="row align-items-center mt-3">
            <div class="col-6">
                <button type="button"
                    class="btn btn-warning rounded-circle d-flex align-items-center justify-content-center shadow-sm hover-scale"
                    onclick="window.location.href='./Pagos/'"
                    style="width: 50px; height: 50px;">
                    <i class="fa-solid fa-wallet fs-5"></i>
                </button>
            </div>
            <div class="col-6 text-end">
                <h5 class="text-muted mb-0"><?php echo "$mes $current_year"; ?></h5>
            </div>
        </div>


        <!-- Selector de cantidad de meses -->
        <div class="row mb-2">
            <div class="ocultar">
                <div class="col-12 col-md-8 mx-auto ">
                    <div class="d-flex align-items-center justify-content-center ">
                        <label for="mesesSelect" class="form-label me-3 mb-0 fw-bold">Mostrar últimos:</label>
                        <select id="mesesSelect"
                            class="form-select form-select-sm w-auto"
                            onchange="cambiarCantidadMeses()">
                            <option value="3" <?php echo ($cantidad_meses_balance == 3) ? 'selected' : ''; ?>>3 meses</option>
                            <option value="6" <?php echo ($cantidad_meses_balance == 6) ? 'selected' : ''; ?>>6 meses</option>
                            <option value="12" <?php echo ($cantidad_meses_balance == 12) ? 'selected' : ''; ?>>12 meses</option>
                            <option value="24" <?php echo ($cantidad_meses_balance == 24) ? 'selected' : ''; ?>>24 meses</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Ingresos vs Egresos -->
        <div class="row mb-3">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4 fw-bold">
                            Ingresos vs Egresos de los Últimos <?php echo "$cantidad_meses_balance"; ?> Meses
                        </h4>

                        <!-- Contenedor del gráfico -->
                        <div id="grafico-ingresos-egresos" class="mb-0" style="height: 300px;"></div>

                        <?php
                        $datos_financieros = obtener_datos_ultimos_meses($conexion, $cantidad_meses_balance);
                        $ultimo_mes = end($datos_financieros);
                        $balance_mes_actual = $ultimo_mes['ingresos'] - $ultimo_mes['egresos'];
                        $total_ingresos = $ultimo_mes['ingresos'];
                        ?>

                        <div class="text-center py-3 bg-light rounded-3 mb-4">
                            <h5 class="text-secondary mb-3">Balance del Mes Actual</h5>
                            <h2 class="display-6 mb-0">
                                <?php if ($balance_mes_actual < 0): ?>
                                    <span class="text-danger">
                                        $<?php echo number_format($balance_mes_actual, 0, '', '.'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        $<?php echo number_format($balance_mes_actual, 0, '', '.'); ?>
                                    </span>
                                <?php endif; ?>
                            </h2>
                        </div>

                        <!-- Botones con mejor diseño y espaciado -->
                        <div class="d-flex justify-content-center align-items-center gap-3">
                            <!-- Botón Añadir Ingreso -->
                            <button type="button"
                                class="btn btn-primary d-inline-flex align-items-center px-4 py-2 shadow-sm hover-lift"
                                data-bs-toggle="modal"
                                data-bs-target="#modalIngresos">
                                <i class="bi bi-plus-circle me-2"></i>
                                Añadir Ingreso
                            </button>

                            <!-- Botón de Búsqueda -->
                            <button type="button"
                                class="btn btn-secondary align-items-center justify-content-center shadow-sm hover-lift ocultar"
                                onclick="window.location.href='./Busqueda/general.php'"
                                style="width: 42px; height: 42px;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>

                            <button type="button"
                                class="btn btn-info align-items-center px-4 py-2 shadow-sm hover-lift ocultar"
                                data-bs-toggle="modal"
                                data-bs-target="#modalAgenteFinanciero" style="color:white;">
                                <i class="bi bi-robot me-2"></i>
                                Agente Financiero
                            </button>

                            <!-- Botón Dashboard -->
                            <a href="./resumen.php"
                                class="btn btn-outline-secondary d-inline-flex align-items-center px-4 py-2 shadow-sm hover-lift">
                                <i class="bi bi-graph-up me-2"></i>
                                Dashboard Gastos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function cambiarCantidadMeses() {
                let meses = document.getElementById("mesesSelect").value;
                window.location.href = window.location.pathname + "?cantidad_meses=" + meses;
            }
        </script>


        <!--CARTAS 50%, 30% , 20% -->
        <div class="row mb-4">
            <div class="col-md-4 responsivo">
                <div class="card h-100 ">
                    <div class="card-body text-center budget-item ">
                        <h3 class="text-warning">50%</h3>
                        <h5>$
                            <?php $gastos = $total_ingresos * 0.5;
                            echo number_format($gastos, 0, '', '.'); ?>
                        </h5>
                        <p class="text-muted">Gastos y Cuentas</p>
                        <?php
                        // Uso para Gastos
                        echo mostrarBarraProgreso(
                            $total_gastos,  // Variable de tu código original
                            $gastos        // Variable de tu código original que representa el 50%
                        );
                        ?>
                    </div>

                </div>
            </div>
            <div class="col-md-4 responsivo">
                <div class="card h-100">
                    <div class="card-body text-center budget-item">
                        <h3 class="text-success">30%</h3>
                        <h5>$
                            <?php $ocio = $total_ingresos * 0.3;
                            echo number_format($ocio, 0, '', '.'); ?>
                        </h5>
                        <p class="text-muted">Ocio</p>
                        <?php
                        // Uso para Gastos
                        echo mostrarBarraProgreso(
                            $total_ocio,  // Variable de tu código original
                            $ocio        // Variable de tu código original que representa el 50%
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center budget-item">
                        <h3 class="text-info">20%</h3>
                        <h5>$
                            <?php $ahorro = $total_ingresos * 0.2;
                            echo number_format($ahorro, 0, '', '.'); ?>
                        </h5>
                        <p class="text-muted">Ahorro e Inversión</p>
                        <?php
                        // Uso para Ahorro
                        echo mostrarBarraProgreso(
                            $total_ahorros, // Variable de tu código original
                            $ahorro        // Variable de tu código original que representa el 20%
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $gastos_restante = $gastos - $total_gastos;
        $ocio_restante = $ocio - $total_ocio;
        $ahorros_restante = $ahorro - $total_ahorros;

        ?>

        <!--DETALLES 50%, 30% , 20% -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center category-icon text-warning">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h4 class="card-title text-center mb-4">Gastos y Cuentas</h4>


                        <div class="alert alert-warning">
                            Valor Actual:
                            <?php

                            $color_gastos_detalles = obtenerColor($gastos, $total_gastos);

                            echo "<i class=" . $color_gastos_detalles . ">$ " . number_format($total_gastos, 0, '', '.') . "</i>";

                            ?>
                        </div>

                        <div class="detalles-container">
                            <ul class="list-group list-group-flush">
                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles_gastos)): ?>
                                    <?php
                                    // Validamos si es externo o sistema
                                    $es_externo = (isset($detalle['fuente']) && $detalle['fuente'] === 'externo');
                                    ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <div class="d-flex align-items-center">

                                            <span class="text-truncate" style="max-width: 180px;">
                                                <?= htmlspecialchars($detalle['Descripcion']) ?>
                                            </span>
                                        </div>

                                        <span class="badge <?php echo $es_externo ? 'bg-secondary' : 'bg-warning'; ?> rounded-pill">
                                            $<?= number_format($detalle['Valor'], 0, '', '.') ?>
                                        </span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <!-- Botón para añadir gastos -->
                        <div class="text-center mt-4">
                            <a href="./Busqueda/?categoria=Gastos">
                                <button type="button" class="btn btn-secondary" style="color:white">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                            </a>
                            <button type="button" class="btn btn-warning" style="color:white" data-bs-toggle="modal" data-bs-target="#modalGastos">
                                Añadir Gasto
                            </button>
                            <button type="button" class="btn btn-secondary" style="color:white" data-bs-toggle="modal" data-bs-target="#modalDetalleGastos">
                                <i class="fa-solid fa-circle-info"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center category-icon text-success">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h4 class="card-title text-center mb-4">Ocio</h4>
                        <div class="alert alert-success">
                            Valor Actual:
                            <?php

                            $color_ocio_detalle = obtenerColor($ocio, $total_ocio);

                            echo "<i class=" . $color_ocio_detalle . ">$ " . number_format($total_ocio, 0, '', '.') . "</i>";

                            ?>
                        </div>

                        <div class="detalles-container ocio">
                            <ul class="list-group list-group-flush">
                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles_ocio)): ?>
                                    <?php
                                    // Validamos si es externo o sistema
                                    $es_externo = (isset($detalle['fuente']) && $detalle['fuente'] === 'externo');
                                    ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <div class="d-flex align-items-center">

                                            <span class="text-truncate" style="max-width: 180px;">
                                                <?= htmlspecialchars($detalle['Descripcion']) ?>
                                            </span>
                                        </div>

                                        <span class="badge <?php echo $es_externo ? 'bg-secondary' : 'bg-success'; ?> rounded-pill">
                                            $<?= number_format($detalle['Valor'], 0, '', '.') ?>
                                        </span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <div class="text-center mt-4">
                            <a href="./Busqueda/?categoria=Ocio">
                                <button type="button" class="btn btn-secondary" style="color:white">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                            </a>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalOcio">
                                Añadir Ocio
                            </button>
                            <button type="button" class="btn btn-secondary" style="color:white" data-bs-toggle="modal" data-bs-target="#modalDetalleOcio">
                                <i class="fa-solid fa-circle-info"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center category-icon text-info">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <h4 class="card-title text-center mb-4">Ahorro e Inversión</h4>

                        <div class="alert alert-info">
                            Valor Actual:
                            <?php

                            $color_ahorro_detalle = obtenerColor($ahorro, $total_ahorros);


                            echo "<i class=" . $color_ahorro_detalle . ">$ " . number_format($total_ahorros, 0, '', '.') . "</i>";

                            ?>
                        </div>
                        <div class="detalles-container ahorro">
                            <ul class="list-group list-group-flush ahorro">
                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles_ahorros)): ?>
                                    <?php
                                    // Validamos si es externo o sistema
                                    $es_externo = (isset($detalle['fuente']) && $detalle['fuente'] === 'externo');
                                    ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <div class="d-flex align-items-center">

                                            <span class="text-truncate" style="max-width: 180px;">
                                                <?= htmlspecialchars($detalle['Descripcion']) ?>
                                            </span>
                                        </div>

                                        <span class="badge <?php echo $es_externo ? 'bg-secondary' : 'bg-info'; ?> rounded-pill">
                                            $<?= number_format($detalle['Valor'], 0, '', '.') ?>
                                        </span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <div class="text-center mt-4">
                            <div class="text-center mt-4">
                                <a href="./Busqueda/?categoria=Ahorros">
                                    <button type="button" class="btn btn-secondary" style="color:white">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </a>
                                <button type="button" class="btn btn-info" style="color:white" data-bs-toggle="modal" data-bs-target="#modalAhorro">
                                    Añadir Ahorros
                                </button>
                                <button type="button" class="btn btn-secondary" style="color:white" data-bs-toggle="modal" data-bs-target="#modalDetalleAhorro">
                                    <i class="fa-solid fa-circle-info"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php

        // Calcular diferencias
        $anterior_gastos = $anterior_total_gastos - $total_gastos;
        $anterior_ocio = $anterior_total_ocio - $total_ocio;
        $anterior_ahorros = $anterior_total_ahorros - $total_ahorros;

        $color_gastos_restante = obtenerColor($gastos, $total_gastos);
        $color_ocio_restante = obtenerColor($ocio, $total_ocio);
        $color_ahorro_restante = obtenerColor($ahorro, $total_ahorros);

        // Obtener colores para historico
        $color_gastos_historico = obtenerColor($anterior_total_gastos, $total_gastos);
        $color_ocio_historico = obtenerColor($anterior_total_ocio, $total_ocio);
        $color_ahorro_historico = obtenerColor($anterior_total_ahorros, $total_ahorros);

        $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);

        $resultado1 = ejecutar_consulta($pdo, $where_gastos, $cantidad_meses_balance);
        $total_categorias_gastos = $resultado1['categorias'];
        $suma_total_gastos = $resultado1['suma_total'];

        $resultado2 = ejecutar_consulta($pdo, $where_ocio, $cantidad_meses_balance);
        $total_categorias_ocio = $resultado2['categorias'];
        $suma_total_ocio = $resultado2['suma_total'];

        $resultado3 = ejecutar_consulta($pdo, $where_ahorros, $cantidad_meses_balance);

        $total_categorias_ahorro = $resultado3['categorias'];
        $suma_total_ahorro = $resultado3['suma_total'];

        $fecha = "MONTH(g.Fecha) = $current_month AND YEAR(g.Fecha) = $current_year";

        $resultado4 = ejecutar_consulta($pdo, "$fecha AND ($where_gastos)", $cantidad_meses_balance);
        $categorias_gastos = $resultado4['categorias'];

        $resultado5 = ejecutar_consulta($pdo, "$fecha AND ($where_ocio)", $cantidad_meses_balance);
        $categorias_ocio = $resultado5['categorias'];

        $resultado6 = ejecutar_consulta($pdo, "$fecha AND ($where_ahorros)", $cantidad_meses_balance);
        $categorias_ahorro = $resultado6['categorias'];

        include('modal_ingresos.php');
        include('modal_gastos.php');
        include('modal_ocio.php');
        include('modal_ahorro.php');
        include('modal_detalle_gastos.php');
        include('modal_detalle_ocio.php');
        include('modal_detalle_ahorro.php');


        $stmt_pagos_futuros = $pdo->prepare("
            SELECT 
                p.ID,
                p.Cuenta,
                p.Valor,
                DATE_FORMAT(p.Fecha_Vencimiento, '%d/%m/%Y') AS Fecha_Vencimiento
            FROM pagos p
            WHERE p.Estado = 'Pendiente'
            AND p.Fecha_Vencimiento > :fin_mes
            ORDER BY p.Fecha_Vencimiento ASC
            LIMIT 10
        ");

        $stmt_pagos_futuros->execute([
            ':fin_mes' => $fecha_fin
        ]);




        $stmt_pagos_mes_actual = $pdo->prepare("
            SELECT 
                p.ID,
                p.Cuenta,
                p.Valor,
                DATE_FORMAT(p.Fecha_Vencimiento, '%d/%m/%Y') AS Fecha_Vencimiento
            FROM pagos p
            WHERE p.Estado = 'Pendiente'
            AND p.Fecha_Vencimiento BETWEEN :inicio_mes AND :fin_mes
            ORDER BY p.Fecha_Vencimiento ASC
        ");

        $stmt_pagos_mes_actual->execute([
            ':inicio_mes' => $fecha_inicio,
            ':fin_mes'    => $fecha_fin
        ]);


        function generarTablaPagosMarkdown(array $pagos, bool $mostrar_total = true): string
        {
            if (empty($pagos)) {
                return "| Sin pagos pendientes |  |  |\n";
            }

            $tabla  = "";
            $total  = 0;

            foreach ($pagos as $pago) {
                $concepto     = htmlspecialchars($pago['Cuenta']);
                $monto        = number_format($pago['Valor'], 0, ',', '.');
                $vencimiento  = $pago['Fecha_Vencimiento'];

                $tabla .= "| {$concepto} | {$monto} | {$vencimiento} |\n";
                $total += $pago['Valor'];
            }

            if ($mostrar_total) {
                $total_formateado = number_format($total, 0, ',', '.');
                $tabla .= "| **TOTAL** | **{$total_formateado}** |  |\n";
            }

            return $tabla;
        }


        // --- INICIO: GENERACIÓN DEL PROMPT DE ANÁLISIS ---

        // Función auxiliar para generar las filas de subcategorías
        function generar_filas_subcategorias($titulo_general, $categorias_array, $suma_total_modulo)
        {
            $filas = "";

            if (!empty($categorias_array) && $suma_total_modulo > 0) {
                foreach ($categorias_array as $detalle) {

                    $valor_detalle = floatval($detalle['total_categoria']);
                    $descripcion = htmlspecialchars(str_replace("|", "¦", $detalle['categoria']));
                    $porcentaje = ($valor_detalle / $suma_total_modulo) * 100;

                    $filas .= "| $titulo_general | $descripcion | $valor_detalle | " . number_format($porcentaje, 2) . "% |\n";
                }
            } else {
                $filas .= "| $titulo_general | — | 0 | 0.00% |\n";
            }
            return $filas;
        }

        $pagos_mes_actual = $stmt_pagos_mes_actual->fetchAll(PDO::FETCH_ASSOC);

        $tabla_pagos_pendientes = generarTablaPagosMarkdown($pagos_mes_actual);

        $pagos_futuros = $stmt_pagos_futuros->fetchAll(PDO::FETCH_ASSOC);

        $tabla_pagos_pendientes_futuros = generarTablaPagosMarkdown(
            $pagos_futuros,
            false // no mostrar total
        );


        // 1. Datos del Mes Actual
        $gasto_total_mes_actual = $total_gastos + $total_ocio + $total_ahorros;

        // Modificación en el Prompt completo (Variables a insertar)
        $seccion1 = "
            ### 1. Datos Clave del Mes Actual

            | Métrica | Valor |
            |--------|--------|
            | **Ingreso Total del Mes (A)** | $total_ingresos |
            | **Gasto del Sistema (B1 - Afecta liquidez)** | $total_sistema |
            | **Gasto Externo (B2 - Informativo)** | $total_externo |
            | **Gasto Total Real (B1 + B2)** | " . ($total_sistema + $total_externo) . " |
            | **Balance de Liquidez (A - B1)** | $balance_mes_actual |
            ";


        // 2. Regla 50/30/20
        $seccion2 = "
        ### 2. Cumplimiento de la Regla 50/30/20

        | Categoría | Objetivo | Gasto Real | Diferencia |
        |-----------|----------|------------|------------|
        | **Necesarios (50%)** | $gastos | $total_gastos | $gastos_restante |
        | **Ocio (30%)** | $ocio | $total_ocio | $ocio_restante |
        | **Ahorro/Inv. (20%)** | $ahorro | $total_ahorros | $ahorros_restante |
        ";

        // 3. Histórico Mensual
        $filas_historico = "| Mes | Ingresos | Egresos | Balance |\n|-----|----------|---------|---------|\n";
        foreach ($datos_financieros as $item) {
            $balance = $item['ingresos'] - $item['egresos'];
            $filas_historico .= "| {$item['mes']} | {$item['ingresos']} | {$item['egresos']} | $balance |\n";
        }

        $seccion3 = "
        ### 3. Histórico Mensual ($cantidad_meses_balance meses)

        $filas_historico
        ";

        // 4. Subcategorías
        $filas_distribucion = "| Categoría General | Subcategoría | Total | % |\n|------------------|--------------|-------|-------|\n";

        $filas_distribucion .= generar_filas_subcategorias("gastos", $categorias_gastos, $total_gastos);
        $filas_distribucion .= generar_filas_subcategorias("ocio", $categorias_ocio, $total_ocio);
        $filas_distribucion .= generar_filas_subcategorias("ahorros", $categorias_ahorro, $total_ahorros);

        $seccion4 = "
        ### 4. Distribución de Egresos por Subcategoría

        $filas_distribucion
        ";

        // Prompt completo
        $prompt_analisis = "
        **Rol:** Agente Financiero Personal Inteligente  

        **Perfil del Agente:**  
        Eres un asesor financiero especializado en control de gastos personales, análisis de ahorro, proyección financiera y toma de decisiones responsables.  
        Tu enfoque es práctico, conservador y orientado a estabilidad financiera a largo plazo.Tienes la capacidad de diferenciar entre gastos que afectan la liquidez de mis cuentas (Gastos de Sistema) y gastos realizados con fondos externos (Gastos Informativos), permitiéndote analizar mi nivel de consumo total sin distorsionar el saldo real de mis cuentas bancarias.

        **Objetivo Principal:**  
        Analizar mis finanzas actuales e históricas para:
        - Evaluar mi salud financiera real
        - Detectar riesgos y oportunidades
        - Apoyar decisiones financieras importantes (gastos grandes, ajustes de ahorro, reducción de deudas)
        - Analizar mi balance mensual y evolución de ahorros en el tiempo
        - Considerar pagos pendientes del mes actual como compromisos obligatorios
        
        ---

       ## 📌 Instrucciones de Análisis

        Debes:
        1. Considerar **datos del mes actual + histórico**
        2. Evaluar cumplimiento de la **regla 50/30/20**
        3. Analizar tendencias (mejora, estancamiento o deterioro)
        4. Evaluar capacidad de ahorro y **liquidez real**
        5. Detectar meses en déficit o riesgo financiero
        6. Considerar **pagos pendientes del mes** como compromisos obligatorios
        7. Emitir recomendaciones claras, prudentes y accionables
        8. Diferenciación de Fondos: Debes identificar los gastos marcados como 'Externos'. Estos no deben restarse del balance de liquidez mensual, pero sí deben sumarse al calcular el cumplimiento de la regla 50/30/20, ya que representan un consumo real de recursos. 9. Análisis de Capacidad Real: Evalúa si el estilo de vida es sostenible basándote en el Ingreso Total vs. Gasto Total (incluyendo externo), pero emite alertas de riesgo solo basadas en el Balance del Sistema (liquidez).
    

        **Definición de riesgo a corto plazo:**  
        Incapacidad de cubrir gastos y pagos obligatorios del mes sin recurrir a endeudamiento.

        **Criterio de decisión:**  
        Para gastos importantes, adoptar siempre un enfoque conservador, priorizando estabilidad financiera.

        ---

        ## 📊 Datos Financieros

        $seccion1
        $seccion2
        $seccion3
        $seccion4


        ### 5. Pagos Pendientes del Mes Actual (OBLIGATORIOS)
        | Concepto | Monto | Vencimiento |
        |----------|-------|-------------|
        $tabla_pagos_pendientes

        ### 6. Compromisos Financieros Próximos (INFORMATIVO)
        | Concepto | Monto | Vencimiento |
        |----------|-------|-------------|
        $tabla_pagos_pendientes_futuros


        ---

        Considera SOLO los pagos del mes actual para el balance y toma de decisiones inmediatas.  
        Usa los pagos futuros solo para análisis de riesgo y planificación.

        ---

        ## 📤 Salida Esperada (OBLIGATORIA)

        ### 1️⃣ Resumen Ejecutivo
        - Estado financiero general (estable / en riesgo / saludable)
        - Balance del mes actual
        - Nivel de ahorro real

        ### 2️⃣ Diagnóstico Financiero
        - Evaluación del cumplimiento 50/30/20
        - Análisis del peso de gastos fijos y ocio
        - Impacto de pagos pendientes del mes

        ### 3️⃣ Análisis Histórico y Tendencias
        - Evolución de ingresos, egresos y ahorro
        - Identificación de patrones positivos o negativos
        - Comparación del mes actual con el promedio histórico

        ### 4️⃣ Evaluación para Decisiones Importantes
        - ¿Estoy en posición de asumir un gasto importante?
        - ¿Es recomendable aumentar ahorro o reducir gastos?
        - ¿Existe riesgo financiero a corto plazo?

        ### 5️⃣ Recomendaciones Accionables (máx. 3)
        Cada recomendación debe:
        - Ser concreta
        - Tener impacto financiero real
        - Indicar qué acción tomar y por qué

        ### 6️⃣ Alertas (si existen)
        - Déficit financiero
        - Ahorro insuficiente
        - Pagos pendientes que comprometen el balance

        **Tono:** Claro, directo y responsable.  
        **Evitar:** Teoría innecesaria o lenguaje genérico.
        ";



        include('modal_agente.php');

        ?>

        <!--GRAFICO RESTANTE 50%, 30% , 20% -->
        <div class="row mb-4">
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Gastos Restantes</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_gastos_restante . ">$" .

                            number_format($gastos_restante, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="gastos-restante" class="restante"></div>



                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ocio Restante</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_ocio_restante . ">$" .

                            number_format($ocio_restante, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="ocio-restante" class="restante"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ahorro Restante</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_ahorro_restante . ">$" .

                            number_format($ahorros_restante, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="ahorro-restante" class="restante"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--GRAFICO HISTORICO 50%, 30% , 20% -->
        <div class="row mb-4">
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Gastos Historicos</h3>
                        <?php
                        echo "
                        <h5 class=" . $color_gastos_historico . ">$" .

                            number_format($anterior_gastos, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="gastos-historico" class="restante"></div>
                        <div class="text-center mt-4">
                            <a href="./grafico_por_categoria.php?categoria=Gastos">
                                <button type="button" class="btn btn-warning" style="color:white">
                                    Ver Graficos
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ocios Historico</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_ocio_historico . ">$" .

                            number_format($anterior_ocio, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="ocio-historico" class="restante"></div>
                        <div class="text-center mt-4">
                            <a href="./grafico_por_categoria.php?categoria=Ocio">
                                <button type="button" class="btn btn-success" style="color:white">
                                    Ver Graficos
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ahorros Historicos</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_ahorro_historico . ">$" .

                            number_format($anterior_ahorros, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="ahorro-historico" class="restante"></div>
                        <div class="text-center mt-4">
                            <a href="./grafico_por_categoria.php?categoria=Ahorros">
                                <button type="button" class="btn btn-info" style="color:white">
                                    Ver Graficos
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--GRAFICO TOTAL 50%, 30% , 20% -->
        <div class="row mb-4">
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Total Gastos</h3>

                        <h5>$
                            <?php
                            echo number_format($suma_total_gastos, 0, '', '.');
                            ?>
                        </h5>
                        <div id="total-gastos" class="restante"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Total Ocio</h3>

                        <h5>$
                            <?php
                            echo number_format($suma_total_ocio, 0, '', '.');
                            ?>
                        </h5>
                        <div id="total-ocio" class="restante"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Total Ahorro</h3>

                        <h5>$
                            <?php
                            echo number_format($suma_total_ahorro, 0, '', '.');
                            ?>
                        </h5>
                        <div id="total-ahorro" class="restante"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container text-center">

            <!-- Botón para mostrar/ocultar iframe -->
            <button id="toggle-btn" class="btn btn-primary">Mostrar Graficos</button>

            <!-- Contenedor para el iframe -->
            <div id="iframe-container">
                <iframe src="./grafico_por_modulo.php?cantidad_meses=<?php echo $cantidad_meses_balance ?>"></iframe>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!--Grafico de Ingresos y Egresos-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnToggle = document.querySelector('.btn-toggle');
            const contenido = document.getElementById('contenido');

            btnToggle.addEventListener('click', function() {
                const isExpanded = btnToggle.getAttribute('aria-expanded') === 'true';
                btnToggle.textContent = isExpanded ? 'Ver menos' : 'Ver más';
            });
        });
        var dom = document.getElementById('grafico-ingresos-egresos');
        var myChart = echarts.init(dom, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });

        var datos = <?php echo json_encode($datos_financieros); ?>;
        var meses = datos.map(item => item.mes);
        var ingresos = datos.map(item => item.ingresos);
        var egresos = datos.map(item => item.egresos);

        var option = {
            legend: {
                top: '5%',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            toolbox: {
                feature: {
                    magicType: {
                        show: true,
                        type: ['line', 'bar']
                    }
                }
            },
            xAxis: {
                type: 'category',
                data: meses,
                axisLabel: {
                    interval: 0,
                    rotate: 30
                }
            },
            yAxis: {
                type: 'value'

            },
            grid: {
                left: '2%',
                right: '5%',
                bottom: '1%',
                containLabel: true
            },
            series: [{
                    name: 'Ingresos',
                    type: 'bar',
                    data: ingresos,
                    itemStyle: {
                        color: '#5470c6'
                    }
                },
                {
                    name: 'Egresos',
                    type: 'bar',
                    data: egresos,
                    itemStyle: {
                        color: '#ff3333'
                    }
                }


            ]
        };


        myChart.setOption(option);
        window.addEventListener('resize', myChart.resize);
    </script>

    <!-- Script para mostrar/ocultar iframe -->
    <script>
        document.getElementById('toggle-btn').addEventListener('click', function() {
            var iframeContainer = document.getElementById('iframe-container');
            if (iframeContainer.style.display === 'none') {
                iframeContainer.style.display = 'block';
                this.textContent = 'Ocultar Graficos'; // Cambiar texto del botón
            } else {
                iframeContainer.style.display = 'none';
                this.textContent = 'Mostrar Graficos'; // Cambiar texto del botón
            }
        });
    </script>

    <script>
        // Función para formatear el número como pesos chilenos
        function formatPesoChile(value) {
            value = value.replace(/\D/g, ''); // Eliminar todo lo que no sea un número
            return new Intl.NumberFormat('es-CL', {
                style: 'currency',
                currency: 'CLP'
            }).format(value);
        }

        // Obtener todos los campos de entrada con la clase 'monto_gasto'
        const montoInputs = document.querySelectorAll('.valor_formateado');

        // Evento para formatear el valor mientras el usuario escribe en cada campo
        montoInputs.forEach(function(montoInput) {
            montoInput.addEventListener('input', function() {
                let value = montoInput.value;
                montoInput.value = formatPesoChile(value); // Aplicar el formato de peso chileno
            });
        });
    </script>


    <?php
    //Graficos Pie Restantes
    piechart('gastos-restante', $categorias_gastos, $colores_gastos);
    piechart('ocio-restante', $categorias_ocio, $colores_ocios);
    piechart('ahorro-restante', $categorias_ahorro, $colores_ahorros);

    //Graficos Lineal Historico
    DatosHistoricos($where_gastos, $conexion, "gastos-historico", $colores_gastos, $cantidad_meses_balance);
    DatosHistoricos($where_ocio, $conexion, "ocio-historico", $colores_ocios, $cantidad_meses_balance);
    DatosHistoricos($where_ahorros, $conexion, "ahorro-historico", $colores_ahorros, $cantidad_meses_balance);

    //Graficos Pie Total
    bigchart('total-gastos', $total_categorias_gastos, $colores_gastos);
    bigchart('total-ocio', $total_categorias_ocio, $colores_ocios);
    bigchart('total-ahorro', $total_categorias_ahorro, $colores_ahorros);
    ?>
</body>

</html>