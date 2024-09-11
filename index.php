<?php

include('bd.php');

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
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12 text-end">
                <h5 class="text-muted"><?php echo "$mes $anio"; ?></h5>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <div id="chart-container"></div>

                        <?php
                        // Consulta para obtener el total de ingresos
                        $sql = "SELECT SUM(gastos.Valor) AS total_ingresos FROM gastos INNER JOIN categorias_gastos ON categorias_gastos.ID = gastos.ID_Categoria_Gastos WHERE categorias_gastos.Nombre = 'Ingresos' AND MONTH(gastos.Fecha) = MONTH(CURRENT_DATE) AND YEAR(gastos.Fecha) = YEAR(CURRENT_DATE);";
                        $result = mysqli_query($conexion, $sql);
                        $row = mysqli_fetch_assoc($result);
                        $total_ingresos = $row['total_ingresos'];
                        ?>


                        <h2 class="text-center mt-3"><?php echo number_format($total_ingresos, 0, '', '.'); ?></h2>


                        <p class="text-center text-muted">Total Presupuesto</p>
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
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center budget-item">
                        <h3 class="text-warning">50%</h3>
                        <h5>$
                            <?php $gastos = $total_ingresos * 0.5;
                            echo number_format($gastos, 0, '', '.'); ?>
                        </h5>
                        <p class="text-muted">Gastos y Cuentas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
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

                        // SQL queries
                        $sql_total = "SELECT SUM(g.Valor) AS total_gastos
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        WHERE c.Nombre = 'Gastos'
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?";

                        $sql_detalles = "SELECT d.Detalle AS Descripcion, g.Valor
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        INNER JOIN detalle d ON g.ID_Detalle = d.ID
                        WHERE c.Nombre = 'Gastos'
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

                        // SQL queries
                        $sql_total = "SELECT SUM(g.Valor) AS total_ocio
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        WHERE c.Nombre = 'Ocio'
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?";

                        $sql_detalles = "SELECT d.Detalle AS Descripcion, g.Valor
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        INNER JOIN detalle d ON g.ID_Detalle = d.ID
                        WHERE c.Nombre = 'Ocio'
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

                        // SQL queries
                        $sql_total = "SELECT SUM(g.Valor) AS total_ahorro
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        WHERE c.Nombre = 'Ahorro'
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?";

                        $sql_detalles = "SELECT d.Detalle AS Descripcion, g.Valor
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        INNER JOIN detalle d ON g.ID_Detalle = d.ID
                        WHERE c.Nombre = 'Ahorro'
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?
                        ORDER BY g.Fecha DESC";

                        // Prepare and execute queries
                        $stmt_total = mysqli_prepare($conexion, $sql_total);
                        mysqli_stmt_bind_param($stmt_total, "ss", $current_month, $current_year);
                        mysqli_stmt_execute($stmt_total);
                        $result_total = mysqli_stmt_get_result($stmt_total);
                        $total_ahorros = mysqli_fetch_assoc($result_total)['total_ahorro'] ?? 0;

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
            <div class="col-md-4 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Gastos Restantes</h3>

                        <h5>$
                            <?php
                            echo number_format($gastos_restante, 0, '', '.');
                            ?>
                        </h5>
                        <p class="text-muted">Prespuesto Restante</p>
                        <div id="chart-restante"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ocio Restante</h3>

                        <h5>$
                            <?php
                            echo number_format($ocio_restante, 0, '', '.');
                            ?>
                        </h5>
                        <p class="text-muted">Prespuesto Restante</p>
                        <div id="chart-restante"></div>
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
                        <p class="text-muted">Prespuesto Restante</p>
                        <div id="chart-restante"></div>
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
        var dom = document.getElementById('chart-container');
        var myChart = echarts.init(dom, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });

        var option = {
            tooltip: {
                trigger: 'item'
            },
            legend: {
                top: '5%',
                left: 'center'
            },
            series: [{
                name: 'Presupuesto',
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
                data: [{
                        value: <?php echo $total_gastos ?>,
                        name: 'Gastos y Cuentas',
                        itemStyle: {
                            color: '#FFC107' // Amarillo
                        }
                    },
                    {
                        value: <?php echo $total_ocio; ?>,
                        name: 'Ocio',
                        itemStyle: {
                            color: '#198754' // Verde
                        }
                    },
                    {
                        value: <?php echo $total_ahorros; ?>,
                        name: 'Ahorro e Inversión',
                        itemStyle: {
                            color: '#0DCAF0' // Azul
                        }
                    }
                ]
            }]
        };

        myChart.setOption(option);
        window.addEventListener('resize', myChart.resize);

        var dom2 = document.getElementById('chart-restante');
        var myChart2 = echarts.init(dom2, null, {
            renderer: 'canvas',
            useDirtyRect: false
        });

        var option2 = {
            tooltip: {
                trigger: 'item'
            },
            series: [{
                name: 'Presupuesto',
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
                data: [{
                        value: <?php echo intval($gastos_restante); ?>,
                        name: 'Gastos y Cuentas',
                    },
                    {
                        value: <?php echo intval($ocio_restante); ?>,
                        name: 'Ocio',
                    },
                    {
                        value: <?php echo intval($ahorros_restante); ?>,
                        name: 'Ahorro e Inversión',
                    }
                ]
            }]
        };

        myChart2.setOption(option2);
        window.addEventListener('resize', myChart2.resize);
    </script>

</body>

</html>