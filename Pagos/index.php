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


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronología de Pagos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <?php while ($pago = $stmt->fetch()):
                            $estadoClass = strtolower($pago['Estado']) == 'pagado' ? 'estado-pagado' : 'estado-pendiente';
                        ?>
                            <tr>
                                <td><?php echo $pago['Cuenta']; ?></td>
                                <td class="valor-cell">$<?php echo number_format($pago['Valor'], 0, '', '.'); ?></td>
                                <td><?php echo $pago['quien_paga']; ?></td>
                                <td><span class="estado-badge <?php echo $estadoClass; ?>"><?php echo $pago['Estado']; ?></span></td>
                                <td>
                                    <?php if ($pago['comprobante'] == NULL): ?>
                                        <span class="sin-comprobante">Sin Comprobante</span>
                                    <?php else: ?>
                                        <a class="comprobante-link" href="<?php echo $pago['comprobante']; ?>" target="_blank">
                                            <i class="fas fa-external-link-alt me-2"></i>Ver
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?php

                                    $fecha_actual = DateTime::createFromFormat('d/m/Y', $fecha_actual_formateada);
                                    $fecha_pago = DateTime::createFromFormat('d/m/Y', $pago['Fecha_Formateada']);

                                    $color = ($pago['Estado'] == 'Pendiente' && $fecha_actual > $fecha_pago) ? 'red' : 'black';

                                    echo "<span style='color: $color;'>{$pago['Fecha_Formateada']}</span>";
                                    ?></td>
                                <td><?php echo $pago['Fecha_Pagado']; ?></td>
                                <td>
                                    <a href="./cuenta_editar.php?id=<?php echo $pago['ID']; ?>" id="<?php echo $pago['Cuenta']; ?>" class="<?php echo $pago['Estado']; ?> btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tarjetas para móvil -->
            <?php
            // Reiniciar el cursor del resultado
            $stmt->execute();
            while ($pago = $stmt->fetch()):
                $estadoClass = strtolower($pago['Estado']) == 'pagado' ? 'estado-pagado' : 'estado-pendiente';
            ?>
                <div class="mobile-card">
                    <div class="mobile-card-header">
                        <div class="mobile-card-title">
                            <?php if ($pago['Cuenta'] === 'Luz'): ?>
                                <i class="fas fa-lightbulb"></i> <?php echo $pago['Cuenta']; ?>
                            <?php elseif ($pago['Cuenta'] === 'Agua'): ?>
                                <i class="fas fa-tint"></i> <?php echo $pago['Cuenta']; ?>
                            <?php else: ?>
                                <?php echo $pago['Cuenta']; ?>
                            <?php endif; ?>
                        </div>
                        <span class="estado-badge <?php echo $estadoClass; ?>"><?php echo $pago['Estado']; ?></span>
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
                            <span class="mobile-card-label"><i class="fas fa-hourglass-half me-2"></i>Fecha Venc.</span>
                            <span class="mobile-card-value"><?php echo $pago['Fecha_Formateada']; ?></span>
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
</body>

</html>