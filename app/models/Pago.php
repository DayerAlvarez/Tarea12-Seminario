<?php
require_once 'Conexion.php';

class Pago {
    private $pdo;
    
    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->getConnection();
    }
    
    // Obtener todos los pagos realizados con información del contrato y beneficiario
    public function obtenerPagosRealizados() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, c.idcontrato, c.monto as monto_contrato, c.fechainicio, c.numcuotas,
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM pagos p 
                INNER JOIN contratos c ON p.idcontrato = c.idcontrato
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario
                WHERE p.fechapago IS NOT NULL
                ORDER BY p.idpago ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener pagos realizados: " . $e->getMessage());
        }
    }
    
    // Obtener pago por ID
    public function obtenerPorId($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, c.idbeneficiario, c.fechainicio, c.diapago, c.numcuotas
                FROM pagos p 
                INNER JOIN contratos c ON p.idcontrato = c.idcontrato
                WHERE p.idpago = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener pago: " . $e->getMessage());
        }
    }
    
    // Obtener cuotas pendientes por contrato
    public function obtenerCuotasPendientes($idContrato) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*
                FROM pagos p 
                WHERE p.idcontrato = :idcontrato 
                AND p.fechapago IS NULL
                ORDER BY p.numcuota ASC
            ");
            $stmt->bindParam(':idcontrato', $idContrato, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener cuotas pendientes: " . $e->getMessage());
        }
    }
    
    // Registrar pago
    public function registrarPago($idPago, $penalidad, $medio) {
        try {
            // Establecer zona horaria a Perú
            date_default_timezone_set('America/Lima');
            $fechaActual = date('Y-m-d H:i:s');
            
            $stmt = $this->pdo->prepare("
                UPDATE pagos 
                SET fechapago = :fechapago,
                    penalidad = :penalidad,
                    medio = :medio
                WHERE idpago = :idpago
            ");
            
            $stmt->bindParam(':idpago', $idPago, PDO::PARAM_INT);
            $stmt->bindParam(':fechapago', $fechaActual);
            $stmt->bindParam(':penalidad', $penalidad);
            $stmt->bindParam(':medio', $medio);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error al registrar pago: " . $e->getMessage());
        }
    }
    
    // Anular pago (nuevo método implementado)
    public function anularPago($idPago) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE pagos 
                SET fechapago = NULL,
                    penalidad = 0,
                    medio = NULL
                WHERE idpago = :idpago
            ");
            
            $stmt->bindParam(':idpago', $idPago, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new Exception("No se pudo anular el pago");
            }
            
            return true;
        } catch(PDOException $e) {
            throw new Exception("Error al anular pago: " . $e->getMessage());
        }
    }
    
    // Obtener todas las cuotas de un contrato (pagadas y pendientes)
    public function obtenerCuotasPorContrato($idContrato) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*
                FROM pagos p 
                WHERE p.idcontrato = :idcontrato 
                ORDER BY p.numcuota ASC
            ");
            $stmt->bindParam(':idcontrato', $idContrato, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener cuotas del contrato: " . $e->getMessage());
        }
    }
}
?>