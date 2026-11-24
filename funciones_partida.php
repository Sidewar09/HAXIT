<?php
// $datos = [
//   "ganador" => "red" | "blue", "goles_red" => 5, "goles_blue" => 3,
//   "jugadores" => [ ["username"=>"messi10","equipo"=>"red","goles"=>5,"goles_en_contra"=>0,"toques"=>40], ... ]
// ]
// Devuelve ["ok" => true, "partida_id" => N] o ["ok" => false, "error" => "..."]
function registrar_resultado($conexion, $datos) {
    $ganador    = isset($datos["ganador"]) ? $datos["ganador"] : "";
    $goles_red  = intval(isset($datos["goles_red"]) ? $datos["goles_red"] : 0);
    $goles_blue = intval(isset($datos["goles_blue"]) ? $datos["goles_blue"] : 0);
    $jugadores  = isset($datos["jugadores"]) ? $datos["jugadores"] : [];

    if ($ganador != "red" && $ganador != "blue") {
        return ["ok" => false, "error" => "Ganador inválido (tiene que ser red o blue)."];
    }
    if (count($jugadores) != 2) {
        return ["ok" => false, "error" => "Tienen que ser exactamente 2 jugadores (1v1)."];
    }
    if ($jugadores[0]["equipo"] == $jugadores[1]["equipo"]) {
        return ["ok" => false, "error" => "Los dos jugadores no pueden ser del mismo equipo."];
    }
    if ($jugadores[0]["username"] == $jugadores[1]["username"]) {
        return ["ok" => false, "error" => "Los dos jugadores no pueden ser el mismo usuario."];
    }

    $conexion->begin_transaction();

    try {
        foreach ($jugadores as $i => $j) {
            $c = $conexion->prepare("SELECT id, elo FROM usuarios WHERE username = ? FOR UPDATE");
            $c->bind_param("s", $j["username"]);
            $c->execute();
            $u = $c->get_result()->fetch_assoc();

            if (!$u) {
                $conexion->rollback();
                return ["ok" => false, "error" => "No existe el usuario: " . $j["username"]];
            }
            $jugadores[$i]["id"]  = $u["id"];
            $jugadores[$i]["elo"] = $u["elo"];
        }

        $c = $conexion->prepare("INSERT INTO partidas (ganador, goles_red, goles_blue) VALUES (?, ?, ?)");
        $c->bind_param("sii", $ganador, $goles_red, $goles_blue);
        $c->execute();
        $partida_id = $conexion->insert_id;

        foreach ($jugadores as $j) {
            $goles           = intval(isset($j["goles"]) ? $j["goles"] : 0);
            $goles_en_contra = intval(isset($j["goles_en_contra"]) ? $j["goles_en_contra"] : 0);
            $toques          = intval(isset($j["toques"]) ? $j["toques"] : 0);

            // Ganador +32. Perdedor -32, pero sin bajar de 0.
            if ($j["equipo"] == $ganador) {
                $cambio = 32;
            } else {
                $cambio = -min(32, $j["elo"]);
            }

            $c = $conexion->prepare("INSERT INTO partida_jugadores
                (partida_id, usuario_id, equipo, goles, goles_en_contra, toques, elo_cambio)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $c->bind_param("iisiiii", $partida_id, $j["id"], $j["equipo"], $goles, $goles_en_contra, $toques, $cambio);
            $c->execute();

            $c = $conexion->prepare("UPDATE usuarios SET elo = elo + ? WHERE id = ?");
            $c->bind_param("ii", $cambio, $j["id"]);
            $c->execute();
        }

        $id1 = $jugadores[0]["id"];
        $id2 = $jugadores[1]["id"];
        $c = $conexion->prepare("SELECT id, sala_id FROM emparejamientos
            WHERE estado = 'activa'
            AND ((jugador1_id = ? AND jugador2_id = ?) OR (jugador1_id = ? AND jugador2_id = ?))
            LIMIT 1");
        $c->bind_param("iiii", $id1, $id2, $id2, $id1);
        $c->execute();
        $emparejamiento = $c->get_result()->fetch_assoc();

        if ($emparejamiento) {
            $c = $conexion->prepare("UPDATE emparejamientos SET estado = 'finalizada' WHERE id = ?");
            $c->bind_param("i", $emparejamiento["id"]);
            $c->execute();

            $c = $conexion->prepare("UPDATE salas SET ocupada = 0 WHERE id = ?");
            $c->bind_param("i", $emparejamiento["sala_id"]);
            $c->execute();
        }

        $conexion->commit();
        return ["ok" => true, "partida_id" => $partida_id];

    } catch (Exception $e) {
        $conexion->rollback();
        return ["ok" => false, "error" => "Error de base de datos: " . $e->getMessage()];
    }
}
