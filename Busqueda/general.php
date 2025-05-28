<?php
include('../bd.php'); // Conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar términos de búsqueda
    $busqueda = $_POST['busqueda'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $sub_categoria = $_POST['sub_categoria'] ?? '';
    $valorMin = $_POST['valorMin'] ?? '';
    $valorMax = $_POST['valorMax'] ?? '';
    $fechaInicio = $_POST['fechaInicio'] ?? '';
    $fechaFin = $_POST['fechaFin'] ?? '';

    $valorMin = str_replace(['$', '.'], '', $valorMin);
    $valorMax = str_replace(['$', '.'], '', $valorMax);


    // Base de la consulta
    $sql = "SELECT g.ID, d.Detalle AS Descripcion, g.Valor, c.Nombre as categoria, g.Fecha, c.Categoria_Padre as tipo
            FROM gastos g
            INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
            INNER JOIN detalle d ON g.ID_Detalle = d.ID
            WHERE 1=1";

    // Arreglos para parámetros y sus tipos
    $params = [];
    $types = '';

    if (!empty($busqueda)) {
        $sql .= " AND (d.Detalle LIKE ? OR g.Valor LIKE ? OR c.Nombre LIKE ? OR g.Fecha LIKE ?)";
        $like = "%$busqueda%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'ssss';
    }

    if (!empty($sub_categoria)) {
        $sql .= " AND c.Nombre = ?";
        $params[] = $sub_categoria;
        $types .= 's';
    }

    if (!empty($nombre)) {
        $sql .= " AND d.Detalle LIKE ?";
        $params[] = "%$nombre%";
        $types .= 's';
    }

    if ($valorMin !== '' && is_numeric($valorMin) && $valorMin > 0) {
        $sql .= " AND g.Valor >= ?";
        $params[] = (float)$valorMin;
        $types .= 'd';
    }

    if ($valorMax !== '' && is_numeric($valorMax) && $valorMax > 0) {
        $sql .= " AND g.Valor <= ?";
        $params[] = (float)$valorMax;
        $types .= 'd';
    }

    if (!empty($fechaInicio)) {
        $sql .= " AND g.Fecha >= ?";
        $params[] = $fechaInicio;
        $types .= 's';
    }

    if (!empty($fechaFin)) {
        $sql .= " AND g.Fecha <= ?";
        $params[] = $fechaFin;
        $types .= 's';
    }

    $sql .= " ORDER BY g.Fecha DESC LIMIT 50";

    // Preparar y ejecutar consulta
    $stmt = $conexion->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $resultados = $stmt->get_result();
} else {
    // Si no se realiza ninguna búsqueda, mostrar todo
    $sql = "SELECT g.ID,d.Detalle AS Descripcion, g.Valor, c.Nombre as categoria, g.Fecha,c.Categoria_Padre as tipo
    FROM gastos g
    INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
    INNER JOIN detalle d ON g.ID_Detalle = d.ID
    ORDER BY g.Fecha DESC
    LIMIT 50";
    $resultados = $conexion->query($sql);
}

$sql_categoria = "SELECT g.ID, d.Detalle AS Descripcion, g.Valor, g.ID_Categoria_Gastos, 
c.Nombre as categoria, g.Fecha, c.Categoria_Padre as tipo
FROM gastos g
INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
INNER JOIN detalle d ON g.ID_Detalle = d.ID
WHERE g.ID = ?";

$stmt = $conexion->prepare($sql_categoria);
$stmt->bind_param('i', $id);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();

