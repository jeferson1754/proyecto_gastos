<?php

include('bd.php');
$mes_actual = date('n');
$anio_actual = date('Y');
$sql_ingresos_actual = "SELECT SUM(gastos.Valor) AS total_ingresos FROM gastos 
                        INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos 
                        WHERE categorias_gastos.Nombre = 'Ingresos' 
                        AND MONTH(gastos.Fecha) = $mes_actual AND YEAR(gastos.Fecha) = $anio_actual";
$result_ingresos_actual = mysqli_query($conexion, $sql_ingresos_actual);
$total_ingresos = mysqli_fetch_assoc($result_ingresos_actual)['total_ingresos'] ?? 0;
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
        <div class="row mb-1">
            <div class="col-12 text-end">
                <h5 class="text-muted"><?php echo "$mes $anio"; ?></h5>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h4 class="text-center">Ingresos vs Egresos de los Últimos 6 Meses</h4>
                        <div id="grafico-ingresos-egresos"></div>

                        <?php

                        $anio_actual = date('Y');

                        // Función para obtener el nombre del mes en español
                        function obtener_nombre_mes_espanol($numero_mes)
                        {
                            $meses = array(
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
                            return $meses[$numero_mes];
                        }
                        // Función para obtener los datos de ingresos y egresos de los últimos 6 meses
                        function obtener_datos_ultimos_meses($conexion, $meses = 6)
                        {
                            $datos = [];
                            for ($i = $meses - 1; $i >= 0; $i--) {
                                $fecha = date('Y-m-01', strtotime("-$i month"));
                                $mes = date('n', strtotime($fecha));
                                $anio = date('Y', strtotime($fecha));

                                // Consulta para ingresos
                                $sql_ingresos = "SELECT SUM(gastos.Valor) AS total_ingresos FROM gastos 
                                                 INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos 
                                                 WHERE categorias_gastos.Nombre = 'Ingresos' 
                                                 AND MONTH(gastos.Fecha) = $mes AND YEAR(gastos.Fecha) = $anio";
                                $result_ingresos = mysqli_query($conexion, $sql_ingresos);
                                $total_ingresos = mysqli_fetch_assoc($result_ingresos)['total_ingresos'] ?? 0;

                                // Consulta para egresos
                                $sql_egresos = "SELECT SUM(gastos.Valor) AS total_egresos FROM gastos 
                                                INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos 
                                                WHERE categorias_gastos.Nombre != 'Ingresos' 
                                                AND MONTH(gastos.Fecha) = $mes AND YEAR(gastos.Fecha) = $anio";
                                $result_egresos = mysqli_query($conexion, $sql_egresos);
                                $total_egresos = mysqli_fetch_assoc($result_egresos)['total_egresos'] ?? 0;

                                $datos[] = [
                                    'mes' => obtener_nombre_mes_espanol($mes),
                                    'ingresos' => $total_ingresos,
                                    'egresos' => $total_egresos
                                ];
                            }
                            return $datos;
                        }

                        $datos_financieros = obtener_datos_ultimos_meses($conexion);

                        $ultimo_mes = end($datos_financieros);
                        $balance_mes_actual = $ultimo_mes['ingresos'] - $ultimo_mes['egresos'];

                        ?>

                        <h2 class="text-center mt-3">Balance del Mes Actual</h2>
                        <h3 class="text-center">
                            <?php if ($balance_mes_actual < 0) {
                                echo "<p class='red'> $" . number_format($balance_mes_actual, 0, '', '.') . "</p>";
                            } else {
                                echo "<p>$" . number_format($balance_mes_actual, 0, '', '.') . "</p>";
                            }
                            ?>
                        </h3>
                        <div class="text-center" style="color:white">
                            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalIngresos">
                                Añadir Ingreso
                            </button>
                        </div>



                    </div>
                </div>
            </div>
        </div>

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
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center category-icon text-warning">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h4 class="card-title text-center mb-4">Gastos y Cuentas</h4>

                        <?php

                        $where = "WHERE c.Nombre = 'Gastos' OR c.Categoria_Padre = '2'";

                        // SQL queries
                        $sql_total = "SELECT SUM(g.Valor) AS total_gastos
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
                        " . $where . "
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?";

                        $sql_detalles = "SELECT d.Detalle AS Descripcion, g.Valor, c.Nombre
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        INNER JOIN detalle d ON g.ID_Detalle = d.ID
                        " . $where . "
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?
                        ORDER BY g.Fecha DESC";

                        // Prepare and execute queries
                        $stmt_total = mysqli_prepare($conexion, $sql_total);
                        mysqli_stmt_bind_param($stmt_total, "ss", $current_month, $current_year);
                        mysqli_stmt_execute($stmt_total);
                        $result_total = mysqli_stmt_get_result($stmt_total);
                        $total_gastos = mysqli_fetch_assoc($result_total)['total_gastos'] ?? 0;

                        $stmt_detalles = mysqli_prepare($conexion, $sql_detalles);
                        mysqli_stmt_bind_param($stmt_detalles, "ss", $current_month, $current_year);
                        mysqli_stmt_execute($stmt_detalles);
                        $result_detalles = mysqli_stmt_get_result($stmt_detalles);
                        ?>
                        <div class="alert alert-warning">
                            Valor Actual:
                            <?php

                            if ($gastos < $total_gastos) {
                                $color = "red";
                            } else {
                                $color = "";
                            }

                            echo "<i class=" . $color . ">$ " . number_format($total_gastos, 0, '', '.') . "</i>";

                            ?>
                        </div>

                        <div class="detalles-container">
                            <ul class="list-group list-group-flush">
                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles)): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?= htmlspecialchars($detalle['Descripcion']) ?></span>
                                        <span class="badge bg-warning rounded-pill">$<?= number_format($detalle['Valor'], 0, '', '.') ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <!-- Botón para añadir gastos -->
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-warning" style="color:white" data-bs-toggle="modal" data-bs-target="#modalGastos">
                                Añadir Gasto
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
                        <?php

                        $where = "WHERE c.Nombre = 'Ocio' OR c.Categoria_Padre = '3'";

                        // SQL queries
                        $sql_total = "SELECT SUM(g.Valor) AS total_ocio
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
                        " . $where . "
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?";

                        $sql_detalles = "SELECT d.Detalle AS Descripcion, g.Valor, c.Nombre
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        INNER JOIN detalle d ON g.ID_Detalle = d.ID
                        " . $where . "
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?
                        ORDER BY g.Fecha DESC";

                        // Prepare and execute queries
                        $stmt_total = mysqli_prepare($conexion, $sql_total);
                        mysqli_stmt_bind_param($stmt_total, "ss", $current_month, $current_year);
                        mysqli_stmt_execute($stmt_total);
                        $result_total = mysqli_stmt_get_result($stmt_total);
                        $total_ocio = mysqli_fetch_assoc($result_total)['total_ocio'] ?? 0;

                        $stmt_detalles = mysqli_prepare($conexion, $sql_detalles);
                        mysqli_stmt_bind_param($stmt_detalles, "ss", $current_month, $current_year);
                        mysqli_stmt_execute($stmt_detalles);
                        $result_detalles = mysqli_stmt_get_result($stmt_detalles);
                        ?>

                        <div class="alert alert-success">
                            Valor Actual:
                            <?php

                            if ($ocio < $total_ocio) {
                                $color = "red";
                            } else {
                                $color = "";
                            }

                            echo "<i class=" . $color . ">$ " . number_format($total_ocio, 0, '', '.') . "</i>";

                            ?>
                        </div>
                        <div class="detalles-container ocio">
                            <ul class="list-group list-group-flush">
                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles)): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?= htmlspecialchars($detalle['Descripcion']) ?></span>
                                        <span class="badge bg-success rounded-pill">$<?= number_format($detalle['Valor'], 0, '', '.') ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalOcio">
                                Añadir Ocio
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
                        <?php

                        $where = "WHERE c.Nombre = 'Ahorro' OR c.Categoria_Padre = '4'";

                        // SQL queries
                        $sql_total = "SELECT SUM(g.Valor) AS total_ahorros
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
                        " . $where . "
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?";

                        $sql_detalles = "SELECT d.Detalle AS Descripcion, g.Valor, c.Nombre
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        INNER JOIN detalle d ON g.ID_Detalle = d.ID
                        " . $where . "
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?
                        ORDER BY g.Fecha DESC";

                        // Prepare and execute queries
                        $stmt_total = mysqli_prepare($conexion, $sql_total);
                        mysqli_stmt_bind_param($stmt_total, "ss", $current_month, $current_year);
                        mysqli_stmt_execute($stmt_total);
                        $result_total = mysqli_stmt_get_result($stmt_total);
                        $total_ahorros = mysqli_fetch_assoc($result_total)['total_ahorros'] ?? 0;

                        $stmt_detalles = mysqli_prepare($conexion, $sql_detalles);
                        mysqli_stmt_bind_param($stmt_detalles, "ss", $current_month, $current_year);
                        mysqli_stmt_execute($stmt_detalles);
                        $result_detalles = mysqli_stmt_get_result($stmt_detalles);
                        ?>

                        <div class="alert alert-info">
                            Valor Actual:
                            <?php

                            if ($ahorro < $total_ahorros) {
                                $color = "red";
                            } else {
                                $color = "";
                            }

                            echo "<i class=" . $color . ">$ " . number_format($total_ahorros, 0, '', '.') . "</i>";

                            ?>
                        </div>
                        <div class="detalles-container ahorro">
                            <ul class="list-group list-group-flush ahorro">
                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles)): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?= htmlspecialchars($detalle['Descripcion']) ?></span>
                                        <span class="badge bg-info rounded-pill">$<?= number_format($detalle['Valor'], 0, '', '.') ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <div class="text-center mt-4">
                            <div class="text-center mt-4">
                                <button type="button" class="btn btn-info" style="color:white" data-bs-toggle="modal" data-bs-target="#modalAhorro">
                                    Añadir Ahorros
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>


        </div>

        <?php
        $gastos_restante = $gastos - $total_gastos;
        $ocio_restante = $ocio - $total_ocio;
        $ahorros_restante = $ahorro - $total_ahorros;


        ?>
        <div class="row mb-4">
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Gastos Restantes</h3>

                        <h5>$
                            <?php
                            echo number_format($gastos_restante, 0, '', '.');
                            ?>
                        </h5>
                        <p class="text-muted">Presupuesto Restante</p>
                        <div id="gastos-restante" class="restante"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ocio Restante</h3>

                        <h5>$
                            <?php
                            echo number_format($ocio_restante, 0, '', '.');
                            ?>
                        </h5>
                        <p class="text-muted">Presupuesto Restante</p>
                        <div id="ocio-restante" class="restante"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ahorro Restante</h3>

                        <h5>$
                            <?php
                            echo number_format($ahorros_restante, 0, '', '.');
                            ?>
                        </h5>
                        <p class="text-muted">Presupuesto Restante</p>
                        <div id="ahorro-restante" class="restante"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    include('modal_ingresos.php');
    include('modal_gastos.php');
    include('modal_ocio.php');
    include('modal_ahorro.php');

    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /*Grafico de Barras*/
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
    <?php

    try {
        // Conexión a la base de datos
        $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Consulta para obtener el total por categoría
        $stmt = $pdo->prepare("
       SELECT categorias_gastos.Nombre AS categoria, SUM(gastos.Valor) AS total_categoria FROM gastos INNER JOIN categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID WHERE categorias_gastos.Nombre='Gastos' OR categorias_gastos.Categoria_Padre = 2 GROUP BY categorias_gastos.Nombre;
    ");
        $stmt->execute();
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error de conexión: " . $e->getMessage();
    }
    ?>

    <script>
        /*Grafico de Pie Gastos*/
        var dom = document.getElementById('gastos-restante');
        var myChart = echarts.init(dom, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });

        // Datos obtenidos desde PHP
        var data = [
            <?php foreach ($categorias as $categoria): ?> {
                    value: <?php echo $categoria['total_categoria']; ?>,
                    name: '<?php echo $categoria['categoria']; ?>'
                },
            <?php endforeach; ?>
        ];

        // Colores variantes de naranja
        var colors = ['#FF9800', '#FB8C00', '#F57C00', '#EF6C00', '#E65100', '#FFB74D', '#FFCC80'];



        var option = {
            tooltip: {
                trigger: 'item'
            },
            legend: {
                top: '5%',
                left: 'center'
            },
            color: colors, // Asignar los colores personalizados
            series: [{
                name: 'Gastos por Categoría',
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
                data: data
            }]
        };

        myChart.setOption(option);
        window.addEventListener('resize', myChart.resize);
    </script>

    <?php

    try {
        // Conexión a la base de datos
        $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Consulta para obtener el total por categoría
        $stmt = $pdo->prepare("
   SELECT categorias_gastos.Nombre AS categoria, SUM(gastos.Valor) AS total_categoria FROM gastos INNER JOIN categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID WHERE categorias_gastos.Nombre='Ocio' OR categorias_gastos.Categoria_Padre = 3 GROUP BY categorias_gastos.Nombre;
");
        $stmt->execute();
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error de conexión: " . $e->getMessage();
    }
    ?>

    <script>
        /*Grafico de Pie Gastos*/
        var dom = document.getElementById('ocio-restante');
        var myChart = echarts.init(dom, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });

        // Datos obtenidos desde PHP
        var data = [
            <?php foreach ($categorias as $categoria): ?> {
                    value: <?php echo $categoria['total_categoria']; ?>,
                    name: '<?php echo $categoria['categoria']; ?>'
                },
            <?php endforeach; ?>
        ];

        // Colores variantes de verde
        var colors = ['#66BB6A', '#4CAF50', '#43A047', '#388E3C', '#2E7D32', '#1B5E20', '#A5D6A7'];

        var option = {
            tooltip: {
                trigger: 'item'
            },
            legend: {
                top: '5%',
                left: 'center'
            },
            color: colors, // Asignar los colores personalizados
            series: [{
                name: 'Gastos por Categoría',
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
                data: data
            }]
        };

        myChart.setOption(option);
        window.addEventListener('resize', myChart.resize);
    </script>

    <?php

    try {
        // Conexión a la base de datos
        $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Consulta para obtener el total por categoría
        $stmt = $pdo->prepare("
        SELECT categorias_gastos.Nombre AS categoria, SUM(gastos.Valor) AS total_categoria FROM gastos INNER JOIN categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID WHERE categorias_gastos.Nombre='Ahorro' OR categorias_gastos.Categoria_Padre = 4 GROUP BY categorias_gastos.Nombre;
        ");
        $stmt->execute();
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error de conexión: " . $e->getMessage();
    }
    ?>

    <script>
        /*Grafico de Pie Gastos*/
        var dom = document.getElementById('ahorro-restante');
        var myChart = echarts.init(dom, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });

        // Datos obtenidos desde PHP
        var data = [
            <?php foreach ($categorias as $categoria): ?> {
                    value: <?php echo $categoria['total_categoria']; ?>,
                    name: '<?php echo $categoria['categoria']; ?>'
                },
            <?php endforeach; ?>
        ];

        // Colores variantes de azul
        var colors = ['#2196F3', '#1E88E5', '#1976D2', '#1565C0', '#0D47A1', '#64B5F6', '#BBDEFB'];


        var option = {
            tooltip: {
                trigger: 'item'
            },
            legend: {
                left: 'center'
            },
            color: colors, // Asignar los colores personalizados
            series: [{
                name: 'Gastos por Categoría',
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
                data: data
            }]
        };

        myChart.setOption(option);
        window.addEventListener('resize', myChart.resize);
    </script>
</body>

</html>