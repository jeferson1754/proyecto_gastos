<?php

include('bd.php');
require_once('funciones.php');

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

foreach ($modulos as $nombre_categoria => $where_clause) {
    // Llamar a la función con cada where y almacenar los resultados
    $resultados[$nombre_categoria] = obtener_datos($conexion, $where_clause, $current_month, $current_year, $previous_month, $previous_year);
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
                        <?php
                        // Uso para Gastos
                        echo mostrarBarraProgreso(
                            $total_gastos,  // Variable de tu código original
                            $gastos        // Variable de tu código original que representa el 50%
                        );
                        ?>
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
                        <?php
                        // Uso para Gastos
                        echo mostrarBarraProgreso(
                            $total_ocio,  // Variable de tu código original
                            $ocio        // Variable de tu código original que representa el 50%
                        );
                        ?>
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
                        <?php
                        // Uso para Ahorro
                        echo mostrarBarraProgreso(
                            $total_ahorros, // Variable de tu código original
                            $ahorro        // Variable de tu código original que representa el 20%
                        );
                        ?>
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


                        <div class="alert alert-warning">
                            Valor Actual:
                            <?php

                            $color_gastos_detalles = obtenerColor($gastos, $total_gastos);

                            echo "<i class=" . $color_gastos_detalles . ">$ " . number_format($total_gastos, 0, '', '.') . "</i>";

                            ?>
                        </div>

                        <div class="detalles-container">
                            <ul class="list-group list-group-flush">
                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles_gastos)): ?>
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
                            <button type="button" class="btn btn-secondary" style="color:white" data-bs-toggle="modal" data-bs-target="#modalDetalleGastos">
                                <i class="fa-solid fa-circle-info"></i>
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
                        <div class="alert alert-success">
                            Valor Actual:
                            <?php

                            $color_ocio_detalle = obtenerColor($ocio, $total_ocio);

                            echo "<i class=" . $color_ocio_detalle . ">$ " . number_format($total_ocio, 0, '', '.') . "</i>";

                            ?>
                        </div>
                        <div class="detalles-container ocio">
                            <ul class="list-group list-group-flush">
                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles_ocio)): ?>
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
                            <button type="button" class="btn btn-secondary" style="color:white" data-bs-toggle="modal" data-bs-target="#modalDetalleOcio">
                                <i class="fa-solid fa-circle-info"></i>
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

                        <div class="alert alert-info">
                            Valor Actual:
                            <?php

                            $color_ahorro_detalle = obtenerColor($ahorro, $total_ahorros);


                            echo "<i class=" . $color_ahorro_detalle . ">$ " . number_format($total_ahorros, 0, '', '.') . "</i>";

                            ?>
                        </div>
                        <div class="detalles-container ahorro">
                            <ul class="list-group list-group-flush ahorro">
                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles_ahorros)): ?>
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
                                <button type="button" class="btn btn-secondary" style="color:white" data-bs-toggle="modal" data-bs-target="#modalDetalleAhorro">
                                    <i class="fa-solid fa-circle-info"></i>
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

        $color_gastos_restante = obtenerColor($gastos_restante, $total_gastos);
        $color_ocio_restante = obtenerColor($ocio_restante, $total_ocio);
        $color_ahorro_restante = obtenerColor($ahorros_restante, $total_ahorros);

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

        <!--GRAFICO RESTANTE 50%, 30% , 20% -->
        <div class="row mb-4">
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Gastos Restantes</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_gastos_restante . ">$" .

                            number_format($gastos_restante, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="gastos-restante" class="restante"></div>



                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto responsivo">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ocio Restante</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_ocio_restante . ">$" .

                            number_format($ocio_restante, 0, '', '.');

                        "</h5>";
                        ?>
                        <div id="ocio-restante" class="restante"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="text">Ahorro Restante</h3>

                        <?php
                        echo "
                        <h5 class=" . $color_ahorro_restante . ">$" .

                            number_format($ahorros_restante, 0, '', '.');

                        "</h5>";
                        ?>
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

        <div class="container text-center">

            <!-- Botón para mostrar/ocultar iframe -->
            <button id="toggle-btn" class="btn btn-primary">Mostrar Graficos</button>

            <!-- Contenedor para el iframe -->
            <div id="iframe-container">
                <iframe src="./grafico_por_modulo.php"></iframe>
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
            toolbox: {
                feature: {
                    magicType: {
                        show: true,
                        type: ['line', 'bar']
                    }
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

    <!-- Script para mostrar/ocultar iframe -->
    <script>
        document.getElementById('toggle-btn').addEventListener('click', function() {
            var iframeContainer = document.getElementById('iframe-container');
            if (iframeContainer.style.display === 'none') {
                iframeContainer.style.display = 'block';
                this.textContent = 'Ocultar Graficos'; // Cambiar texto del botón
            } else {
                iframeContainer.style.display = 'none';
                this.textContent = 'Mostrar Graficos'; // Cambiar texto del botón
            }
        });
    </script>

    <?php
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