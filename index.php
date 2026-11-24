<?php
require "sesion.php";
$pagina_actual = "home";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAXIT - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include "menu_izq.php"; ?>

        <main class="contenido">
            <h1>Welcome</h1>
            <p>Hola, <?php echo htmlspecialchars($_SESSION["nickname"]); ?>.</p>

            <form method="POST" action="matchmaking.php">
                <input type="hidden" name="accion" value="buscar">
                <button type="submit">Buscar partida</button>
            </form>
        </main>

        <?php include "menu_der.php"; ?>
    </div>
</body>
</html>
