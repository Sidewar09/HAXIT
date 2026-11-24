<?php
require "conexion.php";
require "funciones_partida.php";

// El bot corre en otra página (haxball.com), así que hay que permitir pedidos de otro origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Los navegadores a veces mandan un pedido de "permiso" (OPTIONS) antes del POST
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

// El bot manda el resultado como JSON en el cuerpo del pedido
$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "JSON inválido"]);
    exit;
}

if (!isset($datos["clave"]) || $datos["clave"] !== CLAVE_BOT) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Clave incorrecta"]);
    exit;
}

echo json_encode(registrar_resultado($conexion, $datos));
