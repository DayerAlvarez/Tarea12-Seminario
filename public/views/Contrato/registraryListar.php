<?php
require_once 'app/controllers/contrato.controller.php';

// Obtener datos para mostrar en la vista
try {
    $controller = new ContratoController();
    $respuesta = $controller->obtenerTodosParaVista();
    $contratos = $respuesta['datos'] ?? [];
    $mensaje_exito = isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : null;
    $mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : null;
    
    // Limpiar mensajes de sesión
    unset($_SESSION['mensaje_exito']);
    unset($_SESSION['mensaje_error']);
} catch (Exception $e) {
    $mensaje_error = $e->getMessage();
    $contratos = [];
}
?>

<!-- Incluir CSS -->
<link rel="stylesheet" href="public/css/contrato.css">

<div class="section-header">
    <h2 class="section-title">Lista de Contratos</h2>
    <button class="btn-registrar" onclick="abrirModal()">+ Registrar Nuevo Contrato</button>
</div>

<?php if (isset($mensaje_exito)): ?>
    <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
<?php endif; ?>

<?php if (isset($mensaje_error)): ?>
    <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
<?php endif; ?>

<?php if (count($contratos) > 0): ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="id-col">ID</th>
                    <th class="beneficiario-col">BENEFICIARIO</th>
                    <th class="dni-col">DNI</th>
                    <th class="monto-col">MONTO</th>
                    <th class="interes-col">INTERÉS</th>
                    <th class="fecha-col">FECHA INICIO</th>
                    <th class="dia-col">DÍA PAGO</th>
                    <th class="cuotas-col">CUOTAS</th>
                    <th class="estado-col">ESTADO</th>
                    <th class="acciones-col">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contratos as $contrato): ?>
                    <tr>
                        <td class="id-col"><?php echo $contrato['idcontrato']; ?></td>
                        <td class="beneficiario-col"><?php echo $contrato['beneficiario_nombre']; ?></td>
                        <td class="dni-col"><?php echo $contrato['beneficiario_dni']; ?></td>
                        <td class="monto-col">S/. <?php echo number_format($contrato['monto'], 2); ?></td>
                        <td class="interes-col"><?php echo number_format($contrato['interes'], 2); ?>%</td>
                        <td class="fecha-col"><?php echo date('d/m/Y', strtotime($contrato['fechainicio'])); ?></td>
                        <td class="dia-col">Día <?php echo $contrato['diapago']; ?></td>
                        <td class="cuotas-col"><?php echo $contrato['numcuotas']; ?> meses</td>
                        <td class="estado-col">
                            <?php if ($contrato['estado'] === 'ACT'): ?>
                                <span class="badge badge-active">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-finished">Finalizado</span>
                            <?php endif; ?>
                        </td>
                        <td class="acciones-col">
                            <button type="button" class="btn-action" onclick="verCronograma(<?php echo $contrato['idcontrato']; ?>)">
                                📅 Ver Cronograma
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="no-data">
        <div>📋</div>
        <p>No hay contratos registrados</p>
    </div>
<?php endif; ?>

<!-- Modal para registrar contrato (Rediseñado) -->
<div id="modalContrato" class="modal contrato-modal">
    <div class="modal-content compact-modal">
        <div class="modal-header">
            <h2>Registrar Nuevo Contrato</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body no-scroll">
            <form method="POST" action="?seccion=contratos&action=crear" onsubmit="return validarFormulario()">
                <input type="hidden" id="idbeneficiario" name="idbeneficiario" value="">
                
                <!-- Alerta si tiene contratos activos -->
                <div id="alertaContrato" class="alert-warning form-group-full">
                    <strong>El beneficiario ya tiene un contrato activo.</strong> 
                    Debe finalizar el contrato actual antes de crear uno nuevo.
                </div>
                
                <div class="form-compact">
                    <!-- Fila 1: DNI y Nombre -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dni_buscar">DNI del Beneficiario *</label>
                            <div class="search-container">
                                <input type="text" id="dni_buscar" class="form-control" placeholder="Ingrese DNI (8 dígitos)" maxlength="8">
                                <button type="button" id="btnBuscar" class="btn-buscar" onclick="buscarBeneficiario()">
                                    🔍 Buscar
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nombre_beneficiario">Nombre Completo</label>
                            <input type="text" id="nombre_beneficiario" class="form-control" placeholder="Busque por DNI primero" disabled readonly>
                        </div>
                    </div>
                    
                    <!-- Fila 2: Monto, Interés y Cuotas -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="monto">Monto del Préstamo (S/.) *</label>
                            <input type="number" id="monto" name="monto" class="form-control" required min="0.01" step="0.01" placeholder="50000" oninput="calcularResumen()" disabled>
                        </div>
                        <div class="form-group">
                            <label for="interes">Interés Mensual (%) *</label>
                            <input type="number" id="interes" name="interes" class="form-control" required min="0" max="100" step="0.01" placeholder="5" oninput="calcularResumen()" disabled>
                        </div>
                        <div class="form-group">
                            <label for="numcuotas">Número de Cuotas (meses) *</label>
                            <input type="number" id="numcuotas" name="numcuotas" class="form-control" required min="1" max="255" placeholder="12" oninput="calcularResumen()" disabled>
                        </div>
                    </div>
                    
                    <!-- Fila 3: Fecha de inicio y Día de pago -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fechainicio">Fecha de Inicio *</label>
                            <input type="date" id="fechainicio" name="fechainicio" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="diapago">Día de Pago *</label>
                            <select id="diapago" name="diapago" class="form-control" required disabled>
                                <option value="">Seleccionar día...</option>
                                <?php for ($i = 1; $i <= 31; $i++): ?>
                                    <option value="<?php echo $i; ?>">Día <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Resumen del Préstamo -->
                <div class="loan-summary" id="loanSummary" style="display: none;">
                    <div class="summary-title">Resumen del Préstamo</div>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-label">Cuota Mensual</div>
                            <div class="summary-value summary-monthly" id="cuotaMensual">S/. 0.00</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Total a Pagar</div>
                            <div class="summary-value summary-total" id="totalPagar">S/. 0.00</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Total Intereses</div>
                            <div class="summary-value summary-interest" id="totalIntereses">S/. 0.00</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" id="btnCrearContrato" class="btn-primary" disabled>Crear Contrato</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para mostrar cronograma de pagos -->
<div id="modalCronograma" class="modal cronograma-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Cronograma de Pagos</h2>
            <span class="close" onclick="cerrarModalCronograma()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="cronogramaLoader" class="loader-container">
                <div class="loader"></div>
                <p>Cargando cronograma...</p>
            </div>
            <div id="cronogramaInfo" class="cronograma-info">
                <h3 id="cronogramaTitulo"></h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Beneficiario:</span>
                        <span id="cronogramaBeneficiario" class="info-value"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">DNI:</span>
                        <span id="cronogramaDni" class="info-value"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Monto:</span>
                        <span id="cronogramaMonto" class="info-value"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Interés:</span>
                        <span id="cronogramaInteres" class="info-value"></span>
                    </div>
                </div>
            </div>
            <div id="cronogramaTabla" class="cronograma-tabla"></div>
        </div>
    </div>
</div>

<!-- Incluir JavaScript -->
<script src="public/js/contrato.js"></script>

<?php if (isset($mensaje_error)): ?>
<script>
    // Mostrar modal si hay error (para mantener el formulario abierto)
    document.addEventListener('DOMContentLoaded', function() {
        abrirModal();
    });
</script>
<?php endif; ?>