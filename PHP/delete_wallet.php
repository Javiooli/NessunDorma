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

if (isset($_POST['walletId'])) {
    $walletId = (int)$_POST['walletId'];
    // Eliminar la wallet
    $sql = "DELETE FROM Wallets WHERE walletId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $walletId);

    if ($stmt->execute()) {
        // Si la eliminación fue exitosa, redirigir a la página de wallets
        header("Location: ../wallets.php");
        exit();
    } else {
        echo "Error al eliminar la wallet: " . $stmt->error;
    }

    $stmt->close();
}

?>