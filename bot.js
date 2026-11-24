// =====================================================================
// HAXIT - Bot de HaxBall (1v1)
// Se pega en la consola (F12) de la página "Headless Host" de HaxBall.
// Registra: ganador, goles, goles en contra y toques de cada jugador.
// IMPORTANTE: el nombre del jugador en la sala tiene que ser su username de HAXIT.
// =====================================================================

// ---------- CONFIGURACIÓN (lo único que tenés que tocar) ----------
var CONFIG = {
    token: "PEGA_ACA_TU_TOKEN",                         // https://www.haxball.com/headlesstoken
    roomName: "HAXIT - Sala 1",
    scoreLimit: 3,                                      // partida a 3 goles
    timeLimit: 3,                                       // o 3 minutos
    enviarAutomatico: false,                            // true = intenta mandar el resultado a PHP solo
    apiUrl: "http://localhost/haxit/api_partida.php",
    clave: "haxit-bot-2026"                             // igual a CLAVE_BOT en conexion.php
};

// ---------- Crear la sala ----------
var room = HBInit({
    roomName: CONFIG.roomName,
    maxPlayers: 6,
    public: false,
    noPlayer: true,          // el bot no juega, solo administra
    token: CONFIG.token
});

room.setDefaultStadium("Classic");
room.setScoreLimit(CONFIG.scoreLimit);
room.setTimeLimit(CONFIG.timeLimit);

// ---------- Estado de la partida en curso ----------
var stats = {};          // estadísticas por id de jugador
var ultimoToque = null;  // id del último jugador que tocó la pelota
var enJuego = false;

function nombreEquipo(team) {
    return team === 1 ? "red" : "blue";   // en HaxBall: 1 = rojo, 2 = azul, 0 = espectador
}

function contrario(equipo) {
    return equipo === "red" ? "blue" : "red";
}

function contrasenaAleatoria() {
    return Math.random().toString(36).slice(2, 10);
}

// ---------- Eventos ----------
room.onRoomLink = function (link) {
    console.log("=========================================");
    console.log("LINK DE LA SALA: " + link);
    console.log("=========================================");
};

room.onPlayerJoin = function (player) {
    // Con la partida en curso no puede entrar nadie más: al intruso se lo banea
    if (enJuego) {
        room.kickPlayer(player.id, "Sala ocupada: partida en curso", true);
        return;
    }

    // El 1.º va al rojo, el 2.º al azul, y arranca la partida
    var lista = room.getPlayerList();
    var hayRojo = lista.some(function (p) { return p.team === 1; });
    var hayAzul = lista.some(function (p) { return p.team === 2; });

    var equipo = 0;
    if (!hayRojo) equipo = 1;
    else if (!hayAzul) equipo = 2;

    if (equipo !== 0) {
        room.setPlayerTeam(player.id, equipo);
        room.sendAnnouncement(player.name + " entró al equipo " + nombreEquipo(equipo));
    }

    // Si ya había uno esperando y entró el segundo, arrancamos
    if (equipo !== 0 && (hayRojo || hayAzul)) {
        setTimeout(function () { room.startGame(); }, 1000);
    }
};

// Si un JUGADOR se va en plena partida, pierde: gana el contrario
room.onPlayerLeave = function (player) {
    // Si el que se fue no estaba jugando (espectador), no pasa nada
    if (!enJuego || !stats[player.id]) return;

    var ganador = contrario(stats[player.id].equipo);
    var puntos = room.getScores();   // marcador al momento de irse

    room.stopGame();
    room.sendAnnouncement(stats[player.id].username + " abandonó. Gana el equipo " + ganador + ".");

    finalizarPartida(ganador, puntos ? puntos.red : 0, puntos ? puntos.blue : 0);
};

// Al empezar: estadísticas en cero y sala bloqueada con contraseña
room.onGameStart = function () {
    enJuego = true;
    ultimoToque = null;
    stats = {};
    room.getPlayerList().forEach(function (p) {
        if (p.team !== 0) {
            stats[p.id] = {
                username: p.name,
                equipo: nombreEquipo(p.team),
                goles: 0,
                goles_en_contra: 0,
                toques: 0
            };
        }
    });

    // Con contraseña, nadie que tenga el link puede entrar mientras se juega
    room.setPassword(contrasenaAleatoria());
};

// Cada vez que alguien toca la pelota: sumamos un toque y recordamos quién fue
room.onPlayerBallKick = function (player) {
    if (stats[player.id]) {
        stats[player.id].toques++;
        ultimoToque = player.id;
    }
};

// HaxBall solo dice QUÉ EQUIPO hizo el gol; el goleador es el último que tocó la pelota
room.onTeamGoal = function (team) {
    if (ultimoToque !== null && stats[ultimoToque]) {
        if (stats[ultimoToque].equipo === nombreEquipo(team)) {
            stats[ultimoToque].goles++;
        } else {
            stats[ultimoToque].goles_en_contra++;   // gol en contra
        }
    }
    ultimoToque = null;
};

// Fin normal de la partida (alguien llegó al límite de goles o se acabó el tiempo)
room.onTeamVictory = function (scores) {
    finalizarPartida(scores.red > scores.blue ? "red" : "blue", scores.red, scores.blue);
};

// ---------- Cierre de partida (victoria normal o abandono) ----------
function finalizarPartida(ganador, golesRed, golesBlue) {
    enJuego = false;

    var resultado = {
        clave: CONFIG.clave,
        ganador: ganador,
        goles_red: golesRed,
        goles_blue: golesBlue,
        jugadores: Object.keys(stats).map(function (id) { return stats[id]; })
    };

    enviarResultado(resultado);

    // A los 10 segundos se limpia la sala para el próximo par
    setTimeout(limpiarSala, 10000);
}

function limpiarSala() {
    var ids = room.getPlayerList().map(function (p) { return p.id; });

    ids.forEach(function (id) {
        room.kickPlayer(id, "Partida terminada", true);
    });

    room.setPassword(null);

    setTimeout(function () {
        ids.forEach(function (id) { room.clearBan(id); });
    }, 30000);
}

// ---------- Envío del resultado ----------
async function enviarResultado(resultado) {
    var json = JSON.stringify(resultado);

    window.ULTIMO_RESULTADO = json;
    console.log("RESULTADO (para cargar a mano, o escribí copy(ULTIMO_RESULTADO)):");
    console.log(json);

    if (!CONFIG.enviarAutomatico) return;

    try {
        var respuesta = await fetch(CONFIG.apiUrl, {
            method: "POST",
            headers: { "Content-Type": "text/plain" },  
            body: json,
            targetAddressSpace: "loopback"    
        });
        var datos = await respuesta.json();
        console.log("Respuesta del servidor:", datos);
        room.sendAnnouncement(datos.ok ? "Resultado guardado en HAXIT." : "Error al guardar: " + datos.error);
    } catch (error) {
        console.log("No se pudo enviar automáticamente:", error);
        room.sendAnnouncement("No se pudo enviar el resultado. Se carga manualmente.");
    }
}