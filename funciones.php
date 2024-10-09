<?php

//FUNCION PARA SABER EL NOMBRE EN ESPAÑOL DE LOS MESES
function obtener_nombre_mes_espanol($numero_mes)
{
    $meses_array = array(
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    );
    return $meses_array[$numero_mes];
}
//USO: $mes = obtener_nombre_mes_espanol(date('n'));

//Función para obtener los datos de ingresos y egresos de los últimos 6 meses
function obtener_datos_ultimos_meses($conexion, $meses)
{
    $datos = [];
    $stmt = $conexion->prepare("
                                SELECT 
                                    SUM(CASE WHEN categorias_gastos.Nombre = 'Ingresos' THEN gastos.Valor ELSE 0 END) AS total_ingresos,
                                    SUM(CASE WHEN categorias_gastos.Nombre != 'Ingresos' THEN gastos.Valor ELSE 0 END) AS total_egresos
                                FROM gastos 
                                INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos 
                                WHERE MONTH(gastos.Fecha) = ? AND YEAR(gastos.Fecha) = ?
                            ");

    for ($i = $meses - 1; $i >= 0; $i--) {
        $fecha = date('Y-m-01', strtotime("-$i month"));
        $mes = date('n', strtotime($fecha));
        $anio = date('Y', strtotime($fecha));

        $stmt->bind_param('ii', $mes, $anio);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $total_ingresos = $row['total_ingresos'] ?? 0;
        $total_egresos = $row['total_egresos'] ?? 0;

        $datos[] = [
            'mes' => obtener_nombre_mes_espanol($mes),
            'ingresos' => $total_ingresos,
            'egresos' => $total_egresos
        ];
    }
    $stmt->close();
    return $datos;
}
//USO: $datos_financieros = obtener_datos_ultimos_meses($conexion, $cantidad_meses_balance);

//Funcion para sacar el total, los detalles y el total anterior de cada modulo
function obtener_datos($conexion, $where, $current_month, $current_year, $previous_month, $previous_year)
{
    // SQL queries
    $sql_total = "SELECT SUM(g.Valor) AS total
    FROM gastos g
    INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
    WHERE (MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?)
    AND(" . $where . ")";

    $sql_detalles = "SELECT d.Detalle AS Descripcion, g.Valor, c.Nombre as categoria, g.Fecha
    FROM gastos g
    INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
    INNER JOIN detalle d ON g.ID_Detalle = d.ID
    WHERE (MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?)
    AND(" . $where . ")
    ORDER BY g.Fecha DESC";

    $sql_anterior = "SELECT SUM(g.Valor) AS total
    FROM gastos g
    INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
    WHERE (MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?)
    AND(" . $where . ")";

    // Consulta del total de gastos del mes actual
    $stmt_total = mysqli_prepare($conexion, $sql_total);
    mysqli_stmt_bind_param($stmt_total, "ss", $current_month, $current_year);
    mysqli_stmt_execute($stmt_total);
    $result_total = mysqli_stmt_get_result($stmt_total);
    $total = mysqli_fetch_assoc($result_total)['total'] ?? 0;

    // Consulta de los detalles de los gastos del mes actual
    $stmt_detalles = mysqli_prepare($conexion, $sql_detalles);
    mysqli_stmt_bind_param($stmt_detalles, "ss", $current_month, $current_year);
    mysqli_stmt_execute($stmt_detalles);
    $result_detalles = mysqli_stmt_get_result($stmt_detalles);

    // Consulta del total de gastos del mes anterior
    $stmt_anterior = mysqli_prepare($conexion, $sql_anterior);
    mysqli_stmt_bind_param($stmt_anterior, "ss", $previous_month, $previous_year);
    mysqli_stmt_execute($stmt_anterior);
    $result_anterior = mysqli_stmt_get_result($stmt_anterior);
    $anterior_total = mysqli_fetch_assoc($result_anterior)['total'] ?? 0;

    // Retornar los resultados en un array
    return [
        'total' => $total,
        'detalles' => $result_detalles,
        'anterior_total' => $anterior_total
    ];
}

//USO: $datos_gastos = obtener_datos($conexion, $where, $current_month, $current_year, $previous_month, $previous_year);


// Función para determinar el color basado en la comparación de valores
function obtenerColor($anterior_valor, $valor_actual)
{
    if ($anterior_valor < $valor_actual) {
        #echo "El valor actual de $tipo es mayor al anterior, el color es rojo.<br>";
        return "red"; // El valor actual es mayor, por lo tanto, el color es rojo
    } else {
    }
}

//USO: $color_ahorro = obtenerColor($anterior_total_ahorros, $total_ahorros);

//FUncion para sacar el total por categoria y su nombre
function ejecutar_consulta($pdo, $where)
{
    // Consulta SQL para obtener el total por categoría
    $sql = "SELECT c.Nombre AS categoria, SUM(g.Valor) AS total_categoria
            FROM gastos g
            INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
            WHERE $where
            GROUP BY c.Nombre
            ORDER BY total_categoria DESC";

    try {
        // Preparar y ejecutar la consulta
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        // Obtener las categorías y sus totales
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular la suma total directamente en PHP
        $suma_total = array_sum(array_column($categorias, 'total_categoria'));

        return [
            'categorias' => $categorias,
            'suma_total' => $suma_total
        ];
    } catch (PDOException $e) {
        // Manejo de errores
        throw new Exception("Error en la consulta: " . $e->getMessage());
    }
}
//USO: $resultado2 = ejecutar_consulta($pdo, $where_ocio);

//Funcion para hacer un grafico de pie para cada modulo con sus datos
function piechart($id, $categoria_nombre, $colores, $title = 'Gastos por Categoría')
{
    // Encode the data and colors for JavaScript
    $js_data = json_encode(array_map(function ($categoria) {
        return [
            'value' => $categoria['total_categoria'],
            'name' => $categoria['categoria']
        ];
    }, $categoria_nombre));

    $js_colors = json_encode($colores);

    // Output the JavaScript code
?>
    <script>
        (function() {
            var dom = document.getElementById('<?php echo $id; ?>');
            var myChart = echarts.init(dom, null, {
                renderer: 'canvas',
                useDirtyRect: false
            });

            var option = {
                tooltip: {
                    trigger: 'item'
                },
                legend: {
                    left: 'center'
                },
                color: <?php echo $js_colors; ?>,
                series: [{
                    top: '5%',
                    name: <?php echo json_encode($title); ?>,
                    type: 'pie',
                    radius: ['40%', '70%'],
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 10,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: true,
                            fontSize: '18',
                            fontWeight: 'bold'
                        }
                    },
                    labelLine: {
                        show: false
                    },
                    data: <?php echo $js_data; ?>
                }]
            };

            myChart.setOption(option);
            window.addEventListener('resize', myChart.resize);
        })();
    </script>
