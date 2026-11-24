<?php
session_start();
session_unset();    // borra las variables de la sesión
session_destroy();  // destruye la sesión

header("Location: login.html");
exit;
