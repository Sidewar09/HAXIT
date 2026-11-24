var CONFIG = {
    token: "PEGA_ACA_TU_TOKEN",
    roomName: "HAXIT - Sala 1",
    scoreLimit: 3,
    timeLimit: 3,
    enviarAutomatico: false,
    apiUrl: "http://localhost/haxit/api_partida.php",
    clave: "haxit-bot-2026"
};

var room = HBInit({
    roomName: CONFIG.roomName,
    maxPlayers: 6,
    public: false,
    noPlayer: true,
    token: CONFIG.token
});

room.setDefaultStadium("Classic");
room.setScoreLimit(CONFIG.scoreLimit);
room.setTimeLimit(CONFIG.timeLimit);

var stats = {};
var ultimoToque = null;
var enJuego = false;

function nombreEquipo(team) {
    return team === 1 ? "red" : "blue";
}

function contrario(equipo) {
    return equipo === "red" ? "blue" : "red";
}

function contrasenaAleatoria() {
    return Math.random().toString(36).slice(2, 10);
}

room.onRoomLink = function (link) {
    console.log("=========================================");
    console.log("LINK DE LA SALA: " + link);
    console.log("=========================================");
};

room.onPlayerJoin = function (player) {
    if (enJuego) {
        room.kickPlayer(player.id, "Sala ocupada: partida en curso", true);
        return;
    }

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

    if (equipo !== 0 && (hayRojo || hayAzul)) {
        setTimeout(function () { room.startGame(); }, 1000);
    }
};

room.onPlayerLeave = function (player) {
    if (!enJuego || !stats[player.id]) return;

    var ganador = contrario(stats[player.id].equipo);
    var puntos = room.getScores();

    room.stopGame();
    room.sendAnnouncement(stats[player.id].username + " abandonó. Gana el equipo " + ganador + ".");

    finalizarPartida(ganador, puntos ? puntos.red : 0, puntos ? puntos.blue : 0);
};

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

    room.setPassword(contrasenaAleatoria());
};

room.onPlayerBallKick = function (player) {
    if (stats[player.id]) {
        stats[player.id].toques++;
        ultimoToque = player.id;
    }
};

room.onTeamGoal = function (team) {
    if (ultimoToque !== null && stats[ultimoToque]) {
        if (stats[ultimoToque].equipo === nombreEquipo(team)) {
            stats[ultimoToque].goles++;
        } else {
            stats[ultimoToque].goles_en_contra++;
        }
    }
    ultimoToque = null;
};

room.onTeamVictory = function (scores) {
    finalizarPartida(scores.red > scores.blue ? "red" : "blue", scores.red, scores.blue);
};

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
