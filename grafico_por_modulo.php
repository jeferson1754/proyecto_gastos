<?php

include('bd.php');

$modulos = [
    'Gastos' => $where_gastos,
    'Ocio' => $where_ocio,
    'Ahorros' => $where_ahorros
];

// Crear un arreglo para almacenar los totales mensuales
$resultados_mensuales = [];

// Función para obtener datos mensuales
function obtener_datos_mensuales($conexion, $where)
{
    $sql_total = "SELECT DATE_FORMAT(gastos.Fecha, '%m') AS mes, SUM(gastos.Valor) AS total_mensual
        FROM gastos
        WHERE ID_Categoria_Gastos IN (
            SELECT ID FROM categorias_gastos as c WHERE $where
        )
        GROUP BY mes
        ORDER BY mes";

    $stmt_total = mysqli_prepare($conexion, $sql_total);
    mysqli_stmt_execute($stmt_total);
    $result_total = mysqli_stmt_get_result($stmt_total);

    // Retornar los resultados en un array
    $resultados = [];
    while ($row = mysqli_fetch_assoc($result_total)) {
        $resultados[$row['mes']] = $row['total_mensual'];
    }

    return $resultados;
}

// Obtener los totales mensuales para cada módulo
foreach ($modulos as $nombre_categoria => $where_clause) {
    $resultados_mensuales[$nombre_categoria] = obtener_datos_mensuales($conexion, $where_clause);
}

// Crear un arreglo con los nombres de los meses en español
$nombres_meses = [
    '01' => 'Enero',
    '02' => 'Febrero',
    '03' => 'Marzo',
    '04' => 'Abril',
    '05' => 'Mayo',
    '06' => 'Junio',
    '07' => 'Julio',
    '08' => 'Agosto',
    '09' => 'Septiembre',
    '10' => 'Octubre',
    '11' => 'Noviembre',
    '12' => 'Diciembre'
];

$color_gastos="#FF9800";
$color_ocio="#198754";
$color_ahorro="#0DCAF0";

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://fastly.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
    <title>Resumen Financiero</title>
    <link rel="stylesheet" href="styles.css?<?php echo time() ?>">
</head>


<body>
    <div class="container py-1">
        <div class="row mb-4">
            <div class="col-md-12 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Tendencia y Promedio Mensual</h3>
                        <div id="bar-container" class="chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        var dom = document.getElementById('bar-container');
        var myChart = echarts.init(dom, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });

        // Obtener los datos de PHP
        var meses_numeros = <?php echo json_encode(array_keys($resultados_mensuales['Gastos'] + $resultados_mensuales['Ocio'] + $resultados_mensuales['Ahorros'])); ?>;
        var data_gastos = <?php echo json_encode(array_values($resultados_mensuales['Gastos'])); ?>;
        var data_ocio = <?php echo json_encode(array_values($resultados_mensuales['Ocio'])); ?>;
        var data_ahorros = <?php echo json_encode(array_values($resultados_mensuales['Ahorros'])); ?>;

        // Convertir números de meses a nombres en español
        var nombres_meses = {
            '01': 'Enero',
            '02': 'Febrero',
            '03': 'Marzo',
            '04': 'Abril',
            '05': 'Mayo',
            '06': 'Junio',
            '07': 'Julio',
            '08': 'Agosto',
            '09': 'Septiembre',
            '10': 'Octubre',
            '11': 'Noviembre',
            '12': 'Diciembre'
        };

        // Convertir meses a nombres
        var meses = meses_numeros.map(function(mes) {
            return nombres_meses[mes];
        });




        option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            legend: {},
            grid: {
                left: '3%',
                right: '10%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: [{
                type: 'category',
                data: meses // Cambia los meses numéricos a nombres
            }],
            yAxis: [{
                type: 'value'
            }],
            toolbox: {
                feature: {
                    magicType: {
                        show: true,
                        type: ['line', 'bar']
                    }
                }
            },
            series: [{
                    name: 'Gastos',
                    type: 'bar', // Cambiar a tipo 'bar' para gráfico de barras
                    data: data_gastos,
                    itemStyle: {
                        color: '<?php echo $color_gastos ?>' // Color personalizado para Gastos
                    },
                    markLine: {
                        data: [{
                            name: 'Promedio',
                            type: 'average', // Tipo promedio
                            lineStyle: {
                                type: 'dashed', // Líneas entrecortadas
                                color: '<?php echo $color_gastos ?>' // Color de la línea de límite
                            }
                        }]
                    }
                },
                {
                    name: 'Ocio',
                    type: 'bar', // Cambiar a tipo 'bar' para gráfico de barras
                    data: data_ocio,
                    itemStyle: {
                        color: '<?php echo $color_ocio ?>' // Color personalizado para Ocio
                    },
                    markLine: {
                        data: [{
                            name: 'Promedio',
                            type: 'average', // Tipo promedio
                            lineStyle: {
                                type: 'dashed', // Líneas entrecortadas
                                color: '<?php echo $color_ocio ?>' // Color de la línea de límite
                            }
                        }]
                    }
                },
                {
                    name: 'Ahorros',
                    type: 'bar', // Cambiar a tipo 'bar' para gráfico de barras
                    data: data_ahorros,
                    itemStyle: {
                        color: '<?php echo $color_ahorro ?>' // Color personalizado para Ahorros
                    },
                    markLine: {
                        data: [{
                            name: 'Promedio',
                            type: 'average', // Tipo promedio
                            lineStyle: {
                                type: 'dashed', // Líneas entrecortadas
                                color: '<?php echo $color_ahorro ?>' // Color de la línea de límite
                            }
                        }]
                    }
                }

            ]



        };

        if (option && typeof option === 'object') {
            myChart.setOption(option);
        }

        window.addEventListener('resize', myChart.resize);
    </script>


</body>

</html>