-- Opcional: jugadores y partidas falsas para probar el ranking y el perfil.
-- Estos usuarios NO pueden loguearse (su contraseña es inválida a propósito).
USE haxit;

INSERT INTO usuarios (username, nickname, email, password, region, elo) VALUES
('messi10',  'Leo',     'messi10@test.com',  'x', 'Argentina', 1180),
('dibu23',   'Dibu',    'dibu23@test.com',   'x', 'Argentina', 1040),
('neymar',   'Ney',     'neymar@test.com',   'x', 'Brasil',    1210),
('vinicius', 'Vini',    'vinicius@test.com', 'x', 'Brasil',     985),
('alexis',   'Alexis',  'alexis@test.com',   'x', 'Chile',      920),
('suarez9',  'Pistola', 'suarez9@test.com',  'x', 'Uruguay',   1100),
('james',    'James',   'james@test.com',    'x', 'Colombia',  1005),
('chicharito','Chicha', 'chicha@test.com',   'x', 'México',     870);

INSERT INTO partidas (ganador, goles_red, goles_blue) VALUES ('red', 5, 3);
SET @p1 = LAST_INSERT_ID();
INSERT INTO partida_jugadores (partida_id, usuario_id, equipo, goles, goles_en_contra, toques, elo_cambio) VALUES
(@p1, (SELECT id FROM usuarios WHERE username='messi10'), 'red',  5, 0, 41,  32),
(@p1, (SELECT id FROM usuarios WHERE username='neymar'),  'blue', 3, 0, 38, -32);

INSERT INTO partidas (ganador, goles_red, goles_blue) VALUES ('blue', 2, 4);
SET @p2 = LAST_INSERT_ID();
INSERT INTO partida_jugadores (partida_id, usuario_id, equipo, goles, goles_en_contra, toques, elo_cambio) VALUES
(@p2, (SELECT id FROM usuarios WHERE username='messi10'), 'red',  2, 1, 30, -32),
(@p2, (SELECT id FROM usuarios WHERE username='suarez9'), 'blue', 4, 0, 35,  32);

INSERT INTO partidas (ganador, goles_red, goles_blue) VALUES ('red', 3, 1);
SET @p3 = LAST_INSERT_ID();
INSERT INTO partida_jugadores (partida_id, usuario_id, equipo, goles, goles_en_contra, toques, elo_cambio) VALUES
(@p3, (SELECT id FROM usuarios WHERE username='dibu23'),  'red',  3, 0, 29,  32),
(@p3, (SELECT id FROM usuarios WHERE username='alexis'),  'blue', 1, 0, 27, -32);
