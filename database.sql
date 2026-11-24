DROP DATABASE IF EXISTS haxit;
CREATE DATABASE haxit CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE haxit;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    nickname VARCHAR(30) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    region VARCHAR(30) NOT NULL,
    elo INT NOT NULL DEFAULT 1000,
    codigo_vinculo VARCHAR(10) UNIQUE NULL,
    en_cola TINYINT(1) NOT NULL DEFAULT 0,
    intentos_cola INT NOT NULL DEFAULT 0,
    cola_actividad DATETIME NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ganador ENUM('red','blue') NOT NULL,
    goles_red INT NOT NULL DEFAULT 0,
    goles_blue INT NOT NULL DEFAULT 0
);

CREATE TABLE partida_jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partida_id INT NOT NULL,
    usuario_id INT NOT NULL,
    equipo ENUM('red','blue') NOT NULL,
    goles INT NOT NULL DEFAULT 0,
    goles_en_contra INT NOT NULL DEFAULT 0,
    toques INT NOT NULL DEFAULT 0,
    elo_cambio INT NOT NULL DEFAULT 0,
    FOREIGN KEY (partida_id) REFERENCES partidas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    UNIQUE (partida_id, usuario_id)
);

-- Pool de salas de HaxBall (links)
CREATE TABLE salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link VARCHAR(255) NOT NULL,
    ocupada TINYINT(1) NOT NULL DEFAULT 0
);

-- Cada emparejamiento: dos jugadores + la sala que se les asignó
CREATE TABLE emparejamientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sala_id INT NOT NULL,
    jugador1_id INT NOT NULL,
    jugador2_id INT NOT NULL,
    estado ENUM('activa','finalizada') NOT NULL DEFAULT 'activa',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sala_id) REFERENCES salas(id),
    FOREIGN KEY (jugador1_id) REFERENCES usuarios(id),
    FOREIGN KEY (jugador2_id) REFERENCES usuarios(id)
);

INSERT INTO salas (link) VALUES
('https://www.haxball.com/play?c=SALA-1-LINK-DE-PRUEBA'),
('https://www.haxball.com/play?c=SALA-2-LINK-DE-PRUEBA');