<?php
}
//USO: piechart('ahorro-restante', $categorias_ahorro, $colores_ahorros);


//FUNCION PARA SABER LOS DATOS HISTORICOS DE CADA MODULO CON SUS CATEGORIAS
function DatosHistoricos($where, $conexion, $nombre_grafico, $colores)
{
    // Construir la consulta SQL
    $sql = "
    SELECT 
        c.Nombre AS categoria, 
        DATE_FORMAT(gastos.Fecha, '%Y-%m') AS mes, 
        SUM(gastos.Valor) AS total_categoria 
    FROM 
        gastos 
    INNER JOIN 
        categorias_gastos c ON gastos.ID_Categoria_Gastos = c.ID 
    WHERE $where 
    GROUP BY 
        c.Nombre, mes;";

    // Ejecutar la consulta
    $result = $conexion->query($sql);

    // Inicializar arrays para los datos
    $total_historico = [];
    $mes_historico = [];

    // Crear un array de mapeo de meses
    $meses_nombres = [
        "2024-01" => "Enero",
        "2024-02" => "Febrero",
        "2024-03" => "Marzo",
        "2024-04" => "Abril",
        "2024-05" => "Mayo",
        "2024-06" => "Junio",
        "2024-07" => "Julio",
        "2024-08" => "Agosto",
        "2024-09" => "Septiembre",
        "2024-10" => "Octubre",
        "2024-11" => "Noviembre",
        "2024-12" => "Diciembre",
    ];


    // Procesar los resultados
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categorias_historico[] = $row['categoria'];
            $mes_historico[] = $row['mes'];
            $total_historico[$row['categoria']][$row['mes']] = $row['total_categoria'];
        }
    }

    $mes_historico = array_unique($mes_historico);
    sort($mes_historico); // Ordenar los meses

    // Convertir meses a sus nombres
    $meses_con_nombres = [];
    foreach ($mes_historico as $mes) {
        $meses_con_nombres[] = $meses_nombres[$mes];
    }

    // Convertir arrays a formato JSON para usarlos en JavaScript
    $meses_json = json_encode($mes_historico);
    $meses_nombre = json_encode($meses_con_nombres);
    $valores_json = json_encode($total_historico);
    $js_colors = json_encode($colores);

    // Generar el script del gráfico
    echo "
    <script>
        (function() {
            var dom = document.getElementById('$nombre_grafico');
            var myChart = echarts.init(dom, null, {
                renderer: 'canvas',
                useDirtyRect: false
            });

            var name = $meses_nombre; 
            var meses = $meses_json; // Meses obtenidos de PHP
            var valores = $valores_json; // Valores obtenidos de PHP

            // Preparar datos para el gráfico
            var series = Object.keys(valores).map(function(categoria) {
                var data = meses.map(function(mes) {
                    return valores[categoria][mes] || 0; // Añadir 0 si no hay datos
                });
                return {
                name: categoria,
                type: 'line',
                // Eliminar o establecer stack en null para que no se apilen
                stack: null,
                data: data
                };
            });


            var option = {
                tooltip: { trigger: 'axis' },
                legend: { top: '-1%' },
                grid: {
                    left: '3%', right: '8%', bottom: '1%',
                    containLabel: true
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: name
                },
                yAxis: { type: 'value' },
                color: $js_colors,
                series: series // Usar las series preparadas
            };

            myChart.setOption(option);
            window.addEventListener('resize', myChart.resize);
        })();
    </script>
