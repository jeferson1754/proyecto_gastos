<?php
// api_sincronizar_gastos.php
// Recibe gastos e ingresos desde Google Apps Script.
//
// Formato del JSON:
// {
//   "apikey": "...",
//   "gastos":   [ { descripcion, comercio, origen_gasto, medio_pago, monto, categoria, modulo, fecha_hora }, ... ],
//   "ingresos": [ { descripcion, monto, fecha_hora }, ... ]
// }
// Ambas llaves son opcionales, pero debe venir al menos una.
// Un ítem dentro de "gastos" con "tipo": "Ingreso" también se trata como ingreso.

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

$hayGastos   = isset($input['gastos'])   && is_array($input['gastos']);
$hayIngresos = isset($input['ingresos']) && is_array($input['ingresos']);

if (!$hayGastos && !$hayIngresos) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No se recibieron datos válidos."]);
    exit;
}

// Convierte "17/11/2026 11:50" a "2026-11-17 11:50:00"
function parsearFechaHora($raw) {
    $raw = trim((string)$raw);
    if ($raw !== '') {
        $dt = DateTime::createFromFormat('d/m/Y H:i', $raw);
        if (!$dt) $dt = DateTime::createFromFormat('d/m/Y', $raw);
        if ($dt) return $dt->format('Y-m-d H:i:s');
    }
    return date('Y-m-d H:i:s');
}

// Unificar todo en una sola lista, conservando de dónde viene cada ítem (para los mensajes de error)
$items = [];
if ($hayGastos) {
    foreach ($input['gastos'] as $i => $g) {
        $esIngreso = isset($g['tipo']) && strcasecmp(trim($g['tipo']), 'Ingreso') === 0;
        $items[] = ['etiqueta' => "gastos[$i]", 'ingreso' => $esIngreso, 'datos' => $g];
    }
}
if ($hayIngresos) {
    foreach ($input['ingresos'] as $i => $g) {
        $items[] = ['etiqueta' => "ingresos[$i]", 'ingreso' => true, 'datos' => $g];
    }
}

$procesados_gastos = 0;
$procesados_ingresos = 0;
$errores = [];

try {
    // Una sola conexión para todo el lote
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error de conexión: " . $e->getMessage()]);
    exit;
}

foreach ($items as $item) {
    $gasto = $item['datos'];
    $esIngreso = $item['ingreso'];

    try {
        $pdo->beginTransaction();

        $descripcion_nombre = trim($gasto['descripcion'] ?? '');
        $valor = isset($gasto['monto']) ? (float)$gasto['monto'] : 0;
        $fecha = parsearFechaHora($gasto['fecha_hora'] ?? '');

        if (empty($descripcion_nombre) || $valor <= 0) {
            throw new Exception("Faltan campos obligatorios en " . $item['etiqueta'] . ".");
        }

        if ($esIngreso) {
            // Igual que agregar_ingresos.php: categoría "Ingresos" con padre 1, valor positivo
            $categoria_nombre = 'Ingresos';
            $categoria_padre = 1;
        } else {
            $categoria_nombre = trim($gasto['categoria'] ?? 'Gastos');
            $modulo = trim($gasto['modulo'] ?? 'Gastos/ Necesidades');

            // Mapeo exacto de los IDs de categoría padre según tu sistema web
            $categoria_padre = 2; // Por defecto
            if ($modulo === "Gastos/ Necesidades") {
                $categoria_padre = 23; // ID que usas en agregar_gasto.php
            } elseif ($modulo === "Ocio y Entretención") {
                $categoria_padre = 24; // ID que usas en agregar_ocio.php
            } elseif ($modulo === "Ahorro e Inversión") {
                $categoria_padre = 2;  // ID o sección de ahorro
            }

            $origen = trim($gasto['origen_gasto'] ?? 'Del sistema');
            $fuente_dinero = (stripos($origen, 'sistema') !== false) ? 'sistema' : 'externo';

            $medio_pago_texto = trim($gasto['medio_pago'] ?? 'Débito');
            $medio_pago = '1';
            if (stripos($medio_pago_texto, 'crédito') !== false) $medio_pago = '2';
            elseif (stripos($medio_pago_texto, 'efectivo') !== false) $medio_pago = '3';

            if (function_exists('procesarLogicaMetodoPago')) {
                procesarLogicaMetodoPago($pdo, $valor, $medio_pago);
            }
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

        if ($esIngreso) {
            // Ingreso: mismo INSERT que agregar_ingresos.php (sin fuente de dinero ni medio de pago)
            $stmt = $pdo->prepare("
                INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha)
                VALUES (:detalle_id, :categoria_id, :valor, :fecha)
            ");
            $stmt->execute([
                ':detalle_id'   => $detalle_id,
                ':categoria_id' => $categoria_id,
                ':valor'        => $valor,
                ':fecha'        => $fecha
            ]);
        } else {
            // Gasto: igual que antes, respetando la hora exacta
            $stmt = $pdo->prepare("
                INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha, Fuente_Dinero, id_medio_pago)
                VALUES (:detalle_id, :categoria_id, :valor, :fecha, :fuente_dinero, :medio_pago)
            ");
            $stmt->execute([
                ':detalle_id'    => $detalle_id,
                ':categoria_id'  => $categoria_id,
                ':valor'         => $valor,
                ':fecha'         => $fecha,
                ':fuente_dinero' => $fuente_dinero,
                ':medio_pago'    => $medio_pago
            ]);
        }

        $pdo->commit();
        if ($esIngreso) $procesados_ingresos++; else $procesados_gastos++;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errores[] = $item['etiqueta'] . ": " . $e->getMessage();
    }
}

echo json_encode([
    "success"             => true,
    "procesados"          => $procesados_gastos + $procesados_ingresos, // total (compatible con tu script actual)
    "procesados_gastos"   => $procesados_gastos,
    "procesados_ingresos" => $procesados_ingresos,
    "errores"             => $errores
], JSON_UNESCAPED_UNICODE);
?>