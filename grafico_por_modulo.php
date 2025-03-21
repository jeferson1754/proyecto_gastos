<?php

include('bd.php');

$modulos = [
    'Gastos' => $where_gastos,
    'Ocio' => $where_ocio,
    'Ahorros' => $where_ahorros
];

$cantidad_meses_balance = isset($_GET['cantidad_meses']) ? $_GET['cantidad_meses'] : 6;

// Crear un arreglo para almacenar los totales mensuales
$resultados_mensuales = [];

// Función para obtener datos mensuales
function obtener_datos_mensuales($conexion, $where, $limit)
{
    $sql_total = "SELECT DATE_FORMAT(gastos.Fecha, '%Y-%m') AS mes, SUM(gastos.Valor) AS total_mensual
        FROM gastos
        WHERE ID_Categoria_Gastos IN (
            SELECT ID FROM categorias_gastos as c WHERE $where
        )
         AND gastos.Fecha >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL $limit MONTH), '%Y-%m-01')
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
    $resultados_mensuales[$nombre_categoria] = obtener_datos_mensuales($conexion, $where_clause, $cantidad_meses_balance);
}

// Unir todos los meses y asegurarse de que estén ordenados cronológicamente
$meses_unicos = array_keys(array_merge(...array_values($resultados_mensuales)));
sort($meses_unicos); // Ordenar los meses cronológicamente

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

// Convertir números de meses al formato completo (e.g., "2024-01" a "Enero 2024")
$meses_convertidos = array_map(function ($mes) use ($nombres_meses) {
    [$año, $mes_num] = explode('-', $mes);
    return $nombres_meses[$mes_num] . " " . $año;
}, $meses_unicos);

$color_gastos = "#FF9800";
$color_ocio = "#198754";
$color_ahorro = "#0DCAF0";

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
        <div class="row mb-4">
            <div class="col-md-12 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Distribución de Gastos, Ocio y Ahorros</h3>
                        <div id="pie-chart1" class="chart"></div>
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
        var meses_numeros = <?php echo json_encode($meses_unicos); ?>;
        var data_gastos = <?php echo json_encode(array_values($resultados_mensuales['Gastos'])); ?>;
        var data_ocio = <?php echo json_encode(array_values($resultados_mensuales['Ocio'])); ?>;
        var data_ahorros = <?php echo json_encode(array_values($resultados_mensuales['Ahorros'])); ?>;

        // Meses convertidos a nombres con años
        var meses = <?php echo json_encode($meses_convertidos); ?>;

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
                data: meses,
                axisLabel: {
                    interval: 0,
                    rotate: 30
                } // Usar los nombres completos de los meses
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
                    type: 'bar',
                    data: data_gastos,
                    itemStyle: {
                        color: '<?php echo $color_gastos ?>'
                    },
                    markLine: {
                        data: [{
                            name: 'Promedio',
                            type: 'average',
                            lineStyle: {
                                type: 'dashed',
                                color: '<?php echo $color_gastos ?>'
                            }
                        }]
                    }
                },
                {
                    name: 'Ocio',
                    type: 'bar',
                    data: data_ocio,
                    itemStyle: {
                        color: '<?php echo $color_ocio ?>'
                    },
                    markLine: {
                        data: [{
                            name: 'Promedio',
                            type: 'average',
                            lineStyle: {
                                type: 'dashed',
                                color: '<?php echo $color_ocio ?>'
                            }
                        }]
                    }
                },
                {
                    name: 'Ahorros',
                    type: 'bar',
                    data: data_ahorros,
                    itemStyle: {
                        color: '<?php echo $color_ahorro ?>'
                    },
                    markLine: {
                        data: [{
                            name: 'Promedio',
                            type: 'average',
                            lineStyle: {
                                type: 'dashed',
                                color: '<?php echo $color_ahorro ?>'
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

    <!-- Grafico Circular de Proporcion Total -->
    <script>
        var dom = document.getElementById('pie-chart1');
        var myChart = echarts.init(dom, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });

        var total_gastos = data_gastos.reduce((acc, val) => acc + parseInt(val), 0);
        var total_ocio = data_ocio.reduce((acc, val) => acc + parseInt(val), 0);
        var total_ahorros = data_ahorros.reduce((acc, val) => acc + parseInt(val), 0);

        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        var option = {
            tooltip: {
                trigger: 'item',
                formatter: function(params) {
                    // Formatear el valor en el tooltip
                    return params.seriesName + ' <br/>' + params.name + ' : ' + formatNumber(params.value) + ' (' + params.percent.toFixed(2) + '%)';
                }
            },
            series: [{
                name: 'Total',
                type: 'pie',
                radius: '50%',
                data: [{
                        value: total_gastos,
                        name: 'Gastos',
                        itemStyle: {
                            color: '<?php echo $color_gastos ?>'
                        },
                    },
                    {
                        value: total_ocio,
                        name: 'Ocio',
                        itemStyle: {
                            color: '<?php echo $color_ocio ?>'
                        },
                    },
                    {
                        value: total_ahorros,
                        name: 'Ahorros',
                        itemStyle: {
                            color: '<?php echo $color_ahorro ?>'
                        },
                    }
                ],
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }]
        };

        myChart.setOption(option);
        window.addEventListener('resize', myChart.resize);
    </script>

</body>

</html>