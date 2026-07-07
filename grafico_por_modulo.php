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
    // Consulta SQL mejorada utilizando SUM(CASE WHEN...)
    $sql_total = "SELECT DATE_FORMAT(gastos.Fecha, '%Y-%m') AS mes, 
                         SUM(gastos.Valor) AS total_mensual,
                         SUM(CASE WHEN gastos.Fuente_Dinero = 'externo' THEN gastos.Valor ELSE 0 END) AS total_externo
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

    $resultados = [];
    while ($row = mysqli_fetch_assoc($result_total)) {
        // Guardamos AMBOS valores estructurados por mes
        $resultados[$row['mes']] = [
            'total' => (float)$row['total_mensual'],
            'externo' => (float)$row['total_externo']
        ];
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

// --- PROCESAMIENTO ANTES DEL JAVASCRIPT ---
// Como ahora los datos no son planos, debemos armar los arreglos para las series de ECharts.
// Mapeamos los datos asegurando que si un mes no tiene registros, se llene con 0.

// Inicializamos los arreglos de datos planos que requiere ECharts
$data_gastos_propio  = [];
$data_gastos_externo = [];

$data_ocio_propio    = [];
$data_ocio_externo   = [];

$data_ahorros_propio  = [];
$data_ahorros_externo = [];

foreach ($meses_unicos as $mes) {
    // 1. Módulo Gastos
    $g = $resultados_mensuales['Gastos'][$mes] ?? ['total' => 0, 'externo' => 0];
    $data_gastos_propio[]  = $g['total'] - $g['externo'];
    $data_gastos_externo[] = $g['externo'];

    // 2. Módulo Ocio
    $o = $resultados_mensuales['Ocio'][$mes] ?? ['total' => 0, 'externo' => 0];
    $data_ocio_propio[]    = $o['total'] - $o['externo'];
    $data_ocio_externo[]   = $o['externo'];

    // 3. Módulo Ahorros
    $a = $resultados_mensuales['Ahorros'][$mes] ?? ['total' => 0, 'externo' => 0];
    $data_ahorros_propio[]  = $a['total'] - $a['externo'];
    $data_ahorros_externo[] = $a['externo'];
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

        var meses = <?php echo json_encode($meses_convertidos); ?>;

        var option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                },
                formatter: function(params) {
                    var html = `<strong>${params[0].name}</strong><br/>`;
                    var formatNum = (num) => new Intl.NumberFormat('es-CL').format(num);

                    // Agrupamos los datos por módulo para mostrarlos ordenados en el tooltip
                    var modulos = {
                        'Gastos': {
                            propio: 0,
                            externo: 0,
                            color: '<?php echo $color_gastos ?>'
                        },
                        'Ocio': {
                            propio: 0,
                            externo: 0,
                            color: '<?php echo $color_ocio ?>'
                        },
                        'Ahorros': {
                            propio: 0,
                            externo: 0,
                            color: '<?php echo $color_ahorro ?>'
                        }
                    };

                    params.forEach(function(item) {
                        if (item.seriesName.includes('Gastos')) {
                            if (item.seriesName.includes('Ext')) modulos['Gastos'].externo = item.value;
                            else modulos['Gastos'].propio = item.value;
                        }
                        if (item.seriesName.includes('Ocio')) {
                            if (item.seriesName.includes('Ext')) modulos['Ocio'].externo = item.value;
                            else modulos['Ocio'].propio = item.value;
                        }
                        if (item.seriesName.includes('Ahorros')) {
                            if (item.seriesName.includes('Ext')) modulos['Ahorros'].externo = item.value;
                            else modulos['Ahorros'].propio = item.value;
                        }
                    });

                    // Renderizamos el desglose limpio en el Tooltip
                    Object.keys(modulos).forEach(function(key) {
                        var m = modulos[key];
                        var total = m.propio + m.externo;
                        if (total > 0) {
                            html += `<span style="display:inline-block;margin-right:4px;border-radius:10px;width:10px;height:10px;background-color:${m.color};"></span> `;
                            html += `${key}: <strong>$${formatNum(total)}</strong>`;
                            if (m.externo > 0) {
                                html += ` <span style="color: #6c757d; font-size: 0.9em;">(Propio: $${formatNum(m.propio)} | Ext: $${formatNum(m.externo)})</span>`;
                            }
                            html += `<br/>`;
                        }
                    });

                    return html;
                }
            },
            legend: {
                // Forzamos a mostrar solo las categorías principales en la leyenda superior
                data: ['Gastos', 'Ocio', 'Ahorros']
            },
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
                }
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
            series: [
                // --- MÓDULO GASTOS ---
                {
                    name: 'Gastos',
                    type: 'bar',
                    stack: 'gastos',
                    data: <?php echo json_encode($data_gastos_propio); ?>,
                    itemStyle: {
                        color: '<?php echo $color_gastos ?>'
                    }
                },
                {
                    name: 'Gastos Ext',
                    type: 'bar',
                    stack: 'gastos',
                    data: <?php echo json_encode($data_gastos_externo); ?>,
                    itemStyle: {
                        color: '#adb5bd'
                    } // Color Gris intermedio
                },

                // --- MÓDULO OCIO ---
                {
                    name: 'Ocio',
                    type: 'bar',
                    stack: 'ocio',
                    data: <?php echo json_encode($data_ocio_propio); ?>,
                    itemStyle: {
                        color: '<?php echo $color_ocio ?>'
                    }
                },
                {
                    name: 'Ocio Ext',
                    type: 'bar',
                    stack: 'ocio',
                    data: <?php echo json_encode($data_ocio_externo); ?>,
                    itemStyle: {
                        color: '#adb5bd'
                    } // Color Gris intermedio
                },

                // --- MÓDULO AHORROS ---
                {
                    name: 'Ahorros',
                    type: 'bar',
                    stack: 'ahorros',
                    data: <?php echo json_encode($data_ahorros_propio); ?>,
                    itemStyle: {
                        color: '<?php echo $color_ahorro ?>'
                    }
                },
                {
                    name: 'Ahorros Ext',
                    type: 'bar',
                    stack: 'ahorros',
                    data: <?php echo json_encode($data_ahorros_externo); ?>,
                    itemStyle: {
                        color: '#adb5bd'
                    } // Color Gris intermedio
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

        // --- SOLUCIÓN: Calculamos los totales directamente desde PHP ---
        // Así este archivo no depende de variables externas de otros scripts
        <?php
        $total_g = 0;
        $ext_g = 0;
        $total_o = 0;
        $ext_o = 0;
        $total_a = 0;
        $ext_a = 0;

        foreach ($meses_unicos as $mes) {
            // Sumamos Gastos
            $g = $resultados_mensuales['Gastos'][$mes] ?? ['total' => 0, 'externo' => 0];
            $total_g += $g['total'];
            $ext_g   += $g['externo'];

            // Sumamos Ocio
            $o = $resultados_mensuales['Ocio'][$mes] ?? ['total' => 0, 'externo' => 0];
            $total_o += $o['total'];
            $ext_o   += $o['externo'];

            // Sumamos Ahorros
            $a = $resultados_mensuales['Ahorros'][$mes] ?? ['total' => 0, 'externo' => 0];
            $total_a += $a['total'];
            $ext_a   += $a['externo'];
        }
        ?>

        // Pasamos las sumas finales listas a JavaScript
        var total_gastos = <?= $total_g ?>;
        var ext_gastos = <?= $ext_g ?>;

        var total_ocio = <?= $total_o ?>;
        var ext_ocio = <?= $ext_o ?>;

        var total_ahorros = <?= $total_a ?>;
        var ext_ahorros = <?= $ext_a ?>;

        // Herramienta nativa para formatear a estilo chileno (con puntos)
        function formatNumber(num) {
            return new Intl.NumberFormat('es-CL').format(num);
        }

        var option = {
            tooltip: {
                trigger: 'item',
                formatter: function(params) {
                    var total = params.value;
                    var externo = params.data.externo || 0;
                    var propio = total - externo;

                    var html = `<strong>${params.seriesName}</strong><br/>`;
                    html += `${params.marker} ${params.name}: <strong>$${formatNumber(total)}</strong> (${params.percent.toFixed(2)}%)<br/>`;

                    if (externo > 0) {
                        html += `<small style="padding-left: 15px;">• Propio: $${formatNumber(propio)}</small><br/>`;
                        html += `<small style="color: #aaa; padding-left: 15px;">• Externo: $${formatNumber(externo)}</small>`;
                    }

                    return html;
                }
            },
            series: [{
                name: 'Balance Total',
                type: 'pie',
                radius: '50%',
                data: [{
                        value: total_gastos,
                        name: 'Gastos',
                        externo: ext_gastos,
                        itemStyle: {
                            color: '<?php echo $color_gastos ?>'
                        }
                    },
                    {
                        value: total_ocio,
                        name: 'Ocio',
                        externo: ext_ocio,
                        itemStyle: {
                            color: '<?php echo $color_ocio ?>'
                        }
                    },
                    {
                        value: total_ahorros,
                        name: 'Ahorros',
                        externo: ext_ahorros,
                        itemStyle: {
                            color: '<?php echo $color_ahorro ?>'
                        }
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