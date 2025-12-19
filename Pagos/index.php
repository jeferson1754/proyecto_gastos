<?php
include('../bd.php');

if (isset($_GET['pendientes'])) {
    $where = "WHERE p.Estado ='Pendiente'";
} else if (isset($_GET['mesactual'])) {
    $where = "WHERE p.Estado ='Pendiente' AND MONTH(p.Fecha_Vencimiento) = MONTH(CURRENT_DATE) AND YEAR(p.Fecha_Vencimiento) = YEAR(CURRENT_DATE)";
} else {
    $where = "";
}

// Obtener los pagos del mes actual con mejor formato de fecha
$stmt = $pdo->query("SELECT 
    p.*,
    DATE_FORMAT(p.Fecha_Pago, '%d/%m/%Y %H:%i') as Fecha_Pagado, 
    DATE_FORMAT(p.Fecha_Vencimiento, '%d/%m/%Y') as Fecha_Formateada 
    FROM pagos p 
    LEFT JOIN gastos g ON p.gasto_id = g.ID 
    LEFT JOIN detalle d ON g.ID_Detalle = d.ID 
    $where
    ORDER BY p.Estado DESC, 
    p.Fecha_Vencimiento DESC 
    LIMIT 30");

$fecha_actual_formateada = date('d/m/Y');

$cuentas = $conexion->query("SELECT COUNT(DISTINCT Cuenta) AS total FROM pagos");
$limite = $cuentas->fetch_assoc()['total'] * 30;


// Consulta para obtener los datos históricos agrupados por categoría y mes para el gráfico
$sql = "
SELECT 
    p.Cuenta AS categoria,
    DATE_FORMAT(p.Fecha_Pago, '%Y-%m') AS mes,
    SUM(p.Valor) AS total_categoria
FROM pagos p
LEFT JOIN gastos g ON p.gasto_id = g.ID
LEFT JOIN detalle d ON g.ID_Detalle = d.ID
WHERE 
    p.Fecha_Pago IS NOT NULL
    AND p.Fecha_Pago <> '0000-00-00'
GROUP BY 
    p.Cuenta,
    DATE_FORMAT(p.Fecha_Pago, '%Y-%m')
ORDER BY 
    mes DESC,
    categoria ASC
LIMIT $limite;
";

// Ejecutar la consulta
$result = $conexion->query($sql);

// Inicializar arrays para los datos
$total_historico = [];
$mes_historico = [];

// Generar dinámicamente el array de mapeo de meses
$meses_nombres = [];
$meses = [
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre"
];

// Rango de años a considerar
$año_inicio = 2024;
$año_fin = date("Y");

// Crear el array de mapeo dinámicamente
for ($año = $año_inicio; $año <= $año_fin; $año++) {
    foreach ($meses as $index => $nombre_mes) {
        $mes_numero = str_pad($index + 1, 2, "0", STR_PAD_LEFT); // Formato MM
        $meses_nombres["$año-$mes_numero"] = $nombre_mes;
    }
}

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
    $meses_con_nombres[] = $meses_nombres[$mes] ?? $mes; // Usar el mes original si no está en el mapeo
}

// Convertir arrays a formato JSON para usarlos en JavaScript
$meses_json = json_encode($mes_historico);
$meses_nombre = json_encode($meses_con_nombres);
$valores_json = json_encode($total_historico);

