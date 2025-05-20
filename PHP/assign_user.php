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
    $userToAssignId = (int)$_POST['userId'];

    // Verificamos si client1Id, client2Id, client3Id están libres
    $verifySql = "SELECT client1Id, client2Id, client3Id FROM Users WHERE userId = $userId";
    $verifyResult = $conn->query($verifySql);
    if ($verifyResult->num_rows > 0) {
        $row = $verifyResult->fetch_assoc();
        $client1Id = $row['client1Id'];
        $client2Id = $row['client2Id'];
        $client3Id = $row['client3Id'];

        // Depuración: Registrar estado inicial
        error_log("Estado inicial para gestor $userId: client1Id=$client1Id, client2Id=$client2Id, client3Id=$client3Id");

        // Comprobamos si el cliente ya está asignado
        if ($client1Id == $userToAssignId || $client2Id == $userToAssignId || $client3Id == $userToAssignId) {
            echo "El cliente ya está asignado a este gestor.";
            $conn->close();
            exit();
        }

        // Buscamos el primer campo libre
        $clientFields = ['client1Id', 'client2Id', 'client3Id'];
        $fieldToUpdate = null;
        foreach ($clientFields as $field) {
            if ($row[$field] == null) {
                $fieldToUpdate = $field;
                break;
            }
        }

        if ($fieldToUpdate) {
            $conn->begin_transaction();
            try {
                // Depuración: Registrar qué campo se va a actualizar
                error_log("Asignando cliente $userToAssignId al campo $fieldToUpdate para gestor $userId");

                $updateSql = "UPDATE Users SET $fieldToUpdate = $userToAssignId WHERE userId = $userId";
                $updateManagerSql = "UPDATE Users SET managerId = $userId WHERE userId = $userToAssignId";

                // Depuración: Registrar consultas
                error_log("Consulta gestor: $updateSql");
                error_log("Consulta cliente: $updateManagerSql");

                if ($conn->query($updateSql) === TRUE && $conn->query($updateManagerSql) === TRUE) {
                    $conn->commit();
                    echo "Usuario y Gestor asignados correctamente.";
                } else {
                    throw new Exception("Error en la asignación.");
                }
            } catch (Exception $e) {
                $conn->rollback();
                echo "Error al asignar usuario y gestor: " . $conn->error;
                error_log("Error en asignación: " . $conn->error);
            }
        } else {
            echo "No se puede asignar más usuarios, ya están ocupados.";
        }
    } else {
        echo "No se encontró el usuario.";
    }
} else {
    echo "No se ha recibido el ID del usuario.";
}

$conn->close();
?>