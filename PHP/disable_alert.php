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
    die("Error de conexión: " . $conn->connect_error);
}

if (isset($_POST['userId']) && isset($_POST['currency'])) {
    $userId = (int)$_POST['userId'];
    $currency = trim($_POST['currency']);
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM user_alerts WHERE userId = ? AND currency = ?");
    $checkStmt->bind_param("is", $userId, $currency);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($count > 0) {
        $updateStmt = $conn->prepare("UPDATE user_alerts SET active = 0, pending = 0 WHERE userId = ? AND currency = ?");
        $updateStmt->bind_param("is", $userId, $currency);
        $updateStmt->execute();
        $updateStmt->close();
        exit;
    } else {
        $stmt = $conn->prepare("INSERT INTO user_alerts (userId, currency, active, pending) VALUES (?, ?, 0, 0)");
        $stmt->bind_param("is", $userId, $currency);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "Alerta activada para $currency";
}

$conn->close();
?>