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

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}


// Verificar si se recibió un walletId
if (isset($_GET['walletId'])) {
    $walletId = (int)$_GET['walletId'];

    // Consultar el tipo de cartera
    $stmt = $conn->prepare("SELECT balance, gains FROM Wallets WHERE walletId = ?");
    $stmt->bind_param("i", $walletId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc(); // Fetch a single row
        echo json_encode([
            'balance' => $row['balance'],
            'gains' => $row['gains']
        ]);
    } else {
        echo json_encode(['error' => "El cliente no tiene activos en la cartera $walletId"]);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'No se proporcionó un walletId']);
}

// Cerrar la conexión
$conn->close();
?>