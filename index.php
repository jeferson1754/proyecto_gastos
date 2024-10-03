<?php

include('bd.php');

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

        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h4 class="text-center">Ingresos vs Egresos de los Últimos 6 Meses</h4>
                        <div id="grafico-ingresos-egresos"></div>

                        <?php

                        // Función para obtener los datos de ingresos y egresos de los últimos 6 meses
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

                        // Consulta del total de gastos del mes anterior
                        $sql_anterior = "SELECT SUM(g.Valor) AS total_gastos
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
                        " . $where . "
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?";

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

                        $stmt_anterior = mysqli_prepare($conexion, $sql_anterior);
                        mysqli_stmt_bind_param($stmt_anterior, "ss", $previous_month, $previous_year);
                        mysqli_stmt_execute($stmt_anterior);
                        $result_anterior = mysqli_stmt_get_result($stmt_anterior);
                        $anterior_total_gastos = mysqli_fetch_assoc($result_anterior)['total_gastos'] ?? 0;
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

                        // Consulta del total de gastos del mes anterior
                        $sql_anterior = "SELECT SUM(g.Valor) AS total_ocio
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
                        " . $where . "
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?";

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

                        $stmt_anterior = mysqli_prepare($conexion, $sql_anterior);
                        mysqli_stmt_bind_param($stmt_anterior, "ss", $previous_month, $previous_year);
                        mysqli_stmt_execute($stmt_anterior);
                        $result_anterior = mysqli_stmt_get_result($stmt_anterior);
                        $anterior_total_ocio = mysqli_fetch_assoc($result_anterior)['total_ocio'] ?? 0;
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

                        // Consulta del total de gastos del mes anterior
                        $sql_anterior = "SELECT SUM(g.Valor) AS total_ahorros
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
                        " . $where . "
                        AND MONTH(g.Fecha) = ? AND YEAR(g.Fecha) = ?";

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

                        $stmt_anterior = mysqli_prepare($conexion, $sql_anterior);
                        mysqli_stmt_bind_param($stmt_anterior, "ss", $previous_month, $previous_year);
                        mysqli_stmt_execute($stmt_anterior);
                        $result_anterior = mysqli_stmt_get_result($stmt_anterior);
                        $anterior_total_ahorros = mysqli_fetch_assoc($result_anterior)['total_ahorros'] ?? 0;
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

        // Función para determinar el color basado en la comparación de valores
        function obtenerColor($anterior_valor, $valor_actual, $tipo)
        {
            if ($anterior_valor < $valor_actual) {
                #echo "El valor actual de $tipo es mayor al anterior, el color es rojo.<br>";
                return "red"; // El valor actual es mayor, por lo tanto, el color es rojo
            } else {
                #echo "El valor actual de $tipo es menor o igual al anterior, el color es verde.<br>";
                return "green"; // El valor actual es menor o igual, por lo tanto, el color es verde
            }
        }



        // Calcular diferencias
        $anterior_gastos = $anterior_total_gastos - $total_gastos;
        $anterior_ocio = $anterior_total_ocio - $total_ocio;
        $anterior_ahorros = $anterior_total_ahorros - $total_ahorros;

        // Obtener colores
        $color_gastos = obtenerColor($anterior_total_gastos, $total_gastos, 'gastos');
        $color_ocio = obtenerColor($anterior_total_ocio, $total_ocio, 'ocio');
        $color_ahorro = obtenerColor($anterior_total_ahorros, $total_ahorros, 'ahorros');





        $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);

        function ejecutar_consulta2($pdo, $sql)
        {
            try {
                // Configuración de la conexión
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Preparar y ejecutar la consulta
                $stmt = $pdo->prepare($sql);
                $stmt->execute();

                $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $suma_total = 0;

                // Sumar los totales por categoría
                foreach ($categorias as $categoria) {
                    $suma_total += $categoria['total_categoria'];
                }

                return ['categorias' => $categorias, 'suma_total' => $suma_total];
            } catch (PDOException $e) {
                echo "Error de conexión: " . $e->getMessage();
            }
        }

        $resultado1 = ejecutar_consulta2($pdo, "SELECT categorias_gastos.Nombre AS categoria, SUM(gastos.Valor) AS total_categoria FROM gastos INNER JOIN categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID WHERE categorias_gastos.Nombre='Gastos' OR categorias_gastos.Categoria_Padre = 2 GROUP BY categorias_gastos.Nombre  ORDER BY `total_categoria` DESC;");
        $total_categorias_gastos = $resultado1['categorias'];
        $suma_total_gastos = $resultado1['suma_total'];

        $resultado2 = ejecutar_consulta2($pdo, "SELECT categorias_gastos.Nombre AS categoria, SUM(gastos.Valor) AS total_categoria FROM gastos INNER JOIN categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID WHERE categorias_gastos.Nombre='Ocio' OR categorias_gastos.Categoria_Padre = 3 GROUP BY categorias_gastos.Nombre  ORDER BY `total_categoria` DESC;");
        $total_categorias_ocio = $resultado2['categorias'];
        $suma_total_ocio = $resultado2['suma_total'];

        $resultado3 = ejecutar_consulta2($pdo, "SELECT categorias_gastos.Nombre AS categoria, SUM(gastos.Valor) AS total_categoria FROM gastos INNER JOIN categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID WHERE categorias_gastos.Nombre='Ahorro' OR categorias_gastos.Categoria_Padre = 4 GROUP BY categorias_gastos.Nombre  ORDER BY `total_categoria` DESC;");
        $total_categorias_ahorro = $resultado3['categorias'];
        $suma_total_ahorro = $resultado3['suma_total'];

        $total_gastos;


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

    <?php
    include('modal_ingresos.php');
    include('modal_gastos.php');
    include('modal_ocio.php');
    include('modal_ahorro.php');

    $fecha = "AND MONTH(gastos.Fecha) = $current_month AND YEAR(gastos.Fecha) = $current_year";

    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!--Grafico de Ingresos y Egresos    -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnToggle = document.querySelector('.btn-toggle');
            const contenido = document.getElementById('contenido');

            btnToggle.addEventListener('click', function() {
                const isExpanded = btnToggle.getAttribute('aria-expanded') === 'true';
                btnToggle.textContent = isExpanded ? 'Ver menos' : 'Ver más';
            });
        });
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

    $colores_gastos = ['#FF9800', '#FB8C00', '#F57C00', '#EF6C00', '#E65100', '#FFB74D', '#FFCC80'];
    $colores_ocios = ['#66BB6A', '#4CAF50', '#43A047', '#388E3C', '#2E7D32', '#1B5E20', '#A5D6A7'];
    $colores_ahorros = ['#2196F3', '#1E88E5', '#1976D2', '#1565C0', '#0D47A1', '#64B5F6', '#BBDEFB'];


    $resultado4 = ejecutar_consulta2($pdo, "SELECT categorias_gastos.Nombre AS categoria, SUM(gastos.Valor) AS total_categoria FROM gastos INNER JOIN categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID WHERE categorias_gastos.Nombre='Gastos' OR categorias_gastos.Categoria_Padre = 2 $fecha  GROUP BY categorias_gastos.Nombre");
    $categorias_gastos = $resultado4['categorias'];

    $resultado5 = ejecutar_consulta2($pdo, "SELECT categorias_gastos.Nombre AS categoria, SUM(gastos.Valor) AS total_categoria FROM gastos INNER JOIN categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID WHERE categorias_gastos.Nombre='Ocio' OR categorias_gastos.Categoria_Padre = 3 $fecha GROUP BY categorias_gastos.Nombre;");
    $categorias_ocio = $resultado5['categorias'];

    $resultado6 = ejecutar_consulta2($pdo, "SELECT categorias_gastos.Nombre AS categoria, SUM(gastos.Valor) AS total_categoria FROM gastos INNER JOIN categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID WHERE categorias_gastos.Nombre='Ahorro' OR categorias_gastos.Categoria_Padre = 4 $fecha GROUP BY categorias_gastos.Nombre;");
    $categorias_ahorro = $resultado6['categorias'];


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

    // Example usage
    piechart('gastos-restante', $categorias_gastos, $colores_gastos);

    piechart('ocio-restante', $categorias_ocio, $colores_ocios);

    piechart('ahorro-restante', $categorias_ahorro, $colores_ahorros);

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

    bigchart('total-gastos', $total_categorias_gastos, $colores_gastos);

    bigchart('total-ocio', $total_categorias_ocio, $colores_ocios);

    bigchart('total-ahorro', $total_categorias_ahorro, $colores_ahorros);



    function DatosHistoricos($where, $conexion, $nombre_grafico, $colores)
    {
        // Construir la consulta SQL
        $sql = "
        SELECT 
            categorias_gastos.Nombre AS categoria, 
            DATE_FORMAT(gastos.Fecha, '%Y-%m') AS mes, 
            SUM(gastos.Valor) AS total_categoria 
        FROM 
            gastos 
        INNER JOIN 
            categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID 
        $where 
        GROUP BY 
            categorias_gastos.Nombre, mes;
    ";

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

    // Llamadas a la función con diferentes categorías
    DatosHistoricos("WHERE categorias_gastos.Nombre='Gastos' OR categorias_gastos.Categoria_Padre = 2", $conexion, "gastos-historico", $colores_gastos);
    DatosHistoricos("WHERE categorias_gastos.Nombre='Ocio' OR categorias_gastos.Categoria_Padre = 3", $conexion, "ocio-historico", $colores_ocios);
    DatosHistoricos("WHERE categorias_gastos.Nombre='Ahorro' OR categorias_gastos.Categoria_Padre = 4", $conexion, "ahorro-historico", $colores_ahorros);

    ?>
</body>

</html>