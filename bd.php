<?php

/*
$usuario  = "epiz_32740026";
$password = "eJWcVk2au5gqD";
$servidor = "sql208.epizy.com";
$basededatos = "epiz_32740026_r_user";
*/
$usuario  = "root";
$password = "";
$servidor = "localhost";
$basededatos = "gastos";
$conexion = mysqli_connect($servidor, $usuario, $password) or die("No se ha podido conectar al Servidor");
mysqli_query($conexion, "SET SESSION collation_connection ='utf8_unicode_ci'");
$db = mysqli_select_db($conexion, $basededatos) or die("Upps! Error en conectar a la Base de Datos");

//Linea para los caracteres �
/*
if (!mysqli_set_charset($conexion, "utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", mysqli_error($conn));
    exit();
}

if (mysqli_connect_errno()) {
    die("No se pudo conectar a la base de datos: " . mysqli_connect_error());
}

$max_queries_per_hour = 500;

$current_time = date("Y-m-d H:i:s", time());

// Consultamos el número de consultas realizadas en la última hora
$query = "SELECT COUNT(*) AS num_queries FROM consultas WHERE fecha > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$result = mysqli_query($conexion, $query);

// Si la consulta falla, lanzamos un error
if (!$result) {
    die("La consulta falló: " . mysqli_error($conexion));
}

$row = mysqli_fetch_assoc($result);
$num_queries_last_hour = $row["num_queries"];

// Liberamos el resultado de la consulta
mysqli_free_result($result);

// Si se han superado las consultas permitidas, lanzamos un error
if ($num_queries_last_hour >= $max_queries_per_hour) {
    mysqli_close($conexion); // Cerramos la conexión a la base de datos
    die("Lo siento, has superado el límite de consultas por hora.");
}

$query = "INSERT INTO consultas (fecha) VALUES ('$current_time')";
$result = mysqli_query($conexion, $query);

if (!$result) {
    die("La consulta falló: " . mysqli_error($conexion));
}
*/


$host = $servidor;
$user = $usuario;
$database = $basededatos;

date_default_timezone_set('America/Santiago');


// Array con los nombres de los meses en español
$meses = array(
    1 => 'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre'
);

// Obtener el mes y el año actual
$mes = $meses[date('n')];
$anio = date('Y');
$fecha_actual = date('Y-m-d');
$fecha_actual_hora_actual = date('Y-m-d H:i');

// Get current month and year
$current_month = date('m');
$current_year = date('Y');

$conexion = mysqli_connect($host, $user, $password, $database);
if (!$conexion) {
    echo "No se realizo la conexion a la basa de datos, el error fue:" .
        mysqli_connect_error();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obtener las categorías
    $stmt = $pdo->query("SELECT DISTINCT Nombre FROM `categorias_gastos` as c WHERE c.Nombre = 'Gastos' OR c.Categoria_Padre = '2' ORDER BY `ID` DESC;");
    $categorias_gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obtener las categorías
    $stmt = $pdo->query("SELECT DISTINCT Nombre FROM `categorias_gastos` as c WHERE c.Nombre = 'Ocio' OR c.Categoria_Padre = '3' ORDER BY `ID` DESC;");
    $categorias_ocio = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}


try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obtener las categorías
    $stmt = $pdo->query("SELECT DISTINCT Nombre FROM `categorias_gastos` as c WHERE c.Nombre = 'Ahorro' OR c.Categoria_Padre = '4' ORDER BY `ID` DESC;");
    $categorias_ahorro = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obtener las categorías
    $stmt = $pdo->query("SELECT DISTINCT Detalle FROM detalle");
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
