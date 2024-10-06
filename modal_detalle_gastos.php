<!-- Modal para añadir gastos -->
<div class="modal fade" id="modalDetalleGastos" tabindex="-1" aria-labelledby="modalGastosLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalGastosLabel">Detalles Gastos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php


                // Llamar a la función pasando los parámetros
                $datos_gastos = obtener_datos($conexion, $where_gastos, $current_month, $current_year, $previous_month, $previous_year);

                $result_detalles = $datos_gastos['detalles'];


                ?>


                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-warning">
                            <tr>
                                <th>Detalle</th>
                                <th>Categoria</th>
                                <th>Fecha</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody class="align-items-center">
                            <?php while ($detalle = mysqli_fetch_assoc($result_detalles)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($detalle['Descripcion']) ?></td>
                                    <td><?= htmlspecialchars($detalle['categoria']) ?></td>
                                    <td><?= htmlspecialchars($detalle['Fecha']) ?></td>
                                    <td class="text-end fw-bold">$<?= number_format($detalle['Valor'], 0, '', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>

                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total:</td>
                                <td class="text-end fw-bold <?= $color_gastos_detalle ?>">
                                    $<?= number_format($total_gastos, 0, '', '.') ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>


            </div>

        </div>
    </div>
</div>

<!-- Repetir similar para ocio y ahorro -->