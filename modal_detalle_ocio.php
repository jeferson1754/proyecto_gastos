

<link rel="stylesheet" href="./Busqueda/styles.css?<?php echo time() ?>">
<div class="modal fade" id="modalDetalleOcio" tabindex="-1" aria-labelledby="modalGastosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">

            <div class="modal-header border-0 pb-0" style="padding: 1.5rem 2rem;">
                <h5 class="modal-title fw-bold" id="modalGastosLabel">
                    <i class="fas fa-utensils text-success me-2"></i> Detalles de Ocio
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <?php
                // Llamar a la función pasando los parámetros
                $datos_ocio = obtener_datos($conexion, $where_ocio, $current_month, $current_year, $previous_month, $previous_year);

                $result_detalles = $datos_ocio['detalles'];
                ?>

                <div class="d-flex justify-content-between align-items-center px-4 py-3 bg-light border-bottom border-top mt-3">
                    <div class="small text-muted">
                        <i class="fas fa-list-ul me-1"></i>
                        <span class="fw-bold text-dark"><?= mysqli_num_rows($result_detalles) ?></span> registros
                    </div>
                    <div class="small text-muted">
                        Total: <span class="fw-bold text-primary <?= $color_ocio_detalle ?>" style="font-size: 1.1rem;">$<?= number_format($total_ocio, 0, '', '.') ?></span>
                    </div>
                </div>

                <div class="table-responsive ocio">
                    <table class="table modern-table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 ps-4" style="font-size: 0.75rem; text-transform: uppercase;">Descripción</th>
                                <th class="border-0" style="font-size: 0.75rem; text-transform: uppercase;">Categoría</th>
                                <th class="border-0" style="font-size: 0.75rem; text-transform: uppercase;">Fecha</th>
                                <th class="border-0 pe-4 text-end" style="font-size: 0.75rem; text-transform: uppercase;">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($detalle = mysqli_fetch_assoc($result_detalles)):
                                // Determinar origen visual (SISTEMA vs EXTERNO)
                                $es_externo = (isset($detalle['fuente']) && $detalle['fuente'] === 'externo');
                            ?>
                                <tr class="align-middle">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <span class="dot-indicador <?= $es_externo ? 'bg-externo' : 'bg-sistema' ?> me-2"
                                                style="height: 8px; width: 8px; border-radius: 50%; display: inline-block;"
                                                title="<?= $es_externo ? 'Externo' : 'Sistema' ?>"></span>
                                            <span class="fw-medium"><?= htmlspecialchars($detalle['Descripcion']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge category-ocio" style="font-size: 0.7rem;">
                                            <i class="fas fa-utensils"></i>
                                            <?= htmlspecialchars($detalle['categoria']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($detalle['Fecha'])) ?>
                                    </td>
                                    <td class="text-end pe-4 fw-bold <?= $es_externo ? 'text-secondary' : 'text-dark' ?>">
                                        $<?= number_format($detalle['Valor'], 0, '', '.') ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal" style="border-radius: 10px;">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Repetir similar para ocio y ahorro -->