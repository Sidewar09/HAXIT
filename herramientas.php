<?php
require "sesion.php";
require "conexion.php";
require "funciones_partida.php";
$pagina_actual = "herramientas";
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"];

    if ($accion == "resultado_manual") {
        $rojo = trim($_POST["rojo"]);
        $azul = trim($_POST["azul"]);
        $gr = intval($_POST["goles_red"]);
        $gb = intval($_POST["goles_blue"]);

        if ($gr == $gb) {
            $mensaje = "No puede haber empate.";
        } else {
            $datos = [
                "ganador" => $gr > $gb ? "red" : "blue",
                "goles_red" => $gr,
                "goles_blue" => $gb,
                "jugadores" => [
                    ["username" => $rojo, "equipo" => "red",  "goles" => $gr, "goles_en_contra" => 0, "toques" => 0],
                    ["username" => $azul, "equipo" => "blue", "goles" => $gb, "goles_en_contra" => 0, "toques" => 0]
                ]
            ];
            $r = registrar_resultado($conexion, $datos);
            $mensaje = $r["ok"] ? "Partida #" . $r["partida_id"] . " registrada." : $r["error"];
        }
    }

    if ($accion == "resultado_json") {
        $datos = json_decode($_POST["json"], true);
        if (!$datos) {
            $mensaje = "JSON inválido.";
        } else {
            $r = registrar_resultado($conexion, $datos);
            $mensaje = $r["ok"] ? "Partida #" . $r["partida_id"] . " registrada." : $r["error"];
        }
    }

    if ($accion == "cola_on") {
        $conexion->query("UPDATE usuarios SET en_cola = 1, intentos_cola = 0, cola_actividad = NOW() + INTERVAL 1 HOUR WHERE email LIKE '%@test.com'");
        $mensaje = "Usuarios de prueba metidos en la cola.";
    }
    if ($accion == "cola_off") {
        $conexion->query("UPDATE usuarios SET en_cola = 0, intentos_cola = 0");
        $mensaje = "Todos los usuarios fuera de la cola.";
    }

    if ($accion == "agregar_sala") {
        $link = trim($_POST["link"]);
        if ($link == "") {
            $mensaje = "El link está vacío.";
        } else {
            $c = $conexion->prepare("INSERT INTO salas (link) VALUES (?)");
            $c->bind_param("s", $link);
            $c->execute();
            $mensaje = "Sala agregada.";
        }
    }
    if ($accion == "reset") {
        $conexion->query("UPDATE emparejamientos SET estado = 'finalizada' WHERE estado = 'activa'");
        $conexion->query("UPDATE salas SET ocupada = 0");
        $conexion->query("UPDATE usuarios SET en_cola = 0, intentos_cola = 0");
        $mensaje = "Reset hecho: salas libres, cola vacía y emparejamientos cerrados.";
    }
}

$salas = $conexion->query("SELECT id, link, ocupada FROM salas ORDER BY id");
$en_cola = $conexion->query("SELECT username, region, elo, intentos_cola FROM usuarios WHERE en_cola = 1 ORDER BY elo");
$activos = $conexion->query("SELECT e.id, u1.username AS j1, u2.username AS j2, s.link
    FROM emparejamientos e
    JOIN usuarios u1 ON u1.id = e.jugador1_id
    JOIN usuarios u2 ON u2.id = e.jugador2_id
    JOIN salas s ON s.id = e.sala_id
    WHERE e.estado = 'activa'");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAXIT - Herramientas</title>
</head>
<body>
    <div class="layout">
        <?php include "menu_izq.php"; ?>

        <main class="contenido">
            <h1>Herramientas de prueba</h1>

            <?php if ($mensaje != ""): ?>
                <p class="mensaje"><?php echo htmlspecialchars($mensaje); ?></p>
            <?php endif; ?>

            <h2>Cargar resultado (sin bot)</h2>
            <form method="POST" action="herramientas.php" class="formulario">
                <input type="hidden" name="accion" value="resultado_manual">
                <label for="rojo">Username equipo rojo:</label>
                <input type="text" id="rojo" name="rojo" required>
                <label for="azul">Username equipo azul:</label>
                <input type="text" id="azul" name="azul" required>
                <label for="goles_red">Goles rojo:</label>
                <input type="number" id="goles_red" name="goles_red" min="0" value="3" required>
                <label for="goles_blue">Goles azul:</label>
                <input type="number" id="goles_blue" name="goles_blue" min="0" value="1" required>
                <button type="submit">Registrar</button>
            </form>

            <h2>Cargar resultado (JSON del bot)</h2>
            <form method="POST" action="herramientas.php" class="formulario">
                <input type="hidden" name="accion" value="resultado_json">
                <label for="json">Pegá acá el JSON que imprimió el bot:</label>
                <textarea id="json" name="json" rows="6" required></textarea>
                <button type="submit">Registrar</button>
            </form>

            <h2>Cola de matchmaking</h2>
            <form method="POST" action="herramientas.php" class="filtros">
                <button type="submit" name="accion" value="cola_on">Meter usuarios de prueba en cola</button>
                <button type="submit" name="accion" value="cola_off">Vaciar la cola</button>
                <button type="submit" name="accion" value="reset">Reset total</button>
            </form>

            <table>
                <tr><th>En cola</th><th>Región</th><th>ELO</th><th>Intentos</th></tr>
                <?php while ($f = $en_cola->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($f["username"]); ?></td>
                        <td><?php echo htmlspecialchars($f["region"]); ?></td>
                        <td><?php echo $f["elo"]; ?></td>
                        <td><?php echo $f["intentos_cola"]; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>

            <h2>Emparejamientos activos</h2>
            <table>
                <tr><th>#</th><th>Jugador 1</th><th>Jugador 2</th><th>Sala</th></tr>
                <?php while ($f = $activos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $f["id"]; ?></td>
                        <td><?php echo htmlspecialchars($f["j1"]); ?></td>
                        <td><?php echo htmlspecialchars($f["j2"]); ?></td>
                        <td><?php echo htmlspecialchars($f["link"]); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>

            <h2>Pool de salas</h2>
            <table>
                <tr><th>#</th><th>Link</th><th>Estado</th></tr>
                <?php while ($f = $salas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $f["id"]; ?></td>
                        <td><?php echo htmlspecialchars($f["link"]); ?></td>
                        <td><?php echo $f["ocupada"] ? "Ocupada" : "Libre"; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>

            <form method="POST" action="herramientas.php" class="formulario">
                <input type="hidden" name="accion" value="agregar_sala">
                <label for="link">Agregar link de sala de HaxBall:</label>
                <input type="text" id="link" name="link" required>
                <button type="submit">Agregar sala</button>
            </form>
        </main>

        <?php include "menu_der.php"; ?>
    </div>
</body>
</html>
