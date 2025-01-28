<?php
// Asumimos que tienes la conexión a la base de datos en 'bd.php'.
include('../bd.php');

$id = $_GET['id'] ?? null;


// Obtener los datos del pago si el ID está presente
if ($id) {
    $sql = "SELECT p.*
            FROM pagos p
            LEFT JOIN gastos g ON p.gasto_id = g.ID 
            WHERE p.ID = ?";

    // Verifica que la consulta se haya preparado correctamente
    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
    } else {
        // Si la consulta no se prepara correctamente, muestra el error
        die("Error al preparar la consulta: " . $conexion->error);
    }
}

// Si se envía el formulario para editar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $mensaje = '';
    $error = '';
    try {
        $gasto_id = $_POST['gasto_id'] ?? '';
        $quien_paga = $_POST['quien_paga'];
        $cuenta = $_POST['cuenta'];
        $fecha = $_POST['fecha'];
        $estado = $_POST['estado'];
        $estado_antiguo = $_POST['estado_antiguo'];
        $valor  = formatearMonto($_POST['valor']);
        $comprobante_url = $_POST['comprobante_url'];

        // Validaciones
        if (empty($quien_paga) || empty($cuenta) || empty($valor) || empty($estado) || empty($fecha)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if (!empty($comprobante_url)) {
            if (!filter_var($comprobante_url, FILTER_VALIDATE_URL)) {
                throw new Exception("El enlace del comprobante no es válido");
            }
        }


        // Actualizar el pago
        $stmt = $conexion->prepare("UPDATE pagos SET gasto_id = ?, quien_paga = ?, cuenta = ?, valor = ?, comprobante = ?, Fecha_Pago = ?, Estado = ? WHERE ID = ?");

        if ($stmt === false) {
            die("Error al preparar la consulta de actualización: " . $conexion->error);
        }

        $stmt->bind_param('issdsssi', $gasto_id, $quien_paga, $cuenta, $valor, $comprobante_url, $fecha, $estado, $id);
        $stmt->execute();

        if ($estado != $estado_antiguo) {
            // Obtener el siguiente mes
            $fecha_siguiente_mes = date('Y-m-t', strtotime($fecha . ' +28 days'));


            if ($cuenta == "Luz") {
                $otra_cuenta = "Agua";
            } else {
                $otra_cuenta = "Luz";
            }

            // Insertar el registro de pago para el próximo mes
            $stmt_next = $pdo->prepare("INSERT INTO pagos (quien_paga, cuenta, estado, fecha_pago) 
                                        VALUES (?, ?, ?, ?)");
            $stmt_next->execute([$quien_paga, $otra_cuenta, 'Pendiente', $fecha_siguiente_mes]);

            $mensaje = ["tipo" => "success", "texto" => "Pago actualizado exitosamente y se programó el pago para el siguiente mes."];
        } else {
            $mensaje = ["tipo" => "success", "texto" => "Pago actualizado exitosamente"];
        }
    } catch (Exception $e) {
        $mensaje = ["tipo" => "danger", "texto" => $e->getMessage()];
    }
}

?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pago</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }

        .btn-primary {
            background-color: #4299e1;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #3182ce;
            transform: translateY(-1px);
        }

        .input-group-text {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
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
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-money-bill-wave me-2"></i>Editar Pago Mensual</h2>
            </div>

            <?php
            if (isset($mensaje)): ?>
                <div class="alert alert-<?php echo $mensaje['tipo']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje['texto']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif;
            ?>

            <form method="POST" id="pagoForm" class="needs-validation" novalidate>
                <input type="hidden" name="estado_antiguo" value="<?php echo htmlspecialchars($resultado['Estado'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-4">
                    <label for="gasto_id" class="form-label fw-bold">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Gasto
                    </label>

                    <select class="form-select" id="gasto_id" name="gasto_id">
                        <option value="">Seleccione un gasto...</option>
                        <?php
                        // Ejecutar la consulta
                        $stmt2 = $conexion->query("SELECT 
        gastos.ID, 
        detalle.Detalle, 
        gastos.Valor, 
        gastos.Fecha, 
        categorias_gastos.Nombre AS Categoria
    FROM 
        gastos
    INNER JOIN 
        detalle ON gastos.ID_Detalle = detalle.ID
    INNER JOIN 
        categorias_gastos ON gastos.ID_Categoria_Gastos = categorias_gastos.ID
    WHERE 
                            YEAR(gastos.Fecha) = YEAR(CURRENT_DATE()) AND 
                            MONTH(gastos.Fecha) = MONTH(CURRENT_DATE()) AND
                            categorias_gastos.Nombre = 'Cuentas'
    ORDER BY gastos.Fecha DESC");

                        // Usar mysqli_fetch_assoc para obtener los resultados
                        while ($gasto = mysqli_fetch_assoc($stmt2)) {
                            $valor_formateado = "$" . number_format($gasto['Valor'], 0, '', '.');
                            $detalle_escapado = htmlspecialchars($gasto['Detalle'], ENT_QUOTES, 'UTF-8');
                            $fecha_formateada = date('d/m/Y', strtotime($gasto['Fecha']));
                            $selected = ($gasto['ID'] == $resultado['gasto_id']) ? 'selected' : '';
                            echo "<option value='{$gasto['ID']}' {$selected}>{$fecha_formateada} - {$valor_formateado} - {$detalle_escapado}</option>";
                        }
                        ?>
                    </select>

                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="quien_paga" class="form-label fw-bold">
                            <i class="fas fa-user me-2"></i>Quién Paga
                        </label>
                        <input type="text" list="pagadores" class="form-control" id="quien_paga" name="quien_paga" required value="<?php echo htmlspecialchars($resultado['quien_paga'], ENT_QUOTES, 'UTF-8'); ?>">
                        <datalist id="pagadores">
                            <?php foreach ($pagadores as $pagador): ?>
                                <option value="<?php echo htmlspecialchars($pagador['quien_paga']); ?>">
                                <?php endforeach; ?>
                        </datalist>
                        <div class="invalid-feedback">Por favor ingrese quién realiza el pago</div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label for="cuenta" class="form-label fw-bold">
                            <i class="fas fa-credit-card me-2"></i>Cuenta
                        </label>
                        <input type="text" list="cuentas" class="form-control" id="cuenta" name="cuenta" required value="<?php echo htmlspecialchars($resultado['Cuenta'], ENT_QUOTES, 'UTF-8'); ?>">
                        <datalist id="cuentas">
                            <?php foreach ($cuentas as $cuenta): ?>
                                <option value="<?php echo htmlspecialchars($cuenta['Cuenta']); ?>">
                                <?php endforeach; ?>
                        </datalist>
                        <div class="invalid-feedback">Por favor seleccione una cuenta</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="valor" class="form-label fw-bold">
                        <i class="fas fa-dollar-sign me-2"></i>Valor
                    </label>
                    <input type="text"
                        class="form-control valor_formateado"
                        id="valor"
                        name="valor"
                        required
                        value="$ <?php echo number_format($resultado['Valor'], 0, '', '.'); ?>">
                    <div class="invalid-feedback">Por favor ingrese un valor válido</div>
                </div>

                <div class="mb-4">
                    <label for="fecha" class="form-label fw-bold">
                        <i class="fas fa-calendar me-2"></i>Fecha de Pago
                    </label>
                    <input type="datetime-local"
                        class="form-control"
                        id="fecha"
                        name="fecha"
                        value="<?php echo date('Y-m-d\TH:i', strtotime($resultado['Fecha_Pago'])); ?>"
                        required>
                    <div class="invalid-feedback">Por favor ingrese una fecha válida</div>
                </div>

                <div class="mb-4">
                    <label for="estado" class="form-label fw-bold">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Estado
                    </label>

                    <select class="form-select" id="estado" name="estado" required>
                        <option value="">Seleccione un estado...</option>
                        <?php
                        // Ejecutar la consulta
                        $stmt2 = $conexion->query("SELECT DISTINCT Estado FROM `pagos`;");

                        // Usar mysqli_fetch_assoc para obtener los resultados
                        while ($estado = mysqli_fetch_assoc($stmt2)) {
                            $selected = ($estado['Estado'] == $resultado['Estado']) ? 'selected' : '';
                            echo "<option value='{$estado['Estado']}' {$selected}>{$estado['Estado']}</option>";
                        }
                        ?>
                    </select>
                    <div class="invalid-feedback">Por favor ingrese un estado valido</div>
                </div>

                <div class="mb-4">
                    <label for="comprobante_url" class="form-label fw-bold">
                        <i class="fas fa-file-upload me-2"></i>Enlace del Comprobante
                    </label>
                    <input type="url"
                        class="form-control"
                        id="comprobante_url"
                        name="comprobante_url"
                        placeholder="https://..."
                        value="<?php echo htmlspecialchars($resultado['comprobante'], ENT_QUOTES, 'UTF-8'); ?>"
                        pattern="https?://.+">
                    <div class="invalid-feedback">Por favor ingrese un enlace válido que comience con http:// o https://</div>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="./" class="btn btn-secondary btn-action">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <button type="submit" class="btn btn-primary btn-action">
                        <i class="fas fa-save me-2"></i>Actualizar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        })();
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