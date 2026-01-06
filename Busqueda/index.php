<?php
include('../bd.php');

// 1. Identificar el módulo actual
$categoria_url = isset($_GET['categoria']) ? $_GET['categoria'] : 'Gastos';

// Configuración por defecto según el módulo
switch ($categoria_url) {
    case 'Ocio':
        $titulo = "Ocio";
        $tipo_color = "success";
        $id_padre_filtro = 24;
        break;
    case 'Ahorros':
        $titulo = "Ahorros";
        $tipo_color = "info";
        $id_padre_filtro = 2;
        break;
    default:
        $titulo = "Gastos";
        $tipo_color = "warning";
        $id_padre_filtro = 23;
        break;
}

// 2. Lógica de Búsqueda (Procesamiento de POST)
$busqueda = $_POST['busqueda'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$sub_categoria = $_POST['sub_categoria'] ?? '';
$valorMin = $_POST['valorMin'] ?? '';
$valorMax = $_POST['valorMax'] ?? '';
$fechaInicio = $_POST['fechaInicio'] ?? '';
$fechaFin = $_POST['fechaFin'] ?? '';

// Limpiar valores monetarios para la consulta
$valorMinNum = str_replace(['$', '.'], '', $valorMin);
$valorMaxNum = str_replace(['$', '.'], '', $valorMax);

// Base de la consulta: Filtramos por el Categoria_Padre del módulo actual
$sql = "SELECT g.ID, d.Detalle AS Descripcion, g.Valor, c.Nombre as categoria, g.Fecha, 
               c.Categoria_Padre as tipo, g.fuente_dinero
        FROM gastos g
        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
        INNER JOIN detalle d ON g.ID_Detalle = d.ID
        WHERE c.Categoria_Padre = ?";

$params = [$id_padre_filtro];
$types = 'i';

// Filtros dinámicos
if (!empty($busqueda)) {
    $sql .= " AND (d.Detalle LIKE ? OR c.Nombre LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
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

if (is_numeric($valorMinNum) && $valorMinNum > 0) {
    $sql .= " AND g.Valor >= ?";
    $params[] = (float)$valorMinNum;
    $types .= 'd';
}

if (is_numeric($valorMaxNum) && $valorMaxNum > 0) {
    $sql .= " AND g.Valor <= ?";
    $params[] = (float)$valorMaxNum;
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

$sql .= " ORDER BY g.Fecha DESC LIMIT 100";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultados = $stmt->get_result();

// Obtener categorías específicas del módulo para el select de filtros
$sqlCat = "SELECT Nombre FROM categorias_gastos WHERE Categoria_Padre = ? ORDER BY Nombre";
$stmtCat = $conexion->prepare($sqlCat);
$stmtCat->bind_param('i', $id_padre_filtro);
$stmtCat->execute();
$resCategorias = $stmtCat->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscador de <?= $titulo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --main-color: <?php echo ($tipo_color == 'success' ? '#198754' : ($tipo_color == 'info' ? '#0dcaf0' : '#ffc107')); ?>;
        }

        .search-container {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .category-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .category-ocio {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .category-ahorro {
            background: #e3f2fd;
            color: #1565c0;
        }

        .category-gasto {
            background: #fff8e1;
            color: #827717;
        }

        .badge-externo {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 0.7rem;
        }

        .fuente-externa-dot {
            height: 8px;
            width: 8px;
            background-color: #6c757d;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
        }
    </style>
</head>

<body class="bg-light py-5">

    <div class="container">
        <div class="page-header mb-4">
            <h2 class="fw-bold"><i class="fas fa-search me-2 text-<?= $tipo_color ?>"></i> Buscador de <?= $titulo ?></h2>
        </div>

        <div class="search-container mb-5">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-9">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="busqueda" class="form-control border-start-0" placeholder="Buscar por descripción o categoría..." value="<?= htmlspecialchars($busqueda) ?>">
                            <button type="submit" class="btn btn-<?= $tipo_color ?> px-4 text-white">Buscar</button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="toggleFiltros" class="btn btn-outline-secondary btn-lg w-100">
                            <i class="fas fa-filter me-2"></i>Filtros
                        </button>
                    </div>
                </div>

                <div id="filtrosAvanzados" class="mt-4 p-4 border rounded-3 bg-white" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nombre/Detalle</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($nombre) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Categoría Específica</label>
                            <select name="sub_categoria" class="form-select">
                                <option value="">Todas</option>
                                <?php while ($cat = $resCategorias->fetch_assoc()): ?>
                                    <option value="<?= $cat['Nombre'] ?>" <?= ($sub_categoria == $cat['Nombre'] ? 'selected' : '') ?>>
                                        <?= $cat['Nombre'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Desde ($)</label>
                            <input type="text" name="valorMin" class="form-control valor_formateado" value="<?= htmlspecialchars($valorMin) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Hasta ($)</label>
                            <input type="text" name="valorMax" class="form-control valor_formateado" value="<?= htmlspecialchars($valorMax) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Fecha Inicio</label>
                            <input type="date" name="fechaInicio" class="form-control" value="<?= $fechaInicio ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Fecha Fin</label>
                            <input type="date" name="fechaFin" class="form-control" value="<?= $fechaFin ?>">
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <a href="index.php?categoria=<?= $categoria_url ?>" class="btn btn-light me-2">Limpiar</a>
                        <button type="submit" class="btn btn-dark">Aplicar Filtros</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive bg-white p-4 rounded-3 shadow-sm">
            <?php
            $total_sistema = 0;
            $total_externo = 0;
            $rows = [];
            while ($f = $resultados->fetch_assoc()) {
                $rows[] = $f;
                if (($f['fuente_dinero'] ?? '') === 'externo') $total_externo += $f['Valor'];
                else $total_sistema += $f['Valor'];
            }
            ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="m-0">Resultados (<?= count($rows) ?>)</h4>
                <div class="text-end">
                    <div class="fw-bold text-primary">Sistema: $<?= number_format($total_sistema, 0, '', '.') ?></div>
                    <?php if ($total_externo > 0): ?>
                        <div class="small text-muted">Externo: $<?= number_format($total_externo, 0, '', '.') ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Valor</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $fila):
                        $clase_cat = ($fila['tipo'] == 24 ? 'ocio' : ($fila['tipo'] == 2 ? 'ahorro' : 'gasto'));
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($fila['Descripcion']) ?></strong></td>
                            <td><span class="category-badge category-<?= $clase_cat ?>"><?= htmlspecialchars($fila['categoria']) ?></span></td>
                            <td>
                                $<?= number_format($fila['Valor'], 0, '', '.') ?>
                                <?php if (($fila['fuente_dinero'] ?? '') === 'externo'): ?>
                                    <br><span class="badge badge-externo"><span class="fuente-externa-dot"></span>EXTERNO</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($fila['Fecha'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No se encontraron registros</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Toggle de filtros
        document.getElementById('toggleFiltros').addEventListener('click', function() {
            const f = document.getElementById('filtrosAvanzados');
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
            this.innerHTML = f.style.display === 'none' ? '<i class="fas fa-filter me-2"></i>Filtros' : '<i class="fas fa-times me-2"></i>Cerrar';
        });

        // Formato de moneda CLP
        const formatCLP = (v) => {
            v = v.replace(/\D/g, "");
            return new Intl.NumberFormat('es-CL', {
                style: 'currency',
                currency: 'CLP',
                maximumFractionDigits: 0
            }).format(v);
        }

        document.querySelectorAll('.valor_formateado').forEach(i => {
            i.addEventListener('input', (e) => e.target.value = formatCLP(e.target.value));
        });
    </script>
</body>

</html>