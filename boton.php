<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mostrar/Ocultar iframe</title>
    <!-- Incluye Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?<?php echo time() ?>">
</head>

<body>
    <div class="container text-center">

        <!-- Botón para mostrar/ocultar iframe -->
        <button id="toggle-btn" class="btn btn-primary">Mostrar Graficos</button>

        <!-- Contenedor para el iframe -->
        <div id="iframe-container">
            <iframe src="./grafico_por_modulo.php"></iframe>
        </div>
    </div>

    <!-- Bootstrap JS y Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script para mostrar/ocultar iframe -->
    <script>
        document.getElementById('toggle-btn').addEventListener('click', function() {
            var iframeContainer = document.getElementById('iframe-container');
            if (iframeContainer.style.display === 'none') {
                iframeContainer.style.display = 'block';
                this.textContent = 'Ocultar Graficos'; // Cambiar texto del botón
            } else {
                iframeContainer.style.display = 'none';
                this.textContent = 'Mostrar Graficos'; // Cambiar texto del botón
            }
        });
    </script>
</body>

</html>