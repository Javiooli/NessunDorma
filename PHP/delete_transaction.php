<?php
    include './update_balance.php';
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    // Database connection
    $host = "localhost";
    $dbname = "Nessun_DormaDB";
    $user = "root";
    $password = "";
    $conn = new mysqli($host, $user, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    }
    
    //Get the transaction ID from the POST request
    if (!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid transaction ID']);
        $conn->close();
        exit();
    }
    $transactionID = (int)$_POST['id'];

    //Get info from the transaction so we can revert changes in the activos table
    $sql = "SELECT * FROM Transactions WHERE transactionId = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Failed to prepare statement']);
        $conn->close();
        exit();
    }
    $stmt->bind_param("i", $transactionID);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();
    if (!$transaction) {
        echo json_encode(['success' => false, 'error' => 'Transaccion no encontrada']);
        $conn->close();
        exit();
    }

    // Get the transaction details
    $walletId = $transaction['walletId'];
    $type = $transaction['transactionType'];
    $date = $transaction['transactionDate'];
    $currency = $transaction['transactionCurrency'];
    $newCurrency = $transaction['newCurrency'];
    $transactionFrom = $transaction['transactionFrom'];
    $transactionTo = $transaction['transactionTo'];
    $transactionFee = $transaction['transactionFee'];
    $amount = $transaction['transactionAmount'];
    $fee = $transaction['transactionFee']; // Corrected to use the correct field name

    
    // Update activos table based on the transaction type
    switch ($type) {
        case 'Deposit':
            
            // Check that wallet doesn't end up with negative balance
            $sql = "SELECT amount FROM activos WHERE walletId = ? AND currency = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $walletId, $currency);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if ($row['amount'] - $amount < 0) {
                echo json_encode(['success' => false, 'error' => "La cartera de $currency terminaría con balance negativo."]);
                $conn->close();
                exit();
            }

            // Subtract the amount from activos table
            $sql = "UPDATE activos SET amount = GREATEST(amount - ?, 0), lastTransactionId = 0 WHERE walletId = ? AND currency = ?"; // Prevent negative amounts
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dis", $amount, $walletId, $currency);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
                $stmt->close();
                $conn->close();
                exit();
            }
            $stmt->close();
            break;
        case 'Withdrawal':
            // Add the amount back to activos table
            $sql = "UPDATE activos SET amount = amount + ?, lastTransactionId = 0 WHERE walletId = ? AND currency = ?"; // Ensure this logic is consistent
            $stmt = $conn->prepare($sql);
            $amount = abs($amount);
            $stmt->bind_param("dis", $amount, $walletId, $currency);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
                $stmt->close();
                $conn->close();
                exit();
            }
            $stmt->close();
            break;
        case 'Trade':
            $tradeType = $transactionFrom ? 'tradeIn' : 'tradeOut';

            // Parse correct walletIds
            $contraryWalletId = $tradeType === 'tradeIn' ? $transactionFrom : $transactionTo;
            $destinationWalletId = $tradeType === 'tradeIn' ? $walletId : $transactionTo;
            $originWalletId = $tradeType === 'tradeIn' ? $transactionFrom : $walletId;

            // Parse correct contraryTransactionId
            $contraryTransactionId = $tradeType === 'tradeIn' ? (int)$transactionID - 1 : (int)$transactionID + 1;
            
            // Validate contraryTransactionId
            $sql = "SELECT COUNT(*) as count FROM Transactions WHERE transactionId = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $contraryTransactionId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if ($row['count'] != 0) {
                $sql = "SELECT transactionCurrency, transactionAmount FROM Transactions WHERE transactionId = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $contraryTransactionId);
                $stmt->execute();
                $result = $stmt->get_result();
                $contraryTransaction = $result->fetch_assoc();
                $stmt->close();



                if (!$contraryTransaction) {
                    echo json_encode(['success' => false, 'error' => 'Contrary transaction not found']);
                    $conn->close();
                    exit();
                }
                
                $contraryCurrency = $contraryTransaction['transactionCurrency'];
                $contraryAmount = $contraryTransaction['transactionAmount'];

                $sql = "SELECT amount FROM activos WHERE walletId = ? AND currency = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $contraryWalletId, $contraryCurrency);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                if ($row['amount'] - $contraryAmount < 0) {
                    echo json_encode(['success' => false, 'error' => "La cartera de $contraryCurrency terminaría con balance negativo."]);
                    $conn->close();
                    exit();
                }
            }

            

            

            

            
            // Check that neither wallet ends up with negative balance
            $sql = "SELECT amount FROM activos WHERE walletId = ? AND currency = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $walletId, $currency);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if ($row['amount'] - $amount < 0) {
                echo json_encode(['success' => false, 'error' => "La cartera de $currency terminaría con balance negativo."]);
                $conn->close();
                exit();
            }

            

            // Update this wallet's activo
            $sql = "UPDATE activos SET amount = amount - ?, lastTransactionId = 0 WHERE walletId = ? AND currency = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dis", $amount, $walletId, $currency);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
                $stmt->close();
                $conn->close();
                exit();
            }

            $stmt->close();

            if (isset($contraryTransactionId)) {
                // Update contrary wallet's activo
                $sql = "UPDATE activos SET amount = amount - ?, lastTransactionId = 0 WHERE walletId = ? AND currency = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("dis", $contraryAmount, $contraryWalletId, $contraryCurrency);
                if (!$stmt->execute()) {
                    echo json_encode(['success' => false, 'error' => $stmt->error]);
                    $stmt->close();
                    $conn->close();
                    exit();
                }
                $stmt->close();

                // Remove second transaction
                $sql = "DELETE FROM Transactions WHERE transactionId = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $contraryTransactionId);
                if (!$stmt->execute()) {
                    echo json_encode(['success' => false, 'error' => $stmt->error]);
                    $stmt->close();
                    $conn->close();
                    exit();
                }
            }
            $stmt->close();


            break;
        case 'Transfer':
            // Check if the transaction is a transferIn or transferOut
            if ($transactionFrom)  {$transferType = 'transferIn';}
            else {$transferType = 'transferOut';}

            // Parse correct walletIds
            $destinationWalletId = $transactionFrom ? $walletId : $transactionTo;
            $originWalletId = $transactionFrom ? $transactionFrom : $walletId;

            // Parse correct currencies
            if ($transactionFrom == 'transferIn') {
                $contraryTransactionId = (int)$transactionID + 1; // Ensure contraryTransactionId exists in the database

            } else {
                $contraryTransactionId = (int)$transactionID - 1; // Ensure contraryTransactionId exists in the database

            }

            // Update destination wallet's activo
            $sql = "UPDATE activos SET amount = amount - ?, lastTransactionId = 0 WHERE walletId = ? AND currency = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dis", abs($amount), $destinationWalletId, $currency);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
                $stmt->close();
                $conn->close();
                exit();
            }

            // Update origin wallet's activo
            $sql = "UPDATE activos SET amount = amount + ?, lastTransactionId = 0 WHERE walletId = ? AND currency = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dis", abs($amount), $originWalletId, $currency);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
                $stmt->close();
                $conn->close();
                exit();
            }
            $stmt->close();

            // Remove second transaction
            $sql = "DELETE FROM Transactions WHERE transactionId = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $contraryTransactionId);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
                $stmt->close();
                $conn->close();
                exit();
            }
                
            $stmt->close();
            break;

    }

    
    // Prepare and execute the SQL query to delete the transaction
    $sql = "DELETE FROM Transactions WHERE transactionId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $transactionID);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => "Error al borrar la transacción."]);
    }

    $sql = "SELECT userId, default_currency FROM Users WHERE userId IN (SELECT userId FROM Portfolios WHERE portfolioId IN (SELECT portfolioId FROM Wallets WHERE walletId = ?))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $walletId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();

    if (!$userData) {
        echo json_encode(['success' => false, 'error' => 'User data not found']);
        $conn->close();
        exit();
    }

    $userId = $userData['userId'];
    $defaultCurrency = $userData['default_currency'];
    updateBalance($userId, $defaultCurrency);

    echo json_encode(['success' => true]);
    $conn->close();
    exit();

?>