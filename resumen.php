<?php

include('bd.php');

function obtenerGastosDiarios($conexion)
{
    // Consulta SQL para obtener los gastos por día de la semana
    $dias = "
        SELECT 
            CASE 
                WHEN DAYOFWEEK(Fecha) = 2 THEN 'Lunes'
                WHEN DAYOFWEEK(Fecha) = 3 THEN 'Martes'
                WHEN DAYOFWEEK(Fecha) = 4 THEN 'Miércoles'
                WHEN DAYOFWEEK(Fecha) = 5 THEN 'Jueves'
                WHEN DAYOFWEEK(Fecha) = 6 THEN 'Viernes'
                WHEN DAYOFWEEK(Fecha) = 7 THEN 'Sábado'
                WHEN DAYOFWEEK(Fecha) = 1 THEN 'Domingo'
            END AS nombre_dia, 
            SUM(Valor) AS total_gastos 
        FROM 
            gastos 
        WHERE 
            Fecha >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
            AND Fecha < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)
            AND ID_Categoria_Gastos != 1
        GROUP BY 
            DAYOFWEEK(Fecha) 
        ORDER BY 
            FIELD(nombre_dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo');
    ";


    // Ejecutar la consulta
    $datos_dias = $conexion->query($dias);

    // Arrays para almacenar etiquetas y gastos
    $labels_semanales = [];
    $gastos_semanales = [];

    // Recorrer los resultados de la consulta
    while ($fila = $datos_dias->fetch_assoc()) {
        $labels_semanales[] = $fila['nombre_dia'];     // Guardar el nombre del día
        $gastos_semanales[] = (float)$fila['total_gastos'];  // Guardar el total de gastos
    }

    // Convertir los arrays a formato JSON para usar en JavaScript
    $labels_semanales_json = json_encode($labels_semanales);
    $gastos_semanales_json = json_encode($gastos_semanales);

    // Retornar los resultados
    return [
        'labels' => $labels_semanales_json,
        'gastos' => $gastos_semanales_json
    ];
}

$resultados_dias = obtenerGastosDiarios($conexion);
if ($resultados_dias) {
    $labels_semanales = $resultados_dias['labels'];
    $gastos_semanales = $resultados_dias['gastos'];
}

function obtenerGastosSemanales($conexion)
{
    // Consulta SQL para obtener los gastos semanales de las últimas 8 semanas
    $consulta = "
        SELECT 
            MIN(Fecha) AS Fecha, 
            YEAR(Fecha) AS anio,
            WEEK(Fecha, 1) AS semana, 
            SUM(Valor) AS total_gastos
        FROM 
            gastos
        WHERE 
            Fecha >= CURDATE() - INTERVAL 8 WEEK 
            AND ID_Categoria_Gastos != 1
        GROUP BY 
            anio, semana 
        ORDER BY `anio` DESC,`semana` DESC  LIMIT 8;
    ";

    // Obtener el número de la semana actual
    $semana_actual = date('W'); // Número de semana (de 1 a 52)

    // Calcular la semana actual menos 4
    $semana_menos_4 = $semana_actual - 4;

    // Ajustar si la semana menos 4 es menor que 1 (por si es principio de año)
    if ($semana_menos_4 < 1) {
        // Ajustar para evitar números negativos
        $semana_menos_4 += 52; // O 53 dependiendo de la lógica que necesites
    }

    $resultados = $conexion->query($consulta);

    // Inicializar arreglos para almacenar los gastos
    $gastos_semanales_actual = [];
    $gastos_semanales_anterior = [];
    $labels_mensuales = [];

    // Contadores para verificar el número de semanas
    $contador_actual = 0;
    $contador_anterior = 0;

    // Procesar los resultados de la consulta
    while ($fila = $resultados->fetch_assoc()) {
        $semana = "Semana " . $fila['semana'];
        $labels_mensuales[] = $semana; // Añadir etiqueta de la semana

        if ($semana_menos_4 < $fila['semana']) {
            $gastos_semanales_actual[] = (float)$fila['total_gastos'];
            $contador_actual++;
        } else {
            $gastos_semanales_anterior[] = (float)$fila['total_gastos'];
            $contador_anterior++;
        }
    }

    // Asegurarse de que hay exactamente 4 semanas actuales y anteriores
    if ($contador_actual < 4) {
        $gastos_semanales_actual = array_pad($gastos_semanales_actual, 4, 0);
    }
    if ($contador_anterior < 4) {
        $gastos_semanales_anterior = array_pad($gastos_semanales_anterior, 4, 0);
    }

    $gastos_semanales_invertidos_actual = array_reverse($gastos_semanales_actual);
    $gastos_semanales_invertidos_anterior = array_reverse($gastos_semanales_anterior);

    $labels_mensuales = array_slice(array_reverse($labels_mensuales), 4, 8);

    // Retornar los resultados
    return [
        'gastos_semanales_actual' => $gastos_semanales_invertidos_actual,
        'gastos_semanales_anterior' => $gastos_semanales_invertidos_anterior,
        'labels_mensuales' => $labels_mensuales
    ];
}

