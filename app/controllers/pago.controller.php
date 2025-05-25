<?php
require_once 'app/models/Pago.php';
require_once 'app/models/Contrato.php';

class PagoController {
    private $pagoModel;
    private $contratoModel;
    
    public function __construct() {
        $this->pagoModel = new Pago();
        $this->contratoModel = new Contrato();
    }
    
    // Método específico para la vista - muestra todos los pagos realizados
    public function obtenerPagosRealizadosParaVista() {
        try {
            $pagos = $this->pagoModel->obtenerPagosRealizados();
            return ['exito' => true, 'datos' => $pagos];
        } catch(Exception $e) {
            throw new Exception("Error al obtener pagos: " . $e->getMessage());
        }
    }
    
    // Buscar contrato por DNI del beneficiario
    public function buscarContratoPorDni($dni) {
        try {
            if (!$dni) {
                throw new Exception("DNI requerido");
            }
            
            if (!preg_match('/^\d{8}$/', $dni)) {
                throw new Exception("Formato de DNI inválido. Debe tener 8 dígitos");
            }
            
            $contratos = $this->contratoModel->obtenerActivosPorDni($dni);
            
            if (empty($contratos)) {
                throw new Exception("No se encontraron contratos activos para el DNI: " . $dni);
            }
            
            // Obtener cuotas pendientes para el primer contrato activo
            $contrato = $contratos[0];
            $cuotasPendientes = $this->pagoModel->obtenerCuotasPendientes($contrato['idcontrato']);
            
            if (empty($cuotasPendientes)) {
                throw new Exception("No hay cuotas pendientes para este contrato");
            }
            
            $respuesta = [
                'contrato' => $contrato,
                'cuotas' => $cuotasPendientes
            ];
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'exito' => true, 
                'mensaje' => "Contrato encontrado",
                'datos' => $respuesta
            ]);
            
