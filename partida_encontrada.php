<?php
require "sesion.php";
require "conexion.php";
require "funciones_matchmaking.php";
$pagina_actual = "matchmaking";
$id = $_SESSION["id"];

$c = $conexion->prepare("SELECT e.id, e.sala_id, e.jugador1_id, e.jugador2_id, s.link
    FROM emparejamientos e
    JOIN salas s ON s.id = e.sala_id
    WHERE e.estado = 'activa' AND (e.jugador1_id = ? OR e.jugador2_id = ?)
    LIMIT 1");
$c->bind_param("ii", $id, $id);
$c->execute();
$partida = $c->get_result()->fetch_assoc();

if (!$partida) {
    header("Location: index.php");
    exit;
}

$rival_id = ($partida["jugador1_id"] == $id) ? $partida["jugador2_id"] : $partida["jugador1_id"];
$yo    = datos_usuario($conexion, $id);
$rival = datos_usuario($conexion, $rival_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10">
    <title>HAXIT - Partida encontrada</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include "menu_izq.php"; ?>

        <main class="contenido">
            <h1>Partida encontrada</h1>

            <div class="versus">
                <div class="jugador">
                    <h2><?php echo htmlspecialchars($yo["nickname"]); ?></h2>
                    <p>@<?php echo htmlspecialchars($yo["username"]); ?></p>
                    <p><?php echo htmlspecialchars($yo["region"]); ?></p>
                    <p class="elo"><?php echo $yo["elo"]; ?> ELO</p>
                </div>

                <div class="vs">VS</div>

                <div class="jugador">
                    <h2><?php echo htmlspecialchars($rival["nickname"]); ?></h2>
                    <p>@<?php echo htmlspecialchars($rival["username"]); ?></p>
                    <p><?php echo htmlspecialchars($rival["region"]); ?></p>
                    <p class="elo"><?php echo $rival["elo"]; ?> ELO</p>
                </div>
            </div>

            <h2>Link de la sala</h2>
            <input type="text" class="link-sala" readonly
                   value="<?php echo htmlspecialchars($partida["link"]); ?>"
                   onclick="this.select()">
            <p>Entrá con tu username como nombre. Cuando termine la partida, volvés al home automáticamente.</p>
        </main>

        <?php include "menu_der.php"; ?>
    </div>
</body>
</html>
