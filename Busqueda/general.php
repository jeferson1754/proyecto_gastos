<?php
include('../bd.php'); // Conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar términos de búsqueda
    $busqueda = $_POST['busqueda'] ?? '';

    // Query para buscar en las tres tablas
    $sql = "SELECT g.ID,d.Detalle AS Descripcion, g.Valor, c.Nombre as categoria, g.Fecha,c.Categoria_Padre as tipo
    FROM gastos g
    INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
    INNER JOIN detalle d ON g.ID_Detalle = d.ID
    WHERE d.Detalle LIKE ?  OR c.Nombre LIKE ? OR g.Valor LIKE ? OR g.Fecha LIKE ?   
    ORDER BY g.Fecha DESC
    LIMIT 50";


    $stmt = $conexion->prepare($sql);
    $likeBusqueda = "%$busqueda%";
    $stmt->bind_param('ssss', $likeBusqueda, $likeBusqueda, $likeBusqueda, $likeBusqueda);
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
        <!-- Encabezado -->
        <div class="page-header text-center">
            <h2 class="fw-bold mb-4">Buscador General</h2>
        </div>

        <!-- Buscador -->
        <div class="search-container">
            <form method="POST">
                <div class="input-group">
                    <input type="text"
                        name="busqueda"
                        class="form-control search-input"
                        placeholder="Buscar en gastos, ocio y ahorros..."
                        value="<?php echo htmlspecialchars($busqueda ?? ''); ?>">
                    <button type="submit" class="btn btn-primary btn-search">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
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
                                <th>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input custom-checkbox" id="select_all">
                                    </div>
                                </th>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th>Valor</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fila = $resultados->fetch_assoc()) {
                                $tipos_clases = [
                                    2 => "info",
                                    23 => "warning",
                                    24 => "success"
                                ];
                                $clase = $tipos_clases[$fila['tipo']] ?? "secondary";
                            ?>
                                <tr class="table-<?php echo $clase; ?>">
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox"
                                                class="form-check-input custom-checkbox"
                                                name="seleccion[]"
                                                value="<?php echo $fila['tipo'] . '-' . $fila['ID']; ?>">
                                        </div>
                                    </td>
                                    <td><?php echo $fila['Descripcion']; ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $fila['categoria']; ?></span>
                                    </td>
                                    <td class="fw-bold">
                                        <?php echo "$" . number_format($fila['Valor'], 0, '', '.') ?>
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
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>