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

// Verificamos conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

session_start();
if (!isset($_SESSION["userId"])) {
    header("Location: ./login/login.php");
    exit();
}

$userId = $_SESSION["userId"];
$rol = $_SESSION["rol"];

if (isset($_POST['userId'])) {
    $userToUnassignId = (int)$_POST['userId'];

    //ENCONTRAMOS EL CAMPO QUE HAY QUE VACIAR
    $sql = "SELECT client1Id, client2Id, client3Id FROM Users WHERE userId = $userId";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $client1Id = $row['client1Id'];
        $client2Id = $row['client2Id'];
        $client3Id = $row['client3Id'];

        // Depuración: Registrar estado inicial
        error_log("Estado inicial para gestor $userToUnassignId: client1Id=$client1Id, client2Id=$client2Id, client3Id=$client3Id");

        // Buscamos el primer campo que contiene el cliente a desasignar
        $clientFields = ['client1Id', 'client2Id', 'client3Id'];
        $fieldToUpdate = NULL;
        foreach ($clientFields as $field) {
            if ($row[$field] == $userToUnassignId) {
                $fieldToUpdate = $field;
                $updateClientSql = "UPDATE Users SET $fieldToUpdate = NULL WHERE userId = $userId";
                if ($conn->query($updateClientSql) === TRUE) {
                    // Depuración: Registrar éxito de la desasignación
                    error_log("Desasignación exitosa para cliente $userToUnassignId en campo $fieldToUpdate.");
                } else {
                    // Depuración: Registrar error de la desasignación
                    error_log("Error al desasignar cliente $userToUnassignId en campo $fieldToUpdate: " . $conn->error);
                }
            }
        }

        //DESASIGNAMOS EL GESTOR DEL CLIENTE
        $managerField = 'managerId';
        $updateManagersql = "UPDATE Users SET managerId = NULL WHERE userId = $userToUnassignId";
        if ($conn->query($updateManagersql) === TRUE) {
            // Depuración: Registrar éxito de la desasignación
            error_log("Desasignación exitosa para cliente $userToUnassignId.");
        } else {
            // Depuración: Registrar error de la desasignación
            error_log("Error al desasignar cliente $userToUnassignId: " . $conn->error);    
        } 
    }
}
?>