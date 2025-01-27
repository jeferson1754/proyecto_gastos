<?php
include('bd.php'); // Conexión a la base de datos

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener datos del formulario
    $descripcion_nombre = $_POST['descripcionIngreso'] ?? '';
    $valor = formatearMonto($_POST['monto']);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');


    // Obtener el tipo de categoría seleccionada
    $categoria_principal = $_POST['categoriaIngresoPrincipal'];


    // Verificar qué categoría fue seleccionada
    if ($categoria_principal === 'Ingresos') {
        // Procesar como ingreso
        $categoria_nombre = 'Ingresos';
        $categoria_padre = 1;
    } elseif ($categoria_principal === 'Gastos') {
        // Procesar como gasto
        $categoria_nombre = $_POST['categoriaGasto'];
        $categoria_padre = 23;
    } elseif ($categoria_principal === 'Ocio') {
        // Procesar como ocio
        $categoria_nombre = $_POST['categoriaOcio'];
        $categoria_padre = 24;
    } elseif ($categoria_principal === 'Ahorros') {
        // Procesar como ahorro
        $categoria_nombre = $_POST['categoriaAhorro'];
        $categoria_padre = 2;
    }


    // Validar datos
    if (empty($descripcion_nombre) || empty($categoria_principal) || empty($valor)) {
        throw new Exception('Todos los campos son requeridos.');
    }


    echo "Categoria Padre:" . $categoria_principal . "<br> Nombre_Categoria:" . "$categoria_nombre" . "<br>";

    // Obtener o crear categoría
    // Obtener o crear categoría
    $stmt = $pdo->prepare("SELECT ID FROM categorias_gastos WHERE Nombre = :nombre");
    $stmt->execute([':nombre' => $categoria_nombre]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($categoria) {
        $categoria_id = $categoria['ID'];
    } else {
        // Si la categoría no existe, insertarla y recuperar el ID
        $stmt = $pdo->prepare("INSERT INTO categorias_gastos (Nombre, Categoria_Padre) VALUES (:nombre, :categoria_padre)");
        $stmt->execute([':nombre' => $categoria_nombre, ':categoria_padre' => $categoria_padre]);
        $categoria_id = $pdo->lastInsertId();
    }

    echo "ID_Categoria:" . $categoria_id;

    // Obtener o crear detalle
    $stmt = $pdo->prepare("SELECT ID FROM detalle WHERE Detalle = :nombre");
    $stmt->execute([':nombre' => $descripcion_nombre]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detalle) {
        $detalle_id = $detalle['ID'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO detalle (Detalle) VALUES (:nombre)");
        $stmt->execute([':nombre' => $descripcion_nombre]);
        $detalle_id = $pdo->lastInsertId();
    }

    // Determinar el valor correcto basado en la categoría
    $nuevo_valor = ($categoria_nombre != "Ingresos") ? "-$valor" : $valor;

    $stmt = $pdo->prepare("
        INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha)
        VALUES (:detalle_id, :categoria_id, :valor, :fecha)
    ");
    $stmt->execute([
        ':detalle_id' => $detalle_id,
        ':categoria_id' => $categoria_id,
        ':valor' => $nuevo_valor,
        ':fecha' => $fecha
    ]);


    // Redireccionar
    header("Location: index.php");
    exit;
} catch (PDOException $e) {
    // Error de conexión
    echo "Error de conexión: " . $e->getMessage();
} catch (Exception $e) {
    // Error de validación u otros
    echo "Error: " . $e->getMessage();
}
