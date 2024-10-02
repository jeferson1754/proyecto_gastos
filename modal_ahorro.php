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
                        <input list="list_detalles" class="form-control" id="descripcionIngreso" name="descripcionIngreso" required>
                        <datalist id="list_detalles">
                            <?php foreach ($detalles as $detalle): ?>
                                <option value="<?php echo htmlspecialchars($detalle['Detalle']); ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label for="montoIngreso" class="form-label">Monto</label>
                        <input type="number" class="form-control" id="montoIngreso" name="monto" min="0" step="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoriaIngreso" class="form-label">Categoria del Ahorro</label>
                        <input list="list_categorias_ahorro" class="form-control" id="categoriaIngreso" name="categoriaIngreso" required>
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
                    <button type="submit" class="btn btn-info" style="color:white">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Repetir similar para ocio y ahorro -->