$resultados_gastos = obtenerGastosSemanales($conexion);
if ($resultados_gastos) {
    $gastos_semanales_actual = json_encode($resultados_gastos['gastos_semanales_actual']);
    $gastos_semanales_anterior = json_encode($resultados_gastos['gastos_semanales_anterior']);
    $labels_mensuales = json_encode($resultados_gastos['labels_mensuales']);
}


function obtenerDiferenciaPromedios($conexion)
{
    // Consulta para obtener el promedio de gastos de la semana actual y de la semana anterior
    $sql = "
        SELECT 
            SUM(CASE 
                    WHEN YEARWEEK(Fecha, 1) = YEARWEEK(CURDATE(), 1) 
                    THEN Valor 
                    ELSE 0 
                END) AS promedio_gastos_actual,
            
            SUM(CASE 
                    WHEN YEARWEEK(Fecha, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1) 
                    THEN Valor 
                    ELSE 0 
                END) AS promedio_gastos_anterior
        FROM gastos
        WHERE ID_Categoria_Gastos != 1
        AND (
            YEAR(Fecha) = YEAR(CURDATE()) OR 
            (MONTH(Fecha) = 12 AND YEAR(Fecha) = YEAR(CURDATE()) - 1)
        );
        ";

    $result = $conexion->query($sql);

    // Obtener los promedios
    if ($result) {
        $fila = $result->fetch_assoc();
        $promedio_gastos_actual = $fila['promedio_gastos_actual'] !== null ? $fila['promedio_gastos_actual'] : 0; // Retornar 0 si no hay gastos
        $promedio_gastos_anterior = $fila['promedio_gastos_anterior'] !== null ? $fila['promedio_gastos_anterior'] : 0; // Retornar 0 si no hay gastos
    } else {
        echo "Error en la consulta: " . $conexion->error;
        return;
    }

    // Calcular la diferencia
    $diferencia = $promedio_gastos_actual - $promedio_gastos_anterior;

    // Formatear la diferencia para mostrarla
    if ($diferencia < 0) {
        $formato_diferencia = "↓ " . number_format(abs($diferencia), 0, '', '.') . " vs semana anterior";
        $color_diferencia = "green";
    } else {
        $formato_diferencia = "↑ " .  number_format($diferencia, 0, '', '.') . " vs semana anterior";
        $color_diferencia = "red";
    }

    // Retornar los resultados
    return [
        'promedio_actual' => $promedio_gastos_actual,
        'promedio_anterior' => $promedio_gastos_anterior,
        'diferencia' => $formato_diferencia,
        'color_diferencia' => $color_diferencia
    ];
}

$resultados_promedios = obtenerDiferenciaPromedios($conexion);
if ($resultados_promedios) {
    $promedio_semanal = $resultados_promedios['promedio_actual'];
    $tendencia_semanal = $resultados_promedios['diferencia'];
    $color_tendencia_semanal = $resultados_promedios['color_diferencia'];
}

