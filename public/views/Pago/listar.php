<?php
require_once 'app/controllers/pago.controller.php';

// Obtener datos para mostrar en la vista
try {
    $controller = new PagoController();
    $respuesta = $controller->obtenerProximosPagosParaVista();
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
    <h2 class="section-title">Gestión de Pagos</h2>
    <button class="btn-registrar" onclick="abrirModalPago()">+ Registrar Pago</button>
</div>

<?php if (isset($mensaje_exito)): ?>
    <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
<?php endif; ?>

<?php if (isset($mensaje_error)): ?>
    <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
<?php endif; ?>

<div class="upcoming-payments">
    <h3 class="upcoming-title">Próximos Pagos</h3>
    
    <?php if (count($pagos) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Contrato</th>
                    <th>Beneficiario</th>
                    <th>DNI</th>
                    <th>Cuota</th>
                    <th>Monto</th>
                    <th>Fecha de Pago</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td><?php echo $pago['idcontrato']; ?></td>
                        <td><?php echo $pago['beneficiario_nombre']; ?></td>
                        <td><?php echo $pago['beneficiario_dni']; ?></td>
                        <td><?php echo $pago['numcuota']; ?> de <?php echo $pago['total_cuotas']; ?></td>
                        <td class="payment-amount">S/. <?php echo number_format($pago['monto'], 2); ?></td>
                        <td class="payment-date"><?php echo $pago['fecha_programada']; ?></td>
                        <td>
                            <?php if ($pago['fechapago'] === null): ?>
                                <span class="payment-status status-pending">Pendiente</span>
                            <?php else: ?>
                                <span class="payment-status status-paid">Pagado</span>
                            <?php endif; ?>
                        </td>
                        <td class="payment-actions">
                            <?php if ($pago['fechapago'] === null): ?>
                                <button class="btn-pay" onclick="registrarPago(<?php echo $pago['idpago']; ?>)">Registrar Pago</button>
                            <?php else: ?>
                                <button class="btn-pay" onclick="anularPago(<?php echo $pago['idpago']; ?>)">Anular Pago</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-payments">
            <div>💸</div>
            <p>No hay pagos próximos pendientes</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para registrar pago -->
<div id="modalPago" class="modal pago-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Registrar Pago</h2>
            <span class="close" onclick="cerrarModalPago()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="?seccion=pagos&action=registrar" id="formPago">
                <input type="hidden" id="idcontrato" name="idcontrato" value="">
                <input type="hidden" id="numcuota" name="numcuota" value="">
                
                <div class="form-group">
                    <label for="contrato_info">Contrato</label>
                    <input type="text" id="contrato_info" class="form-control" disabled>
                </div>
                
                <div class="form-group">
                    <label for="beneficiario_info">Beneficiario</label>
                    <input type="text" id="beneficiario_info" class="form-control" disabled>
                </div>
                
                <div class="form-group">
                    <label for="cuota_info">Cuota</label>
                    <input type="text" id="cuota_info" class="form-control" disabled>
                </div>
                
                <div class="form-group">
                    <label for="monto">Monto a Pagar (S/.) *</label>
                    <input type="number" id="monto" name="monto" class="form-control" required min="0.01" step="0.01">
                </div>
                
                <div class="form-group">
                    <label for="penalidad">Penalidad (S/.)</label>
                    <input type="number" id="penalidad" name="penalidad" class="form-control" min="0" step="0.01" value="0">
                </div>
                
                <div class="form-group">
                    <label for="medio">Medio de Pago *</label>
                    <select id="medio" name="medio" class="form-control" required>
                        <option value="">Seleccionar medio...</option>
                        <option value="EFC">Efectivo</option>
                        <option value="DEP">Depósito</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary">Registrar Pago</button>
            </form>
        </div>
    </div>
</div>

<!-- Incluir JavaScript -->
<script src="public/js/pago.js"></script>

<!-- Inicializar datos de pagos -->
<script>
    // Datos de pagos para el modal
    pagosData = <?php echo json_encode($pagos); ?>;
</script>