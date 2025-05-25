# Sistema de Préstamos Web
## Procedimientos para implementación
1. Clonar Repositorio
```
git clone https://github.com/usuario/prestamos-web
```
2. Configurar el servidor web
```
Apuntar DocumentRoot a la carpeta del proyecto
```
3. Configurar conexión a base de datos en app/models/Conexion.php
```php
private $host = 'localhost';
private $dbname = 'prestamos';
private $username = 'root';
private $password = '';
```
4. Restaurar la BD
```sql
CREATE DATABASE prestamos;
USE prestamos;

CREATE TABLE beneficiarios
(
	idbeneficiario	INT AUTO_INCREMENT PRIMARY KEY,
    apellidos		VARCHAR(50) 		NOT NULL,
    nombres 		VARCHAR(50) 		NOT NULL,
    dni 			CHAR(8) 			NOT NULL,
    telefono 		CHAR(9) 			NOT NULL,
    direccion 		VARCHAR(90) 		NULL,
    creado 			DATETIME 			NOT NULL DEFAULT NOW(),
    modificado 		DATETIME 			NULL,
    CONSTRAINT uk_dni_be UNIQUE (dni)
)ENGINE = INNODB;

CREATE TABLE contratos
(
	idcontrato 		INT AUTO_INCREMENT PRIMARY KEY,
    idbeneficiario  INT 			NOT NULL,
    monto 			DECIMAL(7,2)  	NOT NULL,
    interes 		DECIMAL(5,2)  	NOT NULL,
    fechainicio 	DATE 			NOT NULL,
    diapago 		TINYINT 		NOT NULL,
    numcuotas 		TINYINT 		NOT NULL COMMENT 'Expresado en meses',
    estado 			ENUM('ACT','FIN') NOT NULL DEFAULT 'ACT' COMMENT 'ACT = Activo, FIN = Finalizo',
    creado 			DATETIME 			NOT NULL DEFAULT NOW(),
    modificado 		DATETIME 			NULL,
    CONSTRAINT fk_idbeneficiario_con FOREIGN KEY (idbeneficiario) REFERENCES beneficiarios (idbeneficiario)
)ENGINE = INNODB;

CREATE TABLE pagos
(
	idpago		INT AUTO_INCREMENT PRIMARY KEY,
    idcontrato	INT 			NOT NULL,
    numcuota 	TINYINT 		NOT NULL COMMENT 'Se debe cancelar la cuota en su totalidad sin AMORTIZACIONES',
    fechapago	DATETIME 		NOT NULL COMMENT 'Fecha efectiva de pago',
    monto 		DECIMAL(7,2)	NOT NULL,
    penalidad	DECIMAL(7,2)	NOT NULL DEFAULT 0 COMMENT '10% del valor de la cuota',
    medio 		ENUM('EFC', 'DEP')NOT NULL COMMENT 'EFC = Efectivo, DEP = Depósito',
    CONSTRAINT fk_idcontrato_pag FOREIGN KEY (idcontrato) REFERENCES contratos (idcontrato),
    CONSTRAINT uk_numcuota_pag UNIQUE (idcontrato, numcuota)
)ENGINE = INNODB;
```
5. Ejecutar el proyecto:
```
Acceder desde el navegador a la URL del proyecto
```
## Endpoints disponibles
### Beneficiarios
#### GET
- `?seccion=beneficiarios` - Mostrar vista de beneficiarios
- `?seccion=beneficiarios&action=buscar_beneficiario&dni=:dni` - Buscar beneficiario por DNI

#### POST
- `?seccion=beneficiarios&action=crear` - Crear nuevo beneficiario
  - Validaciones:
    - DNI debe tener exactamente 8 dígitos numéricos
    - Teléfono debe tener exactamente 9 dígitos numéricos
    - Apellidos y nombres no pueden exceder 50 caracteres
    - Dirección no puede exceder 90 caracteres (puede ser NULL)

- `?seccion=beneficiarios&action=actualizar` - Actualizar beneficiario existente
  - Validaciones:
    - Mismas validaciones que al crear

#### DELETE
- `?seccion=beneficiarios&action=eliminar&id=:id` - Eliminar beneficiario
  - Restricciones:
    - No se pueden eliminar beneficiarios con contratos asociados

