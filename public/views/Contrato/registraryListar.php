<?php
// Funciones para obtener datos de contratos y beneficiarios
function obtenerContratos($pdo) {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
               b.dni as beneficiario_dni
        FROM contratos c 
        INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
        ORDER BY c.fechainicio DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para buscar beneficiario por DNI
function buscarBeneficiarioPorDni($pdo, $dni) {
    $stmt = $pdo->prepare("SELECT * FROM beneficiarios WHERE dni = ?");
    $stmt->execute([$dni]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Función para verificar si un beneficiario tiene contratos activos
function tieneContratosActivos($pdo, $idbeneficiario) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contratos WHERE idbeneficiario = ? AND estado = 'ACT'");
    $stmt->execute([$idbeneficiario]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado['total'] > 0;
}

// Procesar búsqueda de beneficiario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar_beneficiario'])) {
    $response = array();
    
    try {
        $dni = trim($_POST['dni_buscar']);
        
        if (empty($dni)) {
            throw new Exception("El DNI es requerido");
        }
        
        if (!preg_match('/^\d{8}$/', $dni)) {
            throw new Exception("El DNI debe tener exactamente 8 dígitos");
        }
        
        $beneficiario = buscarBeneficiarioPorDni($pdo, $dni);
        
        if (!$beneficiario) {
            throw new Exception("No se encontró beneficiario con el DNI: " . $dni);
        }
        
        // Verificar si tiene contratos activos
        $tieneContratosActivos = tieneContratosActivos($pdo, $beneficiario['idbeneficiario']);
        
        $response = array(
            'success' => true,
            'beneficiario' => $beneficiario,
            'tiene_contratos_activos' => $tieneContratosActivos
        );
        
    } catch(Exception $e) {
        $response = array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Procesar el registro de nuevo contrato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_contrato') {
    try {
        // Validar datos
        $idbeneficiario = (int)$_POST['idbeneficiario'];
        $monto = (float)$_POST['monto'];
        $interes = (float)$_POST['interes'];
        $fechainicio = trim($_POST['fechainicio']);
        $diapago = (int)$_POST['diapago'];
        $numcuotas = (int)$_POST['numcuotas'];

        
        // Validaciones básicas
        if (empty($idbeneficiario) || empty($monto) || empty($interes) || empty($fechainicio) || empty($diapago) || empty($numcuotas)) {
            throw new Exception("Todos los campos obligatorios deben ser completados");
        }
        
        // Validar que el beneficiario existe
        $stmt = $pdo->prepare("SELECT idbeneficiario FROM beneficiarios WHERE idbeneficiario = ?");
        $stmt->execute([$idbeneficiario]);
        if (!$stmt->fetch()) {
            throw new Exception("El beneficiario seleccionado no existe");
        }
        
        // Verificar si el beneficiario ya tiene contratos activos
        if (tieneContratosActivos($pdo, $idbeneficiario)) {
            throw new Exception("El beneficiario ya tiene un contrato activo. Debe finalizar el contrato actual antes de crear uno nuevo.");
        }
        
        // Validaciones específicas
        if ($monto <= 0) {
            throw new Exception("El monto debe ser mayor a 0");
        }
        
        if ($interes < 0 || $interes > 100) {
            throw new Exception("El interés debe ser entre 0 y 100%");
        }
        
        if (strtotime($fechainicio) < strtotime(date('Y-m-d'))) {
            throw new Exception("La fecha de inicio no puede ser anterior a hoy");
        }
        
        if ($diapago < 1 || $diapago > 31) {
            throw new Exception("El día de pago debe ser entre 1 y 31");
        }
        
        if ($numcuotas < 1 || $numcuotas > 255) {
            throw new Exception("El número de cuotas debe ser entre 1 y 255");
        }
        
        // Insertar nuevo contrato
        $stmt = $pdo->prepare("
            INSERT INTO contratos (idbeneficiario, monto, interes, fechainicio, diapago, numcuotas) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$idbeneficiario, $monto, $interes, $fechainicio, $diapago, $numcuotas]);
        
        $mensaje_exito = "Contrato registrado correctamente";
        
    } catch(Exception $e) {
        $mensaje_error = $e->getMessage();
    }
}

$contratos = obtenerContratos($pdo);
?>

<style>
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f3f4;
    }

    .section-title {
        font-size: 1.8em;
        color: #2c3e50;
        font-weight: 700;
    }

    .btn-registrar {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    }

    .btn-registrar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .data-table th {
        background: linear-gradient(135deg, #495057, #6c757d);
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9em;
        letter-spacing: 0.5px;
    }

    .data-table td {
        padding: 15px;
        border-bottom: 1px solid #f1f3f4;
        color: #495057;
    }

    .data-table tr:hover {
        background: #f8f9fa;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .data-table tr:last-child td {
        border-bottom: none;
    }

    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8em;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-active {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .badge-finished {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
    }

    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
        font-size: 1.2em;
    }

    .no-data i {
        font-size: 3em;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.6);
        backdrop-filter: blur(8px);
    }

    .modal-content {
        background-color: #ffffff;
        margin: 2% auto;
        padding: 0;
        border: none;
        width: 90%;
        max-width: 700px;
        border-radius: 12px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        animation: modalSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        max-height: 95vh;
        overflow: hidden;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-100px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 25px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }

    .modal-header::before {
        content: '📋';
        font-size: 1.5em;
        margin-right: 10px;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1.4em;
        font-weight: 600;
    }

    .close {
        color: white;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
    }

    .close:hover {
        background: rgba(255,255,255,0.2);
        transform: rotate(90deg);
    }

    .modal-body {
        padding: 0;
        max-height: calc(95vh - 120px);
        overflow-y: auto;
    }

    .form-container {
        padding: 30px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 25px;
    }

    .form-group-full {
        grid-column: 1 / -1;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9em;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 1em;
        transition: all 0.3s ease;
        box-sizing: border-box;
        background: #fafbfc;
    }

    .form-control:focus {
        outline: none;
        border-color: #007bff;
        background: white;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        transform: translateY(-1px);
    }

    .form-control:hover {
        border-color: #007bff;
        background: white;
    }

    .form-control:disabled {
        background-color: #f8f9fa;
        color: #6c757d;
        cursor: not-allowed;
        opacity: 0.8;
    }

    /* Estilos para búsqueda de beneficiario */
    .search-container {
        display: flex;
        gap: 10px;
        align-items: end;
    }

    .search-input {
        flex: 1;
    }

    .btn-buscar {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-size: 0.9em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-buscar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }

    .btn-buscar:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .alert-warning {
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        display: none;
    }

    .alert-warning.show {
        display: block;
        animation: slideDown 0.3s ease;
    }

    .alert-warning::before {
        content: '⚠️ ';
        font-size: 1.2em;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Resumen del Préstamo */
    .loan-summary {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 12px;
        padding: 25px;
        margin: 25px 0;
        border-left: 4px solid #007bff;
    }

    .summary-title {
        font-size: 1.1em;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }

    .summary-title::before {
        content: '📊';
        margin-right: 8px;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .summary-item {
        background: white;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e1e8ed;
    }

    .summary-label {
        font-size: 0.8em;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .summary-value {
        font-size: 1.3em;
        font-weight: 700;
    }

    .summary-monthly {
        color: #007bff;
    }

    .summary-total {
        color: #28a745;
    }

    .summary-interest {
        color: #ffc107;
    }

    .modal-footer {
        padding: 25px 30px;
        background: #f8f9fa;
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        border-top: 1px solid #e9ecef;
    }

    .btn-cancel {
        background: #6c757d;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-1px);
    }

    .btn-primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    }

    .btn-primary:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-weight: 500;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .currency {
        font-weight: 600;
        color: #28a745;
    }

    .loading {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .section-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .data-table {
            font-size: 0.8em;
        }
        
        .data-table th,
        .data-table td {
            padding: 8px 6px;
        }

        .modal-content {
            width: 95%;
            margin: 5% auto;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .summary-grid {
            grid-template-columns: 1fr;
        }

        .modal-footer {
            flex-direction: column;
        }

        .search-container {
            flex-direction: column;
            gap: 15px;
        }
    }
</style>

<div class="section-header">
    <h2 class="section-title">Lista de Contratos</h2>
    <button class="btn-registrar" onclick="abrirModal()">+ Nuevo Contrato de Préstamo</button>
</div>

<?php if (isset($mensaje_exito)): ?>
    <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
<?php endif; ?>

<?php if (isset($mensaje_error)): ?>
    <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
<?php endif; ?>

<?php if (count($contratos) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Beneficiario</th>
                <th>DNI</th>
                <th>Monto</th>
                <th>Interés</th>
                <th>Fecha Inicio</th>
                <th>Día Pago</th>
                <th>Cuotas</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contratos as $contrato): ?>
                <tr>
                    <td><strong><?php echo $contrato['idcontrato']; ?></strong></td>
                    <td><?php echo $contrato['beneficiario_nombre']; ?></td>
                    <td><?php echo $contrato['beneficiario_dni']; ?></td>
                    <td class="currency">S/. <?php echo number_format($contrato['monto'], 2); ?></td>
                    <td><?php echo number_format($contrato['interes'], 2); ?>%</td>
                    <td><?php echo date('d/m/Y', strtotime($contrato['fechainicio'])); ?></td>
                    <td>Día <?php echo $contrato['diapago']; ?></td>
                    <td><?php echo $contrato['numcuotas']; ?> meses</td>
                    <td>
                        <?php if ($contrato['estado'] === 'ACT'): ?>
                            <span class="badge badge-active">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-finished">Finalizado</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="no-data">
        <div>📋</div>
        <p>No hay contratos registrados</p>
    </div>
<?php endif; ?>

<!-- Modal para registrar contrato -->
<div id="modalContrato" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nuevo Contrato de Préstamo</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" onsubmit="return validarFormulario()">
                <input type="hidden" name="action" value="registrar_contrato">
                <input type="hidden" id="idbeneficiario" name="idbeneficiario" value="">
                
                <div class="form-container">
                    <div class="form-grid">
                        <!-- Campo de búsqueda por DNI -->
                        <div class="form-group">
                            <label for="dni_buscar">DNI del Beneficiario *</label>
                            <div class="search-container">
                                <div class="search-input">
                                    <input type="text" id="dni_buscar" class="form-control" placeholder="Ingrese DNI (8 dígitos)" maxlength="8">
                                </div>
                                <button type="button" id="btnBuscar" class="btn-buscar" onclick="buscarBeneficiario()">
                                    🔍 Buscar
                                </button>
                            </div>
                        </div>

                        <!-- Campo bloqueado con nombre completo -->
                        <div class="form-group">
                            <label for="nombre_beneficiario">Nombre Completo</label>
                            <input type="text" id="nombre_beneficiario" class="form-control" placeholder="Busque por DNI primero" disabled readonly>
                        </div>

                        <!-- Alerta si tiene contratos activos -->
                        <div id="alertaContrato" class="alert-warning form-group-full">
                            <strong>El beneficiario ya tiene un contrato activo.</strong> 
                            Debe finalizar el contrato actual antes de crear uno nuevo.
                        </div>

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

                        <div class="form-group">
                            <label for="fechainicio">Fecha de Inicio *</label>
                            <input type="date" id="fechainicio" name="fechainicio" class="form-control" required min="<?php echo date('Y-m-d'); ?>" disabled>
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
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" id="btnCrearContrato" class="btn-primary" disabled>Crear Contrato</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let beneficiarioSeleccionado = null;
let tieneContratosActivos = false;

function abrirModal() {
    document.getElementById('modalContrato').style.display = 'block';
    document.body.style.overflow = 'hidden';
    // Establecer fecha mínima como hoy
    document.getElementById('fechainicio').value = new Date().toISOString().split('T')[0];
    // Limpiar búsqueda anterior
    limpiarFormulario();
}

function cerrarModal() {
    document.getElementById('modalContrato').style.display = 'none';
    document.body.style.overflow = 'auto';
    // Limpiar formulario
    limpiarFormulario();
}

function limpiarFormulario() {
    document.querySelector('#modalContrato form').reset();
    document.getElementById('nombre_beneficiario').value = '';
    document.getElementById('alertaContrato').classList.remove('show');
    document.getElementById('loanSummary').style.display = 'none';
    
    // Deshabilitar campos
    const campos = ['monto', 'interes', 'numcuotas', 'fechainicio', 'diapago'];
    campos.forEach(campo => {
        document.getElementById(campo).disabled = true;
    });
    
    document.getElementById('btnCrearContrato').disabled = true;
    beneficiarioSeleccionado = null;
    tieneContratosActivos = false;
    
    // Restablecer fecha mínima
    document.getElementById('fechainicio').value = new Date().toISOString().split('T')[0];
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modalContrato');
    if (event.target == modal) {
        cerrarModal();
    }
}

function buscarBeneficiario() {
    const dni = document.getElementById('dni_buscar').value.trim();
    const btnBuscar = document.getElementById('btnBuscar');
    
    if (!dni) {
        alert('Por favor ingrese un DNI');
        return;
    }
    
    if (dni.length !== 8 || !/^\d{8}$/.test(dni)) {
        alert('El DNI debe tener exactamente 8 dígitos');
        return;
    }
    
    // Mostrar loading
    btnBuscar.disabled = true;
    btnBuscar.innerHTML = '<div class="loading"></div> Buscando...';
    
    // Hacer petición con fetch
    const formData = new FormData();
    formData.append('buscar_beneficiario', '1');
    formData.append('dni_buscar', dni);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        btnBuscar.disabled = false;
        btnBuscar.innerHTML = '🔍 Buscar';
        
        if (data.success) {
            mostrarBeneficiario(data.beneficiario, data.tiene_contratos_activos);
        } else {
            alert(data.message);
            ocultarBeneficiario();
        }
    })
    .catch(error => {
        btnBuscar.disabled = false;
        btnBuscar.innerHTML = '🔍 Buscar';
        alert('Error al buscar beneficiario: ' + error.message);
        console.error('Error:', error);
        ocultarBeneficiario();
    });
}

function mostrarBeneficiario(beneficiario, tieneContratos) {
    beneficiarioSeleccionado = beneficiario;
    tieneContratosActivos = tieneContratos;
    
    // Mostrar nombre completo en el campo bloqueado
    const nombreCompleto = beneficiario.nombres + ' ' + beneficiario.apellidos;
    document.getElementById('nombre_beneficiario').value = nombreCompleto;
    
    // Establecer ID del beneficiario en el campo oculto
    document.getElementById('idbeneficiario').value = beneficiario.idbeneficiario;
    
    if (tieneContratos) {
        // Mostrar alerta de contratos activos
        document.getElementById('alertaContrato').classList.add('show');
        
        // Mantener campos deshabilitados
        const campos = ['monto', 'interes', 'numcuotas', 'fechainicio', 'diapago'];
        campos.forEach(campo => {
            document.getElementById(campo).disabled = true;
        });
        
        document.getElementById('btnCrearContrato').disabled = true;
    } else {
        // Ocultar alerta
        document.getElementById('alertaContrato').classList.remove('show');
        
        // Habilitar campos del formulario
        const campos = ['monto', 'interes', 'numcuotas', 'fechainicio', 'diapago'];
        campos.forEach(campo => {
            document.getElementById(campo).disabled = false;
        });
        
        document.getElementById('btnCrearContrato').disabled = false;
    }
}

function ocultarBeneficiario() {
    document.getElementById('nombre_beneficiario').value = '';
    document.getElementById('alertaContrato').classList.remove('show');
    document.getElementById('loanSummary').style.display = 'none';
    
    // Deshabilitar campos
    const campos = ['monto', 'interes', 'numcuotas', 'fechainicio', 'diapago'];
    campos.forEach(campo => {
        document.getElementById(campo).disabled = true;
    });
    
    document.getElementById('btnCrearContrato').disabled = true;
    document.getElementById('idbeneficiario').value = '';
    beneficiarioSeleccionado = null;
    tieneContratosActivos = false;
}

function calcularResumen() {
    const monto = parseFloat(document.getElementById('monto').value) || 0;
    const numCuotas = parseInt(document.getElementById('numcuotas').value) || 0;
    const interes = parseFloat(document.getElementById('interes').value) || 0;
    
    if (monto > 0 && numCuotas > 0 && interes >= 0) {
        // Mostrar el resumen
        document.getElementById('loanSummary').style.display = 'block';
        
        // Calcular usando fórmula de amortización francesa
        const tasaMensual = interes / 100; // 5% = 0.05
        let cuotaMensual;
        
        if (tasaMensual === 0) {
            // Si no hay interés, solo dividir el capital
            cuotaMensual = monto / numCuotas;
        } else {
            // Fórmula: C = P × [i × (1+i)^n] / [(1+i)^n - 1]
            const factor = Math.pow(1 + tasaMensual, numCuotas);
            cuotaMensual = monto * (tasaMensual * factor) / (factor - 1);
        }
        
        const totalPagar = cuotaMensual * numCuotas;
        const totalIntereses = totalPagar - monto;
        
        // Actualizar valores en el resumen
        document.getElementById('cuotaMensual').textContent = 'S/. ' + cuotaMensual.toFixed(2);
        document.getElementById('totalPagar').textContent = 'S/. ' + totalPagar.toFixed(2);
        document.getElementById('totalIntereses').textContent = 'S/. ' + totalIntereses.toFixed(2);
    } else {
        document.getElementById('loanSummary').style.display = 'none';
    }
}

function validarFormulario() {
    if (!beneficiarioSeleccionado) {
        alert('Debe buscar y seleccionar un beneficiario válido');
        return false;
    }
    
    if (tieneContratosActivos) {
        alert('El beneficiario seleccionado ya tiene un contrato activo. No se puede crear un nuevo contrato.');
        return false;
    }
    
    const monto = parseFloat(document.getElementById('monto').value);
    const interes = parseFloat(document.getElementById('interes').value);
    const fechainicio = document.getElementById('fechainicio').value;
    const diapago = document.getElementById('diapago').value;
    const numcuotas = parseInt(document.getElementById('numcuotas').value);
    
    if (!monto || monto <= 0) {
        alert('El monto debe ser mayor a 0');
        return false;
    }
    
    if (isNaN(interes) || interes < 0 || interes > 100) {
        alert('El interés debe ser entre 0 y 100%');
        return false;
    }
    
    if (!fechainicio) {
        alert('Debe seleccionar una fecha de inicio');
        return false;
    }
    
    // Validar que la fecha no sea anterior a hoy
    const hoy = new Date();
    const fechaSeleccionada = new Date(fechainicio);
    if (fechaSeleccionada < hoy.setHours(0,0,0,0)) {
        alert('La fecha de inicio no puede ser anterior a hoy');
        return false;
    }
    
    if (!diapago) {
        alert('Debe seleccionar un día de pago');
        return false;
    }
    
    if (!numcuotas || numcuotas < 1 || numcuotas > 255) {
        alert('El número de cuotas debe ser entre 1 y 255');
        return false;
    }
    
    const nombreCompleto = beneficiarioSeleccionado.nombres + ' ' + beneficiarioSeleccionado.apellidos;
    return confirm('¿Está seguro de crear este contrato de préstamo para ' + nombreCompleto + '?');
}

// Formatear DNI mientras se escribe
document.getElementById('dni_buscar').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^\d]/g, '');
    if (this.value.length > 8) {
        this.value = this.value.slice(0, 8);
    }
});

// Permitir buscar con Enter
document.getElementById('dni_buscar').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        buscarBeneficiario();
    }
});

// Formatear monto mientras se escribe
document.getElementById('monto').addEventListener('input', function(e) {
    let value = this.value;
    value = value.replace(/[^\d.]/g, '');
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    this.value = value;
});

// Formatear interés
document.getElementById('interes').addEventListener('input', function(e) {
    let value = this.value;
    value = value.replace(/[^\d.]/g, '');
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    this.value = value;
});

// Formatear número de cuotas (solo números enteros)
document.getElementById('numcuotas').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^\d]/g, '');
    if (parseInt(this.value) > 255) {
        this.value = '255';
    }
});

// Mostrar modal si hay error (para mantener el formulario abierto)
<?php if (isset($mensaje_error)): ?>
    abrirModal();
<?php endif; ?>
</script>