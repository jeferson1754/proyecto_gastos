<!--coment-->
<header>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</header>


<?php
include('bd.php'); // Conexión a la base de datos

try {
    // Establecer la conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener datos del formulario con validación básica
    $descripcion_nombre = $_POST['descripcionIngreso'] ?? '';
    $categoria_nombre = $_POST['categoriaIngreso'] ?? '';
    $categoria_padre = 23; // ID predeterminado de la categoría padre

    $valor = formatearMonto($_POST['monto']);
    $monto = $_POST['monto'] ?? 0;
    $presupuesto_restante = $_POST['presupuesto'] ?? '';

    // Si el checkbox no llega en el POST, asumimos que es 'externo'
    $fuente_dinero = isset($_POST['fuente_dinero']) ? 'sistema' : 'externo';

    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $monto_formateado = formatearNumero($monto);
    $presupuesto_formateado = formatearNumero($presupuesto_restante);

    $resta = $valor - $presupuesto_restante;

    $restante_presupuesto = formatearNumero($resta);

    // Validación de los campos obligatorios
    if (empty($descripcion_nombre) || empty($categoria_nombre) || empty($valor)) {
        throw new Exception('Todos los campos son requeridos.');
    }

    // Iniciar una transacción para asegurarse de que todas las consultas se ejecutan correctamente
    $pdo->beginTransaction();

    // Obtener o crear categoría
    $stmt = $pdo->prepare("SELECT ID FROM categorias_gastos WHERE Nombre = :nombre AND Categoria_Padre = :categoria_padre ");
    $stmt->execute([':nombre' => $categoria_nombre, ':categoria_padre' => $categoria_padre]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($categoria) {
        $categoria_id = $categoria['ID'];
    } else {
        // Si la categoría no existe, insertarla y recuperar el ID
        $stmt = $pdo->prepare("INSERT INTO categorias_gastos (Nombre, Categoria_Padre) VALUES (:nombre, :categoria_padre)");
        $stmt->execute([':nombre' => $categoria_nombre, ':categoria_padre' => $categoria_padre]);
        $categoria_id = $pdo->lastInsertId();
    }

    // Obtener o crear detalle
    $stmt = $pdo->prepare("SELECT ID FROM detalle WHERE Detalle = :nombre");
    $stmt->execute([':nombre' => $descripcion_nombre]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detalle) {
        $detalle_id = $detalle['ID'];
    } else {
        // Si el detalle no existe, insertarlo y recuperar el ID
        $stmt = $pdo->prepare("INSERT INTO detalle (Detalle) VALUES (:nombre)");
        $stmt->execute([':nombre' => $descripcion_nombre]);
        $detalle_id = $pdo->lastInsertId();
    }

    // Insertar el ingreso en la tabla de gastos
    $stmt = $pdo->prepare("
        INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha, Fuente_Dinero)
        VALUES (:detalle_id, :categoria_id, :valor, :fecha, :fuente_dinero)
    ");
    $stmt->execute([
        ':detalle_id' => $detalle_id,
        ':categoria_id' => $categoria_id,
        ':valor' => $valor,
        ':fecha' => $fecha,
        ':fuente_dinero' => $fuente_dinero
    ]);

    // Confirmar la transacción
    $pdo->commit();

    if ($presupuesto_restante < $monto) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM alertas_presupuesto WHERE seccion = :seccion AND mes_alerta = :mes AND anio_alerta = :anio");
        $stmt->execute([
            ':seccion' => $categoria_padre,
            ':mes' => $current_month,
            ':anio' => $current_year
        ]);
        $resultado1 = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado1['total'] == 0) {
            // 2. Si no hay registros, mostrar la alerta
            $alertTitle = '¡Monto Excede!';
            $alertText = "El monto $" . $monto_formateado . " excede el presupuesto de $" . $presupuesto_formateado . " por $" . $restante_presupuesto;
            $alertType = 'warning';

            echo '
            <script>
                Swal.fire({
                    title: "' . $alertTitle . '",
                    html: "' . $alertText . '",
                    icon: "' . $alertType . '",
                    confirmButtonText: "OK",
                }).then(function(result) {
                    if (result.isConfirmed) {
                        window.location.href = "index.php"; 
                    }
                });
            </script>';

            // 3. Registrar la alerta en la base de datos para no mostrarla de nuevo este mes
            $stmt_insert = $pdo->prepare("INSERT INTO alertas_presupuesto (seccion, mes_alerta, anio_alerta, ultima_alerta) VALUES (:seccion, :mes, :anio, NOW())");
            $stmt_insert->execute([
                ':seccion' => $categoria_padre,
                ':mes' => $current_month,
                ':anio' => $current_year
            ]);
            die();
        } else {
            // Si ya hay un registro, simplemente redirigir sin mostrar la alerta
            header("Location: index.php");
            exit;
        }
    }

    $clasificion = clasificarGasto($descripcion_nombre);
    echo $clasificion . "<br>";

    if ($clasificion != "Sin Clasificar") {

        $alertTitle = '¡Se detectó un ' . $clasificion . '!';
        $alertText  = '¿Desea agregar ' . $descripcion_nombre . ' al gestor de tiempos?';
        $alertType  = 'info';
        $redireccion = "window.location='agregar_tiempo.php?nombre=" . urlencode($descripcion_nombre) .
            "&tipo=" . urlencode($clasificion) .
            "&fecha_inicio=" . urlencode($fecha) . "';";
        alerta2($alertTitle, $alertText, $alertType, $redireccion);
        die();
    }


    // Redireccionar al usuario después de una inserción exitosa
    header("Location: index.php");
    exit;
} catch (PDOException $e) {
    // En caso de error de base de datos, deshacer la transacción y mostrar un mensaje
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error de conexión: " . $e->getMessage();
} catch (Exception $e) {
    // En caso de error de validación u otros
    echo "Error: " . $e->getMessage();
}
