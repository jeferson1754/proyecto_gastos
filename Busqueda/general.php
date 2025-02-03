<?php
include('../bd.php'); // Conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar términos de búsqueda
    $busqueda = $_POST['busqueda'] ?? '';
    $sub_categoria = $_POST['sub_categoria'] ?? '';
    $valorMin = $_POST['valorMin'] ?? '';
    $valorMax = $_POST['valorMax'] ?? '';
    $fechaInicio = $_POST['fechaInicio'] ?? '';
    $fechaFin = $_POST['fechaFin'] ?? '';

    // Query para buscar en las tres tablas
    $sql = "SELECT g.ID,d.Detalle AS Descripcion, g.Valor, c.Nombre as categoria, g.Fecha,c.Categoria_Padre as tipo
    FROM gastos g
    INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
    INNER JOIN detalle d ON g.ID_Detalle = d.ID
    WHERE 1=1 ";


    if (!empty($sub_categoria)) {
        $sql .= " AND c.Nombre = '$sub_categoria'";
    }
    if (!empty($nombre)) {
        $sql .= " AND d.Detalle LIKE '%$nombre%'";
    }
    if ($valorMin > 0) {
        $sql .= " AND g.Valor >= $valorMin";
    }
    if ($valorMax > 0) {
        $sql .= " AND g.Valor <= $valorMax";
    }
    if (!empty($fechaInicio)) {
        $sql .= " AND g.Fecha >= '$fechaInicio'";
    }
    if (!empty($fechaFin)) {
        $sql .= " AND g.Fecha <= '$fechaFin'";
    }
    if (!empty($busqueda)) {
        $sql .= " AND (d.Detalle LIKE '%$busqueda%' OR g.Valor LIKE '%$busqueda%' OR c.Nombre LIKE '%$busqueda%' OR g.Fecha LIKE '%$busqueda%')";
    }


    $sql .= " ORDER BY g.Fecha DESC LIMIT 50";

    $resultados = $conexion->query($sql);
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Buscador General</title>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .search-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .search-input {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .search-input:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-search {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-edit {
            border-radius: 6px;
            padding: 0.4rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-edit:hover {
            transform: translateY(-1px);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .bulk-edit-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .custom-checkbox {
            width: 18px;
            height: 18px;
        }
    </style>
</head>

<body class="py-5">

    <div class="container">

        <a href="../" class="btn btn-secondary btn-action">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
        <!-- Encabezado -->
        <div class="page-header text-center">
            <h2 class="fw-bold mb-4">Buscador General</h2>
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
                                <i class="fas fa-search me-2"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2 text-md-end text-center mt-2 mt-md-0">
                        <button type="button" class="btn btn-secondary btn-search" id="toggleFiltros">
                            <i class="fas fa-filter"></i> Filtros
                        </button>
                    </div>
                </div>

                <!-- Filtros avanzados (inicialmente ocultos) -->
                <div id="filtrosAvanzados" class="p-3 border rounded bg-light shadow-sm" style="display: none;">
                    <h5 class="mb-3"><i class="fas fa-filter"></i> Filtros Avanzados</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre:</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. Compra en supermercado"
                                value="">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sub_categoria" class="form-label">Categoría del Gasto</label>
                            <select name="sub_categoria" class="form-select">
                                <option value="">Selecciona una Categoria</option>
                                <?php
                                $tipos_clases = [
                                    2 => "info",
                                    23 => "warning",
                                    24 => "success"
                                ];
                                foreach ($categorias as $cat):
                                    // Determinar la clase basada en el tipo
                                    $clase = $tipos_clases[$cat['Categoria_Padre']] ?? "secondary";
                                ?>
                                    <option
                                        class="text-<?php echo $clase; ?>">
                                        <?php echo htmlspecialchars($cat['Nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="valorMin" class="form-label">Valor mínimo:</label>
                            <input type="text" class="form-control valor_formateado" id="valorMin" name="valorMin" placeholder="$0"
                                value="">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="valorMax" class="form-label">Valor máximo:</label>
                            <input type="text" class="form-control valor_formateado" id="valorMax" name="valorMax" placeholder="$0"
                                value="">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fechaInicio" class="form-label">Fecha inicio:</label>
                            <input type="date" class="form-control" id="fechaInicio" name="fechaInicio" value="">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fechaFin" class="form-label">Fecha fin:</label>
                            <input type="date" class="form-control" id="fechaFin" name="fechaFin" value="">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="reset" class="btn btn-secondary me-2"><i class="fas fa-eraser"></i> Limpiar</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Aplicar filtros</button>
                    </div>
                </div>

            </form>
        </div>


        <!-- Tabla de Resultados -->
        <div class="table-container">
            <form method="POST">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th>Valor</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_monto = 0;
                            while ($fila = $resultados->fetch_assoc()) {
                                $tipos_clases = [
                                    2 => "info",
                                    23 => "warning",
                                    24 => "success"
                                ];
                                $clase = $tipos_clases[$fila['tipo']] ?? "secondary";


                            ?>
                                <tr class="table-<?php echo $clase; ?>">
                                    <td><?php echo $fila['Descripcion']; ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $fila['categoria']; ?></span>
                                    </td>
                                    <td class="fw-bold">
                                        <?php
                                        echo "$" . number_format($fila['Valor'], 0, '', '.');
                                        $total_monto += $fila['Valor']; // Sumar correctamente el valor al total
                                        ?>
                                    </td>
                                    <td><?php echo $fila['Fecha']; ?></td>
                                    <td>
                                        <a href="./editar.php?id=<?php echo $fila['ID']; ?>"
                                            class="btn btn-warning btn-edit">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </a>
                                    </td>
                                </tr>

                            <?php } ?>
                            <tr class="table-secondary text-dark fw-bold">
                                <td colspan="2" class="text-end">Total:</td>
                                <td>
                                    <?php echo "$" . number_format($total_monto, 0, '', '.'); ?>
                                </td>
                                <td colspan="2"></td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </form>
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
                } else {
                    div.style.display = "none";
                }
            });
        });
    </script>
    <script>
        // Función para formatear el número como pesos chilenos
        function formatPesoChile(value) {
            value = value.replace(/\D/g, ''); // Eliminar todo lo que no sea un número
            return new Intl.NumberFormat('es-CL', {
                style: 'currency',
                currency: 'CLP'
            }).format(value);
        }

        // Obtener todos los campos de entrada con la clase 'monto_gasto'
        const montoInputs = document.querySelectorAll('.valor_formateado');

        // Evento para formatear el valor mientras el usuario escribe en cada campo
        montoInputs.forEach(function(montoInput) {
            montoInput.addEventListener('input', function() {
                let value = montoInput.value;
                montoInput.value = formatPesoChile(value); // Aplicar el formato de peso chileno
            });
        });
    </script>
</body>

</html>