<nav class="menu menu-der">
    <a href="perfil.php" title="Profile" class="<?php echo $pagina_actual == 'perfil' ? 'activo' : ''; ?>">
        <img src="img/perfil.svg" width="32" height="32" alt="Profile">
    </a>
    <a href="settings.php" title="Settings" class="<?php echo $pagina_actual == 'settings' ? 'activo' : ''; ?>">
        <img src="img/ajustes.svg" width="32" height="32" alt="Settings">
    </a>
    <a href="logout.php" class="salir">Cerrar sesión</a>
</nav>
