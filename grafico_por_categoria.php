<?php

include('bd.php');

// Verificar si se envió la organización desde el formulario
$numero = isset($_POST['organizacion']) ? $_POST['organizacion'] : '0';

// Obtener la categoría de la URL, por defecto será "Gastos"
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'Gastos';

// Definir los parámetros según la categoría seleccionada
switch ($categoria) {
    case 'Ocio':
        $where = $where_ocio;
        $colores = $colores_ocios;
        $titulo = "Ocio Histórico por Categoría";
        $color_hover = "#66BB6A";
        break;

    case 'Ahorros':
        $where = $where_ahorros;
        $colores = $colores_ahorros;
        $titulo = "Ahorro Histórico por Categoría";
        $color_hover = "#2196F3";
        break;

    default:
        $where = $where_gastos;
        $colores = $colores_gastos;
        $titulo = "Gastos Históricos por Categoría";
        $color_hover = "#FF9800";
        break;
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://fastly.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
    <title>Gráficos por Categoría</title>
    <link rel="stylesheet" href="styles.css?<?php echo time(); ?>">
</head>
<style>
    .menu-icon:hover {
        color: <?php echo $color_hover; ?>
    }
</style>

<body>
    <div class="container">
        <div class="menu-container">
            <!-- Formulario oculto para enviar la organización seleccionada -->
            <form id="formOrganizacion" method="POST" action="">
                <input type="hidden" name="organizacion" id="inputOrganizacion" value="">
            </form>

            <!-- Opción de Simple -->
            <div class="menu-item">
                <div class="menu-icon" onclick="cambiarOrganizacion('0')" title="Simple">
                    <i class="fa-solid fa-bars"></i> <!-- Icono de lista simple -->
                </div>
            </div>

            <!-- Opción de Compacto -->
            <div class="menu-item">
                <div class="menu-icon" onclick="cambiarOrganizacion('6')" title="Grupal">
                    <i class="fa-solid fa-table-cells-large"></i>
                </div>
            </div>

            <!-- Opción de Grupal -->
            <div class="menu-item">
                <div class="menu-icon" onclick="cambiarOrganizacion('3')" title="Compacto">
                    <i class="fa-solid fa-table-cells"></i>
                </div>
            </div>
        </div>
        <?php //echo $numero; 
        ?>

        <div class="container">
            <div class="row mb-3">
                <h3 class="text-center mb-8"><?php echo $titulo; ?></h3>


                <?php
                // Llamar a la función para generar gráficos por categoría según la selección
                generarGraficosPorCategoria($conexion, $where, $colores, $categoria, $numero);
                ?>
            </div>
        </div>
        <script>
            // Función para cambiar el valor del input y enviar el formulario
            function cambiarOrganizacion(organizacion) {
                document.getElementById('inputOrganizacion').value = organizacion;
                document.getElementById('formOrganizacion').submit();
            }
        </script>

</body>

</html>