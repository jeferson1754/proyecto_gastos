<?php
// Configuración inicial
define('TELEGRAM_TOKEN', '8800212574:AAH4XyLhBo53qqzUrpSgeZ1WzFMRdP9J4KQ');
define('CHAT_ID_PERMITIDO', 8463410128); // TU ID de Telegram por seguridad (evita que otros usen tu bot)

// Conexión a la base de datos (PDO)
include('bd.php'); // Conexión a la base de datos

try {
    $pdo = new PDO("mysql:host=$host;dbname=$basededatos;charset=utf8", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión");
}

// 1. Capturar la entrada de Telegram
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update || !isset($update['message'])) {
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = trim($message['text'] ?? '');

// 2. Validación de Seguridad (Sólo tú puedes registrar gastos)
if ($chatId != CHAT_ID_PERMITIDO) {
    enviarMensaje($chatId, "⛔ No estás autorizado para usar este sistema.");
    exit;
}

// Comandos básicos
if ($text === '/start') {
    enviarMensaje($chatId, "¡Hola! Envíame tus gastos con el formato:\n`Monto detalle #categoria`\n\nEjemplo:\n`15000 supermercado #necesidad`\n`5000 netflix #ocio`");
    exit;
}

// 3. Procesar el texto usando Expresiones Regulares (Regex)
// Formato esperado: Números iniciales + texto intermedio + #categoria opcional al final
// Ejemplo: "12500 almuerzo con clientes #ocio"
if (preg_match('/^(\d+)\s+(.+?)(?:\s+#(\w+))?$/u', $text, $matches)) {
    $monto = (float)$matches[1];
    $descripcion = trim($matches[2]);
    $tagCategoria = isset($matches[3]) ? strtolower($matches[3]) : 'necesidad'; // 'necesidad' por defecto si no pones hashtag

    // 4. Buscar el ID de la categoría correspondiente en la base de datos
    // Mapeamos el tag recibido (#ocio, #necesidad, #ahorro) al tipo de tu tabla categorías
    $stmtCat = $pdo->prepare("SELECT id FROM categorias_gastos WHERE Nombre = :tipo LIMIT 1");
    $stmtCat->execute(['tipo' => $tagCategoria]);
    $categoria = $stmtCat->fetch();

    if (!$categoria) {
        // Si no encuentra la categoría exacta, busca una genérica o usa la primera disponible
        $stmtCat->execute(['tipo' => 'Compras']);
        $categoria = $stmtCat->fetch();
    }

    // Si aun así no existe 'Compras' en tu BD, manejamos un fallback seguro
    $categoriaId = $categoria ? $categoria['ID'] : 1;


    // Obtener o crear detalle
    $stmt = $pdo->prepare("SELECT ID FROM detalle WHERE Detalle = :nombre");
    $stmt->execute([':nombre' => $descripcion]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detalle) {
        $detalle_id = $detalle['ID'];
    } else {
        // Si el detalle no existe, insertarlo y recuperar el ID
        $stmt = $pdo->prepare("INSERT INTO detalle (Detalle) VALUES (:nombre)");
        $stmt->execute([':nombre' => $descripcion]);
        $detalle_id = $pdo->lastInsertId();
    }

    // 5. Insertar el gasto en la tabla movimientos
    try {
        $stmt = $pdo->prepare("
        INSERT INTO gastos (ID_Detalle, ID_Categoria_Gastos, Valor, Fecha, Fuente_Dinero, id_medio_pago)
        VALUES (:detalle_id, :categoria_id, :valor, :fecha, :fuente_dinero, :medio_pago)
        ");
        $stmt->execute([
            ':detalle_id' => $detalle_id,
            ':categoria_id' => $categoria_id,
            ':valor' => $monto,
            ':fecha' => $fecha_actual,
            ':fuente_dinero' => $fuente_dinero ?? 'sistema',
            ':medio_pago' => $medio_pago ?? 1
        ]);
        // 6. Confirmar éxito al usuario
        $montoFormateado = number_format($monto, 0, ',', '.');
        $respuesta = "✅ *Gasto Registrado*\n\n";
        $respuesta .= "💰 *Monto:* $$montoFormateado\n";
        $respuesta .= "📝 *Detalle:* $descripcion\n";
        $respuesta .= "🏷️ *Categoría:* " . ucfirst($tagCategoria);

        enviarMensaje($chatId, $respuesta);
    } catch (PDOException $e) {
        enviarMensaje($chatId, "❌ Error interno al guardar en la base de datos.");
    }
} else {
    enviarMensaje($chatId, "⚠️ Formato incorrecto. Recuerda usar:\n`Monto detalle #categoria`");
}

// Función auxiliar para responder a Telegram
function enviarMensaje($chatId, $texto)
{
    $url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $texto,
        'parse_mode' => 'Markdown'
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}
