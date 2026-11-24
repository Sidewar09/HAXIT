<?php
require "sesion.php";
require "conexion.php";
$pagina_actual = "ranking";

$region = isset($_GET["region"]) ? $_GET["region"] : "NA";
$orden  = isset($_GET["orden"])  ? $_GET["orden"]  : "desc";

$lista_regiones = [];
$res = $conexion->query("SELECT DISTINCT region FROM usuarios ORDER BY region");
while ($fila = $res->fetch_assoc()) {
    $lista_regiones[] = $fila["region"];
}

$sql = "SELECT username, nickname, region, elo FROM usuarios";

if ($region != "NA") {
    $sql .= " WHERE region = ?";
}

if ($orden == "desc") {
    $sql .= " ORDER BY elo DESC";
} elseif ($orden == "asc") {
    $sql .= " ORDER BY elo ASC";
} elseif ($region != "NA") {
    $sql .= " ORDER BY username ASC";                 // ELO en N/A + una región
} else {
    $sql .= " ORDER BY region ASC, username ASC";     // ambos en N/A
}

$consulta = $conexion->prepare($sql);
if ($region != "NA") {
    $consulta->bind_param("s", $region);
}
$consulta->execute();
$resultado = $consulta->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAXIT - Ranking</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include "menu_izq.php"; ?>

        <main class="contenido">
            <h1>Ranking</h1>

            <form method="GET" action="ranking.php" class="filtros">
                <label for="region">Región:</label>
                <select id="region" name="region">
                    <option value="NA">N/A</option>
                    <?php foreach ($lista_regiones as $r): ?>
                        <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $region == $r ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($r); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="orden">ELO:</label>
                <select id="orden" name="orden">
                    <option value="desc" <?php echo $orden == "desc" ? "selected" : ""; ?>>Mayor a menor</option>
                    <option value="asc"  <?php echo $orden == "asc"  ? "selected" : ""; ?>>Menor a mayor</option>
                    <option value="NA"   <?php echo $orden == "NA"   ? "selected" : ""; ?>>N/A</option>
                </select>

                <button type="submit">Filtrar</button>
            </form>

            <table class="ranking">
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Nickname</th>
                    <th>Región</th>
                    <th>ELO</th>
                </tr>
                <?php $pos = 1; ?>
                <?php while ($fila = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $pos++; ?></td>
                        <td><?php echo htmlspecialchars($fila["username"]); ?></td>
                        <td><?php echo htmlspecialchars($fila["nickname"]); ?></td>
                        <td><?php echo htmlspecialchars($fila["region"]); ?></td>
                        <td><?php echo $fila["elo"]; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </main>

        <?php include "menu_der.php"; ?>
    </div>
</body>
</html>
