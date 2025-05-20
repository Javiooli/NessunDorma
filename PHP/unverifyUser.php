<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Conexión a la base de datos
$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";
$conn = new mysqli($host, $user, $password, $dbname);

//VERIFICAMOS CONEXIÓN
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

//RECIBIMOS DATOS DE USUARIO Y HACEMOS UPDATE PARA DESVERIFICACION
if (isset($_POST['userId'])) {
    $userId = (int)$_POST['userId'];

    $sql = "UPDATE Users SET verified = 0 WHERE userId = $userId";
    if ($conn->query($sql) === TRUE) {
        echo "Usuario verificado correctamente.";
    } else {
        echo "Error al verificar el usuario: " . $conn->error;
    }
}
$conn->close();
?>