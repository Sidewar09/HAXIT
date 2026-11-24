<?php
require "sesion.php";
require "conexion.php";
$pagina_actual = "perfil";
$id = $_SESSION["id"];

$consulta = $conexion->prepare("SELECT username, nickname, email, region, elo, fecha_registro FROM usuarios WHERE id = ?");
$consulta->bind_param("i", $id);
$consulta->execute();
$usuario = $consulta->get_result()->fetch_assoc();

$consulta = $conexion->prepare("
    SELECT COUNT(*) AS jugadas,
           COALESCE(SUM(pj.equipo = p.ganador), 0) AS victorias,
           COALESCE(SUM(pj.goles), 0) AS goles,
           COALESCE(SUM(pj.goles_en_contra), 0) AS en_contra
    FROM partida_jugadores pj
    JOIN partidas p ON p.id = pj.partida_id
    WHERE pj.usuario_id = ?");
$consulta->bind_param("i", $id);
$consulta->execute();
$stats = $consulta->get_result()->fetch_assoc();
$derrotas = $stats["jugadas"] - $stats["victorias"];
$consulta = $conexion->prepare("
    SELECT p.fecha, pj.equipo, p.ganador, p.goles_red, p.goles_blue, pj.goles, pj.goles_en_contra, pj.elo_cambio
    FROM partida_jugadores pj
    JOIN partidas p ON p.id = pj.partida_id
    WHERE pj.usuario_id = ?
    ORDER BY p.fecha DESC
    LIMIT 10");
$consulta->bind_param("i", $id);
$consulta->execute();
$partidas = $consulta->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAXIT - Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include "menu_izq.php"; ?>

        <main class="contenido">
            <h1>Profile</h1>
            <p><strong><?php echo htmlspecialchars($usuario["nickname"]); ?></strong>
               (@<?php echo htmlspecialchars($usuario["username"]); ?>)</p>
            <p>Región: <?php echo htmlspecialchars($usuario["region"]); ?></p>
            <p>ELO: <?php echo $usuario["elo"]; ?></p>
            <p>Miembro desde: <?php echo $usuario["fecha_registro"]; ?></p>

            <h2>Estadísticas</h2>
            <table>
                <tr><th>Partidas</th><th>Victorias</th><th>Derrotas</th><th>Goles</th><th>Goles en contra</th></tr>
                <tr>
                    <td><?php echo $stats["jugadas"]; ?></td>
                    <td><?php echo $stats["victorias"]; ?></td>
                    <td><?php echo $derrotas; ?></td>
                    <td><?php echo $stats["goles"]; ?></td>
                    <td><?php echo $stats["en_contra"]; ?></td>
                </tr>
            </table>

            <h2>Últimas partidas</h2>
            <table>
                <tr><th>Fecha</th><th>Equipo</th><th>Resultado</th><th>Marcador</th><th>Goles</th><th>ELO</th></tr>
                <?php while ($p = $partidas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $p["fecha"]; ?></td>
                        <td><?php echo $p["equipo"]; ?></td>
                        <td><?php echo $p["equipo"] == $p["ganador"] ? "Victoria" : "Derrota"; ?></td>
                        <td><?php echo $p["goles_red"] . " - " . $p["goles_blue"]; ?></td>
                        <td><?php echo $p["goles"]; ?></td>
                        <td><?php echo $p["elo_cambio"] > 0 ? "+" . $p["elo_cambio"] : $p["elo_cambio"]; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </main>

        <?php include "menu_der.php"; ?>
    </div>
</body>
</html>
