<?php
// Asumimos que tienes la conexión a la base de datos en 'bd.php'.
include('../bd.php');

// Si se envía el formulario

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $gasto_id = $_POST['gasto_id'] ?? '';
        $quien_paga = $_POST['quien_paga'];
        $cuenta = $_POST['cuenta'];
        $fecha = $_POST['fecha'];
        $valor = str_replace(['$', '.', ' '], '', $_POST['valor']); // Limpiamos el formato de moneda
        $comprobante_url = $_POST['comprobante_url'];

        // Validaciones
        if (empty($quien_paga) || empty($cuenta) || empty($valor) || empty($comprobante_url) || empty($fecha)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if (!filter_var($comprobante_url, FILTER_VALIDATE_URL)) {
            throw new Exception("El enlace del comprobante no es válido");
        }

        // Registrar el pago
        $stmt = $pdo->prepare("INSERT INTO pagos (gasto_id, quien_paga, cuenta, valor, comprobante, Fecha_Pago) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$gasto_id, $quien_paga, $cuenta, $valor, $comprobante_url, $fecha]);

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

        $mensaje = ["tipo" => "success", "texto" => "Pago registrado exitosamente y se programó el pago para el siguiente mes."];
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
    <title>Registrar Pago</title>
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
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-money-bill-wave me-2"></i>Registrar Pago Mensual</h2>
            </div>

            <?php if (isset($mensaje)): ?>
                <div class="alert alert-<?php echo $mensaje['tipo']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje['texto']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="pagoForm" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="gasto_id" class="form-label fw-bold">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Gasto
                    </label>
                    <select class="form-select" id="gasto_id" name="gasto_id">
                        <option value="">Seleccione un gasto...</option>
                        <?php
                        $stmt = $pdo->query("SELECT 
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

                        while ($gasto = $stmt->fetch()) {
                            $valor_formateado = "$" . number_format($gasto['Valor'], 0, '', '.');
                            $detalle_escapado = htmlspecialchars($gasto['Detalle'], ENT_QUOTES, 'UTF-8');
                            $fecha_formateada = date('d/m/Y', strtotime($gasto['Fecha']));
                            echo "<option value='{$gasto['ID']}'>{$fecha_formateada} - {$valor_formateado} - {$detalle_escapado}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="quien_paga" class="form-label fw-bold">
                            <i class="fas fa-user me-2"></i>Quién Paga
                        </label>
                        <input type="text" list="pagadores" class="form-control" id="quien_paga" name="quien_paga" required>
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
                        <input type="text" list="cuentas" class="form-control" id="cuenta" name="cuenta" required>
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
                        required>
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
                        value="<?php echo $fecha_actual_hora_actual ?>"
                        required>
                    <div class="invalid-feedback">Por favor ingrese una fecha válida</div>
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
                        required
                        pattern="https?://.+">
                    <div class="invalid-feedback">Por favor ingrese un enlace válido que comience con http:// o https://</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Registrar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario
        (function() {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Formateo de moneda
        function formatPesoChile(value) {
            value = value.replace(/[^0-9-]/g, '');
            if (value === '') return '';

            if (value.startsWith('-')) {
                return '-' + new Intl.NumberFormat('es-CL', {
                    style: 'currency',
                    currency: 'CLP',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(Math.abs(parseInt(value)));
            } else {
                return new Intl.NumberFormat('es-CL', {
                    style: 'currency',
                    currency: 'CLP',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(parseInt(value));
            }
        }

        const montoInputs = document.querySelectorAll('.valor_formateado');
        montoInputs.forEach(function(input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                let formattedValue = formatPesoChile(value);
                if (formattedValue !== 'CLP 0') {
                    e.target.value = formattedValue;
                }
            });

            input.addEventListener('blur', function(e) {
                let value = e.target.value;
                if (value === '') {
                    e.target.value = '';
                } else {
                    e.target.value = formatPesoChile(value);
                }
            });
        });
    </script>
</body>

</html>