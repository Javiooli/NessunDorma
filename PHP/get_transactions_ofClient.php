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
    $sql = "SELECT * FROM Transactions t, Wallets w, Portfolios p WHERE t.walletId = w.walletId AND w.portfolioId = p.portfolioId AND p.userId = $userId";
    $transactionResult = $conn->query($sql);

    $html = '';
    while ($row = $transactionResult->fetch_assoc()) {
        // Determinar el tipo de transacción para mostrar
        if ($row["transactionType"] == "Transfer") {
            $transactionType = $row["transactionTo"] ? "Transfer Out" : "Transfer In";
        } else if ($row["transactionType"] == "Trade") {
            $transactionType = $row["transactionTo"] ? "Trade Out" : "Trade In";
        } else {
            $transactionType = $row["transactionType"];
        }

        $html .= "<tr class='transaction' data-id='{$row['transactionId']}'>";
        $html .= "<td>" . htmlspecialchars($row["walletName"]) . "</td>";
        $html .= "<td>" . htmlspecialchars($transactionType) . "</td>";
        $html .= "<td>" . date('Y-m-d', strtotime($row["transactionDate"])) . "</td>";
        $html .= "<td>" . (($row["transactionCurrency"] == "EUR" || $row["transactionCurrency"] == "USD") ? number_format($row["transactionAmount"], 2) : rtrim(rtrim($row["transactionAmount"], "0"), ".")) . "</td>";
        $html .= "<td>" . htmlspecialchars($row["transactionCurrency"]) . "</td>";
        $html .= "<td>" . htmlspecialchars($row["transactionStatus"]) . "</td>";
        $html .= "<td>" . htmlspecialchars($row["transactionDescription"]) . "</td>";
        $html .= "<td class='actions'>
                    <button class='delete-transaction-btn' id='delete-btn'>
                        Eliminar
                    </button> 
                    <button class='edit-transaction-btn' id='edit-btn'>
                        Editar
                    </button>
                </td>";
        $html .= "</tr>";
    }
    echo $html;
    exit;
}
$conn->close();
?>