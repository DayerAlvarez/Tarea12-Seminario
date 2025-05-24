<?php
require_once 'Conexion.php';

class Pago {
    private $pdo;
    
    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->getConnection();
    }
    
    // Obtener todos los pagos
    public function obtenerTodos() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*,
                       c.idbeneficiario,
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM pagos p
                INNER JOIN contratos c ON p.idcontrato = c.idcontrato
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario
                ORDER BY p.fechapago DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener pagos: " . $e->getMessage());
        }
    }
    
    // Obtener pagos por contrato
    public function obtenerPorContrato($idContrato) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM pagos 
                WHERE idcontrato = :idcontrato
                ORDER BY numcuota
            ");
            $stmt->bindParam(':idcontrato', $idContrato, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener pagos del contrato: " . $e->getMessage());
        }
    }
    
    // Obtener pago por ID
    public function obtenerPorId($idPago) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM pagos 
                WHERE idpago = :idpago
            ");
            $stmt->bindParam(':idpago', $idPago, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener pago: " . $e->getMessage());
        }
    }
    
    // Registrar pago
    public function registrarPago($idContrato, $numCuota, $monto, $penalidad, $medio) {
        try {
            // Verificar si la cuota ya fue pagada
            $stmt = $this->pdo->prepare("
                SELECT * FROM pagos 
                WHERE idcontrato = :idcontrato AND numcuota = :numcuota
            ");
            $stmt->bindParam(':idcontrato', $idContrato, PDO::PARAM_INT);
            $stmt->bindParam(':numcuota', $numCuota, PDO::PARAM_INT);
            $stmt->execute();
            $pago = $stmt->fetch();
            
            if (!$pago) {
                throw new Exception("La cuota especificada no existe");
            }
            
            if ($pago['fechapago'] !== null) {
                throw new Exception("La cuota ya ha sido pagada");
            }
            
            // Registrar el pago
            $stmt = $this->pdo->prepare("
                UPDATE pagos 
                SET fechapago = NOW(), 
                    monto = :monto, 
                    penalidad = :penalidad, 
                    medio = :medio
                WHERE idcontrato = :idcontrato AND numcuota = :numcuota
            ");
            
            $stmt->bindParam(':idcontrato', $idContrato, PDO::PARAM_INT);
            $stmt->bindParam(':numcuota', $numCuota, PDO::PARAM_INT);
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':penalidad', $penalidad);
            $stmt->bindParam(':medio', $medio);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error al registrar pago: " . $e->getMessage());
        }
    }
    
    // Anular pago
    public function anularPago($idPago) {
        try {
            // Verificar que el pago existe
            $pago = $this->obtenerPorId($idPago);
            if (!$pago) {
                throw new Exception("El pago no existe");
            }
            
            // Verificar que el pago tiene fecha (ha sido realizado)
            if ($pago['fechapago'] === null) {
                throw new Exception("El pago aún no ha sido registrado");
            }
            
            // Anular el pago (resetear a su estado original)
            $stmt = $this->pdo->prepare("
                UPDATE pagos 
                SET fechapago = NULL, 
                    penalidad = 0, 
                    medio = NULL
                WHERE idpago = :idpago
            ");
            
            $stmt->bindParam(':idpago', $idPago, PDO::PARAM_INT);
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error al anular pago: " . $e->getMessage());
        }
    }
    
    // Obtener próximos pagos (pendientes)
    public function obtenerProximosPagos() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*,
                       c.idbeneficiario,
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM pagos p
                INNER JOIN contratos c ON p.idcontrato = c.idcontrato
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario
                WHERE p.fechapago IS NULL AND c.estado = 'ACT'
                ORDER BY c.diapago ASC, p.numcuota ASC
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener próximos pagos: " . $e->getMessage());
        }
    }
    
    // Validar datos del pago
    public function validarDatos($monto, $penalidad, $medio) {
        $errores = [];
        
        if (!is_numeric($monto) || $monto <= 0) {
            $errores[] = "El monto debe ser un número mayor a 0";
        }
        
        if (!is_numeric($penalidad) || $penalidad < 0) {
            $errores[] = "La penalidad debe ser un número mayor o igual a 0";
        }
        
        if (!in_array($medio, ['EFC', 'DEP'])) {
            $errores[] = "El medio de pago debe ser 'EFC' (Efectivo) o 'DEP' (Depósito)";
        }
        
        return $errores;
    }
}
?>