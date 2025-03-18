<?php

include('bd.php');
require_once('funciones.php');

$meses = isset($_GET['meses']) ? $_GET['meses'] : 6;
function obtener_dashboard($conexion, $meses)
{
    // Array para almacenar los datos de ingresos y egresos
    $datos = [];

    // Preparar la consulta SQL
    $stmt = $conexion->prepare("
        SELECT 
            SUM(CASE WHEN categorias_gastos.Nombre = 'Ingresos' THEN gastos.Valor ELSE 0 END) AS total_ingresos,
            SUM(CASE WHEN categorias_gastos.Nombre != 'Ingresos' THEN gastos.Valor ELSE 0 END) AS total_egresos
        FROM gastos 
        INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos 
        WHERE MONTH(gastos.Fecha) = ? AND YEAR(gastos.Fecha) = ? AND categorias_gastos.ID != 2 AND categorias_gastos.ID != 30
    ");

    // Iterar por los últimos meses
    for ($i = $meses - 1; $i >= 0; $i--) {
        $fecha = date('Y-m-01', strtotime("-$i month"));
        $mes = date('n', strtotime($fecha));
        $anio = date('Y', strtotime($fecha));

        // Ejecutar la consulta para cada mes
        $stmt->bind_param('ii', $mes, $anio);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Obtener los totales de ingresos y egresos
        $total_ingresos = $row['total_ingresos'] ?? 0;
        $total_egresos = $row['total_egresos'] ?? 0;

        // Almacenar los resultados en el array $datos
        $datos[] = [
            'mes' => obtener_nombre_mes_espanol($mes),
            'number_mes' => $anio . "-" . $mes,
            'anio' => $anio,
            'ingresos' => $total_ingresos,
            'egresos' => $total_egresos,
            'diferencia' => $total_ingresos - $total_egresos,
            'diferencia_porcentaje' => ($total_egresos != 0) ? (($total_ingresos - $total_egresos) / $total_egresos) * 100 : 0
        ];
    }

    // Calcular tendencia de diferencia
    for ($i = 1; $i < count($datos); $i++) {
        $datos[$i]['tendencia'] = $datos[$i]['diferencia'] <=> $datos[$i - 1]['diferencia'];
    }
    $datos[0]['tendencia'] = 0; // Sin tendencia para el primer mes
    // Invertir el orden de los elementos en el array
    $datos = array_reverse($datos);

    // Cerrar el statement
    $stmt->close();

    return $datos;
}

$dashboard = obtener_dashboard($conexion, $meses);

// Función para obtener los datos de la consulta SQL
function obtenerDatos($conexion, $id_categoria, $fecha)
{
    // Preparar la consulta SQL con parámetros
    $consulta = "
        SELECT 
            DATE_FORMAT(g.Fecha, '%Y-%m') AS Mes, 
            g.Fecha AS Dia, 
            g.Valor AS Monto, 
            cg.Nombre AS Categoria, 
            d.Detalle 
        FROM 
            (
                SELECT 
                    g.*, 
                    ROW_NUMBER() OVER (PARTITION BY DATE_FORMAT(g.Fecha, '%Y-%m') ORDER BY g.Valor DESC) AS rn 
                FROM 
                    gastos g 
                JOIN 
                    categorias_gastos cg ON g.ID_Categoria_Gastos = cg.ID 
                WHERE 
                    cg.Categoria_Padre = ? 
            ) AS g 
        JOIN 
            categorias_gastos cg ON g.ID_Categoria_Gastos = cg.ID 
        JOIN 
            detalle d ON g.ID_Detalle = d.ID 
        WHERE 
            g.rn = 1 
            AND DATE_FORMAT(g.Fecha, '%Y-%m') = ?
        ORDER BY 
            Mes DESC
        LIMIT 1;
    ";

    // Preparar la declaración SQL
    if ($stmt = $conexion->prepare($consulta)) {
        // Vincular los parámetros a la declaración
        $stmt->bind_param('is', $id_categoria, $fecha);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            // Obtener el resultado
            $resultado = $stmt->get_result();

            // Verificar si hay resultados
            if ($resultado->num_rows > 0) {
                // Crear un array para almacenar los datos
                $datos = array();

                // Recorrer los resultados y almacenarlos en el array
                while ($fila = $resultado->fetch_assoc()) {
                    $datos[] = $fila;
                }

                // Devolver los datos
                return $datos;
            } else {
                // Si no hay resultados, devolver un array vacío
                return array();
            }
        } else {
            // Error al ejecutar la consulta
            return array('error' => 'Error en la ejecución de la consulta.');
        }

        // Cerrar la declaración
        $stmt->close();
    } else {
        // Error al preparar la consulta
        return array('error' => 'Error al preparar la consulta.');
    }
}


