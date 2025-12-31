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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Editar Registro</title>

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            --card-shadow-hover: 0 30px 60px rgba(0, 0, 0, 0.15);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            padding: 2rem 0;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .container {
            position: relative;
            z-index: 1;
        }

        .edit-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 3rem;
            margin-top: 2rem;
            transition: var(--transition);
            animation: slideUp 0.6s ease-out;
        }

        .edit-container:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-2px);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header {
            color: white;
            margin-bottom: 2rem;
            text-align: center;
            animation: fadeInDown 0.8s ease-out;
        }

        .page-header h2 {
            font-weight: 700;
            font-size: 2.5rem;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 0.5rem;
        }

        .page-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            position: relative;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: #667eea;
            font-size: 1rem;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            border: 2px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            font-size: 1rem;
            transition: var(--transition);
            position: relative;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
            background: white;
        }

        .input-group {
            position: relative;
        }

        .input-group-text {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px 0 0 12px;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .btn-action {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
            border: none;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-action:hover::before {
            left: 100%;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            animation: slideInRight 0.5s ease-out;
        }

        .alert-success {
            background: var(--success-gradient);
            color: white;
        }

        .alert-danger {
            background: var(--secondary-gradient);
            color: white;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .form-section {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
        }

        .form-section:hover {
            background: rgba(255, 255, 255, 0.7);
            transform: translateY(-2px);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: #667eea;
        }

        /* Floating labels effect */
        .floating-label {
            position: relative;
        }

        .floating-label input:focus+label,
        .floating-label input:not(:placeholder-shown)+label {
            transform: translateY(-1.5rem) scale(0.8);
            color: #667eea;
        }

        .floating-label label {
            position: absolute;
            left: 1.25rem;
            top: 1rem;
            transition: var(--transition);
            pointer-events: none;
            color: #6b7280;
        }

        /* Category option styling */
        .form-select option {
            padding: 0.5rem;
            font-weight: 500;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .edit-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }

            .page-header h2 {
                font-size: 2rem;
            }

            .btn-action {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.5);
        }

        /* Loading animation for buttons */
        .btn-loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
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

        /* Estilo para agrandar el switch */
        .form-check-input-lg {
            width: 3.5rem !important;
            height: 1.75rem !important;
            cursor: pointer;
        }

        .form-check-label-lg {
            padding-top: 0.25rem;
            margin-left: 0.75rem;
            font-size: 1.1rem;
            cursor: pointer;
        }

        /* Colores dinámicos */
        .switch-dinero:checked {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        .switch-dinero:not(:checked) {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='white'/%3e%3c/svg%3e") !important;
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
                       c.Nombre as categoria, g.Fecha, c.Categoria_Padre as tipo, g.Fuente_Dinero as metodo_pago
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
        $nueva_categoria = $_POST['sub_categoria'];
        $nueva_descripcion = $_POST['descripcion'];
        $nueva_fecha = $_POST['fecha'];
        // Si el switch está apagado, no se envía en el POST, por eso usamos isset
        $fuente_dinero = isset($_POST['fuente_dinero']) ? 'sistema' : 'externo';

        // Luego añade $fuente_dinero a tu consulta UPDATE de la tabla pagos o gastos
        // Ejemplo: $stmt = $pdo->prepare("UPDATE pagos SET metodo_pago = ? WHERE gasto_id = ?");

        try {
            // Establecer la conexión a la base de datos
            $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->beginTransaction();

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
                        Fecha = :nueva_fecha,
                        fuente_dinero = :fuente_dinero
                        WHERE ID = :id";

            $stmtGasto = $pdo->prepare($sqlGasto);

            $stmtGasto->execute([
                ':nuevo_valor' => $nuevo_valor,
                ':nueva_categoria' => $nueva_categoria,
                ':id_detalle' => $id_detalle,
                ':nueva_fecha' => $nueva_fecha,
                ':fuente_dinero' => $fuente_dinero,
                ':id' => $id
            ]);
            // Confirmar transacción

            $pdo->commit();
            $mensaje = "¡Registro actualizado exitosamente! 🎉";

            // Recargar datos actualizados
            $stmt = $pdo->prepare("SELECT g.ID, d.Detalle AS Descripcion, g.Valor, g.ID_Categoria_Gastos, 
                       c.Nombre as categoria, g.Fecha, c.Categoria_Padre as tipo, g.Fuente_Dinero as metodo_pago
                FROM gastos g
                INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
                INNER JOIN detalle d ON g.ID_Detalle = d.ID
                WHERE g.ID = :id");
            $stmt->execute([':id' => $id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
    ?>

    <div class="container">
        <div class="page-header">
            <h2><i class="fas fa-edit me-3"></i>Editar Registro</h2>
            <p class="subtitle">Modifica los datos del registro seleccionado</p>
        </div>

        <div class="edit-container">
            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Información Básica
                            </div>

                            <div class="form-group">
                                <label for="descripcion" class="form-label">
                                    <i class="fas fa-tag"></i>
                                    Descripción
                                </label>
                                <input type="text"
                                    list="detalles"
                                    class="form-control"
                                    id="descripcion"
                                    name="descripcion"
                                    value="<?php echo htmlspecialchars($resultado['Descripcion'] ?? ''); ?>"
                                    placeholder="Ingresa una descripción detallada"
                                    required>
                                <datalist id="detalles">
                                    <?php if (isset($detalles)): foreach ($detalles as $detalle): ?>
                                            <option value="<?php echo htmlspecialchars($detalle['Detalle']); ?>">
                                        <?php endforeach;
                                    endif; ?>
                                </datalist>
                            </div>

                            <div class="form-group">
                                <label for="valor" class="form-label">
                                    <i class="fas fa-dollar-sign"></i>
                                    Valor
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </span>
                                    <input type="text"
                                        class="form-control valor_formateado"
                                        id="valor"
                                        name="valor"
                                        value="<?php echo $resultado['Valor'] ?? ''; ?>"
                                        placeholder="0"
                                        required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="fecha" class="form-label">
                                    <i class="fas fa-calendar-alt"></i>
                                    Fecha y Hora
                                </label>
                                <input type="datetime-local"
                                    class="form-control"
                                    id="fecha"
                                    name="fecha"
                                    value="<?php echo $resultado['Fecha'] ?? ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-cogs"></i>
                                Configuración
                            </div>

                            <div class="form-section border-4 contenedor-fuente">
                                <div class="section-title">
                                    <i class="fas fa-exchange-alt"></i> Origen de los Fondos
                                </div>

                                <div class="form-check form-switch d-flex align-items-center">
                                    <input class="form-check-input form-check-input-lg switch-dinero" type="checkbox" role="switch"
                                        id="fuenteDineroSwitch" name="fuente_dinero" value="sistema"
                                        <?php echo ($resultado['metodo_pago'] !== 'externo') ? 'checked' : ''; ?>>

                                    <label class="form-check-label form-check-label-lg fw-semibold label-fuente" for="fuenteDineroSwitch">
                                        <?php if ($resultado['metodo_pago'] !== 'externo'): ?>
                                            <i class="fas fa-university me-2 text-primary"></i> Dinero del Sistema
                                        <?php else: ?>
                                            <i class="fas fa-wallet me-2 text-secondary"></i> Efectivo Externo
                                        <?php endif; ?>
                                    </label>
                                </div>

                                <div class="mt-3 p-2 rounded-2 bg-white border-start border-4 caja-ayuda"
                                    style="border-color: <?php echo ($resultado['metodo_pago'] !== 'externo') ? '#0d6efd' : '#6c757d'; ?>;">
                                    <small class="text-muted texto-ayuda">
                                        <?php echo ($resultado['metodo_pago'] !== 'externo')
                                            ? 'Este gasto será restado automáticamente de tu <strong>Balance Mensual</strong>.'
                                            : 'Gasto <strong>Informativo</strong>: No afecta tus cuentas ni tu balance.'; ?>
                                    </small>
                                </div>
                            </div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const switchDinero = document.getElementById('fuenteDineroSwitch');

                                    if (switchDinero) {
                                        switchDinero.addEventListener('change', function() {
                                            // Buscamos los elementos dentro del contenedor para cambiarlos
                                            const contenedor = this.closest('.contenedor-fuente');
                                            const label = contenedor.querySelector('.label-fuente');
                                            const ayuda = contenedor.querySelector('.texto-ayuda');
                                            const boxAyuda = contenedor.querySelector('.caja-ayuda');

                                            if (this.checked) {
                                                // CAMBIO EN TIEMPO REAL A MODO SISTEMA
                                                label.innerHTML = '<i class="fas fa-university me-2 text-primary"></i> Dinero del Sistema';
                                                ayuda.innerHTML = 'Este gasto será restado automáticamente de tu <strong>Balance Mensual</strong>.';
                                                boxAyuda.style.borderColor = '#0d6efd'; // Azul
                                            } else {
                                                // CAMBIO EN TIEMPO REAL A MODO EXTERNO
                                                label.innerHTML = '<i class="fas fa-wallet me-2 text-secondary"></i> Efectivo Externo';
                                                ayuda.innerHTML = 'Gasto <strong>Informativo</strong>: No afecta tus cuentas ni tu balance.';
                                                boxAyuda.style.borderColor = '#6c757d'; // Gris
                                            }
                                        });
                                    }
                                });
                            </script>

                            <div class="form-group">
                                <label for="categoria" class="form-label">
                                    <i class="fas fa-list"></i>
                                    Categoría
                                </label>
                                <select name="sub_categoria" id="sub_categoria" class="form-select">
                                    <option value="">Selecciona una Categoría</option>
                                    <?php

                                    $sub_categoria = htmlspecialchars($resultado['categoria'] ?? '');

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
                                                $valor = htmlspecialchars($cat['ID'], ENT_QUOTES);
                                                $selected = (isset($sub_categoria) && $sub_categoria === $cat['Nombre']) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $valor ?>"
                                                    class="text-<?php echo $config['clase']; ?>"
                                                    <?php echo $selected; ?>>
                                                    [<?php echo $config['tipo']; ?>] <?php echo $cat['Nombre']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>


                        </div>
                    </div>
                </div>

                <div class="d-flex gap-3 justify-content-end mt-4 flex-wrap">
                    <a href="./general.php" class="btn btn-secondary btn-action">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                    <button type="submit" class="btn btn-primary btn-action" id="submitBtn">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Validación de formulario con efectos visuales
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        const submitBtn = document.getElementById('submitBtn');

                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()

                            // Shake animation for invalid form
                            form.style.animation = 'shake 0.5s ease-in-out';
                            setTimeout(() => {
                                form.style.animation = '';
                            }, 500);
                        } else {
                            // Add loading state to button
                            submitBtn.classList.add('btn-loading');
                            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
                        }

                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        // Shake animation keyframes
        const shakeKeyframes = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
        `;
        const styleSheet = document.createElement('style');
        styleSheet.textContent = shakeKeyframes;
        document.head.appendChild(styleSheet);

        // Función mejorada para formatear el número como pesos chilenos
        function formatPesoChile(value) {
            // Limpiar el valor manteniendo solo números y el signo negativo
            let cleanValue = value.replace(/[^0-9-]/g, '');

            if (cleanValue === '' || cleanValue === '-') {
                return cleanValue;
            }

            // Manejar números negativos
            const isNegative = cleanValue.startsWith('-');
            if (isNegative) {
                cleanValue = cleanValue.substring(1);
            }

            // Formatear el número
            const formatted = new Intl.NumberFormat('es-CL').format(parseInt(cleanValue) || 0);

            return isNegative ? '-$' + formatted : '$' + formatted;
        }

        // Aplicar formato a los campos de valor
        const montoInputs = document.querySelectorAll('.valor_formateado');
        montoInputs.forEach(function(input) {
            // Formatear valor inicial
            if (input.value) {
                input.value = formatPesoChile(input.value);
            }

            // Evento para formatear mientras se escribe
            input.addEventListener('input', function(e) {
                const cursorPosition = e.target.selectionStart;
                const oldValue = e.target.value;
                const newValue = formatPesoChile(oldValue);

                e.target.value = newValue;

                // Mantener posición del cursor
                const newCursorPosition = cursorPosition + (newValue.length - oldValue.length);
                e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            });

            // Efecto de enfoque
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Efectos de hover y focus para todos los inputs
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.closest('.form-group').style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.closest('.form-group').style.transform = 'translateY(0)';
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Smooth scroll to top after form submission
        if (window.location.hash) {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>