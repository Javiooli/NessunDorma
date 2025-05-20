<?php
include './convert_currency.php';
include './PHP/convert_currency.php';

// update_balances.php
// Verificar conexión


function updateBalance($userId, $defaultCurrency) {
    global $conn;
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
    }

    // Obtener el portfolioId del usuario
    $stmt = $conn->prepare("SELECT portfolioId FROM Portfolios WHERE userId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($portfolioId);
    $stmt->fetch();
    $stmt->close();

    // Fetch all wallet IDs for the user
    $stmt = $conn->prepare("SELECT walletId FROM Wallets WHERE portfolioId = ?");
    $stmt->bind_param("i", $portfolioId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userWalletsData = $result->fetch_all(MYSQLI_ASSOC);

    // Extract wallet IDs into $userWallets
    $userWallets = [];
    foreach ($userWalletsData as $wallet) {
        if (isset($wallet['walletId'])) {
            $userWallets[] = $wallet['walletId'];
        }
    }


    // Calculate total value of all wallets in the default currency
    $totalValue = 0;
    foreach ($userWallets as $walletId) {
        // Select all entries for the walletId from activos_hist
        $stmt = $conn->prepare("SELECT walletId, currency, date, SUM(amount) as totalAmount FROM activos_hist WHERE walletId = ? GROUP BY walletId, currency, date ORDER BY date ASC");
        $stmt->bind_param("i", $walletId);
        $stmt->execute();
        $result = $stmt->get_result();
        $entries = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Calculate the total amount invested in the default currency
        $totalInvested = 0;
        foreach ($entries as $entry) {
            if ($entry['currency'] === $defaultCurrency) {
                $totalInvested += (double)$entry['totalAmount'];
            } else {
                $conversionResult = convertCurrency($entry['currency'], $entry['date'], $entry['totalAmount'], $defaultCurrency);
                $convertedAmount = (double)$conversionResult['convertedAmount'];
                $totalInvested += $convertedAmount;
            }
        }

        $currentBalance = 0;
        $stmt = $conn->prepare("SELECT amount, currency FROM activos WHERE walletId = ?");
        $stmt->bind_param("i", $walletId);
        $stmt->execute();
        $result = $stmt->get_result();
        $entries = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($entries as $entry) {
            if ($entry['currency'] === $defaultCurrency) {
                $currentBalance += (double)$entry['amount'];
            } else {
                $conversionResult = convertCurrency($entry['currency'], date('Y-m-d'), $entry['amount'], $defaultCurrency);
                $convertedAmount = (double)$conversionResult['convertedAmount'];
                $currentBalance += $convertedAmount;
            }
        }

        $gains = $currentBalance - $totalInvested;

        // Update the realBalance and balance in the Wallets table for the current walletId
        $stmt = $conn->prepare("UPDATE Wallets SET balance = ?, gains = ? WHERE walletId = ?");
        $stmt->bind_param("ddi", $currentBalance, $gains, $walletId);
        $stmt->execute();
        $stmt->close();
    }

}
?>