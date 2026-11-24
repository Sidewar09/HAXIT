<?php
// Rango de ELO que se acepta según cuánto llevás buscando.
// Cada 2 refrescos de la página (~6 segundos) se ensancha: 250, 500, 750.
// Después devuelve null = sin límite (cualquier región, el ELO más cercano).
function rango_busqueda($intentos) {
    $paso = intdiv($intentos, 2);
    if ($paso <= 2) {
        return 250 * ($paso + 1);
    }
    return null;
}

// Devuelve el emparejamiento activo del usuario, o null si no tiene
function obtener_emparejamiento_activo($conexion, $id) {
    $c = $conexion->prepare("SELECT id FROM emparejamientos WHERE estado = 'activa' AND (jugador1_id = ? OR jugador2_id = ?) LIMIT 1");
    $c->bind_param("ii", $id, $id);
    $c->execute();
    return $c->get_result()->fetch_assoc();
}

function datos_usuario($conexion, $id) {
    $c = $conexion->prepare("SELECT nickname, username, region, elo FROM usuarios WHERE id = ?");
    $c->bind_param("i", $id);
    $c->execute();
    return $c->get_result()->fetch_assoc();
}

// Si un emparejamiento lleva más de 15 minutos activo (alguien nunca entró a la sala,
// se cayó el bot, etc.) se da por terminado y la sala vuelve al pool.
function liberar_salas_vencidas($conexion) {
    $conexion->query("UPDATE salas SET ocupada = 0 WHERE id IN
        (SELECT sala_id FROM emparejamientos WHERE estado = 'activa' AND fecha < NOW() - INTERVAL 15 MINUTE)");
    $conexion->query("UPDATE emparejamientos SET estado = 'finalizada'
        WHERE estado = 'activa' AND fecha < NOW() - INTERVAL 15 MINUTE");
}

// Intenta emparejar a $id. Devuelve: "encontrada", "buscando", "sin_sala" o "no_en_cola"
function buscar_rival($conexion, $id, $intentos) {
    liberar_salas_vencidas($conexion);

    // Transacción: todo lo siguiente se hace junto o no se hace (evita emparejar dos veces)
    $conexion->begin_transaction();

    try {
        // 1) Mis datos (FOR UPDATE bloquea mi fila mientras dura la transacción)
        $c = $conexion->prepare("SELECT region, elo, en_cola FROM usuarios WHERE id = ? FOR UPDATE");
        $c->bind_param("i", $id);
        $c->execute();
        $yo = $c->get_result()->fetch_assoc();

        if ($yo["en_cola"] != 1) {
            $conexion->rollback();
            return "no_en_cola";   // alguien ya me emparejó
        }

        // 2) ¿Hay una sala libre en el pool?
        $sala = $conexion->query("SELECT id FROM salas WHERE ocupada = 0 LIMIT 1 FOR UPDATE")->fetch_assoc();
        if (!$sala) {
            $conexion->rollback();
            return "sin_sala";
        }

        // 3) Buscar rival: en cola, y que haya refrescado su página hace menos de 15 segundos
        $rango = rango_busqueda($intentos);

        if ($rango !== null) {
            // Misma región y ELO dentro del rango, el más cercano primero
            $c = $conexion->prepare("SELECT id FROM usuarios
                WHERE en_cola = 1 AND id != ? AND region = ? AND ABS(elo - ?) <= ?
                AND cola_actividad > NOW() - INTERVAL 15 SECOND
                ORDER BY ABS(elo - ?) ASC LIMIT 1 FOR UPDATE");
            $c->bind_param("isiii", $id, $yo["region"], $yo["elo"], $rango, $yo["elo"]);
        } else {
            // Sin límites: el de menor diferencia de ELO, de cualquier región
            $c = $conexion->prepare("SELECT id FROM usuarios
                WHERE en_cola = 1 AND id != ?
                AND cola_actividad > NOW() - INTERVAL 15 SECOND
                ORDER BY ABS(elo - ?) ASC LIMIT 1 FOR UPDATE");
            $c->bind_param("ii", $id, $yo["elo"]);
        }
        $c->execute();
        $rival = $c->get_result()->fetch_assoc();

        if (!$rival) {
            $conexion->rollback();
            return "buscando";
        }

        // 4) Crear el emparejamiento, sacar a los dos de la cola y ocupar la sala
        $c = $conexion->prepare("INSERT INTO emparejamientos (sala_id, jugador1_id, jugador2_id) VALUES (?, ?, ?)");
        $c->bind_param("iii", $sala["id"], $id, $rival["id"]);
        $c->execute();

        $c = $conexion->prepare("UPDATE usuarios SET en_cola = 0, intentos_cola = 0 WHERE id IN (?, ?)");
        $c->bind_param("ii", $id, $rival["id"]);
        $c->execute();

        $c = $conexion->prepare("UPDATE salas SET ocupada = 1 WHERE id = ?");
        $c->bind_param("i", $sala["id"]);
        $c->execute();

        $conexion->commit();
        return "encontrada";

    } catch (Exception $e) {
        // Si dos jugadores chocan al mismo tiempo, MySQL cancela uno: reintenta en el próximo refresco
        $conexion->rollback();
        return "buscando";
    }
}
