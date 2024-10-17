<?php

include('bd.php'); // Conexión a la base de datos

// Recibir la categoría desde el POST (que será fija al cargar la página)
$categoria = isset($_POST['categoria']) ? $_POST['categoria'] : 'Gastos';  // 'Gastos' es la categoría predeterminada

// Filtrar según la categoría seleccionada
switch ($categoria) {
    case 'Ocio':
        $where = $where_ocio;
        break;
    case 'Ahorros':
        $where = $where_ahorros;
        break;
    default:
        $where = $where_gastos;
        break;
}


// Si se recibe una consulta de búsqueda
if (isset($_POST['query']) && !empty($_POST['query'])) {
    $query = $conexion->real_escape_string($_POST['query']);
    $sql = "SELECT d.Detalle AS Descripcion, g.Valor, c.Nombre as Categoria, g.Fecha
    FROM gastos g
    INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
    INNER JOIN detalle d ON g.ID_Detalle = d.ID
    WHERE $where
    AND (d.Detalle LIKE '%$query%' OR g.Valor LIKE '%$query%' OR c.Nombre LIKE '%$query%' OR g.Fecha LIKE '%$query%')
    ORDER BY g.Fecha DESC;";
} else {
    // Si no hay una búsqueda, mostrar todos los registros según la categoría
    $sql = "SELECT d.Detalle AS Descripcion, g.Valor, c.Nombre as Categoria, g.Fecha
    FROM gastos g
    INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
    INNER JOIN detalle d ON g.ID_Detalle = d.ID
    WHERE $where
    ORDER BY g.Fecha DESC;";
}

$resultado = $conexion->query($sql);

if ($resultado === false) {
    echo "Error en la consulta SQL: " . $conexion->error;
} else {
    if ($resultado->num_rows > 0) {
        // Generar filas de la tabla con los resultados
        while ($fila = $resultado->fetch_assoc()) {
            echo "
            <tr>
                <td>{$fila['Descripcion']}</td>
                <td>{$fila['Categoria']}</td>
                <td>{$fila['Valor']}</td>
                <td>{$fila['Fecha']}</td>
            </tr>";
        }
    } else {
        echo "
        <tr>
            <td colspan='4'>No se encontraron resultados</td>
        </tr>";
    }
}

// Cerrar la conexión
$conexion->close();


/*


include('bd.php'); // Conexión a la base de datos

// Recibir los filtros enviados por AJAX
$nombre = isset($_POST['nombre']) ? $conexion->real_escape_string($_POST['nombre']) : '';
$categoria = isset($_POST['categoria']) ? $conexion->real_escape_string($_POST['categoria']) : '';
$valorMin = isset($_POST['valorMin']) ? (float)$_POST['valorMin'] : 0;
$valorMax = isset($_POST['valorMax']) ? (float)$_POST['valorMax'] : 0;
$fechaInicio = isset($_POST['fechaInicio']) ? $_POST['fechaInicio'] : '';
$fechaFin = isset($_POST['fechaFin']) ? $_POST['fechaFin'] : '';

// Crear la consulta SQL
$sql = "SELECT * FROM gastos WHERE 1";

// Filtrar por nombre
if (!empty($nombre)) {
    $sql .= " AND nombre LIKE '%$nombre%'";
}

// Filtrar por categoría
if (!empty($categoria)) {
    $sql .= " AND categoria = '$categoria'";
}

// Filtrar por rango de valores
if ($valorMin > 0) {
    $sql .= " AND valor >= $valorMin";
}
if ($valorMax > 0) {
    $sql .= " AND valor <= $valorMax";
}

// Filtrar por rango de fechas
if (!empty($fechaInicio)) {
    $sql .= " AND fecha >= '$fechaInicio'";
}
if (!empty($fechaFin)) {
    $sql .= " AND fecha <= '$fechaFin'";
}

$resultado = $conexion->query($sql);

if ($resultado === false) {
    echo "Error en la consulta SQL: " . $conexion->error;
} else {
    if ($resultado->num_rows > 0) {
        // Generar filas de la tabla con los resultados
        while ($fila = $resultado->fetch_assoc()) {
            echo "
            <tr>
                <td>{$fila['ID']}</td>
                <td>{$fila['nombre']}</td>
                <td>{$fila['categoria']}</td>
                <td>{$fila['valor']}</td>
                <td>{$fila['fecha']}</td>
            </tr>";
        }
    } else {
        echo "
        <tr>
            <td colspan='5'>No se encontraron resultados</td>
        </tr>";
    }
}

// Cerrar la conexión
$conexion->close();

?>
¨*/
