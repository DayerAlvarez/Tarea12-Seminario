<?php
// Funciones para obtener datos de beneficiarios
function obtenerBeneficiarios($pdo) {
    $stmt = $pdo->query("SELECT * FROM beneficiarios ORDER BY apellidos, nombres");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Procesar el registro de nuevo beneficiario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_beneficiario') {
    try {
        // Validar datos
        $apellidos = trim($_POST['apellidos']);
        $nombres = trim($_POST['nombres']);
        $dni = trim($_POST['dni']);
        $telefono = trim($_POST['telefono']);
        $direccion = !empty(trim($_POST['direccion'])) ? trim($_POST['direccion']) : null;
        
        // Validaciones básicas
        if (empty($apellidos) || empty($nombres) || empty($dni) || empty($telefono)) {
            throw new Exception("Todos los campos obligatorios deben ser completados");
        }
        
        if (!preg_match('/^\d{8}$/', $dni)) {
            throw new Exception("El DNI debe tener exactamente 8 dígitos");
        }
        
        if (!preg_match('/^\d{9}$/', $telefono)) {
            throw new Exception("El teléfono debe tener exactamente 9 dígitos");
        }
        
        // Verificar si el DNI ya existe
        $stmt = $pdo->prepare("SELECT idbeneficiario FROM beneficiarios WHERE dni = ?");
        $stmt->execute([$dni]);
        if ($stmt->fetch()) {
            throw new Exception("Ya existe un beneficiario con el DNI: " . $dni);
        }
        
        // Insertar nuevo beneficiario
        $stmt = $pdo->prepare("
            INSERT INTO beneficiarios (apellidos, nombres, dni, telefono, direccion) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$apellidos, $nombres, $dni, $telefono, $direccion]);
        
        $mensaje_exito = "Beneficiario registrado correctamente";
        
    } catch(Exception $e) {
        $mensaje_error = $e->getMessage();
    }
}

$beneficiarios = obtenerBeneficiarios($pdo);
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
        background: linear-gradient(135deg, #28a745, #20c997);
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
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-registrar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
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
        background-color: rgba(0,0,0,0.5);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 0;
        border: none;
        width: 90%;
        max-width: 500px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #2c3e50, #34495e);
        color: white;
        padding: 20px;
        border-radius: 15px 15px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1.5em;
    }

    .close {
        color: white;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .close:hover {
        transform: scale(1.1);
        opacity: 0.8;
    }

    .modal-body {
        padding: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1em;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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

    @media (max-width: 768px) {
        .section-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .data-table {
            font-size: 0.9em;
        }
        
        .data-table th,
        .data-table td {
            padding: 10px 8px;
        }

        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }
</style>

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
<div id="modalBeneficiario" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🙍‍♂️ Registrar Beneficiario</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" onsubmit="return validarFormulario()">
                <input type="hidden" name="action" value="registrar_beneficiario">
                
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

<script>
function abrirModal() {
    document.getElementById('modalBeneficiario').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function cerrarModal() {
    document.getElementById('modalBeneficiario').style.display = 'none';
    document.body.style.overflow = 'auto';
    // Limpiar formulario
    document.querySelector('#modalBeneficiario form').reset();
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modalBeneficiario');
    if (event.target == modal) {
        cerrarModal();
    }
}

// Validar solo números en DNI y teléfono
document.getElementById('dni').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').substring(0, 8);
});

document.getElementById('telefono').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').substring(0, 9);
});

function validarFormulario() {
    const dni = document.getElementById('dni').value;
    const telefono = document.getElementById('telefono').value;
    
    if (dni.length !== 8) {
        alert('El DNI debe tener exactamente 8 dígitos');
        return false;
    }
    
    if (telefono.length !== 9) {
        alert('El teléfono debe tener exactamente 9 dígitos');
        return false;
    }
    
    return true;
}

// Mostrar modal si hay error (para mantener el formulario abierto)
<?php if (isset($mensaje_error)): ?>
    abrirModal();
<?php endif; ?>
</script>