function obtenerDiferenciaGastosMeses($conexion)
{
    // Consulta SQL para obtener los gastos totales de los últimos dos meses
    $consulta = "
        SELECT 
            MONTH(Fecha) AS mes, 
            YEAR(Fecha) AS anio, 
            SUM(Valor) AS total_gastos
        FROM 
            gastos
        WHERE 
            ID_Categoria_Gastos != 1
            AND Fecha >= (
                SELECT DATE_SUB(MAX(Fecha), INTERVAL 2 MONTH)
                FROM gastos
            )
        GROUP BY 
            anio, mes -- Agrupar por año y mes para manejar correctamente meses de años diferentes
        ORDER BY 
            anio DESC, mes DESC
        LIMIT 2;
    ";

    // Ejecutar la consulta
    $resultado = $conexion->query($consulta);

    // Inicializar variables para los gastos
    $gastos_mes_actual = 0;
    $gastos_mes_anterior = 0;

    // Comprobar si hay resultados y procesarlos
    if ($resultado) {
        $contador = 0; // Contador para saber si estamos en el mes actual o anterior
        while ($fila = $resultado->fetch_assoc()) {
            if ($contador == 0) {
                $gastos_mes_actual = (float)$fila['total_gastos']; // Gastos del mes actual
            } elseif ($contador == 1) {
                $gastos_mes_anterior = (float)$fila['total_gastos']; // Gastos del mes anterior
            }
            $contador++;
        }
    } else {
        // Manejo de errores en caso de que no haya resultados
        echo "Error en la consulta: " . $conexion->error;
    }

    // Calcular la diferencia
    $diferencia = $gastos_mes_actual - $gastos_mes_anterior;

    // Formatear la diferencia para mostrarla
    if ($diferencia < 0) {
        $formato_diferencia = "↓ " . number_format(abs($diferencia), 0, '', '.') . " vs mes  anterior";
        $color_diferencia = "green";
    } else {
        $formato_diferencia = "↑ " .  number_format($diferencia, 0, '', '.') . " vs mes  anterior";
        $color_diferencia = "red";
    }

    // Retornar los resultados
    return [
        'gastos_mes_actual' => $gastos_mes_actual,
        'gastos_mes_anterior' => $gastos_mes_anterior,
        'diferencia' => $formato_diferencia,
        'color_diferencia' => $color_diferencia
    ];
}

$resultados_meses = obtenerDiferenciaGastosMeses($conexion);
$gasto_mensual_total = $resultados_meses['gastos_mes_actual'];
$tendencia_mensual = $resultados_meses['diferencia'];
$color_tendencia_mensual = $resultados_meses['color_diferencia'];


function obtenerIngresosMesActual($conexion)
{
    // Consulta SQL para obtener el total de ingresos del mes actual
    $consulta = "
    SELECT Valor AS total_ingresos FROM gastos WHERE MONTH(Fecha) = MONTH(CURDATE()) AND YEAR(Fecha) = YEAR(CURDATE()) AND ID_Categoria_Gastos = 1;
    ";

    // Ejecutar la consulta
    $resultado = $conexion->query($consulta);

    // Inicializar la variable para almacenar el total de ingresos
    $total_ingresos = 0;

    // Comprobar si hay resultados y procesarlos
    if ($resultado) {
        if ($fila = $resultado->fetch_assoc()) {
            $total_ingresos = (float)$fila['total_ingresos']; // Guardar el total de ingresos
        }
    } else {
        // Manejo de errores en caso de que no haya resultados
        echo "Error en la consulta: " . $conexion->error;
    }

    // Retornar el total de ingresos
    return $total_ingresos;
}

$total_ingresos = obtenerIngresosMesActual($conexion);
$variable_comparacion = $gasto_mensual_total;

if ($variable_comparacion > 0) {
    $porcentaje_presupuesto = round(($variable_comparacion / $total_ingresos) * 100, 1);
} else {
    $porcentaje_presupuesto = 0;
}

$presupuesto_restante = $total_ingresos - $gasto_mensual_total;