function obtener_top_categorias($conexion, $meses)
{
    // Consulta para obtener las 5 categorías con más gastos
    $stmt = $conexion->prepare("
    SELECT 
        categorias_gastos.Nombre AS categoria, 
        SUM(gastos.Valor) AS total_gastos
    FROM gastos
    INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos
    WHERE categorias_gastos.ID NOT IN (1, 2, 30)  
      AND gastos.Fecha >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL $meses MONTH), '%Y-%m-01')
    GROUP BY categorias_gastos.Nombre
    ORDER BY total_gastos DESC
    LIMIT 5;

    ");

    $stmt->execute();
    $result = $stmt->get_result();
    $categorias_top = [];

    while ($row = $result->fetch_assoc()) {
        $categorias_top[] = [
            'categoria' => $row['categoria'],
            'total_gastos' => number_format($row['total_gastos'], 0, '', '.')
        ];
    }

    $stmt->close();
    return $categorias_top;
}

function obtener_top_categorias_repetidas($conexion, $meses)
{
    // Consulta para obtener las 5 categorías más repetidas y su total de gastos
    $stmt = $conexion->prepare("
        SELECT 
            categorias_gastos.Nombre AS categoria, 
            COUNT(*) AS cantidad_repeticiones,
            SUM(gastos.Valor) AS total_gastos
        FROM gastos
        INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos
        WHERE categorias_gastos.ID NOT IN (1, 2, 30)
        AND gastos.Fecha >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL $meses MONTH), '%Y-%m-01')
        GROUP BY categorias_gastos.Nombre
        ORDER BY cantidad_repeticiones DESC
        LIMIT 5
    ");

    $stmt->execute();
    $result = $stmt->get_result();
    $categorias_repetidas = [];

    while ($row = $result->fetch_assoc()) {
        $categorias_repetidas[] = [
            'categoria' => $row['categoria'],
            'repeticiones' => $row['cantidad_repeticiones'],
            'total_gastos' => number_format($row['total_gastos'], 0, '', '.')
        ];
    }

    $stmt->close();
    return $categorias_repetidas;
}

function obtener_promedio_gastos($conexion, $meses)
{
    // Consulta para obtener el promedio de gastos por categoría
    $stmt = $conexion->prepare("
        SELECT 
            categorias_gastos.Nombre AS categoria, 
            AVG(gastos.Valor) AS promedio_gastos
        FROM gastos
        INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos
        WHERE categorias_gastos.ID NOT IN (1, 2, 30)
        AND gastos.Fecha >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL $meses MONTH), '%Y-%m-01')
        GROUP BY categorias_gastos.Nombre
        ORDER BY `promedio_gastos` DESC
    ");

    $stmt->execute();
    $result = $stmt->get_result();
    $promedios_gastos = [];

    while ($row = $result->fetch_assoc()) {
        $promedios_gastos[] = [
            'categoria' => $row['categoria'],
            'promedio_gastos' => number_format($row['promedio_gastos'], 0, '', '.')
        ];
    }

    $stmt->close();
    return $promedios_gastos;
}