$mes_anterior = date('Y-m', strtotime('-1 month'));


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronología de Pagos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ECharts JS -->
    <script src="https://fastly.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
    <style>
        :root {
            --primary-color: #4a90e2;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
        }

        body {
            background-color: #f8f9fa;
        }

        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .page-header {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .page-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            width: 100%;
            text-align: center;
        }

        .header-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            width: 100%;
        }

        .btn-action {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        /* Estilos para las tarjetas móviles */
        .mobile-card {
            display: none;
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .mobile-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .mobile-card-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .mobile-card-body {
            display: grid;
            gap: 0.5rem;
        }

        .mobile-card-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .mobile-card-label {
            color: #666;
            font-size: 0.9rem;
        }

        .mobile-card-value {
            font-weight: 500;
        }

        .estado-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .estado-pagado {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }

        .estado-pendiente {
            background-color: rgba(241, 196, 15, 0.2);
            color: #f39c12;
        }

        .comprobante-link {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background-color: rgba(74, 144, 226, 0.1);
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .desktop-table {
            display: table;
            width: 100%;
        }

        /* Media queries para responsividad */
        @media (max-width: 768px) {
            .page-container {
                padding: 0.5rem;
            }

            .dashboard-card {
                padding: 0.75rem;
                margin: 0.5rem;
            }

            .desktop-table {
                display: none;
            }

            .mobile-card {
                display: block;
            }

            .btn-action {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }

            .page-title {
                font-size: 1.3rem;
            }

            .comprobante-link {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }

            .estado-badge {
                padding: 0.3rem 0.8rem;
                font-size: 0.75rem;
            }


        }

        @media (max-width: 576px) {
            .header-buttons {
                grid-template-columns: 1fr;
            }

            .btn-action {
                width: 100%;
            }
        }

        .btn-action i {
            margin-right: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="page-container">
        <div class="dashboard-card">
            <form method="GET">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-history me-2"></i>Cronología de Pagos
                    </h1>

                    <div class="header-buttons">

                        <a href="../" class="btn btn-secondary btn-action">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <a href="./cuenta_pagada.php" class="btn btn-success btn-action">
                            <i class="fas fa-plus me-2"></i>Agregar Pago
                        </a>
                        <button class="btn btn-warning btn-action" type="submit" name="pendientes">
                            <i class="fas fa-exclamation-circle me-2"></i>Cuentas Pendientes
                        </button>
                        <button class="btn btn-danger btn-action" type="submit" name="mesactual">
                            <i class="fas fa-calendar-times me-2"></i>Pendientes Este Mes
                        </button>

                    </div>

                </div>
            </form>
            <div id="chart-container" style="width: 100%; height: 400px; margin-bottom: 2rem;">

            </div>

            <!-- Tabla para desktop -->
            <div class="table-responsive desktop-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-file-invoice-dollar me-2"></i>Gasto</th>
                            <th><i class="fas fa-dollar-sign me-2"></i>Valor</th>
                            <th><i class="fas fa-user me-2"></i>Quién Paga</th>
                            <th><i class="fas fa-info-circle me-2"></i>Estado</th>
                            <th><i class="fas fa-file-alt me-2"></i>Comprobante</th>
                            <th><i class="fas fa-hourglass-half me-2"></i>Fecha Venc.</th>
                            <th><i class="fas fa-money-check-alt me-2"></i>Fecha Pago</th>
                            <th><i class="fas fa-cog me-2"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 1. Convertimos la consulta en un array para manejarlo fácilmente
                        $pagos_array = $stmt->fetchAll();

                        // 2. Buscamos el último pago marcado como 'Pagado' para cada cuenta (Referencia de comparación)
                        $valores_referencia = [];
                        $referencia_query = $pdo->query("SELECT Cuenta, Valor FROM pagos WHERE Estado = 'Pagado' ORDER BY Fecha_Vencimiento DESC");
                        while ($ref = $referencia_query->fetch()) {
                            if (!isset($valores_referencia[$ref['Cuenta']])) {
                                $valores_referencia[$ref['Cuenta']] = (float)$ref['Valor'];
                            }
                        }

                        // 3. Variable para controlar que la flecha solo salga en la primera fila de cada cuenta
                        $ya_mostrado = [];
                        $fecha_hoy = new DateTime();

                        // 4. Empezamos el recorrido de la tabla
                        foreach ($pagos_array as $pago):
                            $cuenta = $pago['Cuenta'];
                            $valor_actual = (float)$pago['Valor'];
                            $flechita = '';
                            $flechita_class = '';

                            // --- LÓGICA DE LA FLECHA ---
                            if (!isset($ya_mostrado[$cuenta])) {
                                if (isset($valores_referencia[$cuenta])) {
                                    $valor_anterior = $valores_referencia[$cuenta];

                                    if ($valor_actual > $valor_anterior) {
                                        // Subió: Flecha hacia arriba, color rojo (alerta de gasto)
                                        $flechita = '<i class="fas fa-arrow-up small"></i>';
                                        $flechita_class = 'text-danger';
                                        $texto_flechita = 'Aumentó respecto al mes anterior';
                                    } elseif ($valor_actual < $valor_anterior) {
                                        // Bajó: Flecha hacia abajo, color verde (ahorro)
                                        $flechita = '<i class="fas fa-arrow-down small"></i>';
                                        $flechita_class = 'text-success';
                                        $texto_flechita = 'Disminuyó respecto al mes anterior';
                                    } else {
                                        // Se mantuvo igual: Guion sutil o nada
                                        $flechita = '<i class="fas fa-minus small" style="font-size: 0.7rem;"></i>';
                                        $flechita_class = 'text-muted';
                                        $texto_flechita = 'Se mantuvo igual respecto al mes anterior';
                                    }
                                }
                                $ya_mostrado[$cuenta] = true;
                            }

                            // --- LÓGICA DE DÍAS RESTANTES ---
                            // Asumimos que Fecha_Formateada viene en formato d/m/Y (ej: 25/12/2023)
                            $fecha_venc = DateTime::createFromFormat('d/m/Y', $pago['Fecha_Formateada']);
                            $dias_restantes = (int)$fecha_hoy->diff($fecha_venc)->format('%r%a');

                            // --- CONFIGURACIÓN DE BADGE DE ESTADO ---
                            if ($pago['Estado'] === 'Pagado') {
                                $icon = '✓';
                                $class = 'bg-success';
                                $texto = 'Pagado';
                            } elseif ($pago['Estado'] === 'Pendiente') {
                                if ($dias_restantes < 0) {
                                    $icon = '🚨';
                                    $class = 'bg-danger';
                                    $texto = 'Vencido';
                                } elseif ($dias_restantes <= 5) {
                                    $icon = '⏳';
                                    $class = 'bg-warning text-dark';
                                    $texto = "Vence en $dias_restantes días";
                                } elseif ($dias_restantes < 7) {
                                    $icon = '📆';
                                    $class = 'bg-warning-subtle text-dark';
                                    $texto = 'Próximo a vencer';
                                } else {
                                    $icon = '📌';
                                    $class = 'bg-success-subtle text-success';
                                    $texto = 'Programado';
                                }
                            } else {
                                $icon = '✕';
                                $class = 'bg-secondary';
                                $texto = $pago['Estado'];
                            }

                            // --- COLOR DE LA FECHA ---
                            $color_fecha = 'black';
                            if ($pago['Estado'] === 'Pendiente') {
                                if ($dias_restantes < 0) $color_fecha = 'red';
                                elseif ($dias_restantes <= 5) $color_fecha = 'orange';
                                elseif ($dias_restantes < 7) $color_fecha = 'goldenrod';
                                else $color_fecha = 'green';
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($pago['Cuenta']) ?></td>
                                <td class="valor-cell" title="<?= $texto_flechita ?>">

                                    <?php if ($flechita): ?>
                                        <strong>$<?= number_format($pago['Valor'], 0, '', '.') ?></strong>
                                        <span class="<?= $flechita_class ?> ms-1">
                                            <?= $flechita ?>
                                        </span>
                                    <?php else: ?>
                                        $<?= number_format($pago['Valor'], 0, '', '.') ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($pago['quien_paga']) ?></td>
                                <td class="py-2">
                                    <span class="badge <?= $class ?> px-3 py-2 rounded-pill">
                                        <?= $icon ?> <?= $texto ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (empty($pago['comprobante'])): ?>
                                        <span class="text-muted small">Sin Comprobante</span>
                                    <?php else: ?>
                                        <a class="btn btn-link btn-sm" href="<?= $pago['comprobante'] ?>" target="_blank">
                                            <i class="fas fa-external-link-alt"></i> Ver
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: <?= $color_fecha ?>; font-weight: 500;" title="Quedan <?= $dias_restantes ?> días">
                                        <?= $pago['Fecha_Formateada'] ?>
                                    </span>
                                </td>
                                <td><?= $pago['Fecha_Pagado'] ?: '-' ?></td>
                                <td>
                                    <a href="./cuenta_editar.php?id=<?= $pago['ID'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tarjetas para móvil -->
            <?php
            // Reiniciar el cursor del resultado
            $stmt->execute();
            while ($pago = $stmt->fetch()):
                $estadoClass = strtolower($pago['Estado']) == 'pagado' ? 'estado-pagado' : 'estado-pendiente';
                $fecha_actual = DateTime::createFromFormat('d/m/Y', $fecha_actual_formateada);
                $fecha_pago   = DateTime::createFromFormat('d/m/Y', $pago['Fecha_Formateada']);
                $dias_restantes = (int)$fecha_actual->diff($fecha_pago)->format('%r%a');

                // Configuración de estados
                if ($pago['Estado'] === 'Pagado') {
                    $icon = '✓';
                    $class = 'bg-success';
                    $texto = 'Pagado';
                } elseif ($pago['Estado'] === 'Pendiente') {
                    if ($dias_restantes < 1) {
                        $icon = '🚨';
                        $class = 'bg-danger';
                        $texto = 'Vencido';
                    } elseif ($dias_restantes <= 5) {
                        $icon = '⏳';
                        $class = 'bg-warning text-dark';
                        $texto = 'Vence en ' . $dias_restantes . ' días';
                    } elseif ($dias_restantes < 7) {
                        $icon = '📆';
                        $class = 'bg-warning-subtle text-dark';
                        $texto = 'Próximo a vencer';
                    } else {
                        $icon = '📌';
                        $class = 'bg-success-subtle text-success';
                        $texto = 'Programado';
                    }
                } else {
                    $icon = '✕';
                    $class = 'bg-secondary';
                    $texto = $pago['Estado'];
                }



            ?>
                <div class="mobile-card">
                    <div class="mobile-card-header d-flex justify-content-between align-items-center">
                        <div class="mobile-card-title d-flex align-items-center gap-2">
                            <?php
                            switch ($pago['Cuenta']) {
                                case 'Luz':
                                    echo '<i class="fas fa-lightbulb text-warning"></i>';
                                    break;
                                case 'Agua':
                                    echo '<i class="fas fa-tint text-info"></i>';
                                    break;
                                case 'VTR':
                                    echo '<i class="fas fa-wifi text-primary"></i>';
                                    break;
                                case 'Plan Celular':
                                    echo '<i class="fas fa-mobile-alt text-secondary"></i>';
                                    break;
                                default:
                                    echo '<i class="fas fa-receipt text-muted"></i>';
                                    break;
                            }
                            ?>
                            <span><?php echo htmlspecialchars($pago['Cuenta']); ?></span>
                        </div>

                        <span class="badge estado-badge <?php echo $class; ?>">
                            <?php echo $icon . ' ' . $texto; ?>
                        </span>
                    </div>

                    <div class="mobile-card-body">
                        <div class="mobile-card-item">
                            <span class="mobile-card-label"><i class="fas fa-dollar-sign me-2"></i>Valor</span>
                            <span class="mobile-card-value">$<?php echo number_format($pago['Valor'], 0, '', '.'); ?></span>
                        </div>
                        <div class="mobile-card-item">
                            <span class="mobile-card-label"><i class="fas fa-user me-2"></i>Pagador</span>
                            <span class="mobile-card-value"><?php echo $pago['quien_paga']; ?></span>
                        </div>
                        <div class="mobile-card-item">
                            <span class="mobile-card-label">
                                <i class="fas fa-hourglass-half me-2"></i>Fecha venc.
                            </span>

                            <span class="mobile-card-value">
                                <?php

                                $color = 'black';

                                if ($pago['Estado'] === 'Pendiente') {

                                    if ($dias_restantes < 1) {
                                        $color = 'red';           // vencido o 1 día
                                    } elseif ($dias_restantes <= 5) {
                                        $color = 'orange';        // 2 a 5 días
                                    } elseif ($dias_restantes < 7) {
                                        $color = 'goldenrod';     // 6 a 7 días
                                    } else {
                                        $color = 'green';         // más de 7 días
                                    }
                                }

                                echo "<span style='color:$color; font-weight:500;' title='Quedan $dias_restantes días'>{$pago['Fecha_Formateada']}</span>";
                                ?>
                            </span>
                        </div>

                        <div class="mobile-card-item">
                            <span class="mobile-card-label"><i class="fas fa-money-check-alt me-2"></i>Fecha Pago</span>
                            <span class="mobile-card-value"><?php echo $pago['Fecha_Pagado']; ?></span>
                        </div>
                        <div class="mobile-card-item">
                            <span class="mobile-card-label"><i class="fas fa-file-alt me-2"></i>Comprobante</span>
                            <?php if ($pago['comprobante'] == NULL): ?>
                                <span class="sin-comprobante">Sin Comprobante</span>
                            <?php else: ?>
                                <a class="comprobante-link" href="<?php echo $pago['comprobante']; ?>" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>Ver
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="mobile-card-item" style="border-bottom: none; justify-content: center;">
                            <a href="./cuenta_editar.php?id=<?php echo $pago['ID']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit me-1"></i>Editar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

    <script>
        (function() {
            var dom = document.getElementById('chart-container');
            var myChart = echarts.init(dom, null, {
                renderer: 'canvas',
                useDirtyRect: false
            });

            // Pasar variables PHP a JavaScript
            const meses = <?php echo $meses_json; ?>;
            const name = <?php echo $meses_nombre; ?>;
            const valores = <?php echo $valores_json; ?>;

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
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    top: '-1%'
                },
                grid: {
                    left: '3%',
                    right: '8%',
                    bottom: '1%',
                    containLabel: true
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
                    boundaryGap: false,
                    data: name
                },
                yAxis: {
                    type: 'value'
                },
                series: series // Usar las series preparadas
            };

            myChart.setOption(option);
            window.addEventListener('resize', myChart.resize);
        })();
    </script>
</body>

</html>