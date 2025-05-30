<?php
// Incluir las clases de modelo para Pago y Contrato
require_once 'app/models/Pago.php';
require_once 'app/models/Contrato.php';

class PagoController {
    // Propiedades para almacenar las instancias de los modelos
    private $pagoModel;
    private $contratoModel;
    
    // Constructor de la clase
    public function __construct() {
        // Establecer la zona horaria de Perú
        date_default_timezone_set('America/Lima');
        
        // Crear instancia del modelo Pago
        $this->pagoModel = new Pago();
        // Crear instancia del modelo Contrato
        $this->contratoModel = new Contrato();
    }
    
    // Método para la vista: obtener todos los pagos realizados
    public function obtenerPagosRealizadosParaVista() {
        try {
            // Llamar al modelo para obtener los pagos realizados
            $pagos = $this->pagoModel->obtenerPagosRealizados();
            // Devolver un array JSON con éxito y datos
            return ['exito' => true, 'datos' => $pagos];
        } catch(Exception $e) {
            // En caso de error, lanzar excepción con el mensaje
            throw new Exception("Error al obtener pagos: " . $e->getMessage());
        }
    }
    
    // Método para buscar un contrato por DNI del beneficiario
    public function buscarContratoPorDni($dni) {
        try {
            // Verificar que se proporcionó un DNI
            if (!$dni) {
                throw new Exception("DNI requerido");
            }
            
            // Validar formato del DNI (8 dígitos)
            if (!preg_match('/^\d{8}$/', $dni)) {
                throw new Exception("Formato de DNI inválido. Debe tener 8 dígitos");
            }
            
            // Obtener contratos activos filtrados por DNI
            $contratos = $this->contratoModel->obtenerActivosPorDni($dni);
            
            // Si no hay contratos, lanzar excepción
            if (empty($contratos)) {
                throw new Exception("No se encontraron contratos activos para el DNI: " . $dni);
            }
            
            // Seleccionar el primer contrato encontrado
            $contrato = $contratos[0];
            // Obtener las cuotas pendientes de ese contrato
            $cuotasPendientes = $this->pagoModel->obtenerCuotasPendientes($contrato['idcontrato']);
            
            // Si no hay cuotas pendientes, lanzar excepción
            if (empty($cuotasPendientes)) {
                throw new Exception("No hay cuotas pendientes para este contrato");
            }
            
            // Preparar la respuesta con contrato y cuotas pendientes
            $respuesta = [
                'contrato' => $contrato,
                'cuotas' => $cuotasPendientes
            ];
            
            // Enviar cabecera JSON
            header('Content-Type: application/json; charset=utf-8');
            // Imprimir la respuesta codificada en JSON
            echo json_encode([
                'exito' => true, 
                'mensaje' => "Contrato encontrado",
                'datos' => $respuesta
            ]);
            
            return true;
            
        } catch(Exception $e) {
            // En caso de error, enviar respuesta de error en JSON
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'exito' => false,
                'mensaje' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    // Método para registrar un pago desde un formulario (no API)
    public function registrarPagoDesdeFormulario($datos) {
        try {
            // Verificar que se proporcionó el ID de pago y es numérico
            if (!isset($datos['idpago']) || !is_numeric($datos['idpago'])) {
                throw new Exception("ID de pago inválido");
            }
            
            // Obtener los datos del pago desde el modelo
            $pago = $this->pagoModel->obtenerPorId($datos['idpago']);
            
            // Si el pago no existe, lanzar excepción
            if (!$pago) {
                throw new Exception("Pago no encontrado");
            }
            
            // Si el pago ya tiene fecha, ya fue realizado
            if ($pago['fechapago'] !== null) {
                throw new Exception("Este pago ya ha sido realizado anteriormente");
            }
            
            // Verificar que el medio de pago sea válido
            if (!isset($datos['medio']) || !in_array($datos['medio'], ['EFC', 'DEP'])) {
                throw new Exception("Medio de pago inválido");
            }
            
            // Calcular penalidad (por defecto 0)
            $penalidad = 0;
            // Fecha actual
            $fechaActual = new DateTime();
            // Calcular la fecha de vencimiento de la cuota
            $fechaVencimiento = $this->calcularFechaVencimiento($pago['idcontrato'], $pago['numcuota']);
            
            // Si la fecha actual es posterior al vencimiento, aplicar 10% de penalidad
            if ($fechaActual > $fechaVencimiento) {
                $penalidad = $pago['monto'] * 0.10;
            }
            
            // Llamar al modelo para registrar el pago
            $this->pagoModel->registrarPago(
                $pago['idpago'],
                $penalidad,
                $datos['medio']
            );
            
            // Devolver mensaje de éxito
            return ['exito' => true, 'mensaje' => "Pago registrado correctamente"];
            
        } catch(Exception $e) {
            // En caso de error, propagar la excepción
            throw new Exception($e->getMessage());
        }
    }
    
    // Método auxiliar para calcular la fecha de vencimiento de una cuota
    private function calcularFechaVencimiento($idContrato, $numCuota) {
        // Obtener datos del contrato
        $contrato = $this->contratoModel->obtenerPorId($idContrato);
        
        // Si no existe el contrato, lanzar excepción
        if (!$contrato) {
            throw new Exception("Contrato no encontrado");
        }
        
        // Crear objeto DateTime a partir de la fecha de inicio
        $fechaInicio = new DateTime($contrato['fechainicio']);
        // Avanzar un mes para la primera cuota
        $fechaInicio->modify('+1 month');
        // Obtener el día de pago configurado
        $diaPago = (int)$contrato['diapago'];
        
        // Clonar la fecha y avanzar (numCuota - 1) meses
        $fechaVencimiento = clone $fechaInicio;
        $fechaVencimiento->modify('+' . ($numCuota - 1) . ' month');
        
        // Ajustar el día al día de pago o al último día del mes si es menor
        $fechaVencimiento->setDate(
            $fechaVencimiento->format('Y'),
            $fechaVencimiento->format('m'),
            min($diaPago, $fechaVencimiento->format('t'))
        );
        
        // Devolver la fecha de vencimiento
        return $fechaVencimiento;
    }
    
    // API REST: listar todos los pagos realizados
    public function listarPagosRealizados() {
        try {
            // Obtener pagos realizados del modelo
            $pagos = $this->pagoModel->obtenerPagosRealizados();
            // Enviar respuesta con datos
            $this->enviarRespuesta(true, "Pagos obtenidos correctamente", $pagos);
        } catch(Exception $e) {
            // Enviar respuesta de error
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST: obtener cuotas pendientes dado un ID de contrato
    public function obtenerCuotasPendientes($idContrato) {
        try {
            // Validar ID de contrato
            if (!$idContrato || !is_numeric($idContrato)) {
                throw new Exception("ID de contrato inválido");
            }
            
            // Obtener cuotas pendientes del modelo
            $cuotas = $this->pagoModel->obtenerCuotasPendientes($idContrato);
            // Enviar respuesta con las cuotas
            $this->enviarRespuesta(true, "Cuotas pendientes obtenidas correctamente", $cuotas);
        } catch(Exception $e) {
            // Enviar respuesta de error
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // API REST: registrar pago vía POST
    public function registrarPago() {
        try {
            // Obtener datos del cuerpo de la petición
            $datos = $this->obtenerDatosPost();
            
            // Validar ID de pago
            if (!isset($datos['idpago']) || !is_numeric($datos['idpago'])) {
                throw new Exception("ID de pago inválido");
            }
            
            // Validar medio de pago
            if (!isset($datos['medio']) || !in_array($datos['medio'], ['EFC', 'DEP'])) {
                throw new Exception("Medio de pago inválido");
            }
            
            // Obtener el registro de pago
            $pago = $this->pagoModel->obtenerPorId($datos['idpago']);
            
            // Si no existe el pago, lanzar excepción
            if (!$pago) {
                throw new Exception("Pago no encontrado");
            }
            
            // Si ya tiene fecha, ya fue realizado
            if ($pago['fechapago'] !== null) {
                throw new Exception("Este pago ya ha sido realizado anteriormente");
            }
            
            // Calcular penalidad si hay retraso
            $penalidad = 0;
            $fechaActual = new DateTime();
            $fechaVencimiento = $this->calcularFechaVencimiento($pago['idcontrato'], $pago['numcuota']);
            if ($fechaActual > $fechaVencimiento) {
                $penalidad = $pago['monto'] * 0.10;
            }
            
            // Registrar el pago en el modelo
            $this->pagoModel->registrarPago(
                $pago['idpago'],
                $penalidad,
                $datos['medio']
            );
            
            // Obtener el pago actualizado
            $pagoActualizado = $this->pagoModel->obtenerPorId($pago['idpago']);
            // Enviar respuesta de éxito con el pago actualizado
            $this->enviarRespuesta(true, "Pago registrado correctamente", $pagoActualizado);
            
        } catch(Exception $e) {
            // Enviar respuesta de error
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Procesar las solicitudes HTTP entrantes (enrutamiento básico)
    public function procesarSolicitud() {
        // Obtener método HTTP y URI
        $metodo = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Dividir la ruta en segmentos
        $partes = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
        // Acciones y posibles IDs desde la ruta
        $accion = isset($partes[2]) ? $partes[2] : '';
        $id = isset($partes[3]) ? $partes[3] : null;
        
        try {
            switch ($metodo) {
                case 'GET':
                    // GET /api/pago/realizados
                    if ($accion === 'realizados') {
                        $this->listarPagosRealizados();
                    // GET /api/pago/pendientes/{id}
                    } elseif ($accion === 'pendientes' && $id) {
                        $this->obtenerCuotasPendientes($id);
                    // GET /api/pago/buscar-contrato/{dni}
                    } elseif ($accion === 'buscar-contrato' && $id) {
                        $this->buscarContratoPorDni($id);
                    } else {
                        // Por defecto listar realizados
                        $this->listarPagosRealizados();
                    }
                    break;
                    
                case 'POST':
                    // POST /api/pago
                    $this->registrarPago();
                    break;
                    
                default:
                    // Método no soportado
                    throw new Exception("Método HTTP no soportado");
            }
        } catch(Exception $e) {
            // Enviar respuesta de error en cualquier excepción no capturada
            $this->enviarRespuesta(false, $e->getMessage());
        }
    }
    
    // Obtener datos enviados en POST (JSON o form-data)
    private function obtenerDatosPost() {
        // Leer flujo de entrada
        $input = file_get_contents('php://input');
        // Intentar parsear JSON
        $datos = json_decode($input, true);
        
        // Si no es JSON válido, usar $_POST
        if (json_last_error() !== JSON_ERROR_NONE) {
            $datos = $_POST;
        }
        
        // Si no hay datos, lanzar excepción
        if (empty($datos)) {
            throw new Exception("No se recibieron datos");
        }
        
        return $datos;
    }
    
    // Enviar respuesta en formato JSON y terminar la ejecución
    private function enviarRespuesta($exito, $mensaje, $datos = null) {
        // Establecer cabecera de respuesta JSON
        header('Content-Type: application/json; charset=utf-8');
        
        // Construir array de respuesta
        $respuesta = [
            'exito'   => $exito,
            'mensaje' => $mensaje
        ];
        
        // Incluir datos si los hay
        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }
        
        // Imprimir respuesta codificada en JSON
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        // Terminar script para evitar salidas adicionales
        exit;
    }
}

// Si este archivo se accede directamente por URL y forma parte de /api/,
if (basename($_SERVER['PHP_SELF']) === 'pago.controller.php' &&
    strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    // Crear instancia del controlador y procesar la solicitud
    $controller = new PagoController();
    $controller->procesarSolicitud();
}
?>