            return true;
            
        } catch(Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'exito' => false,
                'mensaje' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    // Registrar pago desde formulario (para vista)
    public function registrarPagoDesdeFormulario($datos) {
        try {
            // Validar datos requeridos
            if (!isset($datos['idpago']) || !is_numeric($datos['idpago'])) {
                throw new Exception("ID de pago inválido");
            }
            
            // Obtener información del pago
            $pago = $this->pagoModel->obtenerPorId($datos['idpago']);
            
            if (!$pago) {
                throw new Exception("Pago no encontrado");
            }
            
            // Verificar que el pago no esté ya realizado
            if ($pago['fechapago'] !== null) {
                throw new Exception("Este pago ya ha sido realizado anteriormente");
            }
            
            // Validar el medio de pago
            if (!isset($datos['medio']) || !in_array($datos['medio'], ['EFC', 'DEP'])) {
                throw new Exception("Medio de pago inválido");
            }
            
            // Calcular penalidad si corresponde
            $penalidad = 0;
            $fechaActual = new DateTime();
            $fechaVencimiento = $this->calcularFechaVencimiento($pago['idcontrato'], $pago['numcuota']);
            
            if ($fechaActual > $fechaVencimiento) {
                // Aplicar penalidad del 10% sobre el valor de la cuota
                $penalidad = $pago['monto'] * 0.10;
            }
            
            // Registrar pago
            $this->pagoModel->registrarPago(
                $pago['idpago'],
                $penalidad,
                $datos['medio']
            );
            
            return ['exito' => true, 'mensaje' => "Pago registrado correctamente"];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // Método auxiliar para calcular la fecha de vencimiento de una cuota
    private function calcularFechaVencimiento($idContrato, $numCuota) {
        $contrato = $this->contratoModel->obtenerPorId($idContrato);
        
        if (!$contrato) {
            throw new Exception("Contrato no encontrado");
        }
        
        $fechaInicio = new DateTime($contrato['fechainicio']);
        $fechaInicio->modify('+1 month'); // La primera cuota es el mes siguiente
        $diaPago = (int)$contrato['diapago'];
        
        // Calcular fecha de vencimiento
        $fechaVencimiento = clone $fechaInicio;
        $fechaVencimiento->modify('+' . ($numCuota - 1) . ' month');
        
        // Ajustar al día de pago
        $fechaVencimiento->setDate(
            $fechaVencimiento->format('Y'),
            $fechaVencimiento->format('m'),
            min($diaPago, $fechaVencimiento->format('t'))
        );
        
        return $fechaVencimiento;
    }
    
    // API REST - Listar todos los pagos realizados
    public function listarPagosRealizados() {
        try {
            $pagos = $this->pagoModel->obtenerPagosRealizados();
            $this->enviarRespuesta(true, "Pagos obtenidos correctamente", $pagos);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Obtener cuotas pendientes por contrato
    public function obtenerCuotasPendientes($idContrato) {
        try {
            if (!$idContrato || !is_numeric($idContrato)) {
                throw new Exception("ID de contrato inválido");
            }
            
            $cuotas = $this->pagoModel->obtenerCuotasPendientes($idContrato);
            $this->enviarRespuesta(true, "Cuotas pendientes obtenidas correctamente", $cuotas);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Registrar pago
    public function registrarPago() {
        try {
            $datos = $this->obtenerDatosPost();
            
            if (!isset($datos['idpago']) || !is_numeric($datos['idpago'])) {
                throw new Exception("ID de pago inválido");
            }
            
            if (!isset($datos['medio']) || !in_array($datos['medio'], ['EFC', 'DEP'])) {
                throw new Exception("Medio de pago inválido");
            }
            
            // Obtener información del pago
            $pago = $this->pagoModel->obtenerPorId($datos['idpago']);
            
            if (!$pago) {
                throw new Exception("Pago no encontrado");
            }
            
            // Verificar que el pago no esté ya realizado
            if ($pago['fechapago'] !== null) {
                throw new Exception("Este pago ya ha sido realizado anteriormente");
            }
            
            // Calcular penalidad si corresponde
            $penalidad = 0;
            $fechaActual = new DateTime();
            $fechaVencimiento = $this->calcularFechaVencimiento($pago['idcontrato'], $pago['numcuota']);
            
            if ($fechaActual > $fechaVencimiento) {
                // Aplicar penalidad del 10% sobre el valor de la cuota
                $penalidad = $pago['monto'] * 0.10;
            }
            
            // Registrar pago
            $this->pagoModel->registrarPago(
                $pago['idpago'],
                $penalidad,
                $datos['medio']
            );
            
            $pagoActualizado = $this->pagoModel->obtenerPorId($pago['idpago']);
            $this->enviarRespuesta(true, "Pago registrado correctamente", $pagoActualizado);
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Procesar las solicitudes HTTP para API REST
    public function procesarSolicitud() {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Extraer parámetros de la URL
        $partes = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
        $accion = isset($partes[2]) ? $partes[2] : '';
        $id = isset($partes[3]) ? $partes[3] : null;
        
        try {
            switch ($metodo) {
                case 'GET':
                    if ($accion === 'realizados') {
                        $this->listarPagosRealizados();
                    } elseif ($accion === 'pendientes' && $id) {
                        $this->obtenerCuotasPendientes($id);
                    } elseif ($accion === 'buscar-contrato' && $id) {
                        $this->buscarContratoPorDni($id);
                    } else {
                        $this->listarPagosRealizados();
                    }
                    break;
                    
                case 'POST':
                    $this->registrarPago();
                    break;
                    
                default:
                    throw new Exception("Método HTTP no soportado");
            }
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Métodos auxiliares
    private function obtenerDatosPost() {
        $input = file_get_contents('php://input');
        $datos = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Si no es JSON, intentar obtener de $_POST
            $datos = $_POST;
        }
        
        if (empty($datos)) {
            throw new Exception("No se recibieron datos");
        }
        
        return $datos;
    }
    
    private function enviarRespuesta($exito, $mensaje, $datos = null) {
        header('Content-Type: application/json; charset=utf-8');
        
        $respuesta = [
            'exito' => $exito,
            'mensaje' => $mensaje
        ];
        
        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }
        
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Uso del controlador para API REST
if (basename($_SERVER['PHP_SELF']) === 'pago.controller.php' && 
    strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    $controller = new PagoController();
    $controller->procesarSolicitud();
}
?>