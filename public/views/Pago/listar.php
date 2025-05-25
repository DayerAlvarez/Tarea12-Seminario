<?php
require_once 'app/controllers/pago.controller.php';

// Obtener datos para mostrar en la vista
try {
    $controller = new PagoController();
    $respuesta = $controller->obtenerPagosRealizadosParaVista();
    $pagos = $respuesta['datos'] ?? [];
    $mensaje_exito = isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : null;
    $mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : null;
    
    // Limpiar mensajes de sesión
    unset($_SESSION['mensaje_exito']);
    unset($_SESSION['mensaje_error']);
} catch (Exception $e) {
    $mensaje_error = $e->getMessage();
    $pagos = [];
}
?>

<!-- Incluir CSS -->
<link rel="stylesheet" href="public/css/pago.css">

<div class="section-header">
    <h2 class="section-title">Registro de Pagos Realizados</h2>
    <button class="btn-registrar" onclick="abrirModalPago()">+ Registrar Nuevo Pago</button>
</div>

<?php if (isset($mensaje_exito)): ?>
    <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
<?php endif; ?>

<?php if (isset($mensaje_error)): ?>
    <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
<?php endif; ?>

<?php if (count($pagos) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID Pago</th>
                <th>Beneficiario</th>
                <th>DNI</th>
                <th>Contrato</th>
                <th>Cuota</th>
                <th>Fecha Pago</th>
                <th>Monto</th>
                <th>Penalidad</th>
                <th>Total</th>
                <th>Medio</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagos as $pago): ?>
                <tr>
                    <td><strong><?php echo $pago['idpago']; ?></strong></td>
                    <td><?php echo $pago['beneficiario_nombre']; ?></td>
                    <td><?php echo $pago['beneficiario_dni']; ?></td>
                    <td><?php echo $pago['idcontrato']; ?></td>
                    <td><?php echo $pago['numcuota']; ?> de <?php echo $pago['numcuotas'] ?? ''; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($pago['fechapago'])); ?></td>
                    <td class="currency">S/. <?php echo number_format($pago['monto'], 2); ?></td>
                    <td class="currency penalidad">
                        <?php if ($pago['penalidad'] > 0): ?>
                            S/. <?php echo number_format($pago['penalidad'], 2); ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="currency total">S/. <?php echo number_format($pago['monto'] + $pago['penalidad'], 2); ?></td>
                    <td>
                        <?php if ($pago['medio'] === 'EFC'): ?>
                            <span class="badge badge-efectivo">Efectivo</span>
                        <?php else: ?>
                            <span class="badge badge-deposito">Depósito</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="no-data">
        <div>💳</div>
        <p>No hay pagos registrados</p>
    </div>
<?php endif; ?>

<!-- Modal para registrar pago -->
<div id="modalPago" class="modal pago-modal">
    <div class="modal-content compact-modal">
        <div class="modal-header">
            <h2>Registrar Nuevo Pago</h2>
            <span class="close" onclick="cerrarModalPago()">&times;</span>
        </div>
        <div class="modal-body no-scroll">
            <div id="paso1" class="paso-container">
                <h3 class="paso-titulo">Paso 1: Buscar Contrato</h3>
                
                <div class="search-container">
                    <div class="search-input">
                        <label for="dni_buscar">DNI del Beneficiario</label>
                        <input type="text" id="dni_buscar" class="form-control" placeholder="Ingrese DNI (8 dígitos)" maxlength="8">
                    </div>
                    <button type="button" id="btnBuscarContrato" class="btn-buscar" onclick="buscarContrato()">
                        🔍 Buscar
                    </button>
                </div>
                
                <div id="contratoInfo" class="contrato-info" style="display: none;">
                    <h4>Información del Contrato</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Beneficiario:</span>
                            <span id="infoBeneficiario" class="info-value"></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">DNI:</span>
                            <span id="infoDni" class="info-value"></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Contrato:</span>
                            <span id="infoContrato" class="info-value"></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Monto:</span>
                            <span id="infoMonto" class="info-value"></span>
                        </div>
                    </div>
                    
                    <h4>Cuotas Pendientes</h4>
                    <div id="cuotasPendientes" class="cuotas-pendientes"></div>
                </div>
                
                <div id="paso1Footer" class="paso-footer">
                    <button type="button" class="btn-cancel" onclick="cerrarModalPago()">Cancelar</button>
                    <button type="button" id="btnSiguientePaso" class="btn-primary" onclick="mostrarPaso2()" disabled>
                        Continuar
                    </button>
                </div>
            </div>
            
            <div id="paso2" class="paso-container" style="display: none;">
                <h3 class="paso-titulo">Paso 2: Confirmar Pago</h3>
                
                <form method="POST" action="?seccion=pagos&action=registrar" id="formPago" onsubmit="return validarFormularioPago()">
                    <input type="hidden" id="idpago" name="idpago" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_actual">Fecha de Pago</label>
                            <input type="text" id="fecha_actual" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="cuota_detalle">Cuota</label>
                            <input type="text" id="cuota_detalle" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="monto_cuota">Monto</label>
                            <input type="text" id="monto_cuota" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="penalidad">Penalidad (10%)</label>
                            <input type="text" id="penalidad" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label for="total_pagar">Total a Pagar</label>
                            <input type="text" id="total_pagar" class="form-control total-field" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="medio">Medio de Pago *</label>
                            <select id="medio" name="medio" class="form-control" required>
                                <option value="">Seleccionar medio...</option>
                                <option value="EFC">Efectivo</option>
                                <option value="DEP">Depósito</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="paso-footer">
                        <button type="button" class="btn-cancel" onclick="volverPaso1()">Volver</button>
                        <button type="submit" class="btn-primary">Registrar Pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Incluir JavaScript -->
<script src="public/js/pago.js"></script>

<?php if (isset($mensaje_error)): ?>
<script>
    // Mostrar modal si hay error (para mantener el formulario abierto)
    document.addEventListener('DOMContentLoaded', function() {
        abrirModalPago();
    });
</script>
<?php endif; ?>