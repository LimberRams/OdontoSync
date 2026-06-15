CREATE DATABASE odontosync CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE odontosync;

-- 1. Tabla de Usuarios (Soporta Autenticación)
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'odontologo', 'paciente') NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Odontólogos
CREATE TABLE odontologos (
    id_odontologo INT PRIMARY KEY,
    especialidad VARCHAR(50) NOT NULL,
    matricula VARCHAR(30) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    FOREIGN KEY (id_odontologo) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Tabla de Pacientes
CREATE TABLE pacientes (
    id_paciente INT PRIMARY KEY,
    dni VARCHAR(20) UNIQUE NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_paciente) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Tabla de Servicios
CREATE TABLE servicios (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre_servicio VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB;

-- 5. Tabla de Turnos / Citas
CREATE TABLE turnos (
    id_turno INT AUTO_INCREMENT PRIMARY KEY,
    odontologo_id INT NOT NULL,
    paciente_id INT NOT NULL,
    servicio_id INT NOT NULL,
    fecha_turno DATE NOT NULL,
    hora_turno TIME NOT NULL,
    estado ENUM('Pendiente', 'Confirmado', 'Cancelado', 'Completo') DEFAULT 'Pendiente',
    observaciones TEXT,
    FOREIGN KEY (odontologo_id) REFERENCES odontologos(id_odontologo),
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id_paciente),
    FOREIGN KEY (servicio_id) REFERENCES servicios(id_servicio)
) ENGINE=InnoDB;
