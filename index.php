<?php

include('bd.php');
require_once('funciones.php');

$cantidad_meses_balance = 6;

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
                <h5 class="text-muted"><?php echo "$mes $current_year"; ?></h5>
            </div>
        </div>

        <!--INGRESOS VS EGRESOS -->
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h4 class="text-center">Ingresos vs Egresos de los Últimos <?php echo "$cantidad_meses_balance"; ?> Meses</h4>
                        <div id="grafico-ingresos-egresos"></div>

                        <?php

                        $datos_financieros = obtener_datos_ultimos_meses($conexion, $cantidad_meses_balance);

                        $ultimo_mes = end($datos_financieros);
                        $balance_mes_actual = $ultimo_mes['ingresos'] - $ultimo_mes['egresos'];

                        $total_ingresos = $ultimo_mes['ingresos'];
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

        <!--CARTAS 50%, 30% , 20% -->
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

        <!--DETALLES 50%, 30% , 20% -->
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

                        // Llamar a la función pasando los parámetros
                        $datos_gastos = obtener_datos($conexion, $where, $current_month, $current_year, $previous_month, $previous_year);

                        // Acceder a los resultados
                        $total_gastos = $datos_gastos['total'];
                        $result_detalles = $datos_gastos['detalles'];
                        $anterior_total_gastos = $datos_gastos['anterior_total'];


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

                        // Llamar a la función pasando los parámetros
                        $datos_ocio = obtener_datos($conexion, $where, $current_month, $current_year, $previous_month, $previous_year);

                        // Acceder a los resultados
                        $total_ocio = $datos_ocio['total'];
                        $result_detalles = $datos_ocio['detalles'];
                        $anterior_total_ocio = $datos_ocio['anterior_total'];
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

                        // Llamar a la función pasando los parámetros
                        $datos_ahorro = obtener_datos($conexion, $where, $current_month, $current_year, $previous_month, $previous_year);

                        // Acceder a los resultados
                        $total_ahorros = $datos_ahorro['total'];
                        $result_detalles = $datos_ahorro['detalles'];
                        $anterior_total_ahorros = $datos_ahorro['anterior_total'];
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

        // Calcular diferencias
        $anterior_gastos = $anterior_total_gastos - $total_gastos;
        $anterior_ocio = $anterior_total_ocio - $total_ocio;
        $anterior_ahorros = $anterior_total_ahorros - $total_ahorros;

        // Obtener colores
        $color_gastos = obtenerColor($anterior_total_gastos, $total_gastos);
        $color_ocio = obtenerColor($anterior_total_ocio, $total_ocio);
        $color_ahorro = obtenerColor($anterior_total_ahorros, $total_ahorros);

        $where_gastos = "WHERE categorias_gastos.Nombre='Gastos' OR categorias_gastos.Categoria_Padre = 2";
        $where_ocio = "WHERE categorias_gastos.Nombre='Ocio' OR categorias_gastos.Categoria_Padre = 3";
        $where_ahorros = "WHERE categorias_gastos.Nombre='Ahorro' OR categorias_gastos.Categoria_Padre = 4";

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

        $fecha = "AND MONTH(gastos.Fecha) = $current_month AND YEAR(gastos.Fecha) = $current_year";

        $resultado4 = ejecutar_consulta($pdo, "$where_gastos $fecha");
        $categorias_gastos = $resultado4['categorias'];

        $resultado5 = ejecutar_consulta($pdo, "$where_ocio $fecha");
        $categorias_ocio = $resultado5['categorias'];

        $resultado6 = ejecutar_consulta($pdo, "$where_ahorros $fecha");
        $categorias_ahorro = $resultado6['categorias'];

        include('modal_ingresos.php');
        include('modal_gastos.php');
        include('modal_ocio.php');
        include('modal_ahorro.php');

        ?>

        <!--GRAFICO RESTANTE 50%, 30% , 20% -->
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
                        <div id="ahorro-restante" class="restante"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--GRAFICO HISTORICO 50%, 30% , 20% -->
        <div class="row mb-4">
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Gastos Historicos</h3>
                        <?php
                        echo "
                        <h5 class=" . $color_gastos . ">$" .

                            number_format($anterior_gastos, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="gastos-historico" class="restante"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ocios Historico</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_ocio . ">$" .

                            number_format($anterior_ocio, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="ocio-historico" class="restante"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ahorros Historicos</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_ahorro . ">$" .

                            number_format($anterior_ahorros, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="ahorro-historico" class="restante"></div>
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

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!--Grafico de Ingresos y Egresos-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnToggle = document.querySelector('.btn-toggle');
            const contenido = document.getElementById('contenido');

            btnToggle.addEventListener('click', function() {
                const isExpanded = btnToggle.getAttribute('aria-expanded') === 'true';
                btnToggle.textContent = isExpanded ? 'Ver menos' : 'Ver más';
            });
        });
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
            grid: {
                left: '2%',
                right: '5%',
                bottom: '1%',
                containLabel: true
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

    //Colores para graficos
    $colores_gastos = ['#FF9800', '#FB8C00', '#F57C00', '#EF6C00', '#E65100', '#FFB74D', '#FFCC80'];
    $colores_ocios = ['#66BB6A', '#4CAF50', '#43A047', '#388E3C', '#2E7D32', '#1B5E20', '#A5D6A7'];
    $colores_ahorros = ['#2196F3', '#1E88E5', '#1976D2', '#1565C0', '#0D47A1', '#64B5F6', '#BBDEFB'];

    //Graficos Pie Restantes
    piechart('gastos-restante', $categorias_gastos, $colores_gastos);
    piechart('ocio-restante', $categorias_ocio, $colores_ocios);
    piechart('ahorro-restante', $categorias_ahorro, $colores_ahorros);

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