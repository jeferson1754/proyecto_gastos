<!-- Modal para añadir Ocio -->
<div class="modal fade" id="modalOcio" tabindex="-1" aria-labelledby="modalOcioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="agregar_ocio.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalOcioLabel">Añadir Ocio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <input type="hidden" name="presupuesto" value="<?= $ocio_restante ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción del Ocio</label>
                        <input list="list_detalles" class="form-control" id="descripcion" name="descripcionIngreso" required>
                        <datalist id="list_detalles">
                            <?php foreach ($detalles as $detalle): ?>
                                <option value="<?php echo htmlspecialchars($detalle['Detalle']); ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>

                    <?php include 'gasto_externo.php'; ?>

                    <div class="mb-3">
                        <label for="montoIngreso" class="form-label">Monto</label>
                        <input type="text" class="form-control valor_formateado" id="monto" name="monto" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoria" class="form-label">Categoría del Ocio</label>
                        <input list="list_categorias_ocio" class="form-control" id="categoria_ocio" name="categoriaIngreso" required>
                        <datalist id="list_categorias_ocio">
                            <?php foreach ($categorias['ocio'] as $categoria3): ?>
                                <option value="<?php echo htmlspecialchars($categoria3['Nombre']); ?>">
                                    <?php echo htmlspecialchars($categoria3['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <!-- Sección de Ocio Recurrentes -->

                    <div class="mb-3">
                        <h6>Ocio Recurrentes</h6>

                        <?php
                        // Llamar a la función para obtener los gastos recurrentes
                        $ocioRecurrentes = obtenerDatosRecurrentes($conexion, "$fecha AND ($where_ocio)", $minRepeticiones);
                        ?>

                        <ul class="list-group" id="ocioRecurrentesList">
                            <?php if (empty($ocioRecurrentes)): ?>
                                <li class="list-group-item ocio text-center text-muted">
                                    No hay ocios recurrentes registrados.
                                </li>
                            <?php else: ?>
                                <?php foreach ($ocioRecurrentes as $ocio_recurrente): ?>
                                    <li class="list-group-item ocio d-flex justify-content-between align-items-center ocio-recurrente"
                                        data-descripcion="<?php echo htmlspecialchars($ocio_recurrente['descripcion']); ?>"
                                        data-monto="<?php echo htmlspecialchars($ocio_recurrente['monto']); ?>"
                                        data-categoria="<?php echo htmlspecialchars($ocio_recurrente['categoria']); ?>">
                                        <div>
                                            <?php echo htmlspecialchars($ocio_recurrente['descripcion']); ?> - $<?php echo htmlspecialchars($ocio_recurrente['monto']); ?> (<?php echo $ocio_recurrente['cantidad_repeticiones']; ?> veces)
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="rellenarFormulario_ocio(this)">Seleccionar</button>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label for="fechaOcio" class="form-label">Fecha</label>
                        <input type="datetime-local" class="form-control" id="fechaOcio" name="fecha" value="<?php echo $fecha_actual_hora_actual; ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function rellenarFormulario_ocio(button) {
        const li = button.closest('.ocio-recurrente');
        const descripcion = li.getAttribute('data-descripcion');
        const monto = li.getAttribute('data-monto');
        const categoria = li.getAttribute('data-categoria');

        // Rellenar los campos del formulario
        document.getElementById('descripcion').value = descripcion;
        document.getElementById('monto').value = monto;
        document.getElementById('categoria_ocio').value = categoria; // Asegúrate que este ID es correcto
    }
</script>