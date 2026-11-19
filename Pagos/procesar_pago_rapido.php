<?php
include('../bd.php');
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? 'debito';

    if (empty($id)) throw new Exception("ID de pago no proporcionado.");

    // 1. Obtener datos del pago actual
    $stmt = $pdo->prepare("SELECT * FROM pagos WHERE ID = ?");
    $stmt->execute([$id]);
    $pago = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pago) throw new Exception("Pago no encontrado.");
    if ($pago['Estado'] === 'Pagado') throw new Exception("Este pago ya estaba marcado como pagado.");
    if (empty($pago['gasto_id'])) throw new Exception("El pago no tiene un gasto asociado.");

    $es_tarjeta  = esCuentaTarjeta($pago['Cuenta']);
    $valor_pago  = limpiarMontoPago($pago['Valor']);
    $fecha_pago  = $fecha_actual_hora_actual ?? date('Y-m-d H:i:s');
    $tiempo_pago = (int)$pago['Vencimiento'];
    $automatico  = (int)($pago['Automatico'] ?? 0);

    // La tarjeta de crédito siempre se paga con dinero propio (débito/efectivo)
    if ($es_tarjeta) {
        $metodo_pago = 'debito';
    }

    // Iniciar Transacción
    $pdo->beginTransaction();

    // 2. Origen del dinero. Si es crédito, suma el monto a la deuda de la tarjeta
    $configuracionPago = procesarLogicaMetodoPago($pdo, $valor_pago, $metodo_pago, $pago['Cuenta']);
    $origen_dinero = $configuracionPago['origen'];
    $id_medio_pago = $configuracionPago['id_medio'];

    // 3. Categoría y detalle del gasto asociado
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

    if (!$info) throw new Exception("No se pudo obtener la información del gasto asociado.");

    $categoria_id = (int)$info['ID_Categoria_Gastos'];
    $detalle_id   = (int)$info['ID_Detalle'];
    $modulo       = (int)$info['modulo'];

    if (empty($categoria_id) || empty($detalle_id) || empty($modulo)) {
        throw new Exception("No se pudo obtener la información del gasto asociado.");
    }

    // 4. Marcar el pago como 'Pagado'
    $pdo->prepare("UPDATE pagos SET Estado = 'Pagado', Fecha_Pago = ? WHERE ID = ?")
        ->execute([$fecha_pago, $id]);

    // 5. Registrar el gasto (si la tarjeta no tuvo uso este ciclo, no hay nada que registrar)
    if (!($es_tarjeta && $valor_pago <= 0)) {
        $stmt = $pdo->prepare("
                INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha, fuente_dinero, id_medio_pago)
                VALUES (:detalle_id, :categoria_id, :valor, :fecha, :fuente_dinero, :id_medio_pago)
            ");
        $stmt->execute([
            ':detalle_id'    => $detalle_id,
            ':categoria_id'  => $categoria_id,
            ':valor'         => $valor_pago,
            ':fecha'         => $fecha_pago,
            ':fuente_dinero' => $origen_dinero,
            ':id_medio_pago' => $id_medio_pago
        ]);
    }

    // 6. Programar el siguiente ciclo si el pago es recurrente
    //    Vencimiento: 0 = indefinido | 1 = pago único | >1 = cantidad de meses
    $programar_siguiente = false;
    $mensaje = "Pago actualizado exitosamente";

    if ($tiempo_pago === 0) {
        $programar_siguiente = true;
    } elseif ($tiempo_pago > 1) {
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE Cuenta = ?");
        $stmt_count->execute([$pago['Cuenta']]);
        $total_cuentas = (int)$stmt_count->fetchColumn();

        if ($total_cuentas < $tiempo_pago) {
            $programar_siguiente = true;
        } else {
            $mensaje = "Pago actualizado exitosamente y se terminó el ciclo de pago";
        }
    }

    // Evitar duplicar la tarjeta si ya existe otro ciclo pendiente
    // (por ejemplo, uno que se creó solo al registrar un gasto con crédito)
    if ($programar_siguiente && $es_tarjeta) {
        $stmt_dup = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE Cuenta = ? AND Estado = 'Pendiente' AND ID != ?");
        $stmt_dup->execute([$pago['Cuenta'], $id]);
        if ((int)$stmt_dup->fetchColumn() > 0) {
            $programar_siguiente = false;
            $mensaje = "Pago actualizado exitosamente";
        }
    }

    if ($programar_siguiente) {
        // La tarjeta vence siempre el día 10 y el nuevo ciclo parte en $0
        // (el uso nuevo se irá sumando); el resto repite su monto.
        $fecha_siguiente = siguienteVencimiento($pago['Fecha_Vencimiento'], $pago['Cuenta']);
        $valor_siguiente = $es_tarjeta ? 0 : $pago['Valor'];

        $stmt_next = $pdo->prepare("INSERT INTO pagos (gasto_id, quien_paga, cuenta, valor, estado, fecha_vencimiento, Vencimiento, Automatico) 
                VALUES (?, ?, ?, ?, 'Pendiente', ?, ?, ?)");
        $stmt_next->execute([
            $pago['gasto_id'],
            $pago['quien_paga'],
            $pago['Cuenta'],
            $valor_siguiente,
            $fecha_siguiente,
            $tiempo_pago,
            $automatico
        ]);

        $mensaje .= " y se programó el pago para el " . date('d/m/Y', strtotime($fecha_siguiente)) . ".";
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $mensaje]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}