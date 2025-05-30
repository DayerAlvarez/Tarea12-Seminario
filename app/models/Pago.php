<?php
// Incluir la definición de la clase de conexión
require_once 'Conexion.php';

class Pago {
    // Propiedad para almacenar la instancia PDO
    private $pdo;
    
    // Constructor: se ejecuta al crear un objeto Pago
    public function __construct() {
        // Crear instancia de Conexion
        $conexion = new Conexion();
        // Obtener el objeto PDO para uso en métodos
        $this->pdo = $conexion->getConnection();
    }
    
    // Obtener todos los pagos realizados con datos del contrato y beneficiario
    public function obtenerPagosRealizados() {
        try {
            // Preparar consulta que une pagos, contratos y beneficiarios
            $stmt = $this->pdo->prepare("
                SELECT p.*, 
                       c.idcontrato, 
                       c.monto as monto_contrato, 
                       c.fechainicio, 
                       c.numcuotas,
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM pagos p 
                INNER JOIN contratos c ON p.idcontrato = c.idcontrato
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario
                WHERE p.fechapago IS NOT NULL
                ORDER BY p.idpago ASC
            ");
            // Ejecutar la consulta
            $stmt->execute();
            // Devolver todos los resultados obtenidos
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Lanzar excepción si ocurre un error
            throw new Exception("Error al obtener pagos realizados: " . $e->getMessage());
        }
    }
    
    // Obtener un único pago por su ID
    public function obtenerPorId($id) {
        try {
            // Preparar consulta con parámetro :id
            $stmt = $this->pdo->prepare("
                SELECT p.*, c.idbeneficiario, c.fechainicio, c.diapago, c.numcuotas
                FROM pagos p 
                INNER JOIN contratos c ON p.idcontrato = c.idcontrato
                WHERE p.idpago = :id
            ");
            // Enlazar el parámetro :id como entero
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            // Ejecutar la consulta
            $stmt->execute();
            // Devolver el registro encontrado
            return $stmt->fetch();
        } catch(PDOException $e) {
            // Lanzar excepción en caso de error
            throw new Exception("Error al obtener pago: " . $e->getMessage());
        }
    }
    
    // Obtener las cuotas pendientes de pago para un contrato específico
    public function obtenerCuotasPendientes($idContrato) {
        try {
            // Preparar consulta de pagos sin fecha de pago
            $stmt = $this->pdo->prepare("
                SELECT p.*
                FROM pagos p 
                WHERE p.idcontrato = :idcontrato 
                  AND p.fechapago IS NULL
                ORDER BY p.numcuota ASC
            ");
            // Enlazar el parámetro :idcontrato
            $stmt->bindParam(':idcontrato', $idContrato, PDO::PARAM_INT);
            // Ejecutar la consulta
            $stmt->execute();
            // Devolver todas las cuotas pendientes
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Lanzar excepción si falla la consulta
            throw new Exception("Error al obtener cuotas pendientes: " . $e->getMessage());
        }
    }
    
    // Registrar (marcar) un pago como efectuado
    public function registrarPago($idPago, $penalidad, $medio) {
        try {
            // Establecer zona horaria de Perú
            date_default_timezone_set('America/Lima');
            // Obtener la fecha y hora actuales
            $fechaActual = date('Y-m-d H:i:s');
            
            // Preparar actualización del registro de pago
            $stmt = $this->pdo->prepare("
                UPDATE pagos 
                SET fechapago = :fechapago,
                    penalidad = :penalidad,
                    medio = :medio
                WHERE idpago = :idpago
            ");
            
            // Enlazar parámetros para fecha, penalidad, medio y ID
            $stmt->bindParam(':idpago', $idPago, PDO::PARAM_INT);
            $stmt->bindParam(':fechapago', $fechaActual);
            $stmt->bindParam(':penalidad', $penalidad);
            $stmt->bindParam(':medio', $medio);
            
            // Ejecutar y devolver resultado booleano
            return $stmt->execute();
        } catch(PDOException $e) {
            // Lanzar excepción si hay error
            throw new Exception("Error al registrar pago: " . $e->getMessage());
        }
    }
    
    // Anular un pago previamente registrado
    public function anularPago($idPago) {
        try {
            // Preparar actualización para revertir el pago
            $stmt = $this->pdo->prepare("
                UPDATE pagos 
                SET fechapago = NULL,
                    penalidad = 0,
                    medio = NULL
                WHERE idpago = :idpago
            ");
            
            // Enlazar el parámetro :idpago
            $stmt->bindParam(':idpago', $idPago, PDO::PARAM_INT);
            
            // Ejecutar y verificar éxito
            if (!$stmt->execute()) {
                // Si falla, lanzar excepción específica
                throw new Exception("No se pudo anular el pago");
            }
            
            // Retornar true si se anuló correctamente
            return true;
        } catch(PDOException $e) {
            // Lanzar excepción en caso de error de PDO
            throw new Exception("Error al anular pago: " . $e->getMessage());
        }
    }
    
    // Obtener todas las cuotas (pagadas y pendientes) de un contrato
    public function obtenerCuotasPorContrato($idContrato) {
        try {
            // Preparar consulta de todos los pagos de un contrato
            $stmt = $this->pdo->prepare("
                SELECT p.*
                FROM pagos p 
                WHERE p.idcontrato = :idcontrato 
                ORDER BY p.numcuota ASC
            ");
            // Enlazar parámetro :idcontrato
            $stmt->bindParam(':idcontrato', $idContrato, PDO::PARAM_INT);
            // Ejecutar consulta
            $stmt->execute();
            // Devolver lista de cuotas
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Lanzar excepción si hay error
            throw new Exception("Error al obtener cuotas del contrato: " . $e->getMessage());
        }
    }
}
?>
