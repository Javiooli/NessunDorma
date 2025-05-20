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

// Verificar si se recibió un userId
if (isset($_GET['userId'])) {
    $userId = (int)$_GET['userId'];
    $newCurrency = isset($_GET['newCurrency']) ? $_GET['newCurrency'] : null;

    // Obtener el portfolioId del usuario
    $stmt = $conn->prepare("SELECT portfolioId FROM Portfolios WHERE userId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($portfolioId);
    $stmt->fetch();
    $stmt->close();

    

    $walletTypes = ['EUR' => 'Fiat', 'USD' => 'Fiat', 'BTC' => 'Crypto', 'ETH' => 'Crypto', 'XAU' => 'Gold', 'XAG' => 'Gold'];
    $walletType = $walletTypes[$newCurrency];


    if ($portfolioId) {
        
        $stmt = $conn->prepare("SELECT walletId, walletName FROM Wallets WHERE portfolioId = ? AND walletType = ?");
        $stmt->bind_param("is", $portfolioId, $walletType);
        $stmt->execute();
        $result = $stmt->get_result();

        $wallets = [];
        while ($row = $result->fetch_assoc()) {
            $wallets[] = [
                'walletId' => $row['walletId'],
                'walletName' => $row['walletName']
            ];
        }

        if (empty($wallets)) {
            echo json_encode(['error' => 'No hay carteras']);
        } else {
            echo json_encode(['wallets' => $wallets]);
        }

        $stmt->close();

        
    } else {
        echo json_encode(['error' => 'Portfolio no encontrado']);
    }
} else {
    echo json_encode(['error' => 'No se proporcionó un userId']);
}

// Cerrar la conexión
$conn->close();
?>
