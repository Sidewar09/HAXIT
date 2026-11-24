<?php
$host = "localhost";
$usuario = "root";
$clave = "";
$base = "haxit";

$conexion = new mysqli($host, $usuario, $clave, $base);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

define("CLAVE_BOT", "haxit-bot-2026");
