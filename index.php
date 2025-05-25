<?php
// Iniciar la sesión para poder mostrar mensajes entre redirecciones
session_start();

// Configuración de la base de datos
require_once 'app/models/Conexion.php';

try {
    $conexion = new Conexion();
    $pdo = $conexion->getConnection();
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Procesar acciones para controladores si existen
if (isset($_GET['action'])) {
    $seccion = isset($_GET['seccion']) ? $_GET['seccion'] : 'beneficiarios';
    $accion = $_GET['action'];
    
    // Redirigir la acción al controlador apropiado
    switch ($seccion) {
        case 'beneficiarios':
            require_once 'app/controllers/beneficiario.controller.php';
            $controller = new BeneficiarioController();
            
            if ($accion === 'crear') {
                try {
                    $respuesta = $controller->crearDesdeFormulario($_POST);
                    $_SESSION['mensaje_exito'] = $respuesta['mensaje'];
                } catch (Exception $e) {
                    $_SESSION['mensaje_error'] = $e->getMessage();
                }
            } else if ($accion === 'actualizar') {
                try {
                    $respuesta = $controller->actualizarDesdeFormulario($_POST);
                    $_SESSION['mensaje_exito'] = $respuesta['mensaje'];
                } catch (Exception $e) {
                    $_SESSION['mensaje_error'] = $e->getMessage();
                }
            } else if ($accion === 'eliminar' && isset($_GET['id'])) {
                try {
                    $respuesta = $controller->eliminar($_GET['id']);
                    $_SESSION['mensaje_exito'] = $respuesta['mensaje'];
                } catch (Exception $e) {
                    $_SESSION['mensaje_error'] = $e->getMessage();
                }
            }
            break;
            
        case 'contratos':
            require_once 'app/controllers/contrato.controller.php';
            $controller = new ContratoController();
            
            if ($accion === 'crear') {
                try {
                    $respuesta = $controller->crearDesdeFormulario($_POST);
                    $_SESSION['mensaje_exito'] = $respuesta['mensaje'];
                } catch (Exception $e) {
                    $_SESSION['mensaje_error'] = $e->getMessage();
                }
            } else if ($accion === 'actualizar') {
                try {
                    $respuesta = $controller->actualizarDesdeFormulario($_POST);
                    $_SESSION['mensaje_exito'] = $respuesta['mensaje'];
                } catch (Exception $e) {
                    $_SESSION['mensaje_error'] = $e->getMessage();
                }
            } else if ($accion === 'finalizar' && isset($_GET['id'])) {
                try {
                    $respuesta = $controller->finalizar($_GET['id']);
                    $_SESSION['mensaje_exito'] = $respuesta['mensaje'];
                } catch (Exception $e) {
                    $_SESSION['mensaje_error'] = $e->getMessage();
                }
            } else if ($accion === 'eliminar' && isset($_GET['id'])) {
                try {
                    $respuesta = $controller->eliminar($_GET['id']);
                    $_SESSION['mensaje_exito'] = $respuesta['mensaje'];
                } catch (Exception $e) {
                    $_SESSION['mensaje_error'] = $e->getMessage();
                }
            } else if ($accion === 'buscar_beneficiario' && isset($_GET['dni'])) {
                // Endpoint para buscar beneficiario (AJAX)
                try {
                    $resultado = $controller->buscarBeneficiarioPorDni($_GET['dni']);
                    // No hacer redirect ya que es una llamada AJAX
                    exit;
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
                    exit;
                }
            } else if ($accion === 'cronograma' && isset($_GET['id'])) {
                // Endpoint para obtener el cronograma de pagos (AJAX)
                try {
                    $resultado = $controller->obtenerCronograma($_GET['id']);
                    // No hacer redirect ya que es una llamada AJAX
                    exit;
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
                    exit;
                }
            }
            break;
            
        case 'pagos':
            require_once 'app/controllers/pago.controller.php';
            $controller = new PagoController();
            
            if ($accion === 'registrar') {
                try {
                    $respuesta = $controller->registrarPagoDesdeFormulario($_POST);
                    $_SESSION['mensaje_exito'] = $respuesta['mensaje'];
                } catch (Exception $e) {
                    $_SESSION['mensaje_error'] = $e->getMessage();
                }
            } else if ($accion === 'buscar_contrato' && isset($_GET['dni'])) {
                // Endpoint para buscar contrato por DNI (AJAX)
                try {
                    $resultado = $controller->buscarContratoPorDni($_GET['dni']);
                    // No hacer redirect ya que es una llamada AJAX
                    exit;
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
                    exit;
                }
            }
            break;
    }
    
    // Redirigir a la sección correspondiente (excepto si ya se procesó como AJAX)
    if (!isset($resultado)) {
        header('Location: ?seccion=' . $seccion);
        exit;
    }
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
    <!-- Estilos principales -->
    <link rel="stylesheet" href="public/css/main.css">
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
                    include 'public/views/Beneficiario/registraryListar.php';
                    break;
            }
            ?>
        </div>
    </div>
</body>
</html>