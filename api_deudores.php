<?php
// api_deudores.php
// Consulta de solo lectura: deudores con saldo distinto de 0 (igual que tu index de Deudores).
// Ubicación recomendada: Deudores/Gastos/ (junto a api_sincronizar_gastos.php, usa el mismo bd.php).
//
// Uso: api_deudores.php?apikey=XXXX              -> todos los deudores con saldo
//      api_deudores.php?apikey=XXXX&nombre=juan  -> filtra por nombre (parcial)
//
// Convención (la misma de tu web): Total_Deuda > 0 = el deudor te debe; < 0 = saldo a favor.
header('Content-Type: application/json; charset=utf-8');

$CLAVE_SECRETA = "TU_CLAVE_SECRETA_SUPER_SEGURA";

if (($_GET['apikey'] ?? '') !== $CLAVE_SECRETA) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "No autorizado. Clave incorrecta."]);
    exit;
}

include('bd.php');

try {
    if (!isset($pdo)) {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $nombre = trim($_GET['nombre'] ?? '');
    $params = [];

    $sql = "SELECT ID, Nombre, Total_Deuda FROM deudor WHERE Total_Deuda != 0";

    if ($nombre !== '') {
        $sql .= " AND Nombre LIKE :nombre";
        $params[':nombre'] = "%$nombre%";
    }

    $sql .= " ORDER BY Total_Deuda DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado = [];
    $total_por_cobrar = 0;
    $total_a_favor = 0;

    foreach ($filas as $fila) {
        $deuda = (float)$fila['Total_Deuda'];
        if ($deuda > 0) {
            $total_por_cobrar += $deuda;
        } else {
            $total_a_favor += abs($deuda);
        }
        $resultado[] = [
            "nombre"       => $fila['Nombre'],
            "deuda_actual" => $deuda
        ];
    }

    echo json_encode([
        "success"          => true,
        "total_deudores"   => count($resultado),
        "total_por_cobrar" => $total_por_cobrar,
        "total_a_favor"    => $total_a_favor,
        "datos"            => $resultado
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}