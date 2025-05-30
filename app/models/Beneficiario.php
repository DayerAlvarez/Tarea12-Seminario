<?php 
// Incluir el archivo de la clase de conexión a la base de datos
require_once 'Conexion.php';

class Beneficiario {
    // Propiedad que almacenará la instancia PDO
    private $pdo;
    
    // Constructor de la clase: se ejecuta al instanciar el objeto
    public function __construct() {
        // Crear una nueva conexión
        $conexion = new Conexion();
        // Obtener el objeto PDO de la conexión
        $this->pdo = $conexion->getConnection();
    }
    
    // Método para obtener todos los beneficiarios
    public function obtenerTodos() {
        try {
            // Preparar la consulta SQL para seleccionar todos los beneficiarios ordenados por apellidos y nombres
            $stmt = $this->pdo->prepare("
                SELECT * FROM beneficiarios 
                ORDER BY apellidos, nombres
            ");
            // Ejecutar la consulta
            $stmt->execute();
            // Devolver todos los resultados como un array
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Si ocurre un error, lanzar una excepción con el mensaje de error
            throw new Exception("Error al obtener beneficiarios: " . $e->getMessage());
        }
    }
    
    // Método para obtener un beneficiario por su ID
    public function obtenerPorId($id) {
        try {
            // Preparar la consulta SQL con marcador :id
            $stmt = $this->pdo->prepare("
                SELECT * FROM beneficiarios 
                WHERE idbeneficiario = :id
            ");
            // Enlazar el parámetro :id con la variable $id como entero
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            // Ejecutar la consulta
            $stmt->execute();
            // Devolver el único registro encontrado
            return $stmt->fetch();
        } catch(PDOException $e) {
            // En caso de error, lanzar excepción
            throw new Exception("Error al obtener beneficiario: " . $e->getMessage());
        }
    }
    
    // Método para obtener un beneficiario por su DNI
    public function obtenerPorDni($dni) {
        try {
            // Preparar la consulta SQL con marcador :dni
            $stmt = $this->pdo->prepare("
                SELECT * FROM beneficiarios 
                WHERE dni = :dni
            ");
            // Enlazar el parámetro :dni con la variable $dni
            $stmt->bindParam(':dni', $dni);
            // Ejecutar la consulta
            $stmt->execute();
            // Devolver el registro encontrado
            return $stmt->fetch();
        } catch(PDOException $e) {
            // Lanzar excepción en caso de error
            throw new Exception("Error al buscar beneficiario por DNI: " . $e->getMessage());
        }
    }
    
    // Método para crear un nuevo beneficiario
    public function crear($apellidos, $nombres, $dni, $telefono, $direccion = null) {
        try {
            // Verificar si ya existe un beneficiario con el mismo DNI
            if ($this->obtenerPorDni($dni)) {
                throw new Exception("Ya existe un beneficiario con el DNI: " . $dni);
            }
            
            // Preparar la consulta INSERT con marcadores
            $stmt = $this->pdo->prepare("
                INSERT INTO beneficiarios (apellidos, nombres, dni, telefono, direccion) 
                VALUES (:apellidos, :nombres, :dni, :telefono, :direccion)
            ");
            
            // Enlazar cada marcador con su respectiva variable
            $stmt->bindParam(':apellidos', $apellidos);
            $stmt->bindParam(':nombres', $nombres);
            $stmt->bindParam(':dni', $dni);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':direccion', $direccion);
            
            // Ejecutar la inserción
            $stmt->execute();
            // Devolver el ID del nuevo beneficiario insertado
            return $this->pdo->lastInsertId();
        } catch(PDOException $e) {
            // Si el error es de clave duplicada (DNI), lanzar mensaje específico
            if ($e->getCode() == 23000) {
                throw new Exception("El DNI ya está registrado");
            }
            // Para otros errores, lanzar excepción general
            throw new Exception("Error al crear beneficiario: " . $e->getMessage());
        }
    }
    
    // Método para actualizar un beneficiario existente
    public function actualizar($id, $apellidos, $nombres, $dni, $telefono, $direccion = null) {
        try {
            // Verificar si existe otro beneficiario con el mismo DNI
            $beneficiarioExistente = $this->obtenerPorDni($dni);
            if ($beneficiarioExistente && $beneficiarioExistente['idbeneficiario'] != $id) {
                throw new Exception("Ya existe otro beneficiario con el DNI: " . $dni);
            }
            
            // Preparar la consulta UPDATE con marcadores
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
            
            // Enlazar cada marcador con su respectiva variable
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':apellidos', $apellidos);
            $stmt->bindParam(':nombres', $nombres);
            $stmt->bindParam(':dni', $dni);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':direccion', $direccion);
            
            // Ejecutar la actualización y devolver true si tuvo éxito
            return $stmt->execute();
        } catch(PDOException $e) {
            // Manejo de error de clave duplicada
            if ($e->getCode() == 23000) {
                throw new Exception("El DNI ya está registrado");
            }
            // Excepción general para otros casos
            throw new Exception("Error al actualizar beneficiario: " . $e->getMessage());
        }
    }
    
    // Método para eliminar un beneficiario
    public function eliminar($id) {
        try {
            // Verificar si el beneficiario tiene contratos asociados
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total FROM contratos 
                WHERE idbeneficiario = :id
            ");
            // Enlazar el parámetro :id
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            // Ejecutar la consulta
            $stmt->execute();
            // Obtener el conteo de contratos
            $resultado = $stmt->fetch();
            
            // Si tiene contratos, no permitir la eliminación
            if ($resultado['total'] > 0) {
                throw new Exception("No se puede eliminar el beneficiario porque tiene contratos asociados");
            }
            
            // Preparar la consulta DELETE
            $stmt = $this->pdo->prepare("
                DELETE FROM beneficiarios 
                WHERE idbeneficiario = :id
            ");
            // Enlazar el parámetro :id
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            // Ejecutar la eliminación y devolver true si tuvo éxito
            return $stmt->execute();
        } catch(PDOException $e) {
            // Lanzar excepción en caso de error
            throw new Exception("Error al eliminar beneficiario: " . $e->getMessage());
        }
    }
    
    // Método para buscar beneficiarios por término en apellidos, nombres o DNI
    public function buscar($termino) {
        try {
            // Preparar la consulta SELECT con LIKE para búsquedas parciales
            $stmt = $this->pdo->prepare("
                SELECT * FROM beneficiarios 
                WHERE apellidos LIKE :termino 
                   OR nombres LIKE :termino 
                   OR dni LIKE :termino 
                ORDER BY apellidos, nombres
            ");
            // Formatear el término para incluir comodines
            $termino = "%{$termino}%";
            // Enlazar el parámetro :termino
            $stmt->bindParam(':termino', $termino);
            // Ejecutar la consulta
            $stmt->execute();
            // Devolver todos los resultados encontrados
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Excepción en caso de error
            throw new Exception("Error al buscar beneficiarios: " . $e->getMessage());
        }
    }
    
    // Método estático para validar formato de DNI (exactamente 8 dígitos)
    public function validarDni($dni) {
        // Retorna 1 si coincide, 0 si no
        return preg_match('/^\d{8}$/', $dni);
    }
    
    // Método estático para validar formato de teléfono (exactamente 9 dígitos)
    public function validarTelefono($telefono) {
        // Retorna 1 si coincide, 0 si no
        return preg_match('/^\d{9}$/', $telefono);
    }
}
?>
