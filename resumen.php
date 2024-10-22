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
                WHEN DAYOFWEEK(Fecha) = 1 THEN 'Domingo' -- Ajustar para que 1 sea domingo
            END AS nombre_dia, 
            SUM(Valor) AS total_gastos 
        FROM 
            gastos 
        WHERE 
            WEEK(Fecha, 1) = WEEK(CURDATE(), 1) 
            AND YEAR(Fecha) = YEAR(CURDATE()) 
        GROUP BY 
            nombre_dia 
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
        SELECT Fecha, WEEK(Fecha, 1) AS semana, SUM(Valor) AS total_gastos 
        FROM gastos 
        WHERE Fecha >= CURDATE() - INTERVAL 8 WEEK AND ID_Categoria_Gastos != 1 
        GROUP BY semana 
        ORDER BY semana DESC 
        LIMIT 8;
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
        SUM(CASE WHEN WEEK(Fecha, 1) = WEEK(CURDATE(), 1) 
                 AND YEAR(Fecha) = YEAR(CURDATE()) 
                 THEN Valor END) AS promedio_gastos_actual,
        SUM(CASE WHEN WEEK(Fecha, 1) = WEEK(CURDATE() - INTERVAL 1 WEEK, 1) 
                 AND YEAR(Fecha) = YEAR(CURDATE()) 
                 THEN Valor END) AS promedio_gastos_anterior
    FROM gastos;
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
        SELECT MONTH(Fecha) AS mes, SUM(Valor) AS total_gastos 
        FROM gastos 
        WHERE ID_Categoria_Gastos != 1 
        GROUP BY mes 
        ORDER BY mes DESC 
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
        SUM(CASE WHEN WEEK(g.Fecha, 1) = WEEK(CURDATE(), 1) AND YEAR(g.Fecha) = YEAR(CURDATE()) THEN g.Valor ELSE 0 END) AS semanal, 
        SUM(CASE WHEN WEEK(g.Fecha, 1) = WEEK(CURDATE() - INTERVAL 1 WEEK, 1) AND YEAR(g.Fecha) = YEAR(CURDATE()) THEN g.Valor ELSE 0 END) AS semana_anterior 
    FROM 
        gastos g 
    JOIN 
        categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
    WHERE 
        g.ID_Categoria_Gastos != 1 AND g.ID_Categoria_Gastos != 2
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

        <div class="card p-6">
            <h2 class="text-xl font-semibold mb-4">Desglose de Gastos</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Categoría</th>
                            <th class="px-6 py-3">Esta Semana</th>
                            <th class="px-6 py-3">Semana Anterior</th>
                            <th class="px-6 py-3">Tendencia</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTable">

                        <?php

                        foreach ($categorias_gastos as $categoria) {

                            $tendencia_clase = (strpos($categoria['trend'], '+') === 0) ? 'text-red-500' : 'text-green-500';

                            // Suponiendo que ya tienes el arreglo $categorias_gastos y que has definido $tendencia_clase

                            echo "<tr class='border-b hover:bg-gray-50'>";
                            echo "<td class='px-6 py-4 font-medium'>" . htmlspecialchars($categoria['category']) . "</td>"; // Evitar XSS
                            echo "<td class='px-6 py-4'>$" . number_format($categoria['weekly'], 0, '', '.') . "</td>"; // Formato del gasto semanal
                            echo "<td class='px-6 py-4'>$" . number_format($categoria['monthly'], 0, '', '.') . "</td>"; // Formato del gasto mensual
                            echo "<td class='px-6 py-4 {$tendencia_clase}'>" . htmlspecialchars($categoria['trend']) . "</td>"; // Evitar XSS
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
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