function obtener_gastos_menores($conexion, $meses)
{
    // Consulta para obtener las 5 categorías con el gasto más bajo
    $stmt = $conexion->prepare("
          SELECT 
        categorias_gastos.Nombre AS categoria, 
        SUM(gastos.Valor) AS gasto_minimo
    FROM gastos
    INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos
    WHERE categorias_gastos.ID NOT IN (1, 2, 30)  -- Usamos NOT IN para simplificar las condiciones
    AND gastos.Fecha >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL $meses MONTH), '%Y-%m-01')
    GROUP BY categorias_gastos.Nombre
    ORDER BY gasto_minimo ASC
    LIMIT 5;
    ");

    $stmt->execute();
    $result = $stmt->get_result();
    $gastos_menores = [];

    while ($row = $result->fetch_assoc()) {
        $gastos_menores[] = [
            'categoria' => $row['categoria'],
            'gasto_minimo' => number_format($row['gasto_minimo'], 0, '', '.')
        ];
    }

    $stmt->close();
    return $gastos_menores;
}

function formatMonth($number_mes)
{
    // Separamos el año y el mes usando explode
    list($anio, $mes) = explode('-', $number_mes);

    // Aseguramos que el mes tenga dos dígitos (agregamos un cero si es necesario)
    $mes = str_pad($mes, 2, '0', STR_PAD_LEFT);

    // Devolvemos el resultado con el formato adecuado
    return $anio . '-' . $mes;
}