### Contratos
#### GET
- `?seccion=contratos` - Mostrar vista de contratos
- `?seccion=contratos&action=buscar_beneficiario&dni=:dni` - Buscar beneficiario por DNI
- `?seccion=contratos&action=cronograma&id=:id` - Obtener cronograma de pagos

#### POST
- `?seccion=contratos&action=crear` - Crear nuevo contrato
  - Validaciones:
    - El monto debe ser mayor a 0
    - El interés debe estar entre 0 y 100
    - La fecha de inicio no puede ser anterior a la fecha actual
    - El día de pago debe estar entre 1 y 31
    - El número de cuotas debe estar entre 1 y 255
    - El beneficiario no debe tener contratos activos

- `?seccion=contratos&action=actualizar` - Actualizar contrato existente
  - Validaciones:
    - Mismas validaciones que al crear
    - No se pueden actualizar contratos finalizados

- `?seccion=contratos&action=finalizar&id=:id` - Finalizar contrato
  - Restricciones:
    - El contrato debe existir
    - El contrato debe estar activo

#### DELETE
- `?seccion=contratos&action=eliminar&id=:id` - Eliminar contrato
  - Restricciones:
    - No se pueden eliminar contratos con pagos realizados

### Pagos
#### GET
- `?seccion=pagos` - Mostrar vista de pagos
- `?seccion=pagos&action=buscar_contrato&dni=:dni` - Buscar contrato por DNI del beneficiario

#### POST
- `?seccion=pagos&action=registrar` - Registrar nuevo pago
  - Validaciones:
    - El pago debe corresponder a una cuota pendiente
    - El medio de pago debe ser válido ('EFC' o 'DEP')
  - Comportamiento:
    - Calcula automáticamente penalidad del 10% para pagos tardíos
    - Registra la fecha actual como fecha efectiva de pago

## Ejemplos de uso
### Crear un beneficiario
```json
{
  "apellidos": "Pérez López",
  "nombres": "Juan Carlos",
  "dni": "47123456",
  "telefono": "987654321",
  "direccion": "Av. Principal 123"
}
```

### Crear un contrato
```json
{
  "idbeneficiario": 1,
  "monto": 5000.00,
  "interes": 5.00,
  "fechainicio": "2023-05-15",
  "diapago": 15,
  "numcuotas": 12
}
```

### Registrar un pago
```json
{
  "idpago": 5,
  "medio": "EFC"
}
```

### Buscar un beneficiario por DNI
Acceder a la URL:
```
?seccion=contratos&action=buscar_beneficiario&dni=47123456
```
Respuesta:
```json
{
  "exito": true,
  "mensaje": "Beneficiario encontrado",
  "datos": {
    "idbeneficiario": 1,
    "nombre_completo": "Pérez López, Juan Carlos",
    "dni": "47123456",
    "telefono": "987654321",
    "direccion": "Av. Principal 123"
  },
  "tieneContratosActivos": false
}
```

### Obtener cronograma de pagos
Acceder a la URL:
```
?seccion=contratos&action=cronograma&id=1
```
Respuesta:
```json
{
  "exito": true,
  "mensaje": "Cronograma obtenido correctamente",
  "datos": {
    "contrato": {
      "idcontrato": 1,
      "idbeneficiario": 1,
      "beneficiario_nombre": "Pérez López, Juan Carlos",
      "beneficiario_dni": "47123456",
      "monto": 5000.00,
      "interes": 5.00,
      "fechainicio": "2023-05-15",
      "diapago": 15,
      "numcuotas": 12,
      "estado": "ACT"
    },
    "cuotas": [
      {
        "idpago": 1,
        "idcontrato": 1,
        "numcuota": 1,
        "monto": 428.04,
        "fecha_vencimiento": "2023-06-15",
        "fechapago": null,
        "penalidad": 0.00
      },
      {
        "idpago": 2,
        "idcontrato": 1,
        "numcuota": 2,
        "monto": 428.04,
        "fecha_vencimiento": "2023-07-15",
        "fechapago": null,
        "penalidad": 0.00
      }
      // ... más cuotas
    ]
  }
}
```