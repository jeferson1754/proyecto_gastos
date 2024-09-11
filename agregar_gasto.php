<?php
include('bd.php'); // Conexión a la base de datos

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener datos del formulario
    $descripcion_nombre = $_POST['descripcionIngreso'] ?? '';
    $categoria_nombre = $_POST['categoriaIngreso'] ?? '';
    $valor = $_POST['monto'] ?? '';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');

    // Validar datos
    if (empty($descripcion_nombre) || empty($categoria_nombre) || empty($valor)) {
        throw new Exception('Todos los campos son requeridos.');
    }

    // Obtener o crear categoría
    $stmt = $pdo->prepare("SELECT ID FROM categorias_gastos WHERE Nombre = :nombre");
    $stmt->execute([':nombre' => $categoria_nombre]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($categoria) {
        $categoria_id = $categoria['ID'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO categorias_gastos (Nombre) VALUES (:nombre)");
        $stmt->execute([':nombre' => $categoria_nombre]);
        $categoria_id = $pdo->lastInsertId();
    }

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

    // Insertar ingreso
    $stmt = $pdo->prepare("INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha) VALUES (:detalle_id, :categoria_id, :valor, :fecha)");
    $stmt->execute([
        ':detalle_id' => $detalle_id,
        ':categoria_id' => $categoria_id,
        ':valor' => $valor,
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

?>
