<?php
include('../bd.php');

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'Gastos';

// Definir los parámetros según la categoría seleccionada
switch ($categoria) {
    case 'Ocio':
        $titulo = "Ocio";
        $tipo = "success";
        $nombre = "ocio";
        break;

    case 'Ahorros':
        $titulo = "Ahorros";
        $tipo = "info";
        $nombre = "ahorro";
        break;

    default:
        $titulo = "Gastos";
        $tipo = "warning";
        $nombre = "gastos";
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
    <!-- FontAwesome para los íconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!-- jQuery completo -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <style>
        #filtrosAvanzados {
            display: none;
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .btn-filter {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">Búsqueda de <span class="text-<?php echo $tipo; ?>"><?php echo $titulo; ?></span></h2>

        <form id="filtros">
            <!-- Barra de búsqueda con ícono -->
            <div class="row mb-4">
                <div class="col-md-8 mb-3 mb-md-0">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Buscar..." id="buscar" name="query" aria-label="Buscar" aria-describedby="button-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary" type="button" id="button-addon2">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-secondary btn-block" id="toggleFiltros">
                        <i class="fas fa-filter"></i> Filtros avanzados
                    </button>
                </div>
            </div>



            <!-- Filtros avanzados (inicialmente ocultos) -->
            <div id="filtrosAvanzados">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nombre">Nombre:</label>
                        <input type="text" class="form-control" id="nombre" name="nombre">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="categoria_gasto" class="form-label">Categoría del Gasto</label>
                        <select class="form-control" id="sub_categoria" name="sub_categoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias[$nombre] as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria['Nombre']); ?>">
                                    <?php echo htmlspecialchars($categoria['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="valorMin">Valor mínimo:</label>
                        <input type="number" class="form-control" id="valorMin" name="valorMin">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="valorMax">Valor máximo:</label>
                        <input type="number" class="form-control" id="valorMax" name="valorMax">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="fechaInicio">Fecha inicio:</label>
                        <input type="date" class="form-control" id="fechaInicio" name="fechaInicio">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fechaFin">Fecha fin:</label>
                        <input type="date" class="form-control" id="fechaFin" name="fechaFin">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Aplicar filtros</button>
                <button type="reset" class="btn btn-secondary mt-3 ml-2">Limpiar</button>
            </div>


        </form>

        <h3 class="mt-5 mb-3">Resultados</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-<?php echo $tipo; ?>">
                    <tr>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Valor</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody id="resultados">
                    <!-- Aquí se mostrarán los resultados -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Función para cargar los resultados en la tabla
            function cargarDatos() {
                var formData = $('#filtros').serialize();
                var categoria = '<?php echo $titulo; ?>';

                // Añadir la categoría al objeto formData
                formData += '&categoria=' + encodeURIComponent(categoria);

                $.ajax({
                    url: 'buscar_gastos.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        // Actualizar la tabla con los resultados
                        $('#resultados').html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        // Mostrar un mensaje de error al usuario
                        $('#resultados').html('<p class="text-danger">Error al cargar los datos. Por favor, intente de nuevo.</p>');
                    }
                });
            }

            // Asegurarse de que la categoría se actualice si cambia en el formulario
            $('#categoria_gasto').on('change', function() {
                categoria = $(this).val();
            });

            // Cargar todos los datos al cargar la página
            cargarDatos();

            // Mostrar/ocultar filtros avanzados
            $('#toggleFiltros').click(function() {
                $('#filtrosAvanzados').slideToggle();
            });

            // Filtrar al hacer clic en el botón "Aplicar filtros"
            $('#filtros').on('submit', function(event) {
                event.preventDefault(); // Evitar recarga de la página
                cargarDatos(); // Enviar los datos del formulario
            });

            // Buscar mientras se escribe en el campo de búsqueda global
            $('#buscar').on('input', function() {
                cargarDatos(); // Enviar los datos del formulario
            });
        });
    </script>
</body>

</html>