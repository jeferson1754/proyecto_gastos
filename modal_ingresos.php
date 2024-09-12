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
                    <div class="mb-3">
                        <label for="categoriaIngreso" class="form-label">Categoria del Ingreso</label>
                        <select name="categoriaIngreso" class="form-control" id="categoriaIngreso" required>
                            <option value="">Seleccione Categoria</option>
                            <option value="Ingresos">Ingresos Base</option>
                            <option value="Gastos">Gastos</option>
                            <option value="Ocio">Ocio</option>
                            <option value="Ahorro">Ahorro</option>
                        </select>
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