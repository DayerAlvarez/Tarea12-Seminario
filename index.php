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

        @media (max-width: 768px) {
            .nav-tabs {
                flex-direction: column;
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
            <?php
            // Incluir la vista correspondiente según la sección
            switch($seccion) {
                case 'beneficiarios':
                    include 'public/views/Beneficiario/registraryListar.php';
                    break;
                case 'contratos':
                    include 'public/views/Contrato/registraryListar.php';
                    break;
                case 'pagos':
                    include 'public/views/Pago/listar.php';
                    break;
                default:
                    include 'views/Beneficiario/registraryListar.php';
                    break;
            }
            ?>
        </div>
    </div>
</body>
</html>