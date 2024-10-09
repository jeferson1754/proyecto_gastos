<!-- Modal para añadir ingresos -->
<div class=" modal fade" id="modalIngresos" tabindex="-1" aria-labelledby="modalIngresosLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="agregar_ingresos.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalIngresosLabel">Añadir Ingreso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="descripcionIngreso" class="form-label">Descripción del Ingreso</label>
                        <input list="detalles" class="form-control" id="descripcionIngreso" name="descripcionIngreso" required>
                        <datalist id="detalles">
                            <?php foreach ($detalles as $detalle): ?>
                                <option value="<?php echo htmlspecialchars($detalle['Detalle']); ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label for="montoIngreso" class="form-label">Monto</label>
                        <input type="number" class="form-control" id="montoIngreso" name="monto" min="0" step="1" required>
                    </div>
                    <!-- Select principal para elegir la categoría del ingreso -->
                    <div class="mb-3">
                        <label for="categoriaIngresoPrincipal" class="form-label">Categoría del Ingreso</label>
                        <select name="categoriaIngresoPrincipal" class="form-control" id="categoriaIngresoPrincipal" required>
                            <option value="">Seleccione Categoría</option>
                            <option value="Ingresos">Ingresos Base</option>
                            <option value="Gastos">Gastos</option>
                            <option value="Ocio">Ocio</option>
                            <option value="Ahorros">Ahorros</option>
                        </select>
                    </div>

                    <!-- Campo de gastos -->
                    <div class="mb-3 d-none" id="gastos">
                        <label for="categoriaGasto" class="form-label">Categoría del Gasto</label>
                        <input list="list_categorias_gastos" class="form-control" id="categoriaGasto" name="categoriaGasto">
                        <datalist id="list_categorias_gastos">
                            <?php foreach ($categorias['gastos'] as $categoria1): ?>
                                <option value="<?php echo htmlspecialchars($categoria1['Nombre']); ?>">
                                    <?php echo htmlspecialchars($categoria1['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Campo de ocio -->
                    <div class="mb-3 d-none" id="ocio">
                        <label for="categoriaOcio" class="form-label">Categoría del Ocio</label>
                        <input list="list_categorias_ocio" class="form-control" id="categoriaOcio" name="categoriaOcio">
                        <datalist id="list_categorias_ocio">
                            <?php foreach ($categorias['ocio'] as $categoria3): ?>
                                <option value="<?php echo htmlspecialchars($categoria3['Nombre']); ?>">
                                    <?php echo htmlspecialchars($categoria3['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Campo de ahorro -->
                    <div class="mb-3 d-none" id="ahorro">
                        <label for="categoriaAhorro" class="form-label">Categoría del Ahorro</label>
                        <input list="list_categorias_ahorro" class="form-control" id="categoriaAhorro" name="categoriaAhorro">
                        <datalist id="list_categorias_ahorro">
                            <?php foreach ($categorias['ahorro'] as $categoria2): ?>
                                <option value="<?php echo htmlspecialchars($categoria2['Nombre']); ?>">
                                    <?php echo htmlspecialchars($categoria2['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>



                    <div class="mb-3">
                        <label for="montoIngreso" class="form-label">Fecha</label>
                        <input type="datetime-local" class="form-control" id="fecha" name="fecha" value="<?php echo $fecha_actual_hora_actual; ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // JavaScript para mostrar el select correspondiente
    document.getElementById('categoriaIngresoPrincipal').addEventListener('change', function() {
        // Obtener el valor seleccionado
        const selectedValue = this.value;

        // Ocultar todos los campos primero
        document.getElementById('gastos').classList.add('d-none');
        document.getElementById('ocio').classList.add('d-none');
        document.getElementById('ahorro').classList.add('d-none');

        // Mostrar el campo según el valor seleccionado
        if (selectedValue === 'Gastos') {
            document.getElementById('gastos').classList.remove('d-none');
        } else if (selectedValue === 'Ocio') {
            document.getElementById('ocio').classList.remove('d-none');
        } else if (selectedValue === 'Ahorros') {
            document.getElementById('ahorro').classList.remove('d-none');
        }
    });
</script>