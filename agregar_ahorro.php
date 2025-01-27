<!--coment-->
<header>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</header>

<?php
include('bd.php'); // Conexión a la base de datos

function alerta($alertTitle, $alertText, $alertType, $redireccion)
{

    echo '
 <script>
        Swal.fire({
            title: "' . $alertTitle . '",
            text: "' . $alertText . '",
            html: "' . $alertText . '",
            icon: "' . $alertType . '",
            showCancelButton: false,
            confirmButtonText: "OK",
            closeOnConfirm: false
        }).then(function() {
          ' . $redireccion . '  ; // Redirigir a la página principal
        });
    </script>';
}
try {
    // Establecer la conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener datos del formulario con validación básica
    $descripcion_nombre = $_POST['descripcionIngreso'] ?? '';
    $categoria_nombre = $_POST['categoriaIngreso'] ?? '';
    $categoria_padre = 2; // ID predeterminado de la categoría padre
    $valor = formatearMonto($_POST['monto']);

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


    if ($categoria_nombre == "Prestamos" || $categoria_id == 17) {

        $posicion = strpos($descripcion_nombre, "a");  // Encuentra la posición de "a"

        preg_match("/a\s+(.*)/", $descripcion_nombre, $coincidencias);

        // El nombre estará en la primera posición del array de coincidencias
        $nombre = $coincidencias[1];

        // Preparamos la consulta para evitar inyecciones SQL
        $query = $conexion->prepare("SELECT * FROM deudor WHERE nombre LIKE ?");
        $nombre_like = "%" . $nombre . "%";
        $query->bind_param("s", $nombre_like);  // El parámetro "s" indica que es una cadena

        // Ejecutamos la consulta
        $query->execute();
        $resultado = $query->get_result();

        // Verificamos si la consulta devuelve resultados
        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                // Aquí puedes manejar los resultados de la consulta
                $id_deudor = $fila['ID'];
            }
        }

        $alertTitle = '¡Agregar Deuda!';
        $alertText = 'Se detecto un prestamo, desea agregarlo al modulo de deudas correspondiente?';
        $alertType = 'info';
        $redireccion = "window.location='agregar_deuda.php?id_deudor=" . urlencode($id_deudor) . "&monto=" . urlencode($valor) . "';";


        alerta($alertTitle, $alertText, $alertType, $redireccion);
        die();
    } else {
        // Redireccionar al usuario después de una inserción exitosa
        header("Location: index.php");
        exit;
    }
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
