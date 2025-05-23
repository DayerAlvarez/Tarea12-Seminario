<?php
require_once 'Conexion.php';

class Beneficiario {
    private $pdo;
    
    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->getConnection();
    }
    
    // Obtener todos los beneficiarios
    public function obtenerTodos() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM beneficiarios 
                ORDER BY apellidos, nombres
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener beneficiarios: " . $e->getMessage());
        }
    }
    
    // Obtener beneficiario por ID
    public function obtenerPorId($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM beneficiarios 
                WHERE idbeneficiario = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error al obtener beneficiario: " . $e->getMessage());
        }
    }
    
    // Obtener beneficiario por DNI
    public function obtenerPorDni($dni) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM beneficiarios 
                WHERE dni = :dni
            ");
            $stmt->bindParam(':dni', $dni);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            throw new Exception("Error al buscar beneficiario por DNI: " . $e->getMessage());
        }
    }
    
    // Crear nuevo beneficiario
    public function crear($apellidos, $nombres, $dni, $telefono, $direccion = null) {
        try {
            // Verificar si el DNI ya existe
            if ($this->obtenerPorDni($dni)) {
                throw new Exception("Ya existe un beneficiario con el DNI: " . $dni);
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO beneficiarios (apellidos, nombres, dni, telefono, direccion) 
                VALUES (:apellidos, :nombres, :dni, :telefono, :direccion)
            ");
            
            $stmt->bindParam(':apellidos', $apellidos);
            $stmt->bindParam(':nombres', $nombres);
            $stmt->bindParam(':dni', $dni);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':direccion', $direccion);
            
            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("El DNI ya está registrado");
            }
            throw new Exception("Error al crear beneficiario: " . $e->getMessage());
        }
    }
    
    // Actualizar beneficiario
    public function actualizar($id, $apellidos, $nombres, $dni, $telefono, $direccion = null) {
        try {
            // Verificar si existe otro beneficiario con el mismo DNI
            $beneficiarioExistente = $this->obtenerPorDni($dni);
            if ($beneficiarioExistente && $beneficiarioExistente['idbeneficiario'] != $id) {
                throw new Exception("Ya existe otro beneficiario con el DNI: " . $dni);
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE beneficiarios 
                SET apellidos = :apellidos, 
                    nombres = :nombres, 
                    dni = :dni, 
                    telefono = :telefono, 
                    direccion = :direccion,
                    modificado = NOW()
                WHERE idbeneficiario = :id
            ");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':apellidos', $apellidos);
            $stmt->bindParam(':nombres', $nombres);
            $stmt->bindParam(':dni', $dni);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':direccion', $direccion);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("El DNI ya está registrado");
            }
            throw new Exception("Error al actualizar beneficiario: " . $e->getMessage());
        }
    }
    
    // Eliminar beneficiario
    public function eliminar($id) {
        try {
            // Verificar si tiene contratos asociados
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total FROM contratos 
                WHERE idbeneficiario = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch();
            
            if ($resultado['total'] > 0) {
                throw new Exception("No se puede eliminar el beneficiario porque tiene contratos asociados");
            }
            
            $stmt = $this->pdo->prepare("
                DELETE FROM beneficiarios 
                WHERE idbeneficiario = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            throw new Exception("Error al eliminar beneficiario: " . $e->getMessage());
        }
    }
    
    // Buscar beneficiarios
    public function buscar($termino) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM beneficiarios 
                WHERE apellidos LIKE :termino 
                   OR nombres LIKE :termino 
                   OR dni LIKE :termino 
                ORDER BY apellidos, nombres
            ");
            $termino = "%{$termino}%";
            $stmt->bindParam(':termino', $termino);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            throw new Exception("Error al buscar beneficiarios: " . $e->getMessage());
        }
    }
    
    // Validar DNI (8 dígitos)
    public function validarDni($dni) {
        return preg_match('/^\d{8}$/', $dni);
    }
    
    // Validar teléfono (9 dígitos)
    public function validarTelefono($telefono) {
        return preg_match('/^\d{9}$/', $telefono);
    }
}
?>