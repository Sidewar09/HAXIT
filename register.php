<?php
require "conexion.php";
require "regiones.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.html");
    exit;
}

$username = trim($_POST["username"]);
$nickname = trim($_POST["nickname"]);
$email    = trim($_POST["email"]);
$password = $_POST["password"];
$region   = $_POST["region"];

if ($username == "" || $nickname == "" || $email == "" || $password == "" || $region == "") {
    die("Completá todos los campos. <a href='register.html'>Volver</a>");
}

if (!in_array($region, $regiones)) {
    die("Región inválida. <a href='register.html'>Volver</a>");
}

$consulta = $conexion->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
$consulta->bind_param("ss", $username, $email);
$consulta->execute();
$consulta->store_result();

if ($consulta->num_rows > 0) {
    die("Ese username o email ya está en uso. <a href='register.html'>Volver</a>");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$insertar = $conexion->prepare("INSERT INTO usuarios (username, nickname, email, password, region) VALUES (?, ?, ?, ?, ?)");
$insertar->bind_param("sssss", $username, $nickname, $email, $hash, $region);

if ($insertar->execute()) {
    header("Location: login.html");
    exit;
} else {
    die("Error al registrar: " . $conexion->error);
}
