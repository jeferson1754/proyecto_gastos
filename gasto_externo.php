<?php
// Para ocultar el crédito (por ejemplo, en el módulo de Ahorros) define $ocultar_credito = true; antes de incluir este archivo.
$col_medio = empty($ocultar_credito) ? 'col-4' : 'col-6';
?>
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

                        /* Ocultar el radio button real */
                        .radio-medio-pago {
                            display: none;
                        }

                        /* Estilo de los cuadros (Cards) */
                        .medio-pago-box {
                            cursor: pointer;
                            transition: all 0.3s ease;
                            border: 2px solid #dee2e6;
                            text-align: center;
                            padding: 1rem;
                            border-radius: 12px;
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            background-color: white;
                        }

                        .medio-pago-box i {
                            font-size: 1.5rem;
                            margin-bottom: 0.5rem;
                            transition: transform 0.3s ease;
                        }

                        /* Efecto Hover */
                        .medio-pago-box:hover {
                            border-color: #adb5bd;
                            transform: translateY(-2px);
                        }

                        /* Estado Seleccionado */
                        .radio-medio-pago:checked+.medio-pago-box {
                            border-color: #0d6efd;
                            background-color: #f0f7ff;
                            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
                        }

                        .radio-medio-pago:checked+.medio-pago-box i {
                            transform: scale(1.2);
                            color: #0d6efd !important;
                        }

                        .radio-medio-pago:checked+.medio-pago-box span {
                            color: #0d6efd;
                            font-weight: bold;
                        }

                        /* Caja de ayuda dinámica */
                        .caja-ayuda-medio {
                            transition: all 0.3s ease;
                            border-left-width: 4px !important;
                        }
                    </style>
                    <div class="contenedor-logica-gastos">
                        <div class="mb-4 p-3 border rounded bg-light shadow-sm contenedor-fuente">
                            <label class="form-label d-block fw-bold mb-3 text-uppercase small text-muted">
                                ¿Cómo pagarás este gasto?
                            </label>

                            <!-- El checkbox desmarcado no se envía: este campo siempre viaja con 'sistema' o 'externo' -->
                            <input type="hidden" name="fuente_origen" class="input-fuente-origen" value="sistema">

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

                        <div class="mb-4 p-4 border rounded bg-light shadow-sm contenedor-medios">
                            <label class="form-label d-block fw-bold mb-3 text-uppercase small text-muted">
                                Selecciona el Medio de Pago
                            </label>

                            <div class="row g-3">
                                <div class="<?php echo $col_medio; ?>">
                                    <label class="w-100">
                                        <input type="radio" name="medio_pago" value="1" class="radio-medio-pago" checked>
                                        <div class="medio-pago-box">
                                            <i class="fas fa-credit-card text-muted"></i>
                                            <span class="small">Débito</span>
                                        </div>
                                    </label>
                                </div>

<?php if (empty($ocultar_credito)): ?>
                                <div class="<?php echo $col_medio; ?>">
                                    <label class="w-100">
                                        <input type="radio" name="medio_pago" value="2" class="radio-medio-pago">
                                        <div class="medio-pago-box">
                                            <i class="fas fa-hand-holding-usd text-muted"></i>
                                            <span class="small">Crédito</span>
                                        </div>
                                    </label>
                                </div>
