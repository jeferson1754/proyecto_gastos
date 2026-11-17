<?php
// api_vencimientos.php
header('Content-Type: application/json; charset=utf-8');

// Clave secreta que debe enviar Google Apps Script para autorizar la consulta
$CLAVE_SECRETA = "TU_CLAVE_SECRETA_SUPER_SEGURA";

// Validar la clave por seguridad (método GET o Header)
$apikey_recibida = $_GET['apikey'] ?? '';

if ($apikey_recibida !== $CLAVE_SECRETA) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado. Clave incorrecta."]);
    exit;
}

// Incluir tu conexión a la base de datos (asegúrate de que bd.php use MySQLi o PDO correctamente)
include('../bd.php');

try {
    // Si estás usando PDO ($pdo) o MySQLi ($conexion)
    // Ajusta la consulta según el objeto que devuelva tu bd.php
    $stmt = $pdo->query("SELECT p.*, DATE_FORMAT(p.Fecha_Vencimiento, '%d/%m/%Y') as Fecha_Formateada 
                         FROM pagos p 
                         WHERE p.Estado = 'Pendiente' 
                         ORDER BY p.Fecha_Vencimiento ASC");
    $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);
    $resultado = [];

    foreach ($cuentas as $cuenta) {
        if (empty($cuenta['Fecha_Vencimiento']) || $cuenta['Fecha_Vencimiento'] == '0000-00-00') continue;

        $fechaVenc = new DateTime($cuenta['Fecha_Vencimiento']);
        $fechaVenc->setTime(0, 0, 0);

        $diasRestantes = round(($fechaVenc->getTimestamp() - $hoy->getTimestamp()) / 86400);

        // Solo incluir las cuentas relevantes (atrasadas, hoy, mañana o en 3 días)
        if ($diasRestantes <= 3) {
            $resultado[] = [
                "cuenta" => $cuenta['Cuenta'],
                "valor" => (float)$cuenta['Valor'],
                "fecha_vencimiento" => $cuenta['Fecha_Formateada'],
                "dias_restantes" => (int)$diasRestantes
            ];
        }
    }

    echo json_encode([
        "success" => true,
        "total_alertas" => count($resultado),
        "datos" => $resultado
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
