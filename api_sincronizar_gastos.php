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
if (!function_exists('procesarLogicaMetodoPago')) {
    require_once('funciones.php'); // El crédito debe sumarse a la tarjeta: no se puede omitir en silencio
}

$CLAVE_SECRETA = "TU_CLAVE_SECRETA_SUPER_SEGURA";

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$apikey_recibida = (string)($_GET['apikey'] ?? ($input['apikey'] ?? ''));
if (!hash_equals($CLAVE_SECRETA, $apikey_recibida)) {
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

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------

// Minúsculas y sin tildes, para comparar textos escritos a mano ("Crédito" = "credito")
function normalizarTextoSync($texto)
{
    $t = mb_strtolower(trim((string)$texto), 'UTF-8');
    return strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
}

// Convierte "17/11/2026 11:50" a "2026-11-17 11:50:00".
// Vacío = ahora. Una fecha escrita pero inválida lanza error (la fila queda pendiente y no se guarda en otro mes).
function parsearFechaHora($raw)
{
    $raw = trim((string)$raw);
    if ($raw === '') {
        return date('Y-m-d H:i:s');
    }
    foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'] as $formato) {
        $dt = DateTime::createFromFormat('!' . $formato, $raw); // '!' = lo no indicado queda en cero
        $errores = DateTime::getLastErrors();
        $limpio = ($errores === false) || ($errores['warning_count'] == 0 && $errores['error_count'] == 0);
        if ($dt && $limpio) {
            return $dt->format('Y-m-d H:i:s');
        }
    }
    throw new Exception("Fecha inválida: '$raw'.");
}

// Monto en pesos chilenos (enteros). Acepta número o texto como "$ 2.500".
function parsearMontoSync($raw)
{
    if (is_int($raw) || is_float($raw)) return (float)$raw;
    $s = trim((string)$raw);
    if ($s === '') return 0.0;
    if (is_numeric($s)) return (float)$s;
    return (float)preg_replace('/\D/', '', $s);
}

// Módulo -> ID de categoría padre (mismos IDs que agregar_gasto / agregar_ocio / agregar_ahorro)
function padreDeModulo($modulo)
{
    $m = normalizarTextoSync($modulo);
    if ($m === '') return 23; // sin módulo: Gastos
    if (strpos($m, 'ocio') !== false || strpos($m, 'entreten') !== false) return 24;
    if (strpos($m, 'ahorro') !== false || strpos($m, 'inversi') !== false) return 2;
    if (strpos($m, 'gasto') !== false || strpos($m, 'necesidad') !== false) return 23;
    throw new Exception("Módulo no reconocido: '$modulo'.");
}

