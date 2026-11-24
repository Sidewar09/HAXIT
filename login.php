<?php
session_start();
require "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$email    = trim($_POST["email"]);
$password = $_POST["password"];

$consulta = $conexion->prepare("SELECT id, username, nickname, password FROM usuarios WHERE email = ?");
$consulta->bind_param("s", $email);
$consulta->execute();
$resultado = $consulta->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();

    if (password_verify($password, $usuario["password"])) {
        $_SESSION["id"] = $usuario["id"];
        $_SESSION["username"] = $usuario["username"];
        $_SESSION["nickname"] = $usuario["nickname"];

        header("Location: index.php");
        exit;
    }
}

die("Email o contraseña incorrectos. <a href='login.html'>Volver</a>");
