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
if (isset($_POST['userId'])) {
    $userId = intval($_POST['userId']);
    $sql = "SELECT w.walletId, w.walletName FROM Wallets w, Portfolios p WHERE w.portfolioId = p.portfolioId AND p.userId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $html = '';
    while ($row = $result->fetch_assoc()) {
        $html .= '<li data-wallet-id="' . htmlspecialchars($row['walletId']) . '" class="dropdown-item">' . htmlspecialchars($row['walletName']) . '</li>';
    }
    echo $html;
    exit;
}
$conn->close();
?>