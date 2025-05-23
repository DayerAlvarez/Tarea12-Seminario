<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'prestamos';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Determinar qué sección mostrar
$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'beneficiarios';

// Funciones para obtener datos
function obtenerBeneficiarios($pdo) {
    $stmt = $pdo->query("SELECT * FROM beneficiarios ORDER BY apellidos, nombres");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerContratos($pdo) {
    $stmt = $pdo->query("
        SELECT c.*, CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre 
        FROM contratos c 
        INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
        ORDER BY c.fechainicio DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerPagos($pdo) {
    $stmt = $pdo->query("
        SELECT p.*, c.monto as monto_contrato, CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre 
        FROM pagos p 
        INNER JOIN contratos c ON p.idcontrato = c.idcontrato 
        INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
        ORDER BY p.fechapago DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Préstamos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 3px solid #e9ecef;
        }

        .nav-tab {
            flex: 1;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #495057;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s ease;
            border-right: 1px solid #dee2e6;
            position: relative;
        }

        .nav-tab:last-child {
            border-right: none;
        }

        .nav-tab:hover {
            background: #e9ecef;
            color: #2c3e50;
            transform: translateY(-2px);
        }

        .nav-tab.active {
            background: white;
            color: #2c3e50;
            border-bottom: 3px solid #667eea;
        }

        .nav-tab.active::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .content {
            padding: 30px;
            min-height: 500px;
        }

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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-activo {
            background: #d4edda;
            color: #155724;
        }

        .status-finalizado {
            background: #f8d7da;
            color: #721c24;
        }

        .medio-efectivo {
            background: #d1ecf1;
            color: #0c5460;
        }

        .medio-deposito {
            background: #fff3cd;
            color: #856404;
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

        @media (max-width: 768px) {
            .nav-tabs {
                flex-direction: column;
            }
            
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Sistema de Préstamos</h1>
            <p>Gestión integral de beneficiarios, contratos y pagos</p>
        </div>

        <div class="nav-tabs">
            <a href="?seccion=beneficiarios" class="nav-tab <?php echo $seccion === 'beneficiarios' ? 'active' : ''; ?>">
                👥 Beneficiarios
            </a>
            <a href="?seccion=contratos" class="nav-tab <?php echo $seccion === 'contratos' ? 'active' : ''; ?>">
                📋 Contratos
            </a>
            <a href="?seccion=pagos" class="nav-tab <?php echo $seccion === 'pagos' ? 'active' : ''; ?>">
                💳 Pagos
            </a>
        </div>

        <div class="content">
            <?php if ($seccion === 'beneficiarios'): ?>
                <div class="section-header">
                    <h2 class="section-title">Lista de Beneficiarios</h2>
                    <a href="registrar_beneficiario.php" class="btn-registrar">+ Registrar Beneficiario</a>
                </div>
                
                <?php 
                $beneficiarios = obtenerBeneficiarios($pdo);
                if (count($beneficiarios) > 0): ?>
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

            <?php elseif ($seccion === 'contratos'): ?>
                <div class="section-header">
                    <h2 class="section-title">Lista de Contratos</h2>
                    <a href="registrar_contrato.php" class="btn-registrar">+ Registrar Contrato</a>
                </div>
                
                <?php 
                $contratos = obtenerContratos($pdo);
                if (count($contratos) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Beneficiario</th>
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
                                    <td><?php echo $contrato['idcontrato']; ?></td>
                                    <td><strong><?php echo $contrato['beneficiario_nombre']; ?></strong></td>
                                    <td>S/ <?php echo number_format($contrato['monto'], 2); ?></td>
                                    <td><?php echo $contrato['interes']; ?>%</td>
                                    <td><?php echo date('d/m/Y', strtotime($contrato['fechainicio'])); ?></td>
                                    <td><?php echo $contrato['diapago']; ?></td>
                                    <td><?php echo $contrato['numcuotas']; ?> meses</td>
                                    <td>
                                        <span class="status-badge <?php echo $contrato['estado'] === 'ACT' ? 'status-activo' : 'status-finalizado'; ?>">
                                            <?php echo $contrato['estado'] === 'ACT' ? 'Activo' : 'Finalizado'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <div>📄</div>
                        <p>No hay contratos registrados</p>
                    </div>
                <?php endif; ?>

            <?php elseif ($seccion === 'pagos'): ?>
                <div class="section-header">
                    <h2 class="section-title">Lista de Pagos</h2>
                    <a href="registrar_pago.php" class="btn-registrar">+ Registrar Pago</a>
                </div>
                
                <?php 
                $pagos = obtenerPagos($pdo);
                if (count($pagos) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Beneficiario</th>
                                <th>Contrato</th>
                                <th>N° Cuota</th>
                                <th>Fecha Pago</th>
                                <th>Monto</th>
                                <th>Penalidad</th>
                                <th>Medio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td><?php echo $pago['idpago']; ?></td>
                                    <td><strong><?php echo $pago['beneficiario_nombre']; ?></strong></td>
                                    <td><?php echo $pago['idcontrato']; ?></td>
                                    <td><?php echo $pago['numcuota']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pago['fechapago'])); ?></td>
                                    <td>S/ <?php echo number_format($pago['monto'], 2); ?></td>
                                    <td>S/ <?php echo number_format($pago['penalidad'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $pago['medio'] === 'EFC' ? 'medio-efectivo' : 'medio-deposito'; ?>">
                                            <?php echo $pago['medio'] === 'EFC' ? 'Efectivo' : 'Depósito'; ?>
                                        </span>
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
            <?php endif; ?>
        </div>
    </div>
</body>
</html>