<!-- Modal para añadir gastos -->
<div class="modal fade" id="modalGastos" tabindex="-1" aria-labelledby="modalGastosLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="agregar_gasto.php" method="POST" id="formGastos">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGastosLabel">Añadir Gasto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="descripcionIngreso" class="form-label">Descripción del Gasto</label>
                        <input list="list_detalles" class="form-control" id="descripcion_gasto" name="descripcionIngreso" required>
                        <datalist id="list_detalles">
                            <?php foreach ($detalles as $detalle): ?>
                                <option value="<?php echo htmlspecialchars($detalle['Detalle']); ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label for="montoIngreso" class="form-label">Monto</label>
                        <input type="text" class="form-control valor_formateado" id="monto_gasto" name="monto" required>
                    </div>

                    <div class="mb-3">
                        <label for="categoria" class="form-label">Categoría del Gasto</label>
                        <input list="list_categorias_gasto" class="form-control" id="categoria_gasto" name="categoriaIngreso" required>
                        <datalist id="list_categorias_gasto">
                            <?php foreach ($categorias['gastos'] as $categoria1): ?>
                                <option value="<?php echo htmlspecialchars($categoria1['Nombre']); ?>">
                                    <?php echo htmlspecialchars($categoria1['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <!-- Sección de Gastos Recurrentes -->

                    <div class="mb-3">
                        <h6>Gastos Recurrentes</h6>

                        <?php
                        // Llamar a la función para obtener los gastos recurrentes
                        $gastosRecurrentes = obtenerDatosRecurrentes($conexion, "$fecha AND ($where_gastos)", $minRepeticiones);
                        ?>

                        <ul class="list-group" id="gastosRecurrentesList">
                            <?php if (empty($gastosRecurrentes)): ?>
                                <li class="list-group-item text-center text-muted">
                                    No hay gastos recurrentes registrados.
                                </li>
                            <?php else: ?>
                                <?php foreach ($gastosRecurrentes as $gasto_recurrente): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center gasto-recurrente"
                                        data-descripcion="<?php echo htmlspecialchars($gasto_recurrente['descripcion']); ?>"
                                        data-monto="<?php echo htmlspecialchars($gasto_recurrente['monto']); ?>"
                                        data-categoria="<?php echo htmlspecialchars($gasto_recurrente['categoria']); ?>">
                                        <div>
                                            <?php echo htmlspecialchars($gasto_recurrente['descripcion']); ?> - $<?php echo htmlspecialchars($gasto_recurrente['monto']); ?> (<?php echo $gasto_recurrente['cantidad_repeticiones']; ?> veces)
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="rellenarFormulario_gastos(this)">Seleccionar</button>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label for="fechaOcio" class="form-label">Fecha</label>
                        <input type="datetime-local" class="form-control" id="fechaOcio" name="fecha" value="<?php echo $fecha_actual_hora_actual; ?>" required>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-warning">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function rellenarFormulario_gastos(button) {
        const li = button.closest('.gasto-recurrente');
        const descripcion = li.getAttribute('data-descripcion');
        const monto = li.getAttribute('data-monto');
        const categoria = li.getAttribute('data-categoria');

        // Rellenar los campos del formulario
        document.getElementById('descripcion_gasto').value = descripcion;
        document.getElementById('monto_gasto').value = monto;
        document.getElementById('categoria_gasto').value = categoria; // Asegúrate que este ID es correcto
    }
</script>