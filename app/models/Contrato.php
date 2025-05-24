<?php
require_once 'Conexion.php';

class Contrato {
    private $pdo;
    
    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->getConnection();
    }
    
    // Obtener todos los contratos con información del beneficiario
    public function obtenerTodos() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, 
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM contratos c 
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
                ORDER BY c.fechainicio DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener contratos: " . $e->getMessage());
        }
    }
    
    // Obtener contrato por ID
    public function obtenerPorId($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, 
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM contratos c 
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
                WHERE c.idcontrato = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener contrato: " . $e->getMessage());
        }
    }
    
    // Obtener contratos por beneficiario
    public function obtenerPorBeneficiario($idBeneficiario) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM contratos 
                WHERE idbeneficiario = :idbeneficiario 
                ORDER BY fechainicio DESC
            ");
            $stmt->bindParam(':idbeneficiario', $idBeneficiario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener contratos del beneficiario: " . $e->getMessage());
        }
    }
    
    // Obtener contratos activos
    public function obtenerActivos() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, 
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM contratos c 
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
                WHERE c.estado = 'ACT'
                ORDER BY c.fechainicio DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener contratos activos: " . $e->getMessage());
        }
    }
    
    // Crear nuevo contrato
    public function crear($idBeneficiario, $monto, $interes, $fechaInicio, $diaPago, $numCuotas) {
        try {
            $this->pdo->beginTransaction();
            
            // Verificar que el beneficiario existe
            $stmt = $this->pdo->prepare("SELECT idbeneficiario FROM beneficiarios WHERE idbeneficiario = :id");
            $stmt->bindParam(':id', $idBeneficiario, PDO::PARAM_INT);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                throw new Exception("El beneficiario no existe");
            }
            
            // Crear el contrato
            $stmt = $this->pdo->prepare("
                INSERT INTO contratos (idbeneficiario, monto, interes, fechainicio, diapago, numcuotas) 
                VALUES (:idbeneficiario, :monto, :interes, :fechainicio, :diapago, :numcuotas)
            ");
            
            $stmt->bindParam(':idbeneficiario', $idBeneficiario, PDO::PARAM_INT);
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':interes', $interes);
            $stmt->bindParam(':fechainicio', $fechaInicio);
            $stmt->bindParam(':diapago', $diaPago, PDO::PARAM_INT);
            $stmt->bindParam(':numcuotas', $numCuotas, PDO::PARAM_INT);
            
            $stmt->execute();
            $idContrato = $this->pdo->lastInsertId();
            
            // Generar las cuotas automáticamente
            $this->generarCuotas($idContrato, $monto, $interes, $numCuotas);
            
            $this->pdo->commit();
            return $idContrato;
            
        } catch(Exception $e) {
            $this->pdo->rollback();
            throw new Exception("Error al crear contrato: " . $e->getMessage());
        }
    }
    
    // Generar cuotas para el contrato
    private function generarCuotas($idContrato, $monto, $interes, $numCuotas) {
        // Calcular cuota mensual usando fórmula de amortización
        $tasaMensual = ($interes / 100) / 12;
        $cuotaMensual = $monto * ($tasaMensual * pow(1 + $tasaMensual, $numCuotas)) / (pow(1 + $tasaMensual, $numCuotas) - 1);
        
        // Insertar todas las cuotas
        $stmt = $this->pdo->prepare("
            INSERT INTO pagos (idcontrato, numcuota, monto) 
            VALUES (:idcontrato, :numcuota, :monto)
        ");
        
        for ($i = 1; $i <= $numCuotas; $i++) {
            $stmt->bindParam(':idcontrato', $idContrato, PDO::PARAM_INT);
            $stmt->bindParam(':numcuota', $i, PDO::PARAM_INT);
            $stmt->bindParam(':monto', $cuotaMensual);
            $stmt->execute();
        }
    }
    
    // Actualizar contrato
    public function actualizar($id, $idBeneficiario, $monto, $interes, $fechaInicio, $diaPago, $numCuotas) {
        try {
            // Verificar que el beneficiario existe
            $stmt = $this->pdo->prepare("SELECT idbeneficiario FROM beneficiarios WHERE idbeneficiario = :id");
            $stmt->bindParam(':id', $idBeneficiario, PDO::PARAM_INT);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                throw new Exception("El beneficiario no existe");
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE contratos 
                SET idbeneficiario = :idbeneficiario,
                    monto = :monto,
                    interes = :interes,
                    fechainicio = :fechainicio,
                    diapago = :diapago,
                    numcuotas = :numcuotas,
                    modificado = NOW()
                WHERE idcontrato = :id
            ");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':idbeneficiario', $idBeneficiario, PDO::PARAM_INT);
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':interes', $interes);
            $stmt->bindParam(':fechainicio', $fechaInicio);
            $stmt->bindParam(':diapago', $diaPago, PDO::PARAM_INT);
            $stmt->bindParam(':numcuotas', $numCuotas, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error al actualizar contrato: " . $e->getMessage());
        }
    }
    
    // Finalizar contrato
    public function finalizar($id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE contratos 
                SET estado = 'FIN', modificado = NOW()
                WHERE idcontrato = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error al finalizar contrato: " . $e->getMessage());
        }
    }
    
    // Eliminar contrato
    public function eliminar($id) {
        try {
            $this->pdo->beginTransaction();
            
            // Verificar si tiene pagos realizados
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total FROM pagos 
                WHERE idcontrato = :id AND fechapago IS NOT NULL
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch();
            
            if ($resultado['total'] > 0) {
                throw new Exception("No se puede eliminar el contrato porque ya tiene pagos realizados");
            }
            
            // Eliminar primero los pagos pendientes
            $stmt = $this->pdo->prepare("DELETE FROM pagos WHERE idcontrato = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Eliminar el contrato
            $stmt = $this->pdo->prepare("DELETE FROM contratos WHERE idcontrato = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $resultado = $stmt->execute();
            
            $this->pdo->commit();
            return $resultado;
            
        } catch(Exception $e) {
            $this->pdo->rollback();
            throw new Exception("Error al eliminar contrato: " . $e->getMessage());
        }
    }
    
    // Obtener resumen del contrato
    public function obtenerResumen($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.*,
                    CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                    b.dni as beneficiario_dni,
                    COUNT(p.idpago) as total_cuotas,
                    SUM(CASE WHEN p.fechapago IS NOT NULL THEN 1 ELSE 0 END) as cuotas_pagadas,
                    SUM(CASE WHEN p.fechapago IS NULL THEN 1 ELSE 0 END) as cuotas_pendientes,
                    SUM(CASE WHEN p.fechapago IS NOT NULL THEN p.monto ELSE 0 END) as total_pagado,
                    SUM(CASE WHEN p.fechapago IS NULL THEN p.monto ELSE 0 END) as total_pendiente,
                    SUM(p.penalidad) as total_penalidades
                FROM contratos c 
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
                LEFT JOIN pagos p ON c.idcontrato = p.idcontrato
                WHERE c.idcontrato = :id
                GROUP BY c.idcontrato
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener resumen del contrato: " . $e->getMessage());
        }
    }
    
    // Validar datos del contrato
    public function validarDatos($monto, $interes, $fechaInicio, $diaPago, $numCuotas) {
        $errores = [];
        
        if (!is_numeric($monto) || $monto <= 0) {
            $errores[] = "El monto debe ser un número mayor a 0";
        }
        
        if (!is_numeric($interes) || $interes < 0 || $interes > 100) {
            $errores[] = "El interés debe ser un número entre 0 y 100";
        }
        
        if (!strtotime($fechaInicio)) {
            $errores[] = "La fecha de inicio no es válida";
        }
        
        if (!is_numeric($diaPago) || $diaPago < 1 || $diaPago > 31) {
            $errores[] = "El día de pago debe ser entre 1 y 31";
        }
        
        if (!is_numeric($numCuotas) || $numCuotas < 1 || $numCuotas > 255) {
            $errores[] = "El número de cuotas debe ser entre 1 y 255";
        }
        
        return $errores;
    }
    // Método adicional para comprobar si un beneficiario tiene contratos activos
    public function obtenerActivosPorBeneficiario($idBeneficiario) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM contratos 
                WHERE idbeneficiario = :idbeneficiario 
                AND estado = 'ACT'
                ORDER BY fechainicio DESC
            ");
            $stmt->bindParam(':idbeneficiario', $idBeneficiario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener contratos activos del beneficiario: " . $e->getMessage());
        }
    }
}
?>