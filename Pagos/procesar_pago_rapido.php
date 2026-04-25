<?php
include('../bd.php');
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? 'debito';

    if (!$id) throw new Exception("ID de pago no proporcionado.");

    // 1. Obtener datos del pago actual
    $stmt = $pdo->prepare("SELECT * FROM pagos WHERE ID = ?");
    $stmt->execute([$id]);
    $pago = $stmt->fetch();

    if (!$pago) throw new Exception("Pago no encontrado.");

    // Iniciar Transacción
    $pdo->beginTransaction();

    $configuracionPago = procesarLogicaMetodoPago($pdo, $pago['Valor'], $metodo_pago);
    

    // Ahora usas los valores retornados
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

    $stmt->execute([':gasto_id' => $pago['gasto_id']]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    $categoria_id = (int)$info['ID_Categoria_Gastos'];
    $detalle_id   = (int)$info['ID_Detalle'];
    $modulo       = (int)$info['modulo'];


    if (empty($id)) {
        throw new Exception("Favor ingrese un gasto válido");
    }

    // 3. Actualizar el pago a 'Pagado'
    $sql_update = "UPDATE pagos SET Estado = 'Pagado', Fecha_Pago = ? WHERE ID = ?";
    $pdo->prepare($sql_update)->execute([$fecha_actual_hora_actual, $id]);



    // 4. Actualizar la tabla de gastos asociada (si existe gasto_id)
    if (empty($categoria_id) || empty($detalle_id) || empty($modulo)) {
        throw new Exception("No se pudo obtener la información del gasto asociado.");
    }
    $stmt = $pdo->prepare("
                INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha, fuente_dinero,id_medio_pago)
                VALUES (:detalle_id, :categoria_id, :valor, :fecha, :fuente_dinero, :id_medio_pago)
            ");
    $stmt->execute([
        ':detalle_id' => $detalle_id,
        ':categoria_id' => $categoria_id,
        ':valor' => $pago['Valor'],
        ':fecha' => $fecha_actual_hora_actual,
        ':fuente_dinero' => $origen_dinero,
        ':id_medio_pago' => $id_medio_pago
    ]);

    //5.Crea el gasto para el mes siguiente si el pago es recurrente
    $mes_actual = date('m', strtotime($pago['Fecha_Vencimiento']));
    $fecha_siguiente_mes = ($mes_actual == 2)
        ? date('Y-m-d', strtotime($pago['Fecha_Vencimiento'] . ' +28 days'))
        : date('Y-m-d', strtotime($pago['Fecha_Vencimiento'] . ' +1 month'));

    $tiempo_pago = $pago['Vencimiento'];

    if ($tiempo_pago == 0) {
        // Insertar el registro de pago para el próximo mes
        $stmt_next = $pdo->prepare("INSERT INTO pagos (gasto_id, cuenta, valor, estado, fecha_vencimiento, Vencimiento) 
                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_next->execute([$pago['gasto_id'], $pago['Cuenta'], $pago['Valor'], 'Pendiente', $fecha_siguiente_mes, $tiempo_pago]);

        $mensaje = ["tipo" => "success", "texto" => "Pago actualizado exitosamente y se programó el pago para el siguiente mes."];
    } else if ($tiempo_pago > 1) {
        // Ejecutar la consulta directamente y obtener el resultado
        $query = "SELECT COUNT(*) AS total_cuentas FROM pagos WHERE Cuenta = ?";
        $stmt_count = $conexion->prepare($query);
        $stmt_count->bind_param("s", $cuenta); // 's' para string

        // Ejecutar y obtener el total de cuentas en una sola línea
        $stmt_count->execute();
        $stmt_count->bind_result($total_cuentas);
        $stmt_count->fetch();


        echo $total_cuentas . "<br>";
        if ($total_cuentas < $tiempo_pago) {

            $stmt_next = $pdo->prepare("INSERT INTO pagos (gasto_id, cuenta, valor, estado, fecha_vencimiento, Vencimiento) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_next->execute([$pago['gasto_id'], $pago['Cuenta'], $pago['Valor'], 'Pendiente', $fecha_siguiente_mes, $tiempo_pago]);

            $mensaje = ["tipo" => "success", "texto" => "Pago actualizado exitosamente y se programó el pago para el siguiente mes."];
        } else {
            $mensaje = ["tipo" => "success", "texto" => "Pago actualizado exitosamente y se termino el ciclo de pago"];
        }
    } else {
        $mensaje = ["tipo" => "success", "texto" => "Pago actualizado exitosamente"];
    }


    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'El pago se registró correctamente.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
