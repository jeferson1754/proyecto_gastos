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
    $valor = $_POST['monto'] ?? '';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');

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
        INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha)
        VALUES (:detalle_id, :categoria_id, :valor, :fecha)
    ");
    $stmt->execute([
        ':detalle_id' => $detalle_id,
        ':categoria_id' => $categoria_id,
        ':valor' => $valor,
        ':fecha' => $fecha
    ]);

    // Confirmar la transacción
    $pdo->commit();

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