function obtenerCategoriasGastos($conexion)
{
    // Consulta SQL para obtener los datos de gastos por categoría
    $sql = "
    SELECT 
        c.Nombre AS categorias, 
        SUM(CASE 
                WHEN YEARWEEK(g.Fecha, 1) = YEARWEEK(CURDATE(), 1) 
                THEN g.Valor 
                ELSE 0 
            END) AS semanal, 
        
        SUM(CASE 
                WHEN YEARWEEK(g.Fecha, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1) 
                THEN g.Valor 
                ELSE 0 
            END) AS semana_anterior 
    FROM 
        gastos g 
    JOIN 
        categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
    WHERE 
        g.ID_Categoria_Gastos != 1 
        AND g.ID_Categoria_Gastos != 2
        AND (
            YEAR(g.Fecha) = YEAR(CURDATE()) OR 
            (MONTH(g.Fecha) = 12 AND YEAR(g.Fecha) = YEAR(CURDATE()) - 1)
        )
    GROUP BY 
        c.Nombre
    ORDER BY 
        semanal DESC, semana_anterior DESC
    LIMIT 10;
        ";

    $result = $conexion->query($sql);

    // Arreglo para almacenar los datos formateados
    $categorias_gastos = [];

    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $semanal = $fila['semanal'];
            $semana_anterior = $fila['semana_anterior'];


            // Calcular la tendencia de esta semana en comparación con la semana pasada
            $trend = ($semana_anterior != 0) ? (($semanal - $semana_anterior) / $semana_anterior) * 100 : null;

            // Formatear la tendencia para mostrar
            $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "0%";

            // Alternativamente, puedes definir $trend_format como "N/A" si no hay datos
            // $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "N/A";

            // Agregar datos al arreglo
            $categorias_gastos[] = [
                "category" => $fila['categorias'],
                "weekly" => $semanal,
                "monthly" => $semana_anterior,
                "trend" => $trend_format
            ];
        }
    } else {
        echo "Error en la consulta: " . $conexion->error;
    }

    return $categorias_gastos;
}

$categorias_gastos = obtenerCategoriasGastos($conexion);

function obtenerCategoriasGastosMes($conexion)
{
    // Consulta SQL para obtener los datos de gastos por categoría
    $sql = "
        SELECT 
            c.Nombre AS categorias, 
            SUM(CASE 
                    WHEN MONTH(g.Fecha) = MONTH(CURDATE()) AND YEAR(g.Fecha) = YEAR(CURDATE()) 
                    THEN g.Valor ELSE 0 
                END) AS mes_actual, 
            SUM(CASE 
                    WHEN MONTH(g.Fecha) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(g.Fecha) = YEAR(CURDATE() - INTERVAL 1 MONTH) 
                    THEN g.Valor ELSE 0 
                END) AS mes_anterior 
        FROM 
            gastos g 
        JOIN 
            categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
        WHERE 
            g.ID_Categoria_Gastos NOT IN (1, 2)
        GROUP BY 
            c.Nombre
        ORDER BY 
            mes_actual DESC, mes_anterior DESC
        LIMIT 10;
    ";

    $result = $conexion->query($sql);

    // Arreglo para almacenar los datos formateados
    $categorias_gastos = [];

    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $mes_actual = $fila['mes_actual'];
            $mes_anterior = $fila['mes_anterior'];


            // Calcular la tendencia de esta semana en comparación con la semana pasada
            $trend = ($mes_anterior != 0) ? (($mes_actual - $mes_anterior) / $mes_anterior) * 100 : null;

            // Formatear la tendencia para mostrar
            $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "0%";

            // Alternativamente, puedes definir $trend_format como "N/A" si no hay datos
            // $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "N/A";

            // Agregar datos al arreglo
            $categorias_gastos[] = [
                "category" => $fila['categorias'],
                "weekly" => $mes_actual,
                "monthly" => $mes_anterior,
                "trend" => $trend_format
            ];
        }
    } else {
        echo "Error en la consulta: " . $conexion->error;
    }

    return $categorias_gastos;
}

$categorias_gastos_mensual = obtenerCategoriasGastosMes($conexion);


function obtenerCategoriasGastosAnuales($conexion)
{
    // Consulta SQL para obtener los datos de gastos por categoría
    $sql = "
        SELECT 
            c.Nombre AS categorias, 
            SUM(CASE 
                    WHEN YEAR(g.Fecha) = YEAR(CURDATE()) 
                    THEN g.Valor ELSE 0 
                END) AS ano_actual, 
            SUM(CASE 
                    WHEN YEAR(g.Fecha) = YEAR(CURDATE()) - 1 
                    THEN g.Valor ELSE 0 
                END) AS ano_pasado 
        FROM 
            gastos g 
        JOIN 
            categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
        WHERE 
            g.ID_Categoria_Gastos NOT IN (1)
        GROUP BY 
            c.Nombre
        ORDER BY 
            ano_actual DESC, ano_pasado DESC
    ";

    $result = $conexion->query($sql);

    // Arreglo para almacenar los datos formateados
    $categorias_gastos = [];

    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $año_actual = $fila['ano_actual'];
            $año_pasado = $fila['ano_pasado'];

            // Calcular la tendencia comparando los gastos entre el año actual y el año pasado
            $trend = ($año_pasado != 0) ? (($año_actual - $año_pasado) / $año_pasado) * 100 : null;

            // Formatear la tendencia para mostrar
            $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "0%";

            // Alternativamente, puedes definir $trend_format como "N/A" si no hay datos
            // $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "N/A";

            // Agregar datos al arreglo
            $categorias_gastos[] = [
                "category" => $fila['categorias'],
                "year_current" => $año_actual,
                "year_previous" => $año_pasado,
                "trend" => $trend_format
            ];
        }
    } else {
        echo "Error en la consulta: " . $conexion->error;
    }

    return $categorias_gastos;
}

