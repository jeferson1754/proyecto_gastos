<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "gastos";

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
$fecha_actual_hora_actual = date('Y-m-d H:i:s');

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
    $stmt = $pdo->query("SELECT DISTINCT Nombre FROM categorias_gastos");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
