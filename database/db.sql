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
    fechapago	DATETIME 		NULL COMMENT 'Fecha efectiva de pago',
    monto 		DECIMAL(7,2)	NOT NULL,
    penalidad	DECIMAL(7,2)	NOT NULL DEFAULT 0 COMMENT '10% del valor de la cuota',
    medio 		ENUM('EFC', 'DEP')NULL COMMENT 'EFC = Efectivo, DEP = Depósito',
    CONSTRAINT fk_idcontrato_pag FOREIGN KEY (idcontrato) REFERENCES contratos (idcontrato),
    CONsTRAINT uk_numcuota_pag UNIQUE (idcontrato, numcuota)
)ENGINE = INNODB;

INSERT INTO beneficiarios 
	(apellidos, nombres, dni, telefono) VALUES 
    ('Alvarez De la cruz', 'Dyer Samil', '47343234', '96179468');
    
INSERT INTO contratos 
	(idbeneficiario, monto, interes, fechainicio, diapago, numcuotas) VALUES 
    (1, 3000, 5, '2025-03-10', 15, 12);
    
INSERT INTO pagos
	(idcontrato, numcuota, fechapago, monto, penalidad, medio) VALUES
    (1, 1, '2025-04-15', 338.48, 0, 'EFC'),
    (1, 2, '2025-05-17', 338.48, 33.85, 'DEP'),
    (1, 3, NULL, 338.48, 0, NULL),
    (1, 4, NULL, 338.48, 0, NULL),
    (1, 5, NULL, 338.48, 0, NULL),
    (1, 6, NULL, 338.48, 0, NULL),
    (1, 7, NULL, 338.48, 0, NULL),
    (1, 8, NULL, 338.48, 0, NULL),
    (1, 9, NULL, 338.48, 0, NULL),
    (1, 10, NULL, 338.48, 0, NULL),
    (1, 11, NULL, 338.48, 0, NULL),
    (1, 12, NULL, 338.48, 0, NULL);
    
SELECT * FROM beneficiarios; 

-- ¿Cuantos pagos tiene pendiente Dyer?
SELECT COUNT(*) FROM pagos WHERE idcontrato = 1 AND fechapago IS NULL;

-- ¿Cuantos es el total de la deuda actual?
SELECT COUNT(*) monto FROM pagos WHERE idcontrato = 1 AND fechapago IS NULL;

-- ¿Cuantos pgos se han realizado?
SELECT COUNT(*) FROM pagos WHERE idcontrato = 1 AND fechapago IS NOT NULL;

-- ¿Cuantos pagos se arealizaron en EFECTIVO?
SELECT COUNT(*) FROM pagos WHERE idcontrato = 1 AND medio = 'EFC';

-- ¿Cuantos es el total de penalidad pagadas con deposito?
SELECT SUM(penalidad) FROM pagos WHERE idcontrato = 1 AND MEDIO = 'DEP';