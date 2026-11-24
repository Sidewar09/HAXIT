<?php
require "sesion.php";
require "conexion.php";
require "funciones_matchmaking.php";
$pagina_actual = "matchmaking";
$id = $_SESSION["id"];

if (obtener_emparejamiento_activo($conexion, $id)) {
    header("Location: partida_encontrada.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($_POST["accion"] == "buscar") {
        $c = $conexion->prepare("UPDATE usuarios SET en_cola = 1, intentos_cola = 0, cola_actividad = NOW() WHERE id = ?");
        $c->bind_param("i", $id);
        $c->execute();
        header("Location: matchmaking.php");
        exit;
    }
    if ($_POST["accion"] == "cancelar") {
        $c = $conexion->prepare("UPDATE usuarios SET en_cola = 0, intentos_cola = 0 WHERE id = ?");
        $c->bind_param("i", $id);
        $c->execute();
        header("Location: index.php");
        exit;
    }
}

$c = $conexion->prepare("SELECT region, elo, en_cola, intentos_cola FROM usuarios WHERE id = ?");
$c->bind_param("i", $id);
$c->execute();
$yo = $c->get_result()->fetch_assoc();

if ($yo["en_cola"] != 1) {
    header("Location: index.php");
    exit;
}

$intentos = $yo["intentos_cola"];
$c = $conexion->prepare("UPDATE usuarios SET intentos_cola = intentos_cola + 1, cola_actividad = NOW() WHERE id = ?");
$c->bind_param("i", $id);
$c->execute();

$estado = buscar_rival($conexion, $id, $intentos);

if ($estado == "encontrada" || $estado == "no_en_cola") {
    header("Location: partida_encontrada.php");
    exit;
}

$rango = rango_busqueda($intentos);
if ($estado == "sin_sala") {
    $mensaje = "Esperando una sala libre...";
} elseif ($rango !== null) {
    $min = $yo["elo"] - $rango;
    $max = $yo["elo"] + $rango;
    $mensaje = "Buscando rival de " . $yo["region"] . " con ELO entre $min y $max";
} else {
    $mensaje = "Buscando al rival con el ELO más cercano (cualquier región)";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Recarga la página sola cada 3 segundos: cada recarga es un nuevo intento de emparejar -->
    <meta http-equiv="refresh" content="3">
    <title>HAXIT - Buscando partida</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include "menu_izq.php"; ?>

        <main class="contenido">
            <h1>Buscando partida...</h1>
            <p><?php echo htmlspecialchars($mensaje); ?></p>
            <p>Tu ELO: <?php echo $yo["elo"]; ?> | Región: <?php echo htmlspecialchars($yo["region"]); ?></p>

            <form method="POST" action="matchmaking.php">
                <input type="hidden" name="accion" value="cancelar">
                <button type="submit">Cancelar búsqueda</button>
            </form>
        </main>

        <?php include "menu_der.php"; ?>
    </div>
</body>
</html>
