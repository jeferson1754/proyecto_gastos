<!-- Modal para añadir Ahorro -->
<div class="modal fade" id="modalAhorro" tabindex="-1" aria-labelledby="modalAhorroLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="agregar_ahorro.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAhorroLabel">Añadir Ahorro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="descripcionIngreso" class="form-label">Descripción del Ahorro</label>
                        <input list="list_detalles" class="form-control" id="descripcion_ahorro" name="descripcionIngreso" required>
                        <datalist id="list_detalles">
                            <?php foreach ($detalles as $detalle): ?>
                                <option value="<?php echo htmlspecialchars($detalle['Detalle']); ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label for="montoIngreso" class="form-label">Monto</label>
                        <input type="text" class="form-control valor_formateado" id="monto" name="monto" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoriaIngreso" class="form-label">Categoria del Ahorro</label>
                        <input list="list_categorias_ahorro" class="form-control" id="categoria_ahorro" name="categoriaIngreso" required>
                        <datalist id="list_categorias_ahorro">
                            <?php foreach ($categorias['ahorro'] as $categoria2): ?>
                                <option value="<?php echo htmlspecialchars($categoria2['Nombre']); ?>">
                                    <?php echo htmlspecialchars($categoria2['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <!-- Sección de Ahorros Recurrentes -->

                    <div class="mb-3">
                        <h6>Ahorros Recurrentes</h6>

                        <?php
                        // Llamar a la función para obtener los gastos recurrentes
                        $ahorrosRecurrentes = obtenerDatosRecurrentes($conexion, "$fecha AND ($where_ahorros)", $minRepeticiones);
                        ?>

                        <ul class="list-group" id="ahorrosRecurrentesList">
                            <?php if (empty($ahorrosRecurrentes)): ?>
                                <li class="list-group-item ahorro text-center text-muted">
                                    No hay ahorros recurrentes registrados.
                                </li>
                            <?php else: ?>
                                <?php foreach ($ahorrosRecurrentes as $ahorros_recurrente): ?>
                                    <li class="list-group-item ahorro d-flex justify-content-between align-items-center ahorro-recurrente"
                                        data-descripcion="<?php echo htmlspecialchars($ahorros_recurrente['descripcion']); ?>"
                                        data-monto="<?php echo htmlspecialchars($ahorros_recurrente['monto']); ?>"
                                        data-categoria="<?php echo htmlspecialchars($ahorros_recurrente['categoria']); ?>">
                                        <div>
                                            <?php echo htmlspecialchars($ahorros_recurrente['descripcion']); ?> - $<?php echo htmlspecialchars($gasto_recurrente['monto']); ?> (<?php echo $gasto_recurrente['cantidad_repeticiones']; ?> veces)
                                        </div>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="rellenarFormulario_ahorro(this)">Seleccionar</button>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label for="montoIngreso" class="form-label">Fecha</label>
                        <input type="datetime-local" class="form-control" id="fecha" name="fecha" value="<?php echo $fecha_actual_hora_actual; ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-info" style="color:white">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Repetir similar para ocio y ahorro -->
<script>
    function rellenarFormulario_ahorro(button) {
        const li = button.closest('.ahorro-recurrente');
        const descripcion = li.getAttribute('data-descripcion');
        const monto = li.getAttribute('data-monto');
        const categoria = li.getAttribute('data-categoria');

        // Rellenar los campos del formulario
        document.getElementById('descripcion_ahorro').value = descripcion;
        document.getElementById('monto_ahorro').value = monto;
        document.getElementById('categoria_ahorro').value = categoria; // Asegúrate que este ID es correcto
    }
</script>