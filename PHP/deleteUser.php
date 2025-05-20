<?php
$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Error de conexión: " . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'];

    if (!empty($userId)) {
        $deleteQuery = "DELETE FROM Users WHERE userId = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $userId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Error al eliminar el usuario."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["success" => false, "error" => "ID de usuario no proporcionado."]);
    }
}

$conn->close();
?>