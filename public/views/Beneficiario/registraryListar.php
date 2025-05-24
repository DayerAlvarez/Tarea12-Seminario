<?php
require_once 'app/controllers/beneficiario.controller.php';

// Obtener datos para mostrar en la vista
try {
    $controller = new BeneficiarioController();
    $respuesta = $controller->obtenerTodosParaVista();
    $beneficiarios = $respuesta['datos'] ?? [];
    $mensaje_exito = isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : null;
    $mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : null;
    
    // Limpiar mensajes de sesión
    unset($_SESSION['mensaje_exito']);
    unset($_SESSION['mensaje_error']);
} catch (Exception $e) {
    $mensaje_error = $e->getMessage();
    $beneficiarios = [];
}
?>

<!-- Incluir CSS -->
<link rel="stylesheet" href="public/css/beneficiario.css">

<div class="section-header">
    <h2 class="section-title">Lista de Beneficiarios</h2>
    <button class="btn-registrar" onclick="abrirModal()">+ Registrar Beneficiario</button>
</div>

<?php if (isset($mensaje_exito)): ?>
    <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
<?php endif; ?>

<?php if (isset($mensaje_error)): ?>
    <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
<?php endif; ?>

<?php if (count($beneficiarios) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Apellidos y Nombres</th>
                <th>DNI</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Fecha Registro</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($beneficiarios as $beneficiario): ?>
                <tr>
                    <td><?php echo $beneficiario['idbeneficiario']; ?></td>
                    <td><strong><?php echo $beneficiario['apellidos'] . ', ' . $beneficiario['nombres']; ?></strong></td>
                    <td><?php echo $beneficiario['dni']; ?></td>
                    <td><?php echo $beneficiario['telefono']; ?></td>
                    <td><?php echo $beneficiario['direccion'] ?: 'No especificada'; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($beneficiario['creado'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="no-data">
        <div>📋</div>
        <p>No hay beneficiarios registrados</p>
    </div>
<?php endif; ?>

<!-- Modal para registrar beneficiario -->
<div id="modalBeneficiario" class="modal beneficiario-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🙍‍♂️ Registrar Beneficiario</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="?seccion=beneficiarios&action=crear" onsubmit="return validarFormulario()">
                <div class="form-group">
                    <label for="apellidos">Apellidos *</label>
                    <input type="text" id="apellidos" name="apellidos" class="form-control" required maxlength="50">
                </div>

                <div class="form-group">
                    <label for="nombres">Nombres *</label>
                    <input type="text" id="nombres" name="nombres" class="form-control" required maxlength="50">
                </div>

                <div class="form-group">
                    <label for="dni">DNI *</label>
                    <input type="text" id="dni" name="dni" class="form-control" required pattern="[0-9]{8}" maxlength="8" placeholder="12345678">
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono *</label>
                    <input type="text" id="telefono" name="telefono" class="form-control" required pattern="[0-9]{9}" maxlength="9" placeholder="987654321">
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" name="direccion" class="form-control" maxlength="90">
                </div>

                <button type="submit" class="btn-primary">Registrar Beneficiario</button>
            </form>
        </div>
    </div>
</div>

<!-- Incluir JavaScript -->
<script src="public/js/beneficiario.js"></script>

<?php if (isset($mensaje_error)): ?>
<script>
    // Mostrar modal si hay error (para mantener el formulario abierto)
    document.addEventListener('DOMContentLoaded', function() {
        abrirModal();
    });
</script>
<?php endif; ?>