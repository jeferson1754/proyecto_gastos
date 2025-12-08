<div class="modal fade" id="modalAgenteFinanciero" tabindex="-1" aria-labelledby="modalAgenteFinancieroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalAgenteFinancieroLabel">
                    <i class="bi bi-robot me-2"></i> Prompt de Análisis Financiero
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Copia el siguiente texto (en formato **Markdown**) y pégalo en tu modelo de IA para recibir un análisis detallado:</p>
                <textarea id="promptContent" class="form-control" rows="20" readonly><?php echo trim($prompt_analisis); ?></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="copiarPrompt()">Copiar al Portapapeles</button>
            </div>
        </div>
    </div>
</div>

<script>
    function copiarPrompt() {
        const textarea = document.getElementById('promptContent');
        textarea.select();
        document.execCommand('copy');
        alert('¡Prompt copiado al portapapeles!');
    }
</script>