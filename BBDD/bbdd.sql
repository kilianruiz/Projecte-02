-- Crear la base de datos
CREATE DATABASE db_mokadictos;

USE db_mokadictos;

-- Tabla de Roles (opcional para escalabilidad)
CREATE TABLE tbl_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL
);

-- Tabla de Usuarios (Camareros)
CREATE TABLE tbl_users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    pwd VARCHAR(255) NOT NULL,
    role_id INT,
    lastname VARCHAR(50),
    room_id INT,
    FOREIGN KEY (role_id) REFERENCES tbl_roles(role_id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES tbl_rooms(room_id) ON DELETE SET NULL
);

-- Tabla de Salas (ahora con columna para imagen)
CREATE TABLE tbl_rooms (
    room_id INT PRIMARY KEY AUTO_INCREMENT,
    name_rooms VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    description TEXT,
    image_url VARCHAR(255)  -- Nueva columna para la URL de la imagen
);

-- Tabla de Mesas
CREATE TABLE tbl_tables (
    table_id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    table_number INT NOT NULL,
    capacity INT NOT NULL,
    status ENUM('free', 'occupied') DEFAULT 'free',  -- Estado de mesa (ocupada o libre)
    FOREIGN KEY (room_id) REFERENCES tbl_rooms(room_id) ON DELETE CASCADE
);

