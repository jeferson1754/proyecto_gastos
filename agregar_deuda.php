<!--coment-->
<header>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</header>

<?php
include('bd.php'); // Conexión a la base de datos
if (isset($_GET['id_deudor'])) {
    $id_deudor = $_GET['id_deudor'];  // Obtener el valor de 'id_deudor'
}

$valor = formatearMonto($_GET['monto']);

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
    // Establecer la conexión a la base de datos con PDO
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener datos del formulario con validación básica
    $id_detalle = 1;

    // Iniciar una transacción para asegurarse de que todas las consultas se ejecutan correctamente
    $pdo->beginTransaction();

    // Insertar el ingreso en la tabla de deudas
    $stmt = $pdo->prepare("
        INSERT INTO deudas (ID_Deudor, ID_Detalle, Monto, Fecha_Deuda)
        VALUES (:id_deudor, :id_detalle, :valor, :fecha)
    ");

    $stmt->execute([
        ':id_deudor' => $id_deudor,
        ':id_detalle' => $id_detalle,
        ':valor' => $valor,
        ':fecha' => $fecha_actual_hora_actual
    ]);

    // Actualizar el total de deuda para todos los deudores
    $stmt_update = $pdo->prepare("
        UPDATE deudor
        SET Total_Deuda = (
            SELECT SUM(Monto) 
            FROM deudas
            WHERE deudor.ID = deudas.ID_Deudor
        )
    ");
    $stmt_update->execute();

    // Confirmar la transacción
    $pdo->commit();

    // Mensaje de éxito
    $alertTitle = '¡Deuda Agregada Exitosamente!';
    $alertText = 'La deuda fue agregada correctamente al módulo de deudas';
    $alertType = 'success';
    $redireccion = "window.location='./index.php'";  // Redirigir a la página de deudas o donde desees

    alerta($alertTitle, $alertText, $alertType, $redireccion);
    die();
} catch (Exception $e) {
    // Si ocurre un error, revertir la transacción
    $pdo->rollBack();
    // Log y manejo de errores
    error_log($e->getMessage());
    echo 'Error en la transacción. Intenta nuevamente.';
}
