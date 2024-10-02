<!-- Modal para añadir Ocio -->
<div class="modal fade" id="modalOcio" tabindex="-1" aria-labelledby="modalOcioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="agregar_ocio.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalOcioLabel">Añadir Ocio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="descripcionIngreso" class="form-label">Descripción del Ocio</label>
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
                        <label for="categoriaIngreso" class="form-label">Categoria del Ocio</label>
                        <input list="list_categorias_ocio" class="form-control" id="categoriaIngreso" name="categoriaIngreso" required>
                        <datalist id="list_categorias_ocio">
                            <?php foreach ($categorias['ocio'] as $categoria3): ?>
                                <option value="<?php echo htmlspecialchars($categoria3['Nombre']); ?>">
                                    <?php echo htmlspecialchars($categoria3['Nombre']); ?>
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
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Repetir similar para ocio y ahorro -->