";
}
//USO: DatosHistoricos($where_ahorros, $conexion, "ahorro-historico", $colores_ahorros);


//FUNCION PARA HACER UN GRAFICO GRANDE DE PIE PARA SABER EL TOTAL DE CADA MODULO Y SUS CATEGORIAS
function bigchart($id, $categoria_nombre, $colores)
{
    // Encode the data and colors for JavaScript
    $js_data = json_encode(array_map(function ($categoria) {
        return [
            'value' => $categoria['total_categoria'],
            'name' => $categoria['categoria']
        ];
    }, $categoria_nombre));

    $js_colors = json_encode($colores);

    // Output the JavaScript code
?>
    <script>
        (function() {
            var dom = document.getElementById('<?php echo $id; ?>');
            var myChart = echarts.init(dom, null, {
                renderer: 'canvas',
                useDirtyRect: false
            });

            option = {
                tooltip: {
                    trigger: 'item',
                    formatter: '{b} : {c} - ({d}%)'
                },
                legend: {
                    top: 'top',
                },
                toolbox: {
                    show: true,
                },
                color: <?php echo $js_colors; ?>,
                series: [{
                    type: 'pie',
                    radius: [50, 150],
                    top: '15%',
                    center: ['50%', '50%'],
                    roseType: 'radius',
                    itemStyle: {
                        borderRadius: 5
                    },
                    label: {
                        show: false
                    },
                    emphasis: {
                        label: {
                            show: true
                        }
                    },
                    data: <?php echo $js_data; ?>
                }]
            };

            myChart.setOption(option);
            window.addEventListener('resize', myChart.resize);
        })();
    </script>
<?php
}
//USO: bigchart('total-ahorro', $total_categorias_ahorro, $colores_ahorros);

// Función para calcular porcentajes
function calcularPorcentaje($actual, $presupuesto)
{
    if ($presupuesto <= 0) return 0;
    return min(100, round(($actual / $presupuesto) * 100));
}

// Función para obtener color según el porcentaje
function obtenerColorBarra($porcentaje)
{

    if ($porcentaje >= 80) return 'danger';
    if ($porcentaje >= 60) return 'warning';
    if ($porcentaje >= 40) return 'success';
    if ($porcentaje >= 20) return 'secondary';
    return 'primary';
}

// Componente de barra de progreso
function mostrarBarraProgreso($valorActual, $presupuestoTotal)
{
    $porcentaje = calcularPorcentaje($valorActual, $presupuestoTotal);
    $colorBarra = obtenerColorBarra($porcentaje);

    return "
            <div class='progress' style='height: 20px;'>
                <div class='progress-bar bg-$colorBarra' 
                     role='progressbar' 
                     style='width: $porcentaje%' 
                     aria-valuenow='$porcentaje' 
                     aria-valuemin='0' 
                     aria-valuemax='100'>
                    $porcentaje%
                </div>
            </div>";
}

function obtenerDatosRecurrentes($conn, $where, $minRepeticiones)
{
    // Consulta para obtener gastos recurrentes
    $query = "
        SELECT d.Detalle AS Descripcion, g.Valor, c.Nombre  AS categoria, c.Categoria_Padre AS categoria_padre, COUNT(c.ID) AS cantidad_repeticiones
        FROM gastos g
        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
        INNER JOIN detalle d ON g.ID_Detalle = d.ID
        WHERE $where
        GROUP BY d.Detalle, c.ID, c.Categoria_Padre
        HAVING COUNT(c.ID) >= ?
        ORDER BY `cantidad_repeticiones` DESC
    ";

    // Preparar la declaración
    if ($stmt = $conn->prepare($query)) {
        // Vincular parámetros
        $stmt->bind_param("i", $minRepeticiones);

        // Ejecutar la declaración
        $stmt->execute();

        // Obtener el resultado
        $result = $stmt->get_result();

        // Crear el arreglo de gastos recurrentes
        $gastosRecurrentes = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gastosRecurrentes[] = [
                    'descripcion' => $row['Descripcion'],
                    'monto' => $row['Valor'],
                    'categoria' => $row['categoria'],
                    'categoria_padre' => $row['categoria_padre'],
                    'cantidad_repeticiones' => $row['cantidad_repeticiones']
                ];
            }
        }

        // Cerrar la declaración
        $stmt->close();
    }

    return $gastosRecurrentes;
}
?>