<!--coment-->
<header>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</header>
<?php
include('bd.php'); // Conexión a la base de datos
// 1. Recibir datos de la URL (enviados desde la clasificación)



try {
    $nombre = isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : '';
    $tipo   = isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : 'Servicio';
    $fecha  = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d');

    $sql_select = "SELECT* FROM `gestor_tiempos` WHERE nombre='$nombre' ORDER BY `gestor_tiempos`.`id` DESC LIMIT 1;";

    $result = mysqli_query($conexion, $sql_select);

    if ($row = mysqli_fetch_assoc($result)) {
        $id_registro = $row['id'];
        $fecha_fin   = $row['fecha_fin'];
    } else {
        $id_registro = null;
        $fecha_fin   = null;
    }

    $alertText = 'Se creo el registro en gestor de tiempos';

    if ($fecha_fin == "0000-00-00" || empty($fecha_fin)) {

        #actualizar registro
        $sql2 = "UPDATE gestor_tiempos SET 
                fecha_fin = :fecha 
            WHERE id = :id";
        $params = [
            ':fecha'    => $fecha,
            ':id' => $id_registro
        ];

        $stmt = $pdo->prepare($sql2);
        $stmt->execute($params);

        $alertText = 'Se actualizo el registro en gestor de tiempos';
    }

    $sql = "INSERT INTO gestor_tiempos (nombre, tipo, fecha_inicio) 
            VALUES (:nombre, :tipo, :fecha)";
    $params = [
        ':nombre'   => $nombre,
        ':tipo'     => $tipo,
        ':fecha'    => $fecha,
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);


    // Mensaje de éxito
    $alertTitle = '¡Registro Exitoso!';
    $alertType = 'success';
    $redireccion = "window.location='./index.php'";  // Redirigir a la página de deudas o donde desees

    alerta($alertTitle, $alertText, $alertType, $redireccion);
    die();
} catch (PDOException $e) {
    $error = "Error al guardar: " . $e->getMessage();
}
