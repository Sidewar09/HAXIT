<?php
require "sesion.php";
require "conexion.php";
require "regiones.php";
$pagina_actual = "settings";
$id = $_SESSION["id"];
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($_POST["accion"] == "perfil") {
        $nickname = trim($_POST["nickname"]);
        $region   = $_POST["region"];

        if ($nickname == "" || !in_array($region, $regiones)) {
            $mensaje = "Datos inválidos.";
        } else {
            $consulta = $conexion->prepare("UPDATE usuarios SET nickname = ?, region = ? WHERE id = ?");
            $consulta->bind_param("ssi", $nickname, $region, $id);
            $consulta->execute();
            $_SESSION["nickname"] = $nickname;
            $mensaje = "Perfil actualizado.";
        }
    }

    if ($_POST["accion"] == "clave") {
        $actual = $_POST["clave_actual"];
        $nueva  = $_POST["clave_nueva"];

        $consulta = $conexion->prepare("SELECT password FROM usuarios WHERE id = ?");
        $consulta->bind_param("i", $id);
        $consulta->execute();
        $fila = $consulta->get_result()->fetch_assoc();

        if ($nueva == "") {
            $mensaje = "La nueva contraseña no puede estar vacía.";
        } elseif (!password_verify($actual, $fila["password"])) {
            $mensaje = "La contraseña actual es incorrecta.";
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $consulta = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $consulta->bind_param("si", $hash, $id);
            $consulta->execute();
            $mensaje = "Contraseña actualizada.";
        }
    }
}

$consulta = $conexion->prepare("SELECT nickname, region FROM usuarios WHERE id = ?");
$consulta->bind_param("i", $id);
$consulta->execute();
$usuario = $consulta->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAXIT - Settings</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include "menu_izq.php"; ?>

        <main class="contenido">
            <h1>Settings</h1>

            <?php if ($mensaje != ""): ?>
                <p class="mensaje"><?php echo $mensaje; ?></p>
            <?php endif; ?>

            <h2>Perfil</h2>
            <form method="POST" action="settings.php" class="formulario">
                <input type="hidden" name="accion" value="perfil">

                <label for="nickname">Nickname:</label>
                <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($usuario["nickname"]); ?>" required>

                <label for="region">Región:</label>
                <select id="region" name="region">
                    <?php foreach ($regiones as $r): ?>
                        <option <?php echo $usuario["region"] == $r ? "selected" : ""; ?>><?php echo $r; ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Guardar</button>
            </form>

            <h2>Contraseña</h2>
            <form method="POST" action="settings.php" class="formulario">
                <input type="hidden" name="accion" value="clave">

                <label for="clave_actual">Contraseña actual:</label>
                <input type="password" id="clave_actual" name="clave_actual" required>

                <label for="clave_nueva">Contraseña nueva:</label>
                <input type="password" id="clave_nueva" name="clave_nueva" required>

                <button type="submit">Cambiar contraseña</button>
            </form>
        </main>

        <?php include "menu_der.php"; ?>
    </div>
</body>
</html>
