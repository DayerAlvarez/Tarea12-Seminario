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
    CONsTRAINT uk_numcuota_pag UNIQUE (idcontrato, numcuota)
)ENGINE = INNODB;