<?php
include('bd.php');

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'Gastos';

// Definir los parámetros según la categoría seleccionada
switch ($categoria) {
    case 'Ocio':
        $where = $where_ocio;
        $titulo = "Ocio";
        $tipo = "success";
        break;

    case 'Ahorros':
        $where = $where_ahorros;
        $titulo = "Ahorros";
        $tipo = "info";
        break;

    default:
        $where = $where_gastos;
        $titulo = "Gastos";
        $tipo = "warning";
        break;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome para el ícono de búsqueda -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!-- jQuery completo -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">Búsqueda de <span class="text-<?php echo $tipo; ?>"><?php echo $titulo; ?></span></h2>

        <!-- Barra de búsqueda con ícono -->
        <div class="input-group mb-4">
            <input type="text" class="form-control" placeholder="Buscar..." id="buscar" aria-label="Buscar" aria-describedby="button-addon2">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="button-addon2">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <!-- Tabla -->
        <table class="table table-bordered">
            <thead class="table-<?php echo $tipo; ?>">
                <tr>
                    <th>Nombre</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody id="resultados">
                <!-- Aquí se insertarán los resultados de la búsqueda -->
            </tbody>
        </table>
    </div>

    <!-- Código JavaScript -->
    <script>
        $(document).ready(function() {
            // Define la categoría solo una vez cuando la página carga
            var categoria = '<?php echo $titulo; ?>'; // Puedes cambiar 'Ocio' por cualquier otra categoría predeterminada

            // Función para cargar los resultados en la tabla
            function cargarDatos(query = '') {
                $.ajax({
                    url: 'buscar_gastos.php', // Enviar los datos a buscar_gastos.php
                    method: 'POST', // Método POST
                    data: {
                        query: query, // Término de búsqueda
                        categoria: categoria // Categoría definida al cargar la página
                    },
                    success: function(data) {
                        $('#resultados').html(data); // Mostrar los resultados devueltos en el div con id "resultados"
                    }
                });
            }

            // Cargar todos los datos al cargar la página
            cargarDatos();

            // Buscar mientras se escribe en el campo de búsqueda
            $('#buscar').on('input', function() {
                var query = $(this).val(); // Capturar el término de búsqueda actual
                cargarDatos(query); // Enviar el término de búsqueda sin cambiar la categoría
            });
        });
    </script>



    <!-- Bootstrap JS y dependencias -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>