$categorias_gastos_anual = obtenerCategoriasGastosAnuales($conexion);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Gastos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #f6f8fc 0%, #edf1f7 100%);
        }

        .chart-container {
            height: 300px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .red {
            color: rgba(239, 68, 68);
        }

        @media only screen and (max-width: 600px) {
            .chart-container {
                position: relative;
                height: 400px;
                margin-bottom: 10px;
                width: 200px;
            }

            .card {
                width: 100%;
                padding-left: 15px;
            }

            .grid {
                display: flex;
                flex-wrap: wrap;
                /* Permite que los elementos pasen a la siguiente fila si no caben */
                gap: 1rem;
                /* Espacio entre los elementos */
            }

        }

        /* Estilos para la barra de desplazamiento (scrollbar) */
        .scrollbar-thin {
            scrollbar-width: thin;
            /* Estilo del navegador Firefox */
        }

        .scrollbar-thumb-gray-400 {
            scrollbar-color: #9CA3AF #E5E7EB;
            /* Color del "pulgar" y la pista en Firefox */
        }

        /* Para navegadores basados en WebKit (Chrome, Safari) */
        .scrollbar-thin::-webkit-scrollbar {
            height: 8px;
            /* Altura del scrollbar */
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background-color: #E5E7EB;
            /* Color de la pista */
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: #9CA3AF;
            /* Color del "pulgar" */
            border-radius: 10px;
        }
    </style>
</head>

<body class="gradient-bg min-h-screen p-6">

    <div class="max-w-7xl mx-auto">
        <header class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800">Control de Gastos</h1>
            <p class="text-gray-600">Resumen Diario y Semanal</p>
        </header>

        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="card p-6">
                <h2 class="text-xl font-semibold mb-4">Resumen Diario</h2>
                <canvas id="weeklyChart" class="chart-container"></canvas>
            </div>
            <div class="card p-6">
                <h2 class="text-xl font-semibold mb-4">Resumen Semanal</h2>
                <canvas id="monthlyChart" class="chart-container"></canvas>
            </div>
        </div>

        <div class="stats-grid mb-8">
            <div class="card p-6">
                <h3 class="text-gray-600 mb-2">Gasto Semanal</h3>
                <p class="text-3xl font-bold"><?php echo '$' . number_format($promedio_semanal, 0, '', '.'); ?></p>
                <span class="text-sm text-<?php echo $color_tendencia_semanal; ?>-500"><?php echo $tendencia_semanal; ?></span>
            </div>
            <div class="card p-6">
                <h3 class="text-gray-600 mb-2">Gasto Mensual Total</h3>
                <p class="text-3xl font-bold"><?php echo '$' . number_format($gasto_mensual_total, 0, '', '.'); ?></p>
                <span class="text-sm text-<?php echo $color_tendencia_mensual; ?>-500"><?php echo $tendencia_mensual; ?></span>
            </div>
            <div class="card p-6">
                <h3 class="text-gray-600 mb-2">Presupuesto Restante</h3>

                <p class="text-3xl font-bold <?php echo $presupuesto_restante < 0 ? 'red' : ''; ?>">
                    <?php echo '$' . number_format($presupuesto_restante, 0, '', '.'); ?>
                </p>

                <span class="text-sm text-gray-500"><?php echo $porcentaje_presupuesto . '% del presupuesto'; ?></span>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4 mb-8">

            <div class="card p-4 sm:p-6">
                <h2 class="text-lg sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">Desglose de Gastos Semanal</h2>
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200">
                    <table class="divide-y divide-gray-200 table">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Categoría</th>
                                <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Esta Semana</th>
                                <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider hidden md:table-cell">Semana Anterior</th>
                                <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Tendencia</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">

                            <?php
                            $total_semanal = 0;
                            $total_semanal_anterior = 0;
                            foreach ($categorias_gastos as $categoria): ?>
                                <?php
                                $tendencia_clase = (strpos($categoria['trend'], '+') === 0) ? 'text-red-600 font-medium' : 'text-green-600 font-medium';
                                $total_semanal += $categoria['weekly']; // Sumar valores semanales
                                $total_semanal_anterior += $categoria['monthly']; // Sumar valores mensuales
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-xs sm:text-sm font-medium text-gray-900"><?= htmlspecialchars($categoria['category']) ?></div>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['weekly'], 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['monthly'], 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="<?= $tendencia_clase ?> text-xs sm:text-sm"><?= htmlspecialchars($categoria['trend']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach;
                            // Calcular tendencia para la semana y el mes
                            $tendencia_semanal = ($total_semanal_anterior != 0) ? (($total_semanal - $total_semanal_anterior) / $total_semanal_anterior) * 100 : null;

                            // Formatear las tendencias para mostrar
                            $tendencia_semanal_formateada = $tendencia_semanal !== null ? ($tendencia_semanal > 0 ? "+" : "") . round($tendencia_semanal, 2) . "%" : "0%";
                            ?>


                            <!-- Fila para totales -->
                            <tr class="font-semibold bg-gray-100">
                                <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                    <div class="text-xs sm:text-sm">Total</div>
                                </td>
                                <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                    <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_semanal, 0, '', '.') ?></div>
                                </td>
                                <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                    <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_semanal_anterior, 0, '', '.') ?></div>
                                </td>
                                <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                    <span class="<?= $tendencia_semanal < 0 ? 'text-green-600' : 'text-red-600' ?> text-xs sm:text-sm"><?= $tendencia_semanal_formateada ?></span> <!-- Mostrar la tendencia semanal -->
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg sm:text-2xl font-bold text-gray-800 mb-6 sm:mb-11">Desglose de Gastos Mensual</h2>
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 ">
                    <table class="divide-y divide-gray-200 table">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Categoría</th>
                                <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Este Mes</th>
                                <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider hidden md:table-cell">Mes Anterior</th>
                                <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Tendencia</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $total_mensual = 0;
                            $total_mensual_anterior = 0;
                            foreach ($categorias_gastos_mensual as $categoria): ?>
                                <?php $tendencia_clase = (strpos($categoria['trend'], '+') === 0) ? 'text-red-600 font-medium' : 'text-green-600 font-medium';
                                $total_mensual += $categoria['weekly']; // Sumar valores semanales
                                $total_mensual_anterior += $categoria['monthly']; // Sumar valores mensuales 
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-xs sm:text-sm font-medium text-gray-900"><?= htmlspecialchars($categoria['category']) ?></div>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['weekly'], 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['monthly'], 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="<?= $tendencia_clase ?> text-xs sm:text-sm"><?= htmlspecialchars($categoria['trend']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach;
                            // Calcular tendencia para la semana y el mes
                            $tendencia_mensual = ($total_mensual_anterior != 0) ? (($total_mensual - $total_mensual_anterior) / $total_mensual_anterior) * 100 : null;

                            // Formatear las tendencias para mostrar
                            $tendencia_mensual_formateada = $tendencia_mensual !== null ? ($tendencia_mensual > 0 ? "+" : "") . round($tendencia_mensual, 2) . "%" : "0%";
                            ?>

                            <!-- Fila para totales -->
                            <tr class="font-semibold bg-gray-100">
                                <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                    <div class="text-xs sm:text-sm">Total</div>
                                </td>
                                <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                    <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_mensual, 0, '', '.') ?></div>
                                </td>
                                <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                    <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_mensual_anterior, 0, '', '.') ?></div>
                                </td>
                                <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                    <span class="<?= $tendencia_mensual < 0 ? 'text-green-600' : 'text-red-600' ?> text-xs sm:text-sm"><?= $tendencia_mensual_formateada ?></span> <!-- Mostrar la tendencia semanal -->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-1 gap-8 mb-12">
            <div class="card p-8 shadow-lg rounded-lg bg-white">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6 sm:mb-10">Desglose de Gastos Anual</h2>
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 rounded-lg shadow-md">
                    <table class="divide-y divide-gray-200 table-auto w-full min-w-max">
                        <thead class="bg-gray-100 text-sm">
                            <tr>
                                <th class="px-4 sm:px-6 py-2 text-left font-semibold text-gray-700 uppercase tracking-wider">Categoría</th>
                                <th class="px-4 sm:px-6 py-2 text-left font-semibold text-gray-700 uppercase tracking-wider">Este Año</th>
                                <th class="px-4 sm:px-6 py-2 text-left font-semibold text-gray-700 uppercase tracking-wider hidden md:table-cell">Año Anterior</th>
                                <th class="px-4 sm:px-6 py-2 text-left font-semibold text-gray-700 uppercase tracking-wider">Tendencia</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $total_anual = 0;
                            $total_anual_anterior = 0;
                            foreach ($categorias_gastos_anual as $categoria): ?>
                                <?php
                                $tendencia_clase = (strpos($categoria['trend'], '+') === 0) ? 'text-red-600 font-medium' : 'text-green-600 font-medium';
                                $total_anual += $categoria['year_current']; // Sumar valores semanales
                                $total_anual_anterior += $categoria['year_previous']; // Sumar valores mensuales 
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-4 sm:px-6 py-2 text-sm font-medium text-gray-900 whitespace-nowrap"><?= htmlspecialchars($categoria['category']) ?></td>
                                    <td class="px-4 sm:px-6 py-2 text-sm text-gray-900 whitespace-nowrap">$<?= number_format($categoria['year_current'], 0, '', '.') ?></td>
                                    <td class="px-4 sm:px-6 py-2 text-sm text-gray-900 whitespace-nowrap hidden md:table-cell">$<?= number_format($categoria['year_previous'], 0, '', '.') ?></td>
                                    <td class="px-4 sm:px-6 py-2 text-sm whitespace-nowrap">
                                        <span class="<?= $tendencia_clase ?>"><?= htmlspecialchars($categoria['trend']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach;
                            // Calcular tendencia para la semana y el mes
                            $tendencia_anual = ($total_anual_anterior != 0) ? (($total_anual - $total_anual_anterior) / $total_anual_anterior) * 100 : null;

                            // Formatear las tendencias para mostrar
                            $tendencia_anual_formateada = $tendencia_anual !== null ? ($tendencia_anual > 0 ? "+" : "") . round($tendencia_anual, 2) . "%" : "0%";
                            ?>

                            <!-- Fila para totales -->
                            <tr class="font-semibold bg-gray-100">
                                <td class="px-4 sm:px-6 py-2 text-sm">Total</td>
                                <td class="px-4 sm:px-6 py-2 text-sm text-gray-900">$<?= number_format($total_anual, 0, '', '.') ?></td>
                                <td class="px-4 sm:px-6 py-2 text-sm text-gray-900 whitespace-nowrap hidden md:table-cell">$<?= number_format($total_anual_anterior, 0, '', '.') ?></td>
                                <td class="px-4 sm:px-6 py-2 text-sm">
                                    <span class="<?= $tendencia_anual < 0 ? 'text-green-600' : 'text-red-600' ?>"><?= $tendencia_anual_formateada ?></span> <!-- Mostrar la tendencia semanal -->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>


    <script>
        // Datos de ejemplo

        const weeklyData = {
            labels: <?php echo $labels_semanales; ?>, // Días de la semana
            expenses: <?php echo $gastos_semanales; ?> // Total de gastos por día
        };

        const monthlyData = {
            labels: <?php echo $labels_mensuales ?>,
            current: <?php echo $gastos_semanales_actual ?>,
            previous: <?php echo $gastos_semanales_anterior ?>
        };

        const expensesCategories = <?php echo json_encode($categorias_gastos); ?>;

        // Configurar gráfico semanal
        new Chart(document.getElementById('weeklyChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: weeklyData.labels,
                datasets: [{
                    label: 'Gastos',
                    data: weeklyData.expenses,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Gastos Diarios'
                    }
                }
            }
        });

        // Configurar gráfico mensual con comparación
        new Chart(document.getElementById('monthlyChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Mes Anterior',
                    data: monthlyData.previous,
                    borderColor: 'rgb(156, 163, 175)',
                    backgroundColor: 'rgba(156, 163, 175, 0.3)',
                    fill: true
                }, {
                    label: 'Mes Actual',
                    data: monthlyData.current,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Comparativa Semanal'
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    </script>
</body>

</html>