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
    $sql = "SELECT g.ID, d.Detalle AS Descripcion, g.Valor, c.Nombre as categoria, g.Fecha, c.Categoria_Padre as tipo, g.fuente_dinero
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
    $sql = "SELECT g.ID,d.Detalle AS Descripcion, g.Valor, c.Nombre as categoria, g.Fecha,c.Categoria_Padre as tipo, g.fuente_dinero
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
    <link rel="stylesheet" href="styles.css?<?php echo time() ?>">

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
            $total_sistema = 0;  // Gasto real que afecta tus cuentas
            $total_externo = 0;  // Gasto informativo (fuente externa)
            $temp_results = [];

            // Calcular estadísticas separadas
            while ($fila = $resultados->fetch_assoc()) {
                $temp_results[] = $fila;

                // Verificamos el método de pago (asegúrate de que el SQL traiga este campo)
                if (isset($fila['fuente_dinero']) && $fila['fuente_dinero'] === 'externo') {
                    $total_externo += $fila['Valor'];
                } else {
                    $total_sistema += $fila['Valor'];
                }
            }
            // El total general sigue siendo la suma de ambos para efectos de reporte
            $total_monto_general = $total_sistema + $total_externo;
            ?>

            <div class="table-stats d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex gap-4">
                    <div class="stat-item">
                        <i class="fas fa-list-ul text-secondary"></i>
                        <span class="stat-value"><?php echo $total_registros; ?></span>
                        <span class="text-muted">registros</span>
                    </div>

                    <div class="stat-item">
                        <i class="fas fa-university text-primary"></i>
                        <span>Sistema: </span>
                        <span class="stat-value text-primary">
                            <?php echo "$" . number_format($total_sistema, 0, '', '.'); ?>
                        </span>
                    </div>

                    <?php if ($total_externo > 0): ?>
                        <div class="stat-item">
                            <i class="fas fa-wallet text-secondary"></i>
                            <span>Externo: </span>
                            <span class="stat-value text-secondary">
                                <?php echo "$" . number_format($total_externo, 0, '', '.'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="stat-item ms-auto">
                    <span class="stat-value text-secondary">
                        Total General: <?php echo "$" . number_format($total_sistema + $total_externo, 0, '', '.'); ?>
                    </span>
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

                                // Nueva lógica para fuente de dinero
                                $es_externo = (isset($fila['fuente_dinero']) && $fila['fuente_dinero'] === 'externo');
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
                                        <div class="d-flex flex-column">
                                            <span><?php echo "$" . number_format($fila['Valor'], 0, '', '.'); ?></span>

                                            <?php if ($es_externo): ?>
                                                <span class="badge badge-externo w-fit-content">
                                                    <span class="fuente-externa-dot"></span> EXTERNO
                                                </span>
                                            <?php endif; ?>
                                        </div>
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
                                <td colspan="2" class="text-end border-end">
                                    <div class="d-flex flex-column align-items-end">
                                        <small class="text-muted fw-normal">Resumen de Gastos:</small>
                                        <span class="text-uppercase small fw-bold">Total General:</span>
                                    </div>
                                </td>
                                <td class="total-amount">
                                    <div class="d-flex flex-column">
                                        <span class="text-primary" title="Afecta Balance">
                                            <i class="fas fa-university me-1" style="font-size: 0.8rem;"></i>
                                            <?php echo "$" . number_format($total_sistema, 0, '', '.'); ?>
                                        </span>

                                        <?php if ($total_externo > 0): ?>
                                            <span class="text-secondary small fw-normal" title="No afecta Balance">
                                                <i class="fas fa-wallet me-1" style="font-size: 0.7rem;"></i>
                                                <?php echo "$" . number_format($total_externo, 0, '', '.'); ?> (Ext)
                                            </span>
                                        <?php endif; ?>

                                        <div class="border-top mt-1 pt-1" style="border-color: #dee2e6 !important;">
                                            <span class="text-dark" style="font-size: 1.1rem;">
                                                <?php echo "$" . number_format($total_monto_general, 0, '', '.'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

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