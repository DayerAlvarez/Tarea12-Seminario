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

    // Método específico para la vista - muestra próximos pagos
    public function obtenerProximosPagosParaVista() {
        try {
            $pagos = $this->pagoModel->obtenerProximosPagos();
            
            // Enriquecer datos para la vista
            foreach ($pagos as &$pago) {
                // Obtener el total de cuotas del contrato
                $contrato = $this->contratoModel->obtenerPorId($pago['idcontrato']);
                $pago['total_cuotas'] = $contrato['numcuotas'];
                
                // Generar fecha programada de pago
                $fechaInicio = new DateTime($contrato['fechainicio']);
                $diaPago = $contrato['diapago'];
                $numCuota = $pago['numcuota'];
                
                // Calcular fecha programada de pago
                $fechaProgramada = clone $fechaInicio;
                $fechaProgramada->modify('first day of next month'); // Ir al primer día del siguiente mes
                $fechaProgramada->modify('+' . ($numCuota - 1) . ' months'); // Añadir los meses según la cuota
                $fechaProgramada->modify('next day ' . ($diaPago - 1) . ' days'); // Ajustar al día de pago
                
                $pago['fecha_programada'] = $fechaProgramada->format('d/m/Y');
            }
            
            return ['exito' => true, 'datos' => $pagos];
        } catch(Exception $e) {
            throw new Exception("Error al obtener próximos pagos: " . $e->getMessage());
        }
    }
    
    // Registrar pago desde formulario (para vista)
    public function registrarDesdeFormulario($datos) {
        try {
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, ['idcontrato', 'numcuota', 'monto', 'medio']);
            
            // Establecer penalidad (por defecto 0 si no se proporciona)
            $penalidad = isset($datos['penalidad']) ? $datos['penalidad'] : 0;
            
            // Validar datos usando el modelo
            $errores = $this->pagoModel->validarDatos(
                $datos['monto'],
                $penalidad,
                $datos['medio']
            );
            
            if (!empty($errores)) {
                throw new Exception(implode(', ', $errores));
            }
            
            // Verificar que el contrato existe
            $contrato = $this->contratoModel->obtenerPorId($datos['idcontrato']);
            if (!$contrato) {
                throw new Exception("El contrato especificado no existe");
            }
            
            // Verificar que el contrato está activo
            if ($contrato['estado'] !== 'ACT') {
                throw new Exception("No se pueden registrar pagos en un contrato finalizado");
            }
            
            $this->pagoModel->registrarPago(
                $datos['idcontrato'],
                $datos['numcuota'],
                $datos['monto'],
                $penalidad,
                $datos['medio']
            );
            
            return ['exito' => true, 'mensaje' => "Pago registrado correctamente"];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // Anular pago desde la vista
    public function anularDesdeVista($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de pago inválido");
            }
            
            // Verificar que el pago existe
            if (!$this->pagoModel->obtenerPorId($id)) {
                throw new Exception("Pago no encontrado");
            }
            
            $this->pagoModel->anularPago($id);
            return ['exito' => true, 'mensaje' => "Pago anulado correctamente"];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // API REST - Listar todos los pagos
    public function listar() {
        try {
            $pagos = $this->pagoModel->obtenerTodos();
            $this->enviarRespuesta(true, "Pagos obtenidos correctamente", $pagos);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Obtener pago por ID
    public function obtener($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de pago inválido");
            }
            
            $pago = $this->pagoModel->obtenerPorId($id);
            
            if (!$pago) {
                throw new Exception("Pago no encontrado");
            }
            
            $this->enviarRespuesta(true, "Pago obtenido correctamente", $pago);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Obtener pagos por contrato
    public function obtenerPorContrato($idContrato) {
        try {
            if (!$idContrato || !is_numeric($idContrato)) {
                throw new Exception("ID de contrato inválido");
            }
            
            // Verificar que el contrato existe
            if (!$this->contratoModel->obtenerPorId($idContrato)) {
                throw new Exception("Contrato no encontrado");
            }
            
            $pagos = $this->pagoModel->obtenerPorContrato($idContrato);
            $this->enviarRespuesta(true, "Pagos del contrato obtenidos correctamente", $pagos);
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Registrar pago
    public function registrar() {
        try {
            $datos = $this->obtenerDatosPost();
            
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, ['idcontrato', 'numcuota', 'monto', 'medio']);
            
            // Establecer penalidad (por defecto 0 si no se proporciona)
            $penalidad = isset($datos['penalidad']) ? $datos['penalidad'] : 0;
            
            // Validar datos usando el modelo
            $errores = $this->pagoModel->validarDatos(
                $datos['monto'],
                $penalidad,
                $datos['medio']
            );
            
            if (!empty($errores)) {
                throw new Exception(implode(', ', $errores));
            }
            
            // Verificar que el contrato existe
            $contrato = $this->contratoModel->obtenerPorId($datos['idcontrato']);
            if (!$contrato) {
                throw new Exception("El contrato especificado no existe");
            }
            
            // Verificar que el contrato está activo
            if ($contrato['estado'] !== 'ACT') {
                throw new Exception("No se pueden registrar pagos en un contrato finalizado");
            }
            
            $this->pagoModel->registrarPago(
                $datos['idcontrato'],
                $datos['numcuota'],
                $datos['monto'],
                $penalidad,
                $datos['medio']
            );
            
            $this->enviarRespuesta(true, "Pago registrado correctamente");
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Anular pago
    public function anular($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de pago inválido");
            }
            
            // Verificar que el pago existe
            if (!$this->pagoModel->obtenerPorId($id)) {
                throw new Exception("Pago no encontrado");
            }
            
            $this->pagoModel->anularPago($id);
            $this->enviarRespuesta(true, "Pago anulado correctamente");
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Obtener próximos pagos
    public function obtenerProximosPagos() {
        try {
            $pagos = $this->pagoModel->obtenerProximosPagos();
            $this->enviarRespuesta(true, "Próximos pagos obtenidos correctamente", $pagos);
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
                    if ($accion === 'contrato' && $id) {
                        $this->obtenerPorContrato($id);
                    } elseif ($accion === 'proximos') {
                        $this->obtenerProximosPagos();
                    } elseif ($id) {
                        $this->obtener($id);
                    } else {
                        $this->listar();
                    }
                    break;
                    
                case 'POST':
                    $this->registrar();
                    break;
                    
                case 'DELETE':
                    if (!$id) {
                        throw new Exception("ID requerido para anular pago");
                    }
                    $this->anular($id);
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
    
    private function validarDatosRequeridos($datos, $camposRequeridos) {
        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo]) || 
                (is_string($datos[$campo]) && empty(trim($datos[$campo]))) ||
                (is_numeric($datos[$campo]) && $datos[$campo] === '')) {
                throw new Exception("El campo '$campo' es requerido");
            }
        }
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