// Obtener categorías para el select
$sqlCategorias = "SELECT ID, Nombre,Categoria_Padre FROM categorias_gastos ORDER BY Nombre";
$categorias = $conexion->query($sqlCategorias);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Buscador General</title>

    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #6b7280;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .search-container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .table-container {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #6366f1 100%);
            color: white;
            padding: 1.5rem 2rem;
            margin: 0;
        }

        .table-header h4 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-stats {
            background: #f1f5f9;
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .stat-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .modern-table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table thead th {
            background: #f8fafc;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background-color: #f8fafc;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .modern-table tbody td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
            border: none;
        }

        .description-cell {
            font-weight: 500;
            color: var(--text-primary);
            max-width: 300px;
        }

        .description-text {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .category-gasto {
            background-color: #FFC107;
            color: #ffffff;
            border: 1px solid #FFC107;
        }

        .category-ocio {
            background-color: #198754;
            color: #ffffff;
            border: 1px solid #198754;
        }

        .category-ahorro {
            background-color: #0DCAF0;
            color: #ffffff;
            border: 1px solid #0DCAF0;
        }

        .value-cell {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-primary);
        }

        .date-cell {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .btn-edit-modern {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-edit-modern:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .total-row {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-top: 2px solid var(--primary-color);
        }

        .total-row td {
            padding: 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .total-amount {
            color: var(--primary-color);
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 1.25rem;
        }

        .search-input {
            border-radius: 12px;
            padding: 0.875rem 1.25rem;
            border: 2px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(79 70 229 / 0.1);
        }

        .btn-search {
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-search:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .bulk-edit-container {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .no-results {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }

        .empty-state h5 {
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .table-container {
                border-radius: 12px;
            }

            .modern-table {
                font-size: 0.875rem;
            }

            .modern-table thead th,
            .modern-table tbody td {
                padding: 0.75rem 1rem;
            }

            .description-cell {
                max-width: 200px;
            }

            .table-stats {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Loading state */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
        }

        .spinner {
            width: 2rem;
            height: 2rem;
            border: 2px solid var(--border-color);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="py-5">

    <div class="container">

        <a href="../" class="btn btn-secondary btn-action mb-4">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>

        <!-- Encabezado -->
        <div class="page-header text-center">
            <h2 class="fw-bold mb-4">
                <i class="fas fa-search me-2 text-primary"></i>
                Buscador General
            </h2>
        </div>

        <!-- Buscador -->
        <div class="search-container">
            <form method="POST">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-10">
                        <div class="input-group">
                            <input type="search" name="busqueda" class="form-control search-input"
                                placeholder="Buscar en gastos, ocio y ahorros..."
                                value="<?php echo htmlspecialchars($busqueda ?? ''); ?>">
                            <button type="submit" class="btn btn-primary btn-search">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2 text-md-end text-center mt-2 mt-md-0">
                        <button type="button" class="btn btn-outline-secondary btn-search" id="toggleFiltros">
                            <i class="fas fa-filter"></i> Filtros
                        </button>
                    </div>
                </div>

                <!-- Filtros avanzados (inicialmente ocultos) -->
                <div id="filtrosAvanzados" class="p-4 border rounded-3 bg-light shadow-sm" style="display: none;">
                    <h5 class="mb-3"><i class="fas fa-filter text-primary"></i> Filtros Avanzados</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label fw-semibold">Nombre:</label>
                            <input type="text" class="form-control search-input" id="nombre" name="nombre" placeholder="Ej. Compra en supermercado"
                                value="<?= htmlspecialchars($nombre ?? '', ENT_QUOTES) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sub_categoria" class="form-label">
                                <i class="fas fa-layer-group text-primary"></i>
                                Categoría del Gasto
                            </label>

                            <select name="sub_categoria" id="sub_categoria" class="form-select" style="height: 55px;    border-radius: 12px;
    padding: 0.875rem 1.25rem;
    border: 2px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease;">
                                <option value="">Selecciona una Categoría</option>
                                <?php
                                $tipos_clases = [
                                    2 => ["clase" => "info", "tipo" => "AHORRO", "icono" => "📈"],
                                    23 => ["clase" => "warning", "tipo" => "GASTOS", "icono" => "💰"],
                                    24 => ["clase" => "success", "tipo" => "OCIO", "icono" => "🎉"]
                                ];

                                $categorias_agrupadas = [];
                                foreach ($categorias as $cat) {
                                    $padre = $cat['Categoria_Padre'];
                                    $categorias_agrupadas[$padre][] = $cat;
                                }

                                foreach ($categorias_agrupadas as $padre_id => $cats):
                                    $config = $tipos_clases[$padre_id] ?? ["clase" => "secondary", "tipo" => "OTROS", "icono" => "📋"];
                                ?>
                                    <optgroup label="<?php echo $config['icono'] . ' ' . $config['tipo']; ?>">
                                        <?php foreach ($cats as $cat):
                                            $valor = htmlspecialchars($cat['Nombre'], ENT_QUOTES);
                                            $selected = (isset($sub_categoria) && $sub_categoria === $valor) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $valor; ?>"
                                                class="text-<?php echo $config['clase']; ?>"
                                                <?php echo $selected; ?>>
                                                [<?php echo $config['tipo']; ?>] <?php echo $valor; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="valorMin" class="form-label fw-semibold">Valor mínimo:</label>
                            <input type="text" class="form-control valor_formateado search-input" id="valorMin" name="valorMin" placeholder="$0"
                                value="<?= htmlspecialchars($valorMin ?? '', ENT_QUOTES) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="valorMax" class="form-label fw-semibold">Valor máximo:</label>
                            <input type="text" class="form-control valor_formateado search-input" id="valorMax" name="valorMax" placeholder="$0"
                                value="<?= htmlspecialchars($valorMax ?? '', ENT_QUOTES) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fechaInicio" class="form-label fw-semibold">Fecha inicio:</label>
                            <input type="date" class="form-control search-input" id="fechaInicio" name="fechaInicio" value="">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fechaFin" class="form-label fw-semibold">Fecha fin:</label>
                            <input type="date" class="form-control search-input" id="fechaFin" name="fechaFin" value="">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 gap-2">
                        <button type="reset" class="btn btn-outline-secondary btn-search">
                            <i class="fas fa-eraser me-2"></i>Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary btn-search">
                            <i class="fas fa-check me-2"></i>Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de Resultados -->
        <div class="table-container">
            <div class="table-header">
                <h4><i class="fas fa-table me-2"></i>Resultados de la búsqueda</h4>
            </div>

            <?php
            $total_registros = $resultados->num_rows;
            $total_monto = 0;
            $temp_results = [];

            // Calcular estadísticas
            while ($fila = $resultados->fetch_assoc()) {
                $temp_results[] = $fila;
                $total_monto += $fila['Valor'];
            }
            ?>

            <div class="table-stats">
                <div class="stat-item">
                    <i class="fas fa-list-ul"></i>
                    <span class="stat-value"><?php echo $total_registros; ?></span>
                    <span>registros encontrados</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-calculator"></i>
                    <span>Total: </span>
                    <span class="stat-value"><?php echo "$" . number_format($total_monto, 0, '', '.'); ?></span>
                </div>
            </div>

            <?php if (empty($temp_results)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h5>No se encontraron resultados</h5>
                    <p>Intenta ajustar los filtros de búsqueda para obtener más resultados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table modern-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-align-left me-1"></i>Descripción</th>
                                <th><i class="fas fa-tag me-1"></i>Categoría</th>
                                <th><i class="fas fa-dollar-sign me-1"></i>Valor</th>
                                <th><i class="fas fa-calendar me-1"></i>Fecha</th>
                                <th><i class="fas fa-cog me-1"></i>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($temp_results as $fila) {
                                $tipos_clases = [
                                    2 => "ahorro",
                                    23 => "gasto",
                                    24 => "ocio"
                                ];
                                $tipo_categoria = $tipos_clases[$fila['tipo']] ?? "gasto";
                            ?>
                                <tr>
                                    <td class="description-cell">
                                        <span class="description-text" title="<?php echo htmlspecialchars($fila['Descripcion']); ?>">
                                            <?php echo htmlspecialchars($fila['Descripcion']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="category-badge category-<?php echo $tipo_categoria; ?>">
                                            <?php
                                            $icons = [
                                                'gasto' => 'fas fa-shopping-cart',
                                                'ocio' => 'fas fa-gamepad',
                                                'ahorro' => 'fas fa-piggy-bank'
                                            ];
                                            ?>
                                            <i class="<?php echo $icons[$tipo_categoria]; ?>"></i>
                                            <?php echo htmlspecialchars($fila['categoria']); ?>
                                        </span>
                                    </td>
                                    <td class="value-cell">
                                        <?php echo "$" . number_format($fila['Valor'], 0, '', '.'); ?>
                                    </td>
                                    <td class="date-cell">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($fila['Fecha'])); ?>
                                    </td>
                                    <td>
                                        <a href="./editar.php?id=<?php echo $fila['ID']; ?>" class="btn-edit-modern">
                                            <i class="fas fa-edit"></i>
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>

                            <tr class="total-row">
                                <td colspan="2" class="text-end">
                                    <i class="fas fa-calculator me-2"></i>
                                    <strong>Total General:</strong>
                                </td>
                                <td class="total-amount">
                                    <?php echo "$" . number_format($total_monto, 0, '', '.'); ?>
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const boton = document.getElementById("toggleFiltros");
            const div = document.getElementById("filtrosAvanzados");

            boton.addEventListener("click", function() {
                if (div.style.display === "none" || div.style.display === "") {
                    div.style.display = "block";
                    boton.innerHTML = '<i class="fas fa-times"></i> Cerrar';
                } else {
                    div.style.display = "none";
                    boton.innerHTML = '<i class="fas fa-filter"></i> Filtros';
                }
            });

            // Efecto hover mejorado para las filas
            const tableRows = document.querySelectorAll('.modern-table tbody tr:not(.total-row)');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });

                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // Función para formatear el número como pesos chilenos
        function formatPesoChile(value) {
            value = value.replace(/\D/g, '');
            return new Intl.NumberFormat('es-CL', {
                style: 'currency',
                currency: 'CLP'
            }).format(value);
        }

        // Obtener todos los campos de entrada con la clase 'valor_formateado'
        const montoInputs = document.querySelectorAll('.valor_formateado');

        // Evento para formatear el valor mientras el usuario escribe
        montoInputs.forEach(function(montoInput) {
            montoInput.addEventListener('input', function() {
                let value = montoInput.value;
                montoInput.value = formatPesoChile(value);
            });
        });
    </script>
</body>

</html>