?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financiero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #9b59b6;
            --text-color: #333;
            --background-color: #f4f6f9;
            --card-background: #ffffff;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Roboto', 'Segoe UI', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 15px;
        }

        .financial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .financial-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .financial-card {
            background-color: var(--card-background);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .financial-card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .financial-card-header h2 {
            font-size: 1.2rem;
            margin: 0;
            font-weight: 600;
        }

        .financial-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .financial-table th {
            background-color: #f8f9fa;
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 12px 15px;
        }

        .financial-table td {
            background-color: #f8f9fa;
            padding: 15px;
            font-weight: 500;
        }

        .financial-table .total-row {
            background-color: #e9ecef !important;
            font-weight: 700;
        }

        .trend-icon {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        .trend-up {
            color: var(--secondary-color);
        }

        .trend-down {
            color: #e74c3c;
        }

        .trend-neutral {
            color: #95a5a6;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .summary-card {
            background-color: var(--card-background);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-left: 5px solid;
        }

        .summary-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .summary-card-header i {
            margin-right: 12px;
            font-size: 1.5rem;
            opacity: 0.7;
        }

        .summary-card-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-color);
        }

        .summary-card-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f5;
        }

        .summary-card-item:last-child {
            border-bottom: none;
        }

        .summary-card-item .label {
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .summary-card-item .label i {
            margin-right: 10px;
            color: var(--accent-color);
            opacity: 0.7;
        }

        .summary-card-item .value {
            font-weight: 600;
        }

        .top-expenses {
            border-left-color: #3498db;
        }

        .repeated-categories {
            border-left-color: #2ecc71;
        }

        .average-expenses {
            border-left-color: #f39c12;
        }

        .lowest-expenses {
            border-left-color: #e74c3c;
        }

        .summary-card-content {
            max-height: 250px;
            overflow-y: auto;
            scrollbar-width: thin;
            /* Firefox */
            scrollbar-color: #f39c12 #f5f5f5;
            /* Color del thumb y track en Firefox */
        }

        /* Estilos específicos para WebKit (Chrome, Edge, Safari) */
        .summary-card-content::-webkit-scrollbar {
            width: 8px;
            /* Grosor de la barra */
        }

        .summary-card-content::-webkit-scrollbar-track {
            background: #f5f5f5;
            /* Color del fondo de la barra */
            border-radius: 15px;
        }

        .summary-card-content::-webkit-scrollbar-thumb {
            background: #f39c12;
            /* Color del thumb */
            border-radius: 15px;
            border: 2px solid #f5f5f5;
            /* Espacio alrededor del thumb */
        }

        .summary-card-content::-webkit-scrollbar-thumb:hover {
            background: #e67e22;
            /* Color al pasar el cursor */
        }

        .details-row {
            background-color: #f8f9fa;
        }

        .details-content {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin: 10px 0;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .toggle-details {
            transition: transform 0.3s ease;
        }

        .toggle-details:hover {
            transform: scale(1.1);
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="financial-header">
            <h1>Dashboard Financiero</h1>

            <div class="row mb-2">
                <div class="ocultar">
                    <div class="col-12 col-md-8 mx-auto ">
                        <div class="d-flex align-items-center justify-content-center ">
                            <label for="mesesSelect" class="form-label me-3 mb-0 fw-bold">Mostrar:</label>
                            <select id="mesesSelect"
                                class="form-select form-select-sm w-auto"
                                onchange="cambiarCantidadMeses()">
                                <option value="3" <?php echo ($meses == 3) ? 'selected' : ''; ?>>3 meses</option>
                                <option value="6" <?php echo ($meses == 6) ? 'selected' : ''; ?>>6 meses</option>
                                <option value="12" <?php echo ($meses == 12) ? 'selected' : ''; ?>>12 meses</option>
                                <option value="24" <?php echo ($meses == 24) ? 'selected' : ''; ?>>24 meses</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <span class="text-muted">Últimos <?php echo $meses ?> Meses</span>


        </div>

        <script>
            function cambiarCantidadMeses() {
                let meses = document.getElementById("mesesSelect").value;
                window.location.href = window.location.pathname + "?meses=" + meses;
            }
        </script>


        <div class="financial-card">
            <div class="financial-card-header">
                <h2>Resumen Financiero Mensual</h2>
                <i class="bi bi-graph-up text-white"></i>
            </div>
            <div class="table-responsive">
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Ingresos</th>
                            <th>Egresos</th>
                            <th>Diferencia</th>
                            <th>Tendencia</th>
                            <th>Acciones</th> <!-- Nueva columna para el botón -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totales_ingresos = 0;
                        $totales_egresos = 0;
                        $totales_diferencia = 0;

                        foreach ($dashboard as $row):
                            $totales_ingresos += $row['ingresos'];
                            $totales_egresos += $row['egresos'];
                            $totales_diferencia += $row['diferencia'];
                            $formatted_mes = formatMonth($row['number_mes']);
                        ?>
                            <tr>
                                <td><?php echo $row['mes'] . ' ' . $row['anio']; ?></td>
                                <td class="text-success">
                                    <i class="bi bi-cash-coin trend-icon"></i>
                                    $<?php echo number_format($row['ingresos'], 0, '', '.'); ?>
                                </td>
                                <td class="text-danger">
                                    <i class="bi bi-credit-card trend-icon"></i>
                                    $<?php echo number_format($row['egresos'], 0, '', '.'); ?>
                                </td>
                                <td class="<?php echo $row['diferencia'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <i class="bi bi-balance trend-icon"></i>
                                    $<?php echo number_format($row['diferencia'], 0, '', '.'); ?>
                                </td>
                                <td class="<?php echo $row['diferencia'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php if ($row['tendencia'] > 0): ?>
                                        <i class="bi bi-arrow-up-circle trend-icon trend-up"></i>
                                    <?php elseif ($row['tendencia'] < 0): ?>
                                        <i class="bi bi-arrow-down-circle trend-icon trend-down"></i>
                                    <?php else: ?>
                                        <i class="bi bi-arrow-right-circle trend-icon trend-neutral"></i>
                                    <?php endif; ?>
                                    <?php echo number_format($row['diferencia_porcentaje'], 2) ?>%
                                </td>
                                <td>
                                    <!-- Botón para expandir/colapsar -->
                                    <button class="btn btn-sm btn-outline-primary toggle-details" data-target="details-<?php echo $row['mes'] . '-' . $row['anio']; ?>">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Fila de detalles oculta -->
                            <tr id="details-<?php echo $row['mes'] . '-' . $row['anio']; ?>" class="details-row" style="display: none;">
                                <td colspan="6">
                                    <div class="details-content p-4 bg-light rounded shadow-sm">
                                        <div class="row g-3">
                                            <?php
                                            // Definir los tipos de tarjetas con sus configuraciones
                                            $tarjetas = [
                                                [
                                                    'funcion' => '23',
                                                    'clase' => 'bg-warning',
                                                    'icono' => 'fa-file-invoice-dollar',
                                                    'titulo' => 'Mayor Gasto del Mes',
                                                    'descripcion' => 'El gasto más significativo registrado'
                                                ],
                                                [
                                                    'funcion' => '24',
                                                    'clase' => 'bg-success',
                                                    'icono' => 'fa-utensils',
                                                    'titulo' => 'Mayor Ocio del Mes',
                                                    'descripcion' => 'Tu actividad de ocio más costosa'
                                                ],
                                                [
                                                    'funcion' => '2',
                                                    'clase' => 'bg-info',
                                                    'icono' => 'fa-piggy-bank',
                                                    'titulo' => 'Mayor Ahorro del Mes',
                                                    'descripcion' => 'Tu ahorro más destacado'
                                                ]
                                            ];

                                            // Recorrer cada tipo de tarjeta y generar su contenido
                                            foreach ($tarjetas as $tarjeta):
                                                $datos = obtenerDatos($conexion, $tarjeta['funcion'], $formatted_mes);
                                                foreach ($datos as $fila):
                                            ?>
                                                    <div class="col-md-4">
                                                        <div class="card h-100 border-0 shadow-sm overflow-hidden">
                                                            <div class="card-header <?php echo $tarjeta['clase']; ?> text-white py-3">
                                                                <h5 class="card-title mb-0">
                                                                    <i class="fas <?php echo $tarjeta['icono']; ?> me-2"></i>
                                                                    <?php echo $tarjeta['titulo']; ?>
                                                                </h5>
                                                            </div>
                                                            <div class="card-body">
                                                                <p class="text-muted small mb-2"><?php echo $tarjeta['descripcion']; ?></p>
                                                                <h3 class="mb-3 text-dark">$<?php echo number_format($fila['Monto'], 0, '', '.'); ?></h3>
                                                                <div class="d-flex align-items-center text-secondary">
                                                                    <div class="me-3">
                                                                        <i class="far fa-calendar-alt me-1"></i>
                                                                        <span>Día: <?php echo date("d-m-Y h:i A", strtotime($fila['Dia']));  ?></span>
                                                                    </div>
                                                                    <div>
                                                                        <i class="fas fa-tag me-1"></i>
                                                                        <span><?php echo $fila['Categoria']; ?></span>
                                                                    </div>
                                                                </div>
                                                                <p class="mt-3 mb-0 text-secondary small">
                                                                    <i class="fas fa-info-circle me-1"></i>
                                                                    <?php echo $fila['Detalle']; ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                            <?php
                                                endforeach;
                                            endforeach;
                                            ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                        <!-- Fila de totales -->
                        <tr class="total-row">
                            <td><strong>Totales</strong></td>
                            <td class="text-success">
                                <i class="bi bi-cash-stack trend-icon"></i>
                                $<?php echo number_format($totales_ingresos, 0, '', '.'); ?>
                            </td>
                            <td class="text-danger">
                                <i class="bi bi-wallet2 trend-icon"></i>
                                $<?php echo number_format($totales_egresos, 0, '', '.'); ?>
                            </td>
                            <td class="<?php echo $totales_diferencia >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <i class="bi bi-piggy-bank trend-icon"></i>
                                $<?php echo number_format($totales_diferencia, 0, '', '.'); ?>
                            </td>
                            <td class="<?php echo $totales_diferencia >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php
                                if ($totales_egresos != 0) {
                                    $totales_diferencia_porcentaje = (($totales_ingresos - $totales_egresos) / $totales_egresos) * 100;
                                } else {
                                    $totales_diferencia_porcentaje = $totales_ingresos > 0 ? 100 : 0;
                                }
                                $diferencia = $totales_ingresos - $totales_egresos;
                                $totales_tendencia = $diferencia > 0 ? 1 : ($diferencia < 0 ? -1 : 0);

                                if ($totales_tendencia > 0): ?>
                                    <i class="bi bi-arrow-up-circle trend-icon trend-up"></i>
                                <?php elseif ($totales_tendencia < 0): ?>
                                    <i class="bi bi-arrow-down-circle trend-icon trend-down"></i>
                                <?php else: ?>
                                    <i class="bi bi-arrow-right-circle trend-icon trend-neutral"></i>
                                <?php endif; ?>
                                <?php echo number_format($totales_diferencia_porcentaje, 2); ?>%
                            </td>
                            <td></td> <!-- Celda vacía para alinear -->
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


        <?php
        // Obtener los 5 módulos y categorías con más gastos
        $categorias_top = obtener_top_categorias($conexion, $meses);

        // Obtener las categorías más repetidas
        $categorias_repetidas = obtener_top_categorias_repetidas($conexion, $meses);

        // Obtener el promedio de gastos por categoría
        $promedios_gastos = obtener_promedio_gastos($conexion, $meses);

        // Obtener los gastos más bajos
        $gastos_menores = obtener_gastos_menores($conexion, $meses);

        ?>

        <div class="summary-grid">
            <div class="summary-card top-expenses">
                <div class="summary-card-header">
                    <i class="bi bi-bar-chart-line"></i>
                    <h3>Top 5 Categorías por Gasto</h3>
                </div>
                <div class="summary-card-content">
                    <?php foreach ($categorias_top as $categoria): ?>
                        <div class="summary-card-item">
                            <span class="label">
                                <i class="bi bi-graph-up-arrow"></i>
                                <?php echo $categoria['categoria']; ?>
                            </span>
                            <span class="value text-primary">$<?php echo $categoria['total_gastos']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-card repeated-categories">
                <div class="summary-card-header">
                    <i class="bi bi-repeat"></i>
                    <h3>Categorías más Repetidas</h3>
                </div>
                <div class="summary-card-content">
                    <?php foreach ($categorias_repetidas as $categoria): ?>
                        <div class="summary-card-item">
                            <span class="label">
                                <i class="bi bi-bookmarks"></i>
                                <?php echo $categoria['categoria']; ?>
                            </span>
                            <span class="value text-success"><?php echo $categoria['repeticiones']; ?> veces</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-card average-expenses">
                <div class="summary-card-header">
                    <i class="bi bi-pie-chart"></i>
                    <h3>Promedio de Gastos por Categoría</h3>
                </div>
                <div class="summary-card-content">
                    <?php foreach ($promedios_gastos as $categoria): ?>
                        <div class="summary-card-item">
                            <span class="label">
                                <i class="bi bi-plus-slash-minus"></i>
                                <?php echo $categoria['categoria']; ?>
                            </span>
                            <span class="value text-warning">$<?php echo $categoria['promedio_gastos']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="summary-card lowest-expenses">
                <div class="summary-card-header">
                    <i class="bi bi-arrow-down-short"></i>
                    <h3>Categorías con Menor Gasto</h3>
                </div>
                <div class="summary-card-content">
                    <?php foreach ($gastos_menores as $categoria): ?>
                        <div class="summary-card-item">
                            <span class="label">
                                <i class="bi bi-patch-minus"></i>
                                <?php echo $categoria['categoria']; ?>
                            </span>
                            <span class="value text-danger">$<?php echo $categoria['gasto_minimo']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Seleccionar todos los botones de toggle
            const toggleButtons = document.querySelectorAll(".toggle-details");

            toggleButtons.forEach(button => {
                button.addEventListener("click", function() {
                    const targetId = this.getAttribute("data-target");
                    const detailsRow = document.getElementById(targetId);

                    // Alternar la visibilidad de la fila de detalles
                    if (detailsRow.style.display === "none" || !detailsRow.style.display) {
                        detailsRow.style.display = "table-row";
                        this.innerHTML = '<i class="bi bi-chevron-up"></i>'; // Cambiar ícono
                    } else {
                        detailsRow.style.display = "none";
                        this.innerHTML = '<i class="bi bi-chevron-down"></i>'; // Cambiar ícono
                    }
                });
            });
        });
    </script>
</body>

</html>