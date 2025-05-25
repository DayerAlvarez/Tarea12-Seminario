<?php
require_once 'app/models/Contrato.php';
require_once 'app/models/Beneficiario.php';

class ContratoController {
    private $contratoModel;
    private $beneficiarioModel;
    
    public function __construct() {
        $this->contratoModel = new Contrato();
        $this->beneficiarioModel = new Beneficiario();
    }
    
    // Método específico para la vista - muestra todos los contratos
    public function obtenerTodosParaVista() {
        try {
            $contratos = $this->contratoModel->obtenerTodos();
            return ['exito' => true, 'datos' => $contratos];
        } catch(Exception $e) {
            throw new Exception("Error al obtener contratos: " . $e->getMessage());
        }
    }
    
    // Crear contrato desde formulario (para vista)
    public function crearDesdeFormulario($datos) {
        try {
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
            
            // Validar que la fecha de inicio no sea anterior a hoy - CORREGIDO
            // Comparar solo las fechas sin la hora
            $fechaHoy = date('Y-m-d');
            $fechaInicio = date('Y-m-d', strtotime($datos['fechainicio']));
            
            if (strtotime($fechaInicio) < strtotime($fechaHoy)) {
                throw new Exception("La fecha de inicio no puede ser anterior a hoy");
            }
            
            // Verificar si el beneficiario ya tiene contratos activos
            $contratosActivos = $this->contratoModel->obtenerActivosPorBeneficiario($datos['idbeneficiario']);
            if (count($contratosActivos) > 0) {
                throw new Exception("El beneficiario ya tiene un contrato activo. Debe finalizar el contrato actual antes de crear uno nuevo.");
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
            return ['exito' => true, 'mensaje' => "Contrato creado correctamente", 'datos' => $contrato];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // Actualizar contrato desde formulario (para vista)
    public function actualizarDesdeFormulario($datos) {
        try {
            if (!isset($datos['idcontrato']) || !is_numeric($datos['idcontrato'])) {
                throw new Exception("ID de contrato inválido");
            }
            
            $id = $datos['idcontrato'];
            
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
            return ['exito' => true, 'mensaje' => "Contrato actualizado correctamente", 'datos' => $contrato];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // Finalizar contrato (para vista)
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
            
            return ['exito' => true, 'mensaje' => "Contrato finalizado correctamente"];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // Eliminar contrato (para vista)
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
            return ['exito' => true, 'mensaje' => "Contrato eliminado correctamente"];
            
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    // Buscar beneficiario por DNI para el formulario
    public function buscarBeneficiarioPorDni($dni) {
        try {
            if (!$dni) {
                throw new Exception("DNI requerido");
            }
            
            if (!$this->beneficiarioModel->validarDni($dni)) {
                throw new Exception("Formato de DNI inválido. Debe tener 8 dígitos");
            }
            
            $beneficiario = $this->beneficiarioModel->obtenerPorDni($dni);
            
            if (!$beneficiario) {
                throw new Exception("No se encontró beneficiario con el DNI: " . $dni);
            }
            
            // Verificar si tiene contratos activos
            $contratosActivos = $this->contratoModel->obtenerActivosPorBeneficiario($beneficiario['idbeneficiario']);
            $tieneContratosActivos = count($contratosActivos) > 0;
            
            // Formatear respuesta para el formulario
            $beneficiarioFormateado = [
                'idbeneficiario' => $beneficiario['idbeneficiario'],
                'nombre_completo' => $beneficiario['apellidos'] . ', ' . $beneficiario['nombres'],
                'dni' => $beneficiario['dni'],
                'telefono' => $beneficiario['telefono'],
                'direccion' => $beneficiario['direccion']
            ];
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'exito' => true, 
                'mensaje' => "Beneficiario encontrado",
                'datos' => $beneficiarioFormateado,
                'tieneContratosActivos' => $tieneContratosActivos
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
    
    // Método para obtener el cronograma de pagos (para vista)
    public function obtenerCronograma($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de contrato inválido");
            }
            
            // Verificar que el contrato existe
            $contrato = $this->contratoModel->obtenerPorId($id);
            if (!$contrato) {
                throw new Exception("Contrato no encontrado");
            }
            
            // Obtener información de cuotas (pagadas y pendientes)
            require_once 'app/models/Pago.php';
            $pagoModel = new Pago();
            
            // Obtener todas las cuotas (tanto pagadas como pendientes)
            $cuotas = $pagoModel->obtenerCuotasPorContrato($id);
            
            // Calcular fechas de vencimiento para cada cuota
            $fechaInicio = new DateTime($contrato['fechainicio']);
            $diaPago = (int)$contrato['diapago'];
            
            // Mover al primer mes siguiente para empezar a contar
            $fechaInicio->modify('+1 month');
            
            foreach ($cuotas as &$cuota) {
                // Calcular fecha de vencimiento
                $fechaVencimiento = clone $fechaInicio;
                $fechaVencimiento->modify('+' . ($cuota['numcuota'] - 1) . ' month');
                
                // Ajustar al día de pago
                $ultimoDiaMes = (int)$fechaVencimiento->format('t');
                $diaEfectivo = min($diaPago, $ultimoDiaMes);
                
                $fechaVencimiento->setDate(
                    $fechaVencimiento->format('Y'),
                    $fechaVencimiento->format('m'),
                    $diaEfectivo
                );
                
                $cuota['fecha_vencimiento'] = $fechaVencimiento->format('Y-m-d');
            }
            
            // Preparar respuesta
            $respuesta = [
                'contrato' => $contrato,
                'cuotas' => $cuotas
            ];
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'exito' => true,
                'mensaje' => 'Cronograma obtenido correctamente',
                'datos' => $respuesta
            ]);
            
            return true;
            
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'exito' => false,
                'mensaje' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    // API REST - Listar todos los contratos
    public function listar() {
        try {
            $contratos = $this->contratoModel->obtenerTodos();
            $this->enviarRespuesta(true, "Contratos obtenidos correctamente", $contratos);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Listar contratos activos
    public function listarActivos() {
        try {
            $contratos = $this->contratoModel->obtenerActivos();
            $this->enviarRespuesta(true, "Contratos activos obtenidos correctamente", $contratos);
        } catch(Exception $e) {
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST - Obtener contrato por ID
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
    
    // API REST - Obtener resumen del contrato
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
    
    // API REST - Obtener contratos por beneficiario
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
    
    // API REST - Crear nuevo contrato
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
            
            // Validar que la fecha de inicio no sea anterior a hoy - CORREGIDO
            $fechaHoy = date('Y-m-d');
            $fechaInicio = date('Y-m-d', strtotime($datos['fechainicio']));
            
            if (strtotime($fechaInicio) < strtotime($fechaHoy)) {
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
    
    // API REST - Actualizar contrato
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
    
    // API REST - Finalizar contrato
    public function finalizarRest($id) {
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
    
    // API REST - Eliminar contrato
    public function eliminarRest($id) {
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
                    if ($accion === 'activos') {
                        $this->listarActivos();
                    } elseif ($accion === 'resumen' && $id) {
                        $this->obtenerResumen($id);
                    } elseif ($accion === 'beneficiario' && $id) {
                        $this->obtenerPorBeneficiario($id);
                    } elseif ($accion === 'buscar-beneficiario' && $id) {
                        $this->buscarBeneficiarioPorDni($id);
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
                        $this->finalizarRest($id);
                    } else {
                        $this->actualizar($id);
                    }
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
if (basename($_SERVER['PHP_SELF']) === 'contrato.controller.php' && 
    strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    $controller = new ContratoController();
    $controller->procesarSolicitud();
}
?>