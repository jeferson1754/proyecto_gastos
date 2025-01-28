<?php
// Asumimos que tienes la conexión a la base de datos en 'bd.php'.
include('../bd.php');

// Obtener los pagos del mes actual
$stmt = $pdo->query("SELECT p.* FROM pagos p LEFT JOIN gastos g ON p.gasto_id = g.ID LEFT JOIN detalle d ON g.ID_Detalle = d.ID ORDER BY `p`.`Fecha_Pago` DESC limit 30");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronologia de Pagos</title>
    <!-- FontAwesome para los íconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Agregar estilo para mejorar el diseño de la tabla */
        table th,
        table td {
            text-align: center;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .btn {
            margin-bottom: 15px;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .comprobante-link {
            text-decoration: none;
            color: #007bff;
        }

        .comprobante-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <a href="../" class="btn btn-secondary btn-action">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
        <h2 class="text-center text-primary mb-4">Cronologia de Pagos</h2>

        <!-- Botón de acción, puede ser para algún tipo de filtro o funcionalidad -->
        <div class="text-end">
            <a href="./cuenta_pagada.php">
                <button class="btn btn-success"> Agregar Cuenta Pagada</button>
            </a>
        </div>

        <!-- Tabla de pagos -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Gasto</th>
                        <th>Valor</th>
                        <th>Quién Paga</th>
                        <th>Estado</th>
                        <th>Comprobante (Enlace)</th>
                        <th>Fecha de Pago</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($pago = $stmt->fetch()) {

                        echo "<tr>";
                        echo "<td>{$pago['Cuenta']}</td>";
                        echo "<td>$" . number_format($pago['Valor'], 0, '', '.') . "</td>";
                        echo "<td>{$pago['quien_paga']}</td>";
                        echo "<td>{$pago['Estado']}</td>";
                        // Mostrar el enlace del comprobante
                        if ($pago['comprobante'] == NULL) {
                            echo "<td>Sin Comprobante</td>";
                        } else {
                            echo "<td><a class='comprobante-link' href='{$pago['comprobante']}' target='_blank'>Ver Comprobante</a></td>";
                        }
                        echo "<td>{$pago['Fecha_Pago']}</td>";
                        echo "<td>
                        <a href='./cuenta_editar.php?id={$pago['ID']}' class='btn btn-warning' title='Editar pago'>
                            <i class='fas fa-edit me-1'></i>Editar
                        </a>
                    </td>";

                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Agregar scripts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>