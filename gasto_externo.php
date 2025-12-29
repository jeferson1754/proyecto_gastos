                    <style>
                        /* Estilo para agrandar el switch */
                        .form-check-input-lg {
                            width: 3.5rem !important;
                            height: 1.75rem !important;
                            cursor: pointer;
                        }

                        /* Ajuste para que el label quede centrado verticalmente con el switch grande */
                        .form-check-label-lg {
                            padding-top: 0.25rem;
                            margin-left: 0.75rem;
                            font-size: 1.1rem;
                            cursor: pointer;
                        }

                        /* Colores personalizados cuando está activo/inactivo */
                        .form-check-input:checked {
                            background-color: #0d6efd !important;
                            /* Azul Sistema */
                            border-color: #0d6efd !important;
                        }

                        .form-check-input:not(:checked) {
                            background-color: #6c757d !important;
                            /* Verde Externo */
                            border-color: #6c757d !important;
                            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='white'/%3e%3c/svg%3e") !important;
                        }
                    </style>

                    <div class="mb-4 p-3 border rounded bg-light shadow-sm contenedor-fuente">
                        <label class="form-label d-block fw-bold mb-3 text-uppercase small text-muted">
                            ¿Cómo pagarás este gasto?
                        </label>

                        <div class="form-check form-switch d-flex align-items-center">
                            <input class="form-check-input form-check-input-lg switch-dinero" type="checkbox" role="switch"
                                name="fuente_dinero[]" value="sistema" checked>

                            <label class="form-check-label form-check-label-lg fw-semibold label-fuente">
                                <i class="fas fa-university me-2 text-primary"></i> Dinero del Sistema
                            </label>
                        </div>

                        <div class="mt-3 p-2 rounded-2 bg-white border-start border-4 border-primary caja-ayuda">
                            <small class="text-muted texto-ayuda">
                                Este gasto será restado automáticamente de tu <strong>Balance Mensual</strong>.
                            </small>
                        </div>
                    </div>

                    <script>
                        // Seleccionamos todos los elementos con la clase 'switch-dinero'
                        document.querySelectorAll('.switch-dinero').forEach(function(elemento) {
                            elemento.addEventListener('change', function() {
                                // Buscamos el contenedor padre de este switch específico
                                const contenedor = this.closest('.contenedor-fuente');

                                // Buscamos los elementos hijos dentro de ese contenedor
                                const label = contenedor.querySelector('.label-fuente');
                                const ayuda = contenedor.querySelector('.texto-ayuda');
                                const boxAyuda = contenedor.querySelector('.caja-ayuda');

                                if (this.checked) {
                                    label.innerHTML = '<i class="fas fa-university me-2 text-primary"></i> Dinero del Sistema';
                                    ayuda.innerHTML = 'Este gasto será restado automáticamente de tu <strong>Balance Mensual</strong>.';
                                    boxAyuda.style.borderColor = '#0d6efd';
                                } else {
                                    label.innerHTML = '<i class="fas fa-wallet me-2 text-secondary"></i> Efectivo Externo';
                                    ayuda.innerHTML = 'Gasto <strong>Informativo</strong>: No afecta tus cuentas ni tu balance.';
                                    boxAyuda.style.borderColor = '#6c757d';
                                }
                            });
                        });
                    </script>