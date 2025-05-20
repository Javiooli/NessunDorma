<?php
#CERRAMOS LA SESIÓN
session_start();
session_unset();
session_destroy();

header("Location: ./../index.html");
exit();
?>