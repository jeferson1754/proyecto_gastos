<?php
// Asumimos que tienes la conexión a la base de datos en 'bd.php'.
include('../bd.php');

$id = $_GET['id'] ?? null;


// Obtener los datos del pago si el ID está presente
if ($id) {
    $sql = "SELECT p.*, d.Detalle AS descripcion_gasto
            FROM pagos p
            LEFT JOIN gastos g ON p.gasto_id = g.ID 
            LEFT JOIN detalle d ON g.ID_Detalle = d.ID
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
        $quien_paga = $_POST['quien_paga'] ?? '';
        $cuenta = $_POST['cuenta'] ?? '';
        $fecha = !empty($_POST['fecha']) ? $_POST['fecha'] : $fecha_actual_hora_actual;
        $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';
        $estado = $_POST['estado'] ?? '';
        $estado_antiguo = $_POST['estado_antiguo'] ?? '';
        $valor  = formatearMonto($_POST['valor'] ?? '0');
        $comprobante_url = $_POST['comprobante_url'] ?? '';
        $tiempo_pago = (int)($_POST['tiempo_pago'] ?? 0);
        $metodo_pago = $_POST['metodo_pago'] ?? 'debito';
        $automatico = isset($_POST['automatico']) ? 1 : 0;

        $es_tarjeta = esCuentaTarjeta($cuenta);
        $pasa_a_pagado = ($estado == 'Pagado' && $estado_antiguo != 'Pagado');

        // Validaciones (la tarjeta puede quedar en $0 cuando aún no tiene uso)
        if (empty($quien_paga) || empty($cuenta) || empty($estado) || empty($fecha_vencimiento) || ($valor <= 0 && !$es_tarjeta)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if (empty($gasto_id)) {
            throw new Exception("Favor ingrese un gasto válido");
        }

        if (!empty($comprobante_url)) {
            if (!filter_var($comprobante_url, FILTER_VALIDATE_URL)) {
                throw new Exception("El enlace del comprobante no es válido");
            }
        }

        $pdo->beginTransaction();

        // El crédito y el gasto solo se registran al pasar de Pendiente a Pagado
        // (antes se sumaba a la tarjeta en cualquier guardado, incluso sin pagar)
        if ($pasa_a_pagado) {
            // La tarjeta siempre se paga con dinero propio
            if ($es_tarjeta) {
                $metodo_pago = 'debito';
            }

            // Si es crédito, suma el valor a la deuda de la tarjeta
            $configuracionPago = procesarLogicaMetodoPago($pdo, $valor, $metodo_pago, $cuenta);
            $origen_dinero = $configuracionPago['origen'];
            $id_medio_pago = $configuracionPago['id_medio'];

            $stmt = $pdo->prepare("
                SELECT 
                    g.ID_Categoria_Gastos,
                    g.ID_Detalle,
                    c.Categoria_Padre AS modulo
                FROM gastos g
                INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                WHERE g.ID = :gasto_id
            ");
            $stmt->execute([':gasto_id' => $gasto_id]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$info) {
                throw new Exception("No se pudo obtener la información del gasto asociado.");
            }

            $categoria_id = (int)$info['ID_Categoria_Gastos'];
            $detalle_id   = (int)$info['ID_Detalle'];
            $modulo       = (int)$info['modulo'];

            if (empty($categoria_id) || empty($detalle_id) || empty($modulo)) {
                throw new Exception("No se pudo obtener la información del gasto asociado.");
            }

            // Registrar el gasto (si la tarjeta no tuvo uso, no hay nada que registrar)
            if (!($es_tarjeta && $valor <= 0)) {
                $stmt = $pdo->prepare("
                    INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha, fuente_dinero, id_medio_pago)
                    VALUES (:detalle_id, :categoria_id, :valor, :fecha, :fuente_dinero, :id_medio_pago)
                ");
                $stmt->execute([
                    ':detalle_id' => $detalle_id,
                    ':categoria_id' => $categoria_id,
                    ':valor' => $valor,
                    ':fecha' => $fecha,
                    ':fuente_dinero' => $origen_dinero,
                    ':id_medio_pago' => $id_medio_pago
                ]);
            }
        }

        // Actualizar el pago
        $stmt = $pdo->prepare("UPDATE pagos SET gasto_id = ?, quien_paga = ?, cuenta = ?, valor = ?, comprobante = ?, Fecha_Pago = ?, Fecha_Vencimiento = ?, Estado = ?, Vencimiento = ?, Automatico = ? WHERE ID = ?");
        $stmt->execute([$gasto_id, $quien_paga, $cuenta, $valor, $comprobante_url, $fecha, $fecha_vencimiento, $estado, $tiempo_pago, $automatico, $id]);

        $mensaje = ["tipo" => "success", "texto" => "Pago actualizado exitosamente"];

        // Programar el siguiente ciclo solo cuando el pago pasa a 'Pagado'
        if ($pasa_a_pagado) {
            $programar_siguiente = false;

            if ($tiempo_pago == 0) {
                $programar_siguiente = true;
            } else if ($tiempo_pago > 1) {
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE Cuenta = ?");
                $stmt_count->execute([$cuenta]);
                $total_cuentas = (int)$stmt_count->fetchColumn();

                if ($total_cuentas < $tiempo_pago) {
                    $programar_siguiente = true;
                } else {
                    $mensaje = ["tipo" => "success", "texto" => "Pago actualizado exitosamente y se terminó el ciclo de pago"];
                }
            }

            // Evitar duplicar la tarjeta si ya existe otro ciclo pendiente
            if ($programar_siguiente && $es_tarjeta) {
                $stmt_dup = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE Cuenta = ? AND Estado = 'Pendiente' AND ID != ?");
                $stmt_dup->execute([$cuenta, $id]);
                if ((int)$stmt_dup->fetchColumn() > 0) {
                    $programar_siguiente = false;
                }
            }

            if ($programar_siguiente) {
                // La tarjeta vence siempre el día 10 y el nuevo ciclo parte en $0
                $fecha_siguiente_mes = siguienteVencimiento($fecha_vencimiento, $cuenta);
                $valor_siguiente = $es_tarjeta ? 0 : $valor;

                $stmt_next = $pdo->prepare("INSERT INTO pagos (gasto_id, quien_paga, cuenta, valor, estado, fecha_vencimiento, fecha_pago, Vencimiento, Automatico) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_next->execute([$gasto_id, $quien_paga, $cuenta, $valor_siguiente, 'Pendiente', $fecha_siguiente_mes, "0000-00-00 00:00:00", $tiempo_pago, $automatico]);

                $mensaje = ["tipo" => "success", "texto" => "Pago actualizado exitosamente y se programó el pago para el " . date('d/m/Y', strtotime($fecha_siguiente_mes)) . "."];
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
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

                    <?php
                    $sql = "SELECT 
                            MIN(g.ID) AS ID,
                            d.Detalle AS Descripcion,
                            g.ID_Categoria_Gastos,
                            c.Nombre AS categoria,
                            c.Categoria_Padre AS tipo
                        FROM gastos g
                        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                        INNER JOIN detalle d ON g.ID_Detalle = d.ID
                        AND c.Categoria_Padre != 2
                        AND c.Nombre NOT IN ('Comida', 'Familiar', 'Compras','Laboral','Mascotas','Prestamos','Transporte')
                        GROUP BY d.Detalle
                        ORDER BY categoria ASC;
                        ";

                    $stmt = $pdo->query($sql);
                    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    function nombreModulo($modulo)
                    {
                        return match ($modulo) {
                            23 => 'Gasto',
                            24 => 'Ocio',
                            default => 'Otro'
                        };
                    }
                    $gastoSeleccionado = isset($resultado['gasto_id']) && is_numeric($resultado['gasto_id'])
                        ? (int)$resultado['gasto_id']
                        : 0;


                    ?>


                    <select name="gasto_id" id="gasto_id" class="form-select">

                        <option value="" <?= $gastoSeleccionado === 0 ? 'selected' : '' ?>>
                            Seleccione un gasto
                        </option>

                        <?php foreach ($datos as $fila): ?>
                            <option
                                value="<?= (int)$fila['ID']; ?>"
                                data-categoria="<?= htmlspecialchars($fila['ID_Categoria_Gastos']); ?>"
                                data-modulo="<?= htmlspecialchars($fila['tipo']); ?>"
                                <?= $gastoSeleccionado === (int)$fila['ID'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($fila['Descripcion']); ?>
                                - <?= htmlspecialchars($fila['categoria']); ?>
                                / <?= nombreModulo((int)$fila['tipo']); ?>
                            </option>
                        <?php endforeach; ?>

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

                <div class="contenedor-logica-pago-especial mb-4">
                    <div class="p-3 border rounded bg-light shadow-sm">
                        <label class="form-label d-block fw-bold mb-3 text-uppercase small text-muted">
                            ¿Cómo se realizó este pago?
                        </label>

                        <div class="row g-3">
                            <div class="col-6">
                                <input type="radio" class="btn-check radio-metodo" name="metodo_pago" id="metodo_debito" value="debito" checked>
                                <label class="btn btn-outline-primary w-100 p-3" for="metodo_debito">
                                    <i class="fas fa-wallet d-block mb-1"></i> Débito / Efectivo
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check radio-metodo" name="metodo_pago" id="metodo_credito" value="credito">
                                <label class="btn btn-outline-danger w-100 p-3" for="metodo_credito">
                                    <i class="fas fa-credit-card d-block mb-1"></i> Tarjeta Crédito
                                </label>
                            </div>
                        </div>

                        <div class="mt-3 p-2 rounded bg-white border-start border-4 caja-aviso" style="border-color: #0d6efd;">
                            <small class="text-muted texto-aviso">
                                El gasto se descontará de tu presupuesto mensual inmediatamente.
                            </small>
                        </div>
                    </div>
                </div>


                <div class="row">

                    <div class="col-md-6 mb-4">
                        <label for="fecha" class="form-label fw-bold">
                            <i class="fas fa-hourglass-half me-2"></i>Fecha de Vencimiento
                        </label>
                        <input type="date"
                            class="form-control"
                            id="fecha_vencimiento"
                            name="fecha_vencimiento"
                            value="<?php echo date('Y-m-d', strtotime($resultado['Fecha_Vencimiento'])); ?>"
                            required>
                        <div class="invalid-feedback">Por favor ingrese una fecha válida</div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label for="fecha" class="form-label fw-bold">
                            <i class="fas fa-calendar me-2"></i>Fecha de Pago
                        </label>
                        <input type="datetime-local"
                            class="form-control"
                            id="fecha"
                            name="fecha"
                            value="<?php echo date('Y-m-d\TH:i', strtotime($resultado['Fecha_Pago'])); ?>">
                        <div class="invalid-feedback">Por favor ingrese una fecha válida</div>
                    </div>

                </div>


                <div class="mb-4">
                    <label for="estado" class="form-label fw-bold">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Estado
                    </label>

                    <select class="form-select" id="estado" name="estado" required>
                        <option value="">Seleccione un estado...</option>
                        <?php
                        // Ejecutar la consulta
                        $query = "SELECT DISTINCT Estado FROM `pagos`;";

                        // Verificar si la consulta se ejecutó correctamente
                        if ($stmt3 = $conexion->query($query)) {
                            // Usar mysqli_fetch_assoc para obtener los resultados
                            while ($estado = $stmt3->fetch_assoc()) {
                                $selected = ($estado['Estado'] == $resultado['Estado']) ? 'selected' : '';
                                echo "<option value='{$estado['Estado']}' {$selected}>{$estado['Estado']}</option>";
                            }
                        } else {
                            // Si la consulta falla, mostrar un mensaje de error
                            echo "Error en la consulta: " . $conexion->error;
                        }
                        ?>
                    </select>
                    <div class="invalid-feedback">Por favor ingrese un estado válido</div>
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


                <div class="mb-4">
                    <label for="tiempo_pago" class="form-label fw-bold">
                        <i class="fas fa-calendar-alt me-2"></i>Tiempo de Pago (en meses)
                    </label>
                    <input type="number"
                        class="form-control"
                        id="tiempo_pago"
                        name="tiempo_pago"
                        placeholder="Ingrese meses (0 = Indefinido)"
                        min="0"
                        step="1"
                        value="<?php echo htmlspecialchars($resultado['Vencimiento'], ENT_QUOTES, 'UTF-8'); ?>"
                        max="120"
                        required>
                    <div class="form-text">Ingrese el número de meses. Use "0" para indicar un pago indefinido.</div>
                    <div class="invalid-feedback">Ingrese un número válido de meses (0 o 120 meses).</div>
                </div>

                <div class="mb-4 p-3 border rounded bg-light">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="automatico" name="automatico" value="1"
                            <?php echo !empty($resultado['Automatico']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="automatico">
                            <i class="fas fa-rotate me-2"></i>Pago automático
                        </label>
                    </div>
                    <div class="form-text">Se cobra solo (por ejemplo, cargo a la tarjeta o cuenta). No aparecerá en las alertas del inicio.</div>
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