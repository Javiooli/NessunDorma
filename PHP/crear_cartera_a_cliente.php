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

if (
    isset($_POST['userId'], $_POST['walletName'], $_POST['walletAddress'], $_POST['walletType']) &&
    !empty($_POST['walletName']) && !empty($_POST['walletType'])
) {
    $userId = intval($_POST['userId']);
    $walletName = $_POST['walletName'];
    $walletDirecion = $_POST['walletAddress'];
    $walletType = $_POST['walletType'];

    // Obtener portfolioId del cliente
    $portfolioSql = "SELECT portfolioId FROM Portfolios WHERE userId = ?";
    $stmt = $conn->prepare($portfolioSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $portfolioId = $row['portfolioId'];
        $stmt->close();

        // Insertar nuevo wallet
        $insertWalletSql = "INSERT INTO Wallets (portfolioId, walletAddress, walletName, walletType, balance, gains) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertWalletSql);
        $balance = 0;
        $gains = 0;
        $stmt->bind_param("isssdd", $portfolioId, $walletDirecion, $walletName, $walletType, $balance, $gains);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Cartera creada correctamente";
        } else {
            echo "Error al crear la cartera";
        }
        $stmt->close();
    } else {
        echo "No se encontró el portfolio del usuario";
    }
} else {
    echo "Faltan datos para crear la cartera";
}

$conn->close();
?>