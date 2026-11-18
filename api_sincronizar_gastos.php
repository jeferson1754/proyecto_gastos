<?php
header('Content-Type: application/json; charset=utf-8');
include('bd.php'); // Incluye tu conexión y funciones auxiliares

$CLAVE_SECRETA = "TU_CLAVE_SECRETA_SUPER_SEGURA";

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$apikey_recibida = $_GET['apikey'] ?? ($input['apikey'] ?? '');
if ($apikey_recibida !== $CLAVE_SECRETA) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "No autorizado. Clave incorrecta."]);
    exit;
}

if (!isset($input['gastos']) || !is_array($input['gastos'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No se recibieron datos válidos."]);
    exit;
}

$gastos_procesados = 0;
$errores = [];

foreach ($input['gastos'] as $index => $gasto) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();

        $descripcion_nombre = trim($gasto['descripcion'] ?? '');
        $categoria_nombre = trim($gasto['categoria'] ?? 'Gastos');
        $modulo = trim($gasto['modulo'] ?? 'Gastos/ Necesidades');
        
        // CORRECCIÓN MÓDULO: Mapeo exacto de los IDs de categoría padre según tu sistema web
        $categoria_padre = 2; // Por defecto
        if ($modulo === "Gastos/ Necesidades") {
            $categoria_padre = 23; // ID que usas en agregar_gasto.php
        } elseif ($modulo === "Ocio y Entretención") {
            $categoria_padre = 24; // ID que usas en agregar_ocio.php
        } elseif ($modulo === "Ahorro e Inversión") {
            $categoria_padre = 2;  // ID o sección de ahorro
        }

        $valor = isset($gasto['monto']) ? (float)$gasto['monto'] : 0;
        
        // CORRECCIÓN FECHA Y HORA: Capturar hora exacta en lugar de dejarla en 00:00
        $fecha_hora_raw = trim($gasto['fecha_hora'] ?? '');
        if (!empty($fecha_hora_raw)) {
            // Convierte "17/11/2026 11:50" a "2026-11-17 11:50:00"
            $partes = explode(' ', $fecha_hora_raw);
            $partes_fecha = explode('/', $partes[0]);
            if (count($partes_fecha) === 3) {
                $anio = $partes_fecha[2];
                $mes = $partes_fecha[1];
                $dia = $partes_fecha[0];
                $hora = $partes[1] ?? '00:00';
                $fecha = "$anio-$mes-$dia $hora:00";
            } else {
                $fecha = date('Y-m-d H:i:s');
            }
        } else {
            $fecha = date('Y-m-d H:i:s');
        }

        $origen = trim($gasto['origen_gasto'] ?? 'Del sistema');
        $fuente_dinero = (stripos($origen, 'sistema') !== false) ? 'sistema' : 'externo';

        $medio_pago_texto = trim($gasto['medio_pago'] ?? 'Débito');
        $medio_pago = '1';
        if (stripos($medio_pago_texto, 'crédito') !== false) $medio_pago = '2';
        elseif (stripos($medio_pago_texto, 'efectivo') !== false) $medio_pago = '3';

        if (empty($descripcion_nombre) || $valor <= 0) {
            throw new Exception("Faltan campos obligatorios en el índice $index.");
        }

        if (function_exists('procesarLogicaMetodoPago')) {
            procesarLogicaMetodoPago($pdo, $valor, $medio_pago);
        }

        // Buscar o crear categoría validando su Categoria_Padre correcta
        $stmt = $pdo->prepare("SELECT ID FROM categorias_gastos WHERE Nombre = :nombre AND Categoria_Padre = :categoria_padre");
        $stmt->execute([':nombre' => $categoria_nombre, ':categoria_padre' => $categoria_padre]);
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($categoria) {
            $categoria_id = $categoria['ID'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO categorias_gastos (Nombre, Categoria_Padre) VALUES (:nombre, :categoria_padre)");
            $stmt->execute([':nombre' => $categoria_nombre, ':categoria_padre' => $categoria_padre]);
            $categoria_id = $pdo->lastInsertId();
        }

        // Buscar o crear detalle
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

        // Insertar gasto respetando la hora exacta
        $stmt = $pdo->prepare("
            INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha, Fuente_Dinero, id_medio_pago)
            VALUES (:detalle_id, :categoria_id, :valor, :fecha, :fuente_dinero, :medio_pago)
        ");
        $stmt->execute([
            ':detalle_id' => $detalle_id,
            ':categoria_id' => $categoria_id,
            ':valor' => $valor,
            ':fecha' => $fecha,
            ':fuente_dinero' => $fuente_dinero,
            ':medio_pago' => $medio_pago
        ]);

        $pdo->commit();
        $gastos_procesados++;

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errores[] = "Índice $index: " . $e->getMessage();
    }
}

echo json_encode([
    "success" => true,
    "procesados" => $gastos_procesados,
    "errores" => $errores
], JSON_UNESCAPED_UNICODE);
?>