// Cuántos registros idénticos ya existen en la BD (para no duplicar si Apps Script reintenta un lote)
function contarRegistrosExistentes(PDO $pdo, $descripcion, $categoria_nombre, $categoria_padre, $valor, $fecha)
{
    $sql = "SELECT COUNT(*) FROM gastos g
            INNER JOIN detalle d ON d.ID = g.ID_Detalle
            INNER JOIN categorias_gastos c ON c.ID = g.ID_Categoria_Gastos
            WHERE d.Detalle = ? AND c.Nombre = ? AND g.Valor = ? AND g.Fecha = ?";
    $params = [$descripcion, $categoria_nombre, $valor, $fecha];
    if ($categoria_padre !== null) {
        $sql .= " AND c.Categoria_Padre = ?";
        $params[] = $categoria_padre;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

// ---------------------------------------------------------------------------
// Unificar todo en una sola lista, recordando de qué lista y posición viene cada ítem
// ---------------------------------------------------------------------------
$items = [];
if ($hayGastos) {
    foreach ($input['gastos'] as $i => $g) {
        $g = is_array($g) ? $g : [];
        $esIngreso = isset($g['tipo']) && strcasecmp(trim($g['tipo']), 'Ingreso') === 0;
        $items[] = ['lista' => 'gastos', 'indice' => $i, 'ingreso' => $esIngreso, 'datos' => $g];
    }
}
if ($hayIngresos) {
    foreach ($input['ingresos'] as $i => $g) {
        $items[] = ['lista' => 'ingresos', 'indice' => $i, 'ingreso' => true, 'datos' => is_array($g) ? $g : []];
    }
}

$nuevos_gastos = 0;
$nuevos_ingresos = 0;
$duplicados = 0;
$ok = ['gastos' => [], 'ingresos' => []];
$errores = [];
$yaExistentes = []; // firma => cuántos había en la BD al empezar el lote
$vistos = [];       // firma => cuántos van en este lote

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
    $etiqueta = $item['lista'] . '[' . $item['indice'] . ']';

    try {
        $descripcion_nombre = trim((string)($gasto['descripcion'] ?? ''));
        $valor = parsearMontoSync($gasto['monto'] ?? 0);
        $fecha = parsearFechaHora($gasto['fecha_hora'] ?? '');

        if ($descripcion_nombre === '' || $valor <= 0) {
            throw new Exception("Faltan campos obligatorios (descripción o monto).");
        }

        if ($esIngreso) {
            // Igual que agregar_ingresos.php: categoría "Ingresos", valor positivo
            $categoria_nombre = 'Ingresos';
            $categoria_padre = null;
        } else {
            $categoria_nombre = trim((string)($gasto['categoria'] ?? ''));
            if ($categoria_nombre === '') $categoria_nombre = 'Gastos';

            $categoria_padre = padreDeModulo($gasto['modulo'] ?? '');

            // Tipo de gasto: sistema (afecta tu balance) o externo (solo informativo)
            $origen = normalizarTextoSync($gasto['origen_gasto'] ?? '');
            if ($origen === '' || strpos($origen, 'sistema') !== false) {
                $fuente_dinero = 'sistema';
            } elseif (strpos($origen, 'externo') !== false) {
                $fuente_dinero = 'externo';
            } else {
                throw new Exception("Tipo de gasto no reconocido: '" . $gasto['origen_gasto'] . "'.");
            }

            // Medio de pago: 1 = débito, 2 = crédito, 3 = efectivo
            $mp = normalizarTextoSync($gasto['medio_pago'] ?? '');
            $medio_pago = '1';
            if (strpos($mp, 'credito') !== false) $medio_pago = '2';
            elseif (strpos($mp, 'efectivo') !== false) $medio_pago = '3';

            // Un ahorro es plata que sale de tu cuenta: no se registra con tarjeta de crédito
            if ($categoria_padre === 2 && $medio_pago === '2') {
                throw new Exception("Los ahorros no se pueden registrar con tarjeta de crédito.");
            }
        }

        // ¿Ya está en la web? (pasa cuando Apps Script reintenta un lote que sí llegó). Se compara por
        // cantidad: si la BD ya tiene N registros idénticos, los primeros N del lote se dan por subidos.
        $firma = ($esIngreso ? 'I' : 'G') . '|' . $descripcion_nombre . '|' . $categoria_nombre . '|' . (string)$categoria_padre . '|' . number_format($valor, 2, '.', '') . '|' . $fecha;
        if (!isset($yaExistentes[$firma])) {
            $yaExistentes[$firma] = contarRegistrosExistentes($pdo, $descripcion_nombre, $categoria_nombre, $categoria_padre, $valor, $fecha);
        }
        $vistos[$firma] = ($vistos[$firma] ?? 0) + 1;

        if ($vistos[$firma] <= $yaExistentes[$firma]) {
            $duplicados++;
            $ok[$item['lista']][] = $item['indice'];
            continue;
        }

        $pdo->beginTransaction();

        // Con crédito: se suma a la factura de la tarjeta (ciclo según la fecha de la compra) y
        // el gasto queda siempre como externo con medio 2, aunque la hoja diga "Del sistema".
        if (!$esIngreso) {
            $configuracionPago = procesarLogicaMetodoPago($pdo, $valor, null, $fecha);
            if ($configuracionPago['id_medio'] == 2) {
                $fuente_dinero = $configuracionPago['origen'];
                $medio_pago = (string)$configuracionPago['id_medio'];
            }
        }

        // Buscar o crear categoría
        if ($esIngreso) {
            // Igual que agregar_ingresos.php: se busca solo por nombre (así se usa la categoría Ingresos ya existente)
            $stmt = $pdo->prepare("SELECT ID FROM categorias_gastos WHERE Nombre = :nombre ORDER BY ID ASC LIMIT 1");
            $stmt->execute([':nombre' => $categoria_nombre]);
            $categoria_padre_nueva = 1;
        } else {
            $stmt = $pdo->prepare("SELECT ID FROM categorias_gastos WHERE Nombre = :nombre AND Categoria_Padre = :categoria_padre ORDER BY ID ASC LIMIT 1");
            $stmt->execute([':nombre' => $categoria_nombre, ':categoria_padre' => $categoria_padre]);
            $categoria_padre_nueva = $categoria_padre;
        }
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($categoria) {
            $categoria_id = $categoria['ID'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO categorias_gastos (Nombre, Categoria_Padre) VALUES (:nombre, :categoria_padre)");
            $stmt->execute([':nombre' => $categoria_nombre, ':categoria_padre' => $categoria_padre_nueva]);
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
            // Ingreso: dinero del sistema, débito (igual que agregar_ingresos.php)
            $fuente_dinero = 'sistema';
            $medio_pago = '1';
        }else{
            $medio_pago ='1';
            $fuente_dinero = 'sistema';
        }

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

        $pdo->commit();
        if ($esIngreso) $nuevos_ingresos++; else $nuevos_gastos++;
        $ok[$item['lista']][] = $item['indice'];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Este ítem no se registró: no aparece en "ok" y su fila sigue "Pendiente" en la hoja
        $errores[] = $etiqueta . ": " . $e->getMessage();
    }
}

echo json_encode([
    "success"             => true,
    "procesados"          => $nuevos_gastos + $nuevos_ingresos, // registros NUEVOS insertados
    "procesados_gastos"   => $nuevos_gastos,
    "procesados_ingresos" => $nuevos_ingresos,
    "duplicados"          => $duplicados,                        // ya estaban en la web (reintentos)
    "ok"                  => $ok,                                // índices que quedaron registrados
    "errores"             => $errores
], JSON_UNESCAPED_UNICODE);
?>