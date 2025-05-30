<?php
// Incluir la definición de la clase de conexión
require_once 'Conexion.php';

class Contrato {
    // Propiedad para almacenar la instancia PDO
    private $pdo;
    
    // Constructor: se ejecuta al instanciar la clase
    public function __construct() {
        // Crear nueva conexión
        $conexion = new Conexion();
        // Obtener el objeto PDO de la conexión
        $this->pdo = $conexion->getConnection();
    }
    
    // Obtener todos los contratos junto con datos del beneficiario
    public function obtenerTodos() {
        try {
            // Preparar consulta SQL con INNER JOIN para incluir nombre y DNI del beneficiario
            $stmt = $this->pdo->prepare("
                SELECT c.*, 
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM contratos c 
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
                ORDER BY c.fechainicio DESC
            ");
            // Ejecutar la consulta
            $stmt->execute();
            // Devolver todos los resultados
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // En caso de error, lanzar excepción con mensaje
            throw new Exception("Error al obtener contratos: " . $e->getMessage());
        }
    }
    
    // Obtener un contrato por su ID, incluyendo datos del beneficiario
    public function obtenerPorId($id) {
        try {
            // Preparar consulta con marcador :id
            $stmt = $this->pdo->prepare("
                SELECT c.*, 
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM contratos c 
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
                WHERE c.idcontrato = :id
            ");
            // Enlazar el parámetro id como entero
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            // Ejecutar consulta
            $stmt->execute();
            // Devolver un solo registro
            return $stmt->fetch();
        } catch(PDOException $e) {
            // Lanzar excepción en caso de fallo
            throw new Exception("Error al obtener contrato: " . $e->getMessage());
        }
    }
    
    // Obtener todos los contratos asociados a un beneficiario específico
    public function obtenerPorBeneficiario($idBeneficiario) {
        try {
            // Preparar consulta con marcador :idbeneficiario
            $stmt = $this->pdo->prepare("
                SELECT * FROM contratos 
                WHERE idbeneficiario = :idbeneficiario 
                ORDER BY fechainicio DESC
            ");
            // Enlazar el parámetro idbeneficiario
            $stmt->bindParam(':idbeneficiario', $idBeneficiario, PDO::PARAM_INT);
            // Ejecutar
            $stmt->execute();
            // Devolver todos los contratos encontrados
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Excepción en caso de error
            throw new Exception("Error al obtener contratos del beneficiario: " . $e->getMessage());
        }
    }
    
    // Obtener solo los contratos con estado 'ACT' (activos)
    public function obtenerActivos() {
        try {
            // Preparar consulta con filtro por estado
            $stmt = $this->pdo->prepare("
                SELECT c.*, 
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM contratos c 
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
                WHERE c.estado = 'ACT'
                ORDER BY c.fechainicio DESC
            ");
            // Ejecutar
            $stmt->execute();
            // Retornar resultados
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Lanzar excepción
            throw new Exception("Error al obtener contratos activos: " . $e->getMessage());
        }
    }
    
    // Crear un nuevo contrato y generar sus cuotas
    public function crear($idBeneficiario, $monto, $interes, $fechaInicio, $diaPago, $numCuotas) {
        try {
            // Iniciar transacción
            $this->pdo->beginTransaction();
            
            // Verificar que el beneficiario existe
            $stmt = $this->pdo->prepare("SELECT idbeneficiario FROM beneficiarios WHERE idbeneficiario = :id");
            $stmt->bindParam(':id', $idBeneficiario, PDO::PARAM_INT);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                // Si no existe, lanzar excepción
                throw new Exception("El beneficiario no existe");
            }
            
            // Preparar inserción del contrato
            $stmt = $this->pdo->prepare("
                INSERT INTO contratos (idbeneficiario, monto, interes, fechainicio, diapago, numcuotas) 
                VALUES (:idbeneficiario, :monto, :interes, :fechainicio, :diapago, :numcuotas)
            ");
            // Enlazar parámetros
            $stmt->bindParam(':idbeneficiario', $idBeneficiario, PDO::PARAM_INT);
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':interes', $interes);
            $stmt->bindParam(':fechainicio', $fechaInicio);
            $stmt->bindParam(':diapago', $diaPago, PDO::PARAM_INT);
            $stmt->bindParam(':numcuotas', $numCuotas, PDO::PARAM_INT);
            
            // Ejecutar inserción
            $stmt->execute();
            // Obtener el ID del contrato recién creado
            $idContrato = $this->pdo->lastInsertId();
            
            // Generar las cuotas según la fórmula de amortización
            $this->generarCuotas($idContrato, $monto, $interes, $numCuotas);
            
            // Confirmar transacción
            $this->pdo->commit();
            // Devolver ID del contrato
            return $idContrato;
            
        } catch(Exception $e) {
            // En caso de error, deshacer cambios
            $this->pdo->rollback();
            // Lanzar excepción con mensaje
            throw new Exception("Error al crear contrato: " . $e->getMessage());
        }
    }
    
    // Método privado para generar las cuotas de pago del contrato
    private function generarCuotas($idContrato, $monto, $interes, $numCuotas) {
        // Calcular tasa mensual a partir del interés anual
        $tasaMensual = ($interes / 100) / 12;
        // Fórmula de amortización para cuota mensual fija
        $cuotaMensual = $monto * ($tasaMensual * pow(1 + $tasaMensual, $numCuotas))
                             / (pow(1 + $tasaMensual, $numCuotas) - 1);
        
        // Preparar inserción de cada pago
        $stmt = $this->pdo->prepare("
            INSERT INTO pagos (idcontrato, numcuota, monto) 
            VALUES (:idcontrato, :numcuota, :monto)
        ");
        
        // Bucle para insertar cada cuota numerada
        for ($i = 1; $i <= $numCuotas; $i++) {
            $stmt->bindParam(':idcontrato', $idContrato, PDO::PARAM_INT);
            $stmt->bindParam(':numcuota', $i, PDO::PARAM_INT);
            $stmt->bindParam(':monto', $cuotaMensual);
            $stmt->execute();
        }
    }
    
    // Actualizar datos de un contrato existente
    public function actualizar($id, $idBeneficiario, $monto, $interes, $fechaInicio, $diaPago, $numCuotas) {
        try {
            // Verificar existencia de beneficiario
            $stmt = $this->pdo->prepare("SELECT idbeneficiario FROM beneficiarios WHERE idbeneficiario = :id");
            $stmt->bindParam(':id', $idBeneficiario, PDO::PARAM_INT);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                throw new Exception("El beneficiario no existe");
            }
            
            // Preparar consulta UPDATE
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
            // Enlazar todos los parámetros
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':idbeneficiario', $idBeneficiario, PDO::PARAM_INT);
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':interes', $interes);
            $stmt->bindParam(':fechainicio', $fechaInicio);
            $stmt->bindParam(':diapago', $diaPago, PDO::PARAM_INT);
            $stmt->bindParam(':numcuotas', $numCuotas, PDO::PARAM_INT);
            
            // Ejecutar la actualización
            return $stmt->execute();
        } catch(PDOException $e) {
            // Excepción en caso de error
            throw new Exception("Error al actualizar contrato: " . $e->getMessage());
        }
    }
    
    // Marcar un contrato como finalizado (estado 'FIN')
    public function finalizar($id) {
        try {
            // Preparar UPDATE de estado
            $stmt = $this->pdo->prepare("
                UPDATE contratos 
                SET estado = 'FIN', modificado = NOW()
                WHERE idcontrato = :id
            ");
            // Enlazar el ID
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            // Ejecutar y devolver resultado booleano
            return $stmt->execute();
        } catch(PDOException $e) {
            // Lanzar excepción
            throw new Exception("Error al finalizar contrato: " . $e->getMessage());
        }
    }
    
    // Eliminar un contrato (si no tiene pagos efectuados)
    public function eliminar($id) {
        try {
            // Iniciar transacción
            $this->pdo->beginTransaction();
            
            // Comprobar si hay pagos realizados (fechapago no nula)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total FROM pagos 
                WHERE idcontrato = :id AND fechapago IS NOT NULL
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch();
            
            // Si hay pagos, impedir eliminación
            if ($resultado['total'] > 0) {
                throw new Exception("No se puede eliminar el contrato porque ya tiene pagos realizados");
            }
            
            // Eliminar cuotas pendientes
            $stmt = $this->pdo->prepare("DELETE FROM pagos WHERE idcontrato = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Eliminar el contrato
            $stmt = $this->pdo->prepare("DELETE FROM contratos WHERE idcontrato = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $resultado = $stmt->execute();
            
            // Confirmar transacción
            $this->pdo->commit();
            return $resultado;
            
        } catch(Exception $e) {
            // Deshacer en caso de error
            $this->pdo->rollback();
            throw new Exception("Error al eliminar contrato: " . $e->getMessage());
        }
    }
    
    // Obtener un resumen completo del contrato y sus pagos
    public function obtenerResumen($id) {
        try {
            // Preparar consulta con agregaciones para totales y conteos
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
            // Enlazar ID
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            // Ejecutar consulta
            $stmt->execute();
            // Devolver el resumen como array asociativo
            return $stmt->fetch();
        } catch(PDOException $e) {
            // Lanzar excepción si falla
            throw new Exception("Error al obtener resumen del contrato: " . $e->getMessage());
        }
    }
    
    // Validar los datos básicos de un contrato antes de crearlo o actualizarlo
    public function validarDatos($monto, $interes, $fechaInicio, $diaPago, $numCuotas) {
        // Array para acumular mensajes de error
        $errores = [];
        
        // Monto debe ser numérico y positivo
        if (!is_numeric($monto) || $monto <= 0) {
            $errores[] = "El monto debe ser un número mayor a 0";
        }
        
        // Interés debe ser numérico entre 0 y 100
        if (!is_numeric($interes) || $interes < 0 || $interes > 100) {
            $errores[] = "El interés debe ser un número entre 0 y 100";
        }
        
        // Fecha de inicio debe ser una fecha válida
        if (!strtotime($fechaInicio)) {
            $errores[] = "La fecha de inicio no es válida";
        }
        
        // Día de pago entre 1 y 31
        if (!is_numeric($diaPago) || $diaPago < 1 || $diaPago > 31) {
            $errores[] = "El día de pago debe ser entre 1 y 31";
        }
        
        // Número de cuotas entre 1 y 255
        if (!is_numeric($numCuotas) || $numCuotas < 1 || $numCuotas > 255) {
            $errores[] = "El número de cuotas debe ser entre 1 y 255";
        }
        
        // Devolver array de errores (vacío si no hay)
        return $errores;
    }
    
    // Obtener contratos activos de un beneficiario dado su ID
    public function obtenerActivosPorBeneficiario($idBeneficiario) {
        try {
            // Preparar consulta con filtro por estado y beneficiario
            $stmt = $this->pdo->prepare("
                SELECT * FROM contratos 
                WHERE idbeneficiario = :idbeneficiario 
                  AND estado = 'ACT'
                ORDER BY fechainicio DESC
            ");
            // Enlazar ID del beneficiario
            $stmt->bindParam(':idbeneficiario', $idBeneficiario, PDO::PARAM_INT);
            // Ejecutar
            $stmt->execute();
            // Retornar resultados
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Lanzar excepción en caso de error
            throw new Exception("Error al obtener contratos activos del beneficiario: " . $e->getMessage());
        }
    }
    
    // Obtener contratos activos de un beneficiario dado su DNI
    public function obtenerActivosPorDni($dni) {
        try {
            // Preparar consulta con JOIN y filtros por DNI y estado
            $stmt = $this->pdo->prepare("
                SELECT c.*, 
                       CONCAT(b.apellidos, ', ', b.nombres) as beneficiario_nombre,
                       b.dni as beneficiario_dni
                FROM contratos c 
                INNER JOIN beneficiarios b ON c.idbeneficiario = b.idbeneficiario 
                WHERE b.dni = :dni 
                  AND c.estado = 'ACT'
                ORDER BY c.fechainicio DESC
            ");
            // Enlazar el parámetro dni
            $stmt->bindParam(':dni', $dni);
            // Ejecutar
            $stmt->execute();
            // Retornar lista de contratos
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            // Excepción en caso de fallo
            throw new Exception("Error al obtener contratos por DNI: " . $e->getMessage());
        }
    }
}
?>
