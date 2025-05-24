<?php
require_once 'app/models/Beneficiario.php';

class BeneficiarioController {
    private $beneficiarioModel;
    
    public function __construct() {
        $this->beneficiarioModel = new Beneficiario();
    }
    
    // Método específico para la vista - muestra todos los beneficiarios
    public function obtenerTodosParaVista() {
        try {
            $beneficiarios = $this->beneficiarioModel->obtenerTodos();
            return ['exito' => true, 'datos' => $beneficiarios];
        } catch(Exception $e) {
            throw new Exception("Error al obtener beneficiarios: " . $e->getMessage());
        }
    }
    
    // Crear beneficiario desde formulario (para vista)
    public function crearDesdeFormulario($datos) {
        try {
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, ['apellidos', 'nombres', 'dni', 'telefono']);
            
            // Validar formato de DNI
            if (!$this->beneficiarioModel->validarDni($datos['dni'])) {
                throw new Exception("El DNI debe tener exactamente 8 dígitos");
            }
            
            // Validar formato de teléfono
            if (!$this->beneficiarioModel->validarTelefono($datos['telefono'])) {
                throw new Exception("El teléfono debe tener exactamente 9 dígitos");
            }
            
            // Validar longitud de campos
            if (strlen($datos['apellidos']) > 50) {
                throw new Exception("Los apellidos no pueden exceder 50 caracteres");
            }
            
            if (strlen($datos['nombres']) > 50) {
                throw new Exception("Los nombres no pueden exceder 50 caracteres");
            }
            
            if (isset($datos['direccion']) && strlen($datos['direccion']) > 90) {
                throw new Exception("La dirección no puede exceder 90 caracteres");
            }
            
            $direccion = isset($datos['direccion']) ? trim($datos['direccion']) : null;
            if (empty($direccion)) $direccion = null;
            
            $id = $this->beneficiarioModel->crear(
                trim($datos['apellidos']),
                trim($datos['nombres']),
                trim($datos['dni']),
                trim($datos['telefono']),
                $direccion
            );
            
            $beneficiario = $this->beneficiarioModel->obtenerPorId($id);
            return ['exito' => true, 'mensaje' => "Beneficiario creado correctamente", 'datos' => $beneficiario];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // Actualizar beneficiario desde formulario (para vista)
    public function actualizarDesdeFormulario($datos) {
        try {
            if (!isset($datos['idbeneficiario']) || !is_numeric($datos['idbeneficiario'])) {
                throw new Exception("ID de beneficiario inválido");
            }
            
            $id = $datos['idbeneficiario'];
            
            // Verificar que el beneficiario existe
            if (!$this->beneficiarioModel->obtenerPorId($id)) {
                throw new Exception("Beneficiario no encontrado");
            }
            
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, ['apellidos', 'nombres', 'dni', 'telefono']);
            
            // Validar formato de DNI
            if (!$this->beneficiarioModel->validarDni($datos['dni'])) {
                throw new Exception("El DNI debe tener exactamente 8 dígitos");
            }
            
            // Validar formato de teléfono
            if (!$this->beneficiarioModel->validarTelefono($datos['telefono'])) {
                throw new Exception("El teléfono debe tener exactamente 9 dígitos");
            }
            
            // Validar longitud de campos
            if (strlen($datos['apellidos']) > 50) {
                throw new Exception("Los apellidos no pueden exceder 50 caracteres");
            }
            
            if (strlen($datos['nombres']) > 50) {
                throw new Exception("Los nombres no pueden exceder 50 caracteres");
            }
            
            if (isset($datos['direccion']) && strlen($datos['direccion']) > 90) {
                throw new Exception("La dirección no puede exceder 90 caracteres");
            }
            
            $direccion = isset($datos['direccion']) ? trim($datos['direccion']) : null;
            if (empty($direccion)) $direccion = null;
            
            $this->beneficiarioModel->actualizar(
                $id,
                trim($datos['apellidos']),
                trim($datos['nombres']),
                trim($datos['dni']),
                trim($datos['telefono']),
                $direccion
            );
            
            $beneficiario = $this->beneficiarioModel->obtenerPorId($id);
            return ['exito' => true, 'mensaje' => "Beneficiario actualizado correctamente", 'datos' => $beneficiario];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // Eliminar beneficiario (para vista)
    public function eliminar($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de beneficiario inválido");
            }
            
            // Verificar que el beneficiario existe
            if (!$this->beneficiarioModel->obtenerPorId($id)) {
                throw new Exception("Beneficiario no encontrado");
            }
            
            $this->beneficiarioModel->eliminar($id);
            return ['exito' => true, 'mensaje' => "Beneficiario eliminado correctamente"];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // API REST - Listar todos los beneficiarios
    public function listar() {
        try {
            $beneficiarios = $this->beneficiarioModel->obtenerTodos();
            $this->enviarRespuesta(true, "Beneficiarios obtenidos correctamente", $beneficiarios);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Obtener beneficiario por ID
    public function obtener($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de beneficiario inválido");
            }
            
            $beneficiario = $this->beneficiarioModel->obtenerPorId($id);
            
            if (!$beneficiario) {
                throw new Exception("Beneficiario no encontrado");
            }
            
            $this->enviarRespuesta(true, "Beneficiario obtenido correctamente", $beneficiario);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Crear nuevo beneficiario
    public function crear() {
        try {
            $datos = $this->obtenerDatosPost();
            
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, ['apellidos', 'nombres', 'dni', 'telefono']);
            
            // Validar formato de DNI
            if (!$this->beneficiarioModel->validarDni($datos['dni'])) {
                throw new Exception("El DNI debe tener exactamente 8 dígitos");
            }
            
            // Validar formato de teléfono
            if (!$this->beneficiarioModel->validarTelefono($datos['telefono'])) {
                throw new Exception("El teléfono debe tener exactamente 9 dígitos");
            }
            
            // Validar longitud de campos
            if (strlen($datos['apellidos']) > 50) {
                throw new Exception("Los apellidos no pueden exceder 50 caracteres");
            }
            
            if (strlen($datos['nombres']) > 50) {
                throw new Exception("Los nombres no pueden exceder 50 caracteres");
            }
            
            if (isset($datos['direccion']) && strlen($datos['direccion']) > 90) {
                throw new Exception("La dirección no puede exceder 90 caracteres");
            }
            
            $direccion = isset($datos['direccion']) ? trim($datos['direccion']) : null;
            if (empty($direccion)) $direccion = null;
            
            $id = $this->beneficiarioModel->crear(
                trim($datos['apellidos']),
                trim($datos['nombres']),
                trim($datos['dni']),
                trim($datos['telefono']),
                $direccion
            );
            
            $beneficiario = $this->beneficiarioModel->obtenerPorId($id);
            $this->enviarRespuesta(true, "Beneficiario creado correctamente", $beneficiario);
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Actualizar beneficiario
    public function actualizar($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de beneficiario inválido");
            }
            
            $datos = $this->obtenerDatosPost();
            
            // Verificar que el beneficiario existe
            if (!$this->beneficiarioModel->obtenerPorId($id)) {
                throw new Exception("Beneficiario no encontrado");
            }
            
            // Validar datos requeridos
            $this->validarDatosRequeridos($datos, ['apellidos', 'nombres', 'dni', 'telefono']);
            
            // Validar formato de DNI
            if (!$this->beneficiarioModel->validarDni($datos['dni'])) {
                throw new Exception("El DNI debe tener exactamente 8 dígitos");
            }
            
            // Validar formato de teléfono
            if (!$this->beneficiarioModel->validarTelefono($datos['telefono'])) {
                throw new Exception("El teléfono debe tener exactamente 9 dígitos");
            }
            
            // Validar longitud de campos
            if (strlen($datos['apellidos']) > 50) {
                throw new Exception("Los apellidos no pueden exceder 50 caracteres");
            }
            
            if (strlen($datos['nombres']) > 50) {
                throw new Exception("Los nombres no pueden exceder 50 caracteres");
            }
            
            if (isset($datos['direccion']) && strlen($datos['direccion']) > 90) {
                throw new Exception("La dirección no puede exceder 90 caracteres");
            }
            
            $direccion = isset($datos['direccion']) ? trim($datos['direccion']) : null;
            if (empty($direccion)) $direccion = null;
            
            $this->beneficiarioModel->actualizar(
                $id,
                trim($datos['apellidos']),
                trim($datos['nombres']),
                trim($datos['dni']),
                trim($datos['telefono']),
                $direccion
            );
            
            $beneficiario = $this->beneficiarioModel->obtenerPorId($id);
            $this->enviarRespuesta(true, "Beneficiario actualizado correctamente", $beneficiario);
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Eliminar beneficiario
    public function eliminarRest($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de beneficiario inválido");
            }
            
            // Verificar que el beneficiario existe
            if (!$this->beneficiarioModel->obtenerPorId($id)) {
                throw new Exception("Beneficiario no encontrado");
            }
            
            $this->beneficiarioModel->eliminar($id);
            $this->enviarRespuesta(true, "Beneficiario eliminado correctamente");
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Buscar beneficiarios
    public function buscar() {
        try {
            $termino = isset($_GET['q']) ? trim($_GET['q']) : '';
            
            if (empty($termino)) {
                throw new Exception("Debe proporcionar un término de búsqueda");
            }
            
            if (strlen($termino) < 2) {
                throw new Exception("El término de búsqueda debe tener al menos 2 caracteres");
            }
            
            $beneficiarios = $this->beneficiarioModel->buscar($termino);
            $this->enviarRespuesta(true, "Búsqueda realizada correctamente", $beneficiarios);
            
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Obtener beneficiario por DNI
    public function obtenerPorDni($dni) {
        try {
            if (!$dni) {
                throw new Exception("DNI requerido");
            }
            
            if (!$this->beneficiarioModel->validarDni($dni)) {
                throw new Exception("Formato de DNI inválido");
            }
            
            $beneficiario = $this->beneficiarioModel->obtenerPorDni($dni);
            
            if (!$beneficiario) {
                throw new Exception("No se encontró beneficiario con el DNI: " . $dni);
            }
            
            $this->enviarRespuesta(true, "Beneficiario encontrado", $beneficiario);
            
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
                    if ($accion === 'buscar') {
                        $this->buscar();
                    } elseif ($accion === 'dni' && $id) {
                        $this->obtenerPorDni($id);
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
                    $this->actualizar($id);
                    break;
                    
                case 'DELETE':
                    if (!$id) {
                        throw new Exception("ID requerido para eliminar");
                    }
                    $this->eliminarRest($id);
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
            if (!isset($datos[$campo]) || empty(trim($datos[$campo]))) {
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
if (basename($_SERVER['PHP_SELF']) === 'beneficiario.controller.php' && 
    strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    $controller = new BeneficiarioController();
    $controller->procesarSolicitud();
}
?>