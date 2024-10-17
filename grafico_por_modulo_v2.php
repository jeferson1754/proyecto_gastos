<?php

include('bd.php');

$cantidad_meses_balance = 6;

$minRepeticiones = 5;


// Crear un arreglo para manejar las categorías y sus where correspondientes
$modulos = [
    'Gastos' => $where_gastos,
    'Ocio' => $where_ocio,
    'Ahorros' => $where_ahorros
];

// Crear un arreglo para almacenar los resultados
$resultados = [];
// Crear un arreglo para almacenar los totales mensuales
$resultados_mensuales = [];

foreach ($modulos as $nombre_categoria => $where_clause) {
    // Llamar a la función con cada where y almacenar los resultados
    $resultados[$nombre_categoria] = obtener_datos($conexion, $where_clause, $current_month, $current_year, $previous_month, $previous_year);
}
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

// Acceder a los resultados de cada categoría
$total_gastos = $resultados['Gastos']['total'];
$result_detalles_gastos = $resultados['Gastos']['detalles'];
$anterior_total_gastos = $resultados['Gastos']['anterior_total'];

$total_ocio = $resultados['Ocio']['total'];
$result_detalles_ocio = $resultados['Ocio']['detalles'];
$anterior_total_ocio = $resultados['Ocio']['anterior_total'];

$total_ahorros = $resultados['Ahorros']['total'];
$result_detalles_ahorros = $resultados['Ahorros']['detalles'];
$anterior_total_ahorros = $resultados['Ahorros']['anterior_total'];

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

        <?php

        $datos_financieros = obtener_datos_ultimos_meses($conexion, $cantidad_meses_balance);
        $ultimo_mes = end($datos_financieros);
        $total_ingresos = $ultimo_mes['ingresos'];

        $gastos = $total_ingresos * 0.5;
        $ocio = $total_ingresos * 0.3;
        $ahorro = $total_ingresos * 0.2;

        $gastos_restante = $gastos - $total_gastos;
        $ocio_restante = $ocio - $total_ocio;
        $ahorros_restante = $ahorro - $total_ahorros;

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

        $resultado1 = ejecutar_consulta($pdo, $where_gastos);
        $total_categorias_gastos = $resultado1['categorias'];
        $suma_total_gastos = $resultado1['suma_total'];

        $resultado2 = ejecutar_consulta($pdo, $where_ocio);
        $total_categorias_ocio = $resultado2['categorias'];
        $suma_total_ocio = $resultado2['suma_total'];

        $resultado3 = ejecutar_consulta($pdo, $where_ahorros);

        $total_categorias_ahorro = $resultado3['categorias'];
        $suma_total_ahorro = $resultado3['suma_total'];

        $fecha = "MONTH(g.Fecha) = $current_month AND YEAR(g.Fecha) = $current_year";

        $resultado4 = ejecutar_consulta($pdo, "$fecha AND ($where_gastos)");
        $categorias_gastos = $resultado4['categorias'];

        $resultado5 = ejecutar_consulta($pdo, "$fecha AND ($where_ocio)");
        $categorias_ocio = $resultado5['categorias'];

        $resultado6 = ejecutar_consulta($pdo, "$fecha AND ($where_ahorros)");
        $categorias_ahorro = $resultado6['categorias'];

        include('modal_ingresos.php');
        include('modal_gastos.php');
        include('modal_ocio.php');
        include('modal_ahorro.php');
        include('modal_detalle_gastos.php');
        include('modal_detalle_ocio.php');
        include('modal_detalle_ahorro.php');

        ?>


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

    <!--Grafico de Ingresos y Egresos-->
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

    <?php

    //Graficos Lineal Historico
    DatosHistoricos($where_gastos, $conexion, "gastos-historico", $colores_gastos);
    DatosHistoricos($where_ocio, $conexion, "ocio-historico", $colores_ocios);
    DatosHistoricos($where_ahorros, $conexion, "ahorro-historico", $colores_ahorros);

    //Graficos Pie Total
    bigchart('total-gastos', $total_categorias_gastos, $colores_gastos);
    bigchart('total-ocio', $total_categorias_ocio, $colores_ocios);
    bigchart('total-ahorro', $total_categorias_ahorro, $colores_ahorros);
    ?>
</body>

</html>