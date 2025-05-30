<?php

// Incluir la definición del modelo Beneficiario
require_once __DIR__ . '/../models/Beneficiario.php';

/**
 * Controlador para gestionar operaciones sobre beneficiarios,
 * tanto en modo vista para la aplicación web como en API REST.
 */
class BeneficiarioController {
    /**
     * @var Beneficiario Modelo para interacción con la tabla beneficiarios
     */
    private $beneficiarioModel;
    
    /**
     * Constructor: inicializa el modelo de beneficiario
     */
    public function __construct() {
        $this->beneficiarioModel = new Beneficiario();
    }
    
    // ── MÉTODOS PARA LA VISTA WEB ──

    /**
     * Obtener todos los beneficiarios para mostrar en la vista
     * @return array Resultado con clave 'exito' y lista de 'datos'
     */
    public function obtenerTodosParaVista() {
        try {
            $beneficiarios = $this->beneficiarioModel->obtenerTodos();
            return ['exito' => true, 'datos' => $beneficiarios];
        } catch(Exception $e) {
            // Lanza excepción con mensaje detallado
            throw new Exception("Error al obtener beneficiarios: " . $e->getMessage());
        }
    }

    /**
     * Crear un nuevo beneficiario a partir de datos enviados por formulario
     * @param array $datos Datos del formulario (apellidos, nombres, dni, telefono, direccion)
     * @return array Resultado de la operación con mensaje y datos creados
     * @throws Exception Validaciones fallidas
     */
    public function crearDesdeFormulario($datos) {
        try {
            // Validar campos obligatorios
            $this->validarDatosRequeridos($datos, ['apellidos', 'nombres', 'dni', 'telefono']);
            // Validar formato de DNI (8 dígitos)
            if (!$this->beneficiarioModel->validarDni($datos['dni'])) {
                throw new Exception("El DNI debe tener exactamente 8 dígitos");
            }
            // Validar formato de teléfono (9 dígitos)
            if (!$this->beneficiarioModel->validarTelefono($datos['telefono'])) {
                throw new Exception("El teléfono debe tener exactamente 9 dígitos");
            }
            // Validar longitud de cadenas
            if (strlen($datos['apellidos']) > 50) {
                throw new Exception("Los apellidos no pueden exceder 50 caracteres");
            }
            if (strlen($datos['nombres']) > 50) {
                throw new Exception("Los nombres no pueden exceder 50 caracteres");
            }
            if (isset($datos['direccion']) && strlen($datos['direccion']) > 90) {
                throw new Exception("La dirección no puede exceder 90 caracteres");
            }

            // Normalizar dirección: convertir cadena vacía en null
            $direccion = isset($datos['direccion']) ? trim($datos['direccion']) : null;
            if (empty($direccion)) {
                $direccion = null;
            }

            // Crear beneficiario en BD y obtener su ID
            $id = $this->beneficiarioModel->crear(
                trim($datos['apellidos']),
                trim($datos['nombres']),
                trim($datos['dni']),
                trim($datos['telefono']),
                $direccion
            );

            // Recuperar el registro completo recién creado
            $beneficiario = $this->beneficiarioModel->obtenerPorId($id);
            return [
                'exito'   => true,
                'mensaje' => "Beneficiario creado correctamente",
                'datos'   => $beneficiario
            ];
        } catch(Exception $e) {
            // Propagar error con mensaje limpio
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Actualizar un beneficiario existente mediante formulario
     * @param array $datos Datos enviados (deben incluir idbeneficiario y campos a modificar)
     * @return array Resultado con mensaje y datos actualizados
     * @throws Exception Validación o inexistencia del registro
     */
    public function actualizarDesdeFormulario($datos) {
        try {
            // Validar ID
            if (!isset($datos['idbeneficiario']) || !is_numeric($datos['idbeneficiario'])) {
                throw new Exception("ID de beneficiario inválido");
            }
            $id = $datos['idbeneficiario'];
            // Verificar existencia
            if (!$this->beneficiarioModel->obtenerPorId($id)) {
                throw new Exception("Beneficiario no encontrado");
            }

            // Repetir validaciones del formato de datos
            $this->validarDatosRequeridos($datos, ['apellidos', 'nombres', 'dni', 'telefono']);
            if (!$this->beneficiarioModel->validarDni($datos['dni'])) {
                throw new Exception("El DNI debe tener exactamente 8 dígitos");
            }
            if (!$this->beneficiarioModel->validarTelefono($datos['telefono'])) {
                throw new Exception("El teléfono debe tener exactamente 9 dígitos");
            }
            if (strlen($datos['apellidos']) > 50) {
                throw new Exception("Los apellidos no pueden exceder 50 caracteres");
            }
            if (strlen($datos['nombres']) > 50) {
                throw new Exception("Los nombres no pueden exceder 50 caracteres");
            }
            if (isset($datos['direccion']) && strlen($datos['direccion']) > 90) {
                throw new Exception("La dirección no puede exceder 90 caracteres");
            }

            // Normalizar dirección
            $direccion = isset($datos['direccion']) ? trim($datos['direccion']) : null;
            if (empty($direccion)) {
                $direccion = null;
            }

            // Ejecutar actualización en BD
            $this->beneficiarioModel->actualizar(
                $id,
                trim($datos['apellidos']),
                trim($datos['nombres']),
                trim($datos['dni']),
                trim($datos['telefono']),
                $direccion
            );

            // Obtener estado actualizado
            $beneficiario = $this->beneficiarioModel->obtenerPorId($id);
            return [
                'exito'   => true,
                'mensaje' => "Beneficiario actualizado correctamente",
                'datos'   => $beneficiario
            ];
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Eliminar un beneficiario por su ID (vista web)
     * @param int $id Identificador del beneficiario
     * @return array Resultado con mensaje de eliminación
     * @throws Exception Si el ID es inválido o no existe
     */
    public function eliminar($id) {
        try {
            if (!$id || !is_numeric($id)) {
                throw new Exception("ID de beneficiario inválido");
            }
            if (!$this->beneficiarioModel->obtenerPorId($id)) {
                throw new Exception("Beneficiario no encontrado");
            }
            $this->beneficiarioModel->eliminar($id);
            return ['exito' => true, 'mensaje' => "Beneficiario eliminado correctamente"]; 
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // ── MÉTODOS PARA LA API REST ──

    /**
     * Listar todos los beneficiarios en formato JSON
     */
    public function listar() {
        $this->enviarRespuesta(
            true,
            "Beneficiarios obtenidos correctamente",
            $this->beneficiarioModel->obtenerTodos()
        );
    }

    /**
     * Obtener un beneficiario por ID y responder en JSON
     * @param mixed $id
     */
    public function obtener($id) {
        if (!is_numeric($id)) {
            $this->enviarRespuesta(false, "ID de beneficiario inválido");
        }
        $b = $this->beneficiarioModel->obtenerPorId($id);
        if (!$b) {
            $this->enviarRespuesta(false, "Beneficiario no encontrado");
        }
        $this->enviarRespuesta(true, "Beneficiario obtenido correctamente", $b);
    }

    /**
     * Crear un nuevo beneficiario desde petición POST JSON
     */
    public function crear() {
        $datos = $this->obtenerDatosPost();
        // Reutiliza las mismas validaciones que para el formulario web
        $this->validarDatosRequeridos($datos, ['apellidos', 'nombres', 'dni', 'telefono']);
        if (!$this->beneficiarioModel->validarDni($datos['dni'])) {
            throw new Exception("El DNI debe tener exactamente 8 dígitos");
        }
        if (!$this->beneficiarioModel->validarTelefono($datos['telefono'])) {
            throw new Exception("El teléfono debe tener exactamente 9 dígitos");
        }
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
        if (empty($direccion)) {
            $direccion = null;
        }

        $id = $this->beneficiarioModel->crear(
            trim($datos['apellidos']),
            trim($datos['nombres']),
            trim($datos['dni']),
            trim($datos['telefono']),
            $direccion
        );

        $beneficiario = $this->beneficiarioModel->obtenerPorId($id);
        $this->enviarRespuesta(true, "Beneficiario creado correctamente", $beneficiario);
    }

    /**
     * Actualizar beneficiario desde petición PUT JSON
     * @param mixed $id
     */
    public function actualizar($id) {
        if (!is_numeric($id)) {
            $this->enviarRespuesta(false, "ID de beneficiario inválido");
        }
        $datos = $this->obtenerDatosPost();
        if (!$this->beneficiarioModel->obtenerPorId($id)) {
            throw new Exception("Beneficiario no encontrado");
        }
        // Validaciones similares al método crear()
        $this->validarDatosRequeridos($datos, ['apellidos', 'nombres', 'dni', 'telefono']);
        if (!$this->beneficiarioModel->validarDni($datos['dni'])) {
            throw new Exception("El DNI debe tener exactamente 8 dígitos");
        }
        if (!$this->beneficiarioModel->validarTelefono($datos['telefono'])) {
            throw new Exception("El teléfono debe tener exactamente 9 dígitos");
        }
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
        if (empty($direccion)) {
            $direccion = null;
        }

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
    }

    /**
     * Eliminar beneficiario desde API REST (DELETE)
     * @param mixed $id
     */
    public function eliminarRest($id) {
        if (!is_numeric($id)) {
            $this->enviarRespuesta(false, "ID de beneficiario inválido");
        }
        $this->beneficiarioModel->eliminar($id);
        $this->enviarRespuesta(true, "Beneficiario eliminado correctamente");
    }

    /**
     * Buscar beneficiarios por término de consulta
     */
    public function buscar() {
        $termino = isset($_GET['q']) ? trim($_GET['q']) : '';
        if (strlen($termino) < 2) {
            $this->enviarRespuesta(false, "El término de búsqueda debe tener al menos 2 caracteres");
        }
        $resultados = $this->beneficiarioModel->buscar($termino);
        $this->enviarRespuesta(true, "Búsqueda realizada correctamente", $resultados);
    }

    /**
     * Obtener beneficiario por DNI, validando formato
     * @param string $dni
     */
    public function obtenerPorDni($dni) {
        if (!$this->beneficiarioModel->validarDni($dni)) {
            $this->enviarRespuesta(false, "Formato de DNI inválido");
        }
        $b = $this->beneficiarioModel->obtenerPorDni($dni);
        if (!$b) {
            $this->enviarRespuesta(false, "No se encontró beneficiario con DNI: " . $dni);
        }
        $this->enviarRespuesta(true, "Beneficiario encontrado", $b);
    }

    // ── ROUTER: Procesa la solicitud entrante según método HTTP y parámetros ──
    public function procesarSolicitud() {
        header('Content-Type: application/json; charset=utf-8');

        $metodo = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? null;
        $id     = $_GET['id']     ?? null;

        try {
            switch ($metodo) {
                case 'GET':
                    if ($action === 'buscar') {
                        $this->buscar();
                    } elseif ($action === 'dni' && $id) {
                        $this->obtenerPorDni($id);
                    } elseif ($action === 'obtener' && $id) {
                        $this->obtener($id);
                    } else {
                        $this->listar();
                    }
                    break;

                case 'POST':
                    $this->crear();
                    break;

                case 'PUT':
                    if (!$id) throw new Exception("ID requerido para PUT");
                    $this->actualizar($id);
                    break;

                case 'DELETE':
                    if (!$id) throw new Exception("ID requerido para DELETE");
                    $this->eliminarRest($id);
                    break;

                default:
                    throw new Exception("Método HTTP no soportado: $metodo");
            }
        } catch (Exception $e) {
            // Responder error en JSON
            echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
        }
        exit;
    }

    // ── FUNCIONES AUXILIARES ──

    /**
     * Leer y decodificar datos JSON de la petición HTTP
     * @return array Datos decodificados o $_POST
     * @throws Exception Si no se reciben datos válidos
     */
    private function obtenerDatosPost() {
        $input = file_get_contents('php://input');
        $datos = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $datos = $_POST;
        }
        if (empty($datos)) {
            throw new Exception("No se recibieron datos");
        }
        return $datos;
    }

    /**
     * Validar campos obligatorios en un arreglo de datos
     * @param array $datos
     * @param array $campos Lista de nombres de campos requeridos
     * @throws Exception Si falta alguno
     */
    private function validarDatosRequeridos($datos, $campos) {
        foreach ($campos as $campo) {
            if (!isset($datos[$campo]) || trim($datos[$campo]) === '') {
                throw new Exception("El campo '$campo' es requerido");
            }
        }
    }

    /**
     * Enviar respuesta JSON estándar al cliente
     * @param bool $exito Indica éxito o fallo
     * @param string $mensaje Mensaje descriptivo
     * @param mixed|null $datos Datos a incluir (opcional)
     */
    private function enviarRespuesta($exito, $mensaje, $datos = null) {
        $resp = ['exito' => $exito, 'mensaje' => $mensaje];
        if ($datos !== null) {
            $resp['datos'] = $datos;
        }
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Si se invoca este archivo directamente, crear instancia y procesar solicitud
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $controller = new BeneficiarioController();
    $controller->procesarSolicitud();
}