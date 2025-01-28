<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Editar Registro</title>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .edit-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-top: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .btn-action {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .page-header {
            color: #212529;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <?php
    include('../bd.php');

    $id = $_GET['id'] ?? null;
    $mensaje = '';
    $error = '';

    // Obtener datos del registro
    if ($id) {
        $sql = "SELECT g.ID, d.Detalle AS Descripcion, g.Valor, g.ID_Categoria_Gastos, 
                       c.Nombre as categoria, g.Fecha, c.Categoria_Padre as tipo
                FROM gastos g
                INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                INNER JOIN detalle d ON g.ID_Detalle = d.ID
                WHERE g.ID = ?";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();

        // Obtener categorías para el select
        $sqlCategorias = "SELECT ID, Nombre,Categoria_Padre FROM categorias_gastos ORDER BY Nombre";
        $categorias = $conexion->query($sqlCategorias);
    }

    // Procesar actualización
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nuevo_valor = formatearMonto($_POST['valor']);
        $nueva_categoria = $_POST['categoria'];
        $nueva_descripcion = $_POST['descripcion'];
        $nueva_fecha = $_POST['fecha'];

        try {
            // Establecer la conexión a la base de datos
            $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->beginTransaction(); // Cambiar a $pdo porque estás usando PDO, no MySQLi

            // Obtener o crear detalle
            $stmt = $pdo->prepare("SELECT ID FROM detalle WHERE Detalle = :nombre");
            $stmt->execute([':nombre' => $nueva_descripcion]);
            $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($detalle) {
                $id_detalle = $detalle['ID'];
            } else {
                // Si el detalle no existe, insertarlo y recuperar el ID
                $stmt = $pdo->prepare("INSERT INTO detalle (Detalle) VALUES (:nombre)");
                $stmt->execute([':nombre' => $nueva_descripcion]);
                $id_detalle = $pdo->lastInsertId();
            }

            // Actualizar gasto
            $sqlGasto = "UPDATE gastos SET 
                        Valor = :nuevo_valor,
                        ID_Categoria_Gastos = :nueva_categoria,
                        ID_Detalle = :id_detalle,
                        Fecha = :nueva_fecha
                        WHERE ID = :id";

            $stmtGasto = $pdo->prepare($sqlGasto);
            $stmtGasto->execute([
                ':nuevo_valor' => $nuevo_valor,
                ':nueva_categoria' => $nueva_categoria,
                ':id_detalle' => $id_detalle,
                ':nueva_fecha' => $nueva_fecha,
                ':id' => $id
            ]);

            $pdo->commit();
            $mensaje = "Registro actualizado exitosamente";

            // Recargar datos actualizados
            $stmt = $pdo->prepare("SELECT g.ID, d.Detalle AS Descripcion, g.Valor, g.ID_Categoria_Gastos, 
                       c.Nombre as categoria, g.Fecha, c.Categoria_Padre as tipo
                FROM gastos g
                INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                INNER JOIN detalle d ON g.ID_Detalle = d.ID
                WHERE g.ID = :id");
            $stmt->execute([':id' => $id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC); // Usar fetch para obtener los resultados

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
    ?>

    <div class="container">
        <div class="page-header text-center">
            <h2 class="fw-bold">Editar Registro</h2>
        </div>

        <div class="edit-container">
            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <input type="text" list="detalles" class="form-control" id="descripcion" name="descripcion" value="<?php echo htmlspecialchars($resultado['Descripcion'] ?? ''); ?>" required>
                            <datalist id="detalles">
                                <?php foreach ($detalles as $detalle): ?>
                                    <option value="<?php echo htmlspecialchars($detalle['Detalle']); ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text"
                                    class="form-control valor_formateado"
                                    id="valor"
                                    name="valor"
                                    value="<?php echo $resultado['Valor'] ?? ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select name="categoria" class="form-select">
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
                                    <option value="<?php echo $cat['ID']; ?>"
                                        class="text-<?php echo $clase; ?>"
                                        <?php echo ($cat['ID'] == $resultado['ID_Categoria_Gastos']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['Nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input type="datetime-local"
                                class="form-control"
                                id="fecha"
                                name="fecha"
                                value="<?php echo $resultado['Fecha'] ?? ''; ?>"
                                required>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="./general.php" class="btn btn-secondary btn-action">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <button type="submit" class="btn btn-primary btn-action">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de formulario
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>

    <script>
        // Función para formatear el número como pesos chilenos
        function formatPesoChile(value) {
            value = value.replace(/[^0-9-]/g, ''); // Eliminar todo lo que no sea un número o el signo negativo
            if (value.startsWith('-')) {
                // Si el número es negativo, mantener el signo al formatear
                return '-' + new Intl.NumberFormat('es-CL', {
                    style: 'currency',
                    currency: 'CLP'
                }).format(value.replace('-', ''));
            } else {
                return new Intl.NumberFormat('es-CL', {
                    style: 'currency',
                    currency: 'CLP'
                }).format(value);
            }
        }

        // Obtener todos los campos de entrada con la clase 'valor_formateado'
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