-- Tabla de Ocupaciones (Historial de uso de mesas)
CREATE TABLE tbl_occupations (
    occupation_id INT PRIMARY KEY AUTO_INCREMENT,
    table_id INT NOT NULL,
    user_id INT NOT NULL,
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME DEFAULT NULL,
    FOREIGN KEY (table_id) REFERENCES tbl_tables(table_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE
);

-- Tabla de Grupos de Mesas (para juntar mesas temporalmente)
CREATE TABLE tbl_table_groups (
    group_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,  -- Ahora user_id puede ser NULL
    status ENUM('active', 'completed') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE SET NULL
);

-- Tabla Relacional para unir mesas a los grupos
CREATE TABLE tbl_group_tables (
    group_id INT,
    table_id INT,
    PRIMARY KEY (group_id, table_id),
    FOREIGN KEY (group_id) REFERENCES tbl_table_groups(group_id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES tbl_tables(table_id) ON DELETE CASCADE
);

-- Tabla para controlar el stock de sillas
CREATE TABLE tbl_chairs_stock (
    stock_id INT PRIMARY KEY AUTO_INCREMENT,
    chairs_in_warehouse INT NOT NULL DEFAULT 44  -- Sillas en el almacén
);

-- Tabla para controlar el stock de mesas
CREATE TABLE tbl_tables_stock (
    stock_id INT PRIMARY KEY AUTO_INCREMENT,
    tables_in_warehouse INT NOT NULL DEFAULT 10  -- Mesas en el almacén
);

-- Registro inicial del stock de mesas (cambiado a 10)
INSERT INTO tbl_tables_stock (tables_in_warehouse)
VALUES (10);

-- Registro inicial del stock de sillas (cambiado a 44)
INSERT INTO tbl_chairs_stock (chairs_in_warehouse)
VALUES (44);

-- Insertar roles en la tabla de roles
INSERT INTO tbl_roles (role_name) VALUES ('Camarero'), ('Administrador');

-- Insertar salas en la tabla de salas con imágenes asociadas
INSERT INTO tbl_rooms (name_rooms, capacity, description, image_url) 
VALUES 
    ('Terraza 1', 5, 'Primera terraza exterior del restaurante', 'images/terraza1.jpg'),
    ('Terraza 2', 10, 'Segunda terraza con vistas al parque', 'images/terraza2.jpg'),
    ('Terraza 3', 5, 'Tercera terraza con ambiente acogedor', 'images/terraza3.jpg'),
    ('Salón 1', 15, 'Salón principal del restaurante', 'images/salon1.jpg'),
    ('Salón 2', 20, 'Salón secundario con capacidad amplia', 'images/salon2.jpg'),
    ('Sala Privada 1', 4, 'Sala privada para eventos pequeños', 'images/sala_privada1.jpg'),
    ('Sala Privada 2', 1, 'Sala privada con ambiente íntimo', 'images/sala_privada2.jpg'),
    ('Sala Privada 3', 2, 'Sala privada para reuniones exclusivas', 'images/sala_privada3.jpg'),
    ('Sala Privada 4', 8, 'Sala privada de tamaño mediano', 'images/sala_privada4.jpg');

-- Insertar mesas en la tabla de mesas (70 ocupadas, 10 libres)
INSERT INTO tbl_tables (room_id, table_number, capacity, status) VALUES
-- Terraza 1
(1, 1, 4, 'occupied'),
(1, 2, 4, 'occupied'),
(1, 3, 4, 'occupied'),
(1, 4, 4, 'occupied'),
(1, 5, 4, 'occupied'),

-- Terraza 2
(2, 1, 4, 'occupied'),
(2, 2, 4, 'occupied'),
(2, 3, 4, 'occupied'),
(2, 4, 4, 'occupied'),
(2, 5, 4, 'occupied'),
(2, 6, 4, 'occupied'),
(2, 7, 4, 'occupied'),
(2, 8, 4, 'occupied'),
(2, 9, 4, 'occupied'),
(2, 10, 4, 'occupied'),

-- Terraza 3
(3, 1, 4, 'occupied'),
(3, 2, 4, 'occupied'),
(3, 3, 4, 'occupied'),
(3, 4, 4, 'occupied'),
(3, 5, 4, 'occupied'),

-- Salon 1
(4, 1, 6, 'occupied'),
(4, 2, 6, 'occupied'),
(4, 3, 6, 'occupied'),
(4, 4, 6, 'occupied'),
(4, 5, 6, 'occupied'),
(4, 6, 6, 'occupied'),
(4, 7, 6, 'occupied'),
(4, 8, 6, 'occupied'),
(4, 9, 6, 'occupied'),
(4, 10, 6, 'occupied'),

-- Salon 2
(5, 1, 8, 'occupied'),
(5, 2, 8, 'occupied'),
(5, 3, 8, 'occupied'),
(5, 4, 8, 'occupied'),
(5, 5, 8, 'occupied'),
(5, 6, 8, 'occupied'),
(5, 7, 8, 'occupied'),
(5, 8, 8, 'occupied'),
(5, 9, 8, 'occupied'),
(5, 10, 8, 'occupied'),
(5, 11, 8, 'occupied'),
(5, 12, 8, 'occupied'),
(5, 13, 8, 'occupied'),
(5, 14, 8, 'occupied'),
(5, 15, 8, 'occupied'),

-- Sala Privada 1
(6, 1, 2, 'free'),
(6, 2, 2, 'free'),
(6, 3, 2, 'free'),
(6, 4, 2, 'free'),

-- Sala Privada 2
(7, 1, 2, 'free'),

-- Sala Privada 3
(8, 1, 2, 'free'),
(8, 2, 2, 'free'),

-- Sala Privada 4
(9, 1, 4, 'free'),
(9, 2, 4, 'free'),
(9, 3, 4, 'free'),
(9, 4, 4, 'free'),
(9, 5, 4, 'free'),
(9, 6, 4, 'free'),
(9, 7, 4, 'free'),
(9, 8, 4, 'free');

-- Insertar un usuario llamado 'pau blanch' con el rol de Administrador
INSERT INTO tbl_users (username, lastname, pwd, role_id)
VALUES ('pau', 'blanch', SHA2('qweQWE123', 256), 
    (SELECT role_id FROM tbl_roles WHERE role_name = 'Administrador'));

-- Verificación de los registros
SELECT * FROM tbl_chairs_stock;
SELECT * FROM tbl_tables_stock;
SELECT table_id, status FROM tbl_tables ORDER BY table_id;

UPDATE tbl_chairs_stock 
SET chairs_in_warehouse = 44 
WHERE stock_id = 1;