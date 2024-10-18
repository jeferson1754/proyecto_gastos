<?php

include('../bd.php'); // Conexión a la base de datos

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

// Recibir los filtros enviados por AJAX
$subcategoria = isset($_POST['sub_categoria']) ? $_POST['sub_categoria'] : '';
$nombre = isset($_POST['nombre']) ? $conexion->real_escape_string($_POST['nombre']) : '';
$valorMin = isset($_POST['valorMin']) ? (float)$_POST['valorMin'] : 0;
$valorMax = isset($_POST['valorMax']) ? (float)$_POST['valorMax'] : 0;
$fechaInicio = isset($_POST['fechaInicio']) ? $_POST['fechaInicio'] : '';
$fechaFin = isset($_POST['fechaFin']) ? $_POST['fechaFin'] : '';
$query = isset($_POST['query']) ? $conexion->real_escape_string($_POST['query']) : '';

// Crear la consulta SQL básica
$sql = "SELECT d.Detalle AS Descripcion, g.Valor, c.Nombre as Categoria, g.Fecha
        FROM gastos g
        INNER JOIN categorias_gastos c ON g.ID_Categoria_Gastos = c.ID
        INNER JOIN detalle d ON g.ID_Detalle = d.ID
        WHERE ($where)";

// Filtros opcionales
if (!empty($subcategoria)) {
    $sql .= " AND c.Nombre = '$subcategoria'";
}
if (!empty($nombre)) {
    $sql .= " AND d.Detalle LIKE '%$nombre%'";
}
if ($valorMin > 0) {
    $sql .= " AND g.Valor >= $valorMin";
}
if ($valorMax > 0) {
    $sql .= " AND g.Valor <= $valorMax";
}
if (!empty($fechaInicio)) {
    $sql .= " AND g.Fecha >= '$fechaInicio'";
}
if (!empty($fechaFin)) {
    $sql .= " AND g.Fecha <= '$fechaFin'";
}
if (!empty($query)) {
    $sql .= " AND (d.Detalle LIKE '%$query%' OR g.Valor LIKE '%$query%' OR c.Nombre LIKE '%$query%' OR g.Fecha LIKE '%$query%')";
}

// Ordenar por fecha descendente
$sql .= " ORDER BY g.Fecha DESC LIMIT 50";

//echo $sql."<br>";

// Ejecutar la consulta
$resultado = $conexion->query($sql);

// Verificar si hay resultados
if ($resultado->num_rows > 0) {
    // Mostrar filas de la tabla con los resultados
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

// Cerrar la conexión
$conexion->close();
