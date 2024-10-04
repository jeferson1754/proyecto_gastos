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

                $where = "WHERE c.Nombre = 'Gastos' OR c.Categoria_Padre = '2'";

                // Llamar a la función pasando los parámetros
                $datos_gastos = obtener_datos($conexion, $where, $current_month, $current_year, $previous_month, $previous_year);

                // Acceder a los resultados
                $total_gastos = $datos_gastos['total'];
                $result_detalles = $datos_gastos['detalles'];
                $anterior_total_gastos = $datos_gastos['anterior_total'];


                ?>


                <div class="detalles-container">

                    <table class="table table-striped table-bordered table-hover">
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
                                    <td><?= htmlspecialchars($detalle['Fecha']) ?></td></td>
                                    <td>$<?= number_format($detalle['Valor'], 0, '', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-warning text-center">
                    Valor Actual:
                    <?php

                    if ($gastos < $total_gastos) {
                        $color = "red";
                    } else {
                        $color = "";
                    }

                    echo "<h4 class=" . $color . ">$ " . number_format($total_gastos, 0, '', '.') . "</h4>";

                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>

<!-- Repetir similar para ocio y ahorro -->