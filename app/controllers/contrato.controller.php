<?php
require_once '../models/Contrato.php';
require_once '../models/Beneficiario.php';

class ContratoController {
    private $contratoModel;
    private $beneficiarioModel;
    
    public function __construct() {
        $this->contratoModel = new Contrato();
        $this->beneficiarioModel = new Beneficiario();
    }
    
    // Listar todos los contratos
    public function listar() {
        try {
            $contratos = $this->contratoModel->obtenerTodos();
            $this->enviarRespuesta(true, "Contratos obtenidos correctamente", $contratos);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Listar contratos activos
    public function listarActivos() {
        try {
            $contratos = $this->contratoModel->obtenerActivos();
            $this->enviarRespuesta(true, "Contratos activos obtenidos correctamente", $contratos);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Obtener contrato por ID
    public function obtener($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de contrato inválido");
            }
            
            $contrato = $this->contratoModel->obtenerPorId($id);
            
            if (!$contrato) {
                throw new Exception("Contrato no encontrado");
            }
            
            $this->enviarRespuesta(true, "Contrato obtenido correctamente", $contrato);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Obtener resumen del contrato
    public function obtenerResumen($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de contrato inválido");
            }
            
            $resumen = $this->contratoModel->obtenerResumen($id);
            
            if (!$resumen) {
                throw new Exception("Contrato no encontrado");
            }
            
            $this->enviarRespuesta(true, "Resumen obtenido correctamente", $resumen);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Obtener contratos por beneficiario
    public function obtenerPorBeneficiario($idBeneficiario) {
        try {
            if (!$idBeneficiario || !is_numeric($idBeneficiario)) {
                throw new Exception("ID de beneficiario inválido");
            }
            
            // Verificar que el beneficiario existe
            if (!$this->beneficiarioModel->obtenerPorId($idBeneficiario)) {
                throw new Exception("Beneficiario no encontrado");
            }
            
            $contratos = $this->contratoModel->obtenerPorBeneficiario($idBeneficiario);
            $this->enviarRespuesta(true, "Contratos del beneficiario obtenidos correctamente", $contratos);
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Crear nuevo contrato
    public function crear() {
        try {
            $datos = $this->obtenerDatosPost();
            
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, [
                'idbeneficiario', 'monto', 'interes', 'fechainicio', 'diapago', 'numcuotas'
            ]);
            
            // Validar datos usando el modelo
            $errores = $this->contratoModel->validarDatos(
                $datos['monto'],
                $datos['interes'],
                $datos['fechainicio'],
                $datos['diapago'],
                $datos['numcuotas']
            );
            
            if (!empty($errores)) {
                throw new Exception(implode(', ', $errores));
            }
            
            // Validar que el beneficiario existe
            if (!$this->beneficiarioModel->obtenerPorId($datos['idbeneficiario'])) {
                throw new Exception("El beneficiario especificado no existe");
            }
            
            // Validar que la fecha de inicio no sea anterior a hoy
            if (strtotime($datos['fechainicio']) < strtotime(date('Y-m-d'))) {
                throw new Exception("La fecha de inicio no puede ser anterior a hoy");
            }
            
            $id = $this->contratoModel->crear(
                $datos['idbeneficiario'],
                $datos['monto'],
                $datos['interes'],
                $datos['fechainicio'],
                $datos['diapago'],
                $datos['numcuotas']
            );
            
            $contrato = $this->contratoModel->obtenerPorId($id);
            $this->enviarRespuesta(true, "Contrato creado correctamente", $contrato);
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Actualizar contrato
    public function actualizar($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de contrato inválido");
            }
            
            $datos = $this->obtenerDatosPost();
            
            // Verificar que el contrato existe
            $contratoExistente = $this->contratoModel->obtenerPorId($id);
            if (!$contratoExistente) {
                throw new Exception("Contrato no encontrado");
            }
            
            // No permitir actualizar contratos finalizados
            if ($contratoExistente['estado'] === 'FIN') {
                throw new Exception("No se puede actualizar un contrato finalizado");
            }
            
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, [
                'idbeneficiario', 'monto', 'interes', 'fechainicio', 'diapago', 'numcuotas'
            ]);
            
            // Validar datos usando el modelo
            $errores = $this->contratoModel->validarDatos(
                $datos['monto'],
                $datos['interes'],
                $datos['fechainicio'],
                $datos['diapago'],
                $datos['numcuotas']
            );
            
            if (!empty($errores)) {
                throw new Exception(implode(', ', $errores));
            }
            
            // Validar que el beneficiario existe
            if (!$this->beneficiarioModel->obtenerPorId($datos['idbeneficiario'])) {
                throw new Exception("El beneficiario especificado no existe");
            }
            
            $this->contratoModel->actualizar(
                $id,
                $datos['idbeneficiario'],
                $datos['monto'],
                $datos['interes'],
                $datos['fechainicio'],
                $datos['diapago'],
                $datos['numcuotas']
            );
            
            $contrato = $this->contratoModel->obtenerPorId($id);
            $this->enviarRespuesta(true, "Contrato actualizado correctamente", $contrato);
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Finalizar contrato
    public function finalizar($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de contrato inválido");
            }
            
            // Verificar que el contrato existe
            $contrato = $this->contratoModel->obtenerPorId($id);
            if (!$contrato) {
                throw new Exception("Contrato no encontrado");
            }
            
            // Verificar que está activo
            if ($contrato['estado'] === 'FIN') {
                throw new Exception("El contrato ya está finalizado");
            }
            
            $this->contratoModel->finalizar($id);
            
            $contratoActualizado = $this->contratoModel->obtenerPorId($id);
            $this->enviarRespuesta(true, "Contrato finalizado correctamente", $contratoActualizado);
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Eliminar contrato
    public function eliminar($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de contrato inválido");
            }
            
            // Verificar que el contrato existe
            if (!$this->contratoModel->obtenerPorId($id)) {
                throw new Exception("Contrato no encontrado");
            }
            
            $this->contratoModel->eliminar($id);
            $this->enviarRespuesta(true, "Contrato eliminado correctamente");
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Procesar las solicitudes HTTP
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
                    if ($accion === 'activos') {
                        $this->listarActivos();
                    } elseif ($accion === 'resumen' && $id) {
                        $this->obtenerResumen($id);
                    } elseif ($accion === 'beneficiario' && $id) {
                        $this->obtenerPorBeneficiario($id);
                    } elseif ($id) {
                        $this->obtener($id);
                    } else {
                        $this->listar();
                    }
                    break;
                    
                case 'POST':
                    $this->crear();
                    break;
                    
                case 'PUT':
                    if (!$id) {
                        throw new Exception("ID requerido para actualizar");
                    }
                    
                    if ($accion === 'finalizar') {
                        $this->finalizar($id);
                    } else {
                        $this->actualizar($id);
                    }
                    break;
                    
                case 'DELETE':
                    if (!$id) {
                        throw new Exception("ID requerido para eliminar");
                    }
                    $this->eliminar($id);
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

// Uso del controlador
if (basename($_SERVER['PHP_SELF']) === 'contrato.controller.php') {
    $controller = new ContratoController();
    $controller->procesarSolicitud();
}
?>