<?php endif; ?>

                                <div class="<?php echo $col_medio; ?>">
                                    <label class="w-100">
                                        <input type="radio" name="medio_pago" value="3" class="radio-medio-pago">
                                        <div class="medio-pago-box">
                                            <i class="fas fa-money-bill-wave text-muted"></i>
                                            <span class="small">Efectivo</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4 p-2 rounded-2 bg-white border caja-ayuda-medio" style="border-color: #0d6efd;">
                                <small class="text-muted texto-ayuda-medio">
                                    <i class="fas fa-info-circle me-1"></i> El pago se descontará de tu <strong>saldo disponible</strong> inmediatamente.
                                </small>
                            </div>
                        </div>

                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {

                            // Función para actualizar la UI del Switch de Fuente
                            function actualizarInterfazFuente(switchElement) {
                                const contenedorFuente = switchElement.closest('.contenedor-fuente');
                                const label = contenedorFuente.querySelector('.label-fuente');
                                const ayuda = contenedorFuente.querySelector('.texto-ayuda');
                                const boxAyuda = contenedorFuente.querySelector('.caja-ayuda');
                                const inputOrigen = contenedorFuente.querySelector('.input-fuente-origen');
                                inputOrigen.value = switchElement.checked ? 'sistema' : 'externo';

                                if (switchElement.checked) {
                                    label.innerHTML = '<i class="fas fa-university me-2 text-primary"></i> Dinero del Sistema';
                                    ayuda.innerHTML = 'Este gasto será restado automáticamente de tu <strong>Balance Mensual</strong>.';
                                    boxAyuda.style.borderColor = '#0d6efd';
                                } else {
                                    label.innerHTML = '<i class="fas fa-wallet me-2 text-secondary"></i> Efectivo Externo';
                                    ayuda.innerHTML = 'Gasto <strong>Informativo</strong>: No afecta tus cuentas ni tu balance.';
                                    boxAyuda.style.borderColor = '#6c757d';
                                }
                            }

                            // Evento para los Switches de Dinero
                            document.querySelectorAll('.switch-dinero').forEach(function(elemento) {
                                elemento.addEventListener('change', function() {
                                    const maestro = this.closest('.contenedor-logica-gastos');
                                    const radioCredito = maestro.querySelector('.radio-medio-pago[value="2"]');
                                    // Crédito siempre es Externo: no permitir marcarlo como Sistema
                                    if (radioCredito && radioCredito.checked && this.checked) {
                                        this.checked = false;
                                    }
                                    actualizarInterfazFuente(this);
                                });
                            });

                            // Evento para los Radios de Medio de Pago
                            document.querySelectorAll('.radio-medio-pago').forEach(function(input) {
                                input.addEventListener('change', function() {
                                    // Buscamos el CONTENEDOR MAESTRO para encontrar el switch hermano
                                    const maestro = this.closest('.contenedor-logica-gastos');
                                    const switchFuente = maestro.querySelector('.switch-dinero');

                                    const contenedorMedios = this.closest('.contenedor-medios');
                                    const ayudaMedio = contenedorMedios.querySelector('.texto-ayuda-medio');
                                    const boxMedio = contenedorMedios.querySelector('.caja-ayuda-medio');

                                    const info = {
                                        '1': { // Débito
                                            texto: '<i class="fas fa-info-circle me-1"></i> El pago se descontará de tu <strong>saldo disponible</strong> inmediatamente.',
                                            color: '#0d6efd'
                                        },
                                        '2': { // Crédito
                                            texto: '<i class="fas fa-exclamation-triangle me-1"></i> Gasto con crédito: se marca como <strong>Externo</strong> y se suma a la factura de tu tarjeta (cierra el 24, vence el 10).',
                                            color: '#ffc107'
                                        },
                                        '3': { // Efectivo
                                            texto: '<i class="fas fa-wallet me-1"></i> Asegúrate de registrar la <strong>salida física</strong> de dinero de tu billetera.',
                                            color: '#198754'
                                        }
                                    };

                                    const seleccion = info[this.value];
                                    if (seleccion) {
                                        ayudaMedio.innerHTML = seleccion.texto;
                                        boxMedio.style.borderColor = seleccion.color;
                                    }

                                    // AUTOMATIZACIÓN: Crédito fuerza Externo; al salir de Crédito se
                                    // restaura lo que el usuario tenía antes (no se pisa su elección).
                                    if (this.value === '2') {
                                        if (maestro.dataset.forzado !== '1') {
                                            maestro.dataset.previo = switchFuente.checked ? '1' : '0';
                                            maestro.dataset.forzado = '1';
                                        }
                                        switchFuente.checked = false;
                                        actualizarInterfazFuente(switchFuente);
                                    } else if (maestro.dataset.forzado === '1') {
                                        switchFuente.checked = (maestro.dataset.previo !== '0');
                                        maestro.dataset.forzado = '0';
                                        actualizarInterfazFuente(switchFuente);
                                    }
                                });
                            });
                        });
                    </script>