<?php

require_once('funciones.php');

/*
$usuario  = "if0_37525845";
$password = "mzvAeq8KzhLCy";
$servidor = "sql106.infinityfree.com";
$basededatos = "if0_37525845_data";
*/
$usuario  = "root";
$password = "";
$servidor = "localhost";
$basededatos = "epiz_32740026_r_user";
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

// Obtener el mes y el año actual
$mes = obtener_nombre_mes_espanol(date('n'));
$fecha_actual = date('Y-m-d');
$fecha_actual_hora_actual = date('Y-m-d H:i');

// Get current month and year
$current_month = date('m');
$current_year = date('Y');

$previous_month = $current_month - 1;
$previous_year = $current_year;

// Si es enero, ir al mes 12 del año anterior
if ($previous_month == 0) {
    $previous_month = 12;
    $previous_year = $current_year - 1;
}

$conexion = mysqli_connect($host, $user, $password, $database);
if (!$conexion) {
    echo "No se realizo la conexion a la basa de datos, el error fue:" .
        mysqli_connect_error();
}


try {
    // Crear una única conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consultas para las diferentes categorías
    $consultas = [
        'gastos' => "SELECT DISTINCT Nombre FROM `categorias_gastos` as c WHERE c.Nombre = 'Gastos' OR c.Categoria_Padre = '23' ORDER BY `ID` DESC;",
        'ocio' => "SELECT DISTINCT Nombre FROM `categorias_gastos` as c WHERE c.Nombre = 'Ocio' OR c.Categoria_Padre = '24' ORDER BY `ID` DESC;",
        'ahorro' => "SELECT DISTINCT Nombre FROM `categorias_gastos` as c WHERE c.Nombre = 'Ahorros' OR c.Categoria_Padre = '2' ORDER BY `ID` DESC;"
    ];

    // Ejecutar las consultas y almacenar los resultados
    $categorias = [];
    foreach ($consultas as $tipo => $consulta) {
        $stmt = $pdo->query($consulta);
        $categorias[$tipo] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ahora tienes $categorias['gastos'], $categorias['ocio'], y $categorias['ahorro']
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

$where_gastos = "c.Nombre = 'Gastos' OR c.Categoria_Padre = '23'";
$where_ocio = "c.Nombre = 'Ocio' OR c.Categoria_Padre = '24'";
$where_ahorros = "c.Nombre = 'Ahorros' OR c.Categoria_Padre = '2'";

$colores_gastos = ['#FF9800', '#FB8C00', '#F57C00', '#EF6C00', '#E65100', '#FFB74D', '#FFCC80'];
$colores_ocios = ['#66BB6A', '#4CAF50', '#43A047', '#388E3C', '#2E7D32', '#1B5E20', '#A5D6A7'];
$colores_ahorros = ['#2196F3', '#1E88E5', '#1976D2', '#1565C0', '#0D47A1', '#64B5F6', '#BBDEFB'];
