<?php
include('../bd.php');
header('Content-Type: application/json');

try {
    $id = $_POST['id'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? 'debito';

    if (empty($id)) throw new Exception("ID de pago no proporcionado.");

    $stmt = $pdo->prepare("SELECT * FROM pagos WHERE ID = ?");
    $stmt->execute([$id]);
    $pago = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pago) throw new Exception("Pago no encontrado.");

    $fecha_pago = $fecha_actual_hora_actual ?? date('Y-m-d H:i:s');

    $pdo->beginTransaction();
    $mensaje = registrarPagoCuenta($pdo, $pago, $metodo_pago, $fecha_pago);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => $mensaje]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}