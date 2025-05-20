<?php
if (file_exists('./PHP/append_to_audit_log.php')) {
    include './PHP/append_to_audit_log.php';
} else {
    die("Error: Required file 'append_to_audit_log.php' not found.");
}

if (file_exists('./PHP/update_balance.php')) {
    include './PHP/update_balance.php';
} else {
    die("Error: Required file 'update_balance.php' not found.");
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

session_start();
if (!isset($_SESSION["userId"])) {
    header("Location: ./login/login.php");
    exit();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//RECOGEMOS DATOS DE USUARIO
$userId = $_SESSION["userId"];
$firstName = $_SESSION["firstName"];
$rol = $_SESSION["rol"];
$defaultCurrency = $_SESSION["currency"];
$clientIP = $_SESSION["clientIP"];

//ENVIAR DATOS DEL FORMULARIO
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["wallet"])) {
    //RECOGEMOS DATOS DEL FORMULARIO
    $walletId = $_POST["wallet"];
    $type = $_POST["type"];
    $amount = $_POST["amount"];
    $currency = $_POST["currency"];
    $newCurrency = $_POST["new_currency"] ? $_POST["new_currency"] : $currency; // Para Trade
    $destination = $_POST["destination"] ? $_POST["destination"] : $walletId; // Para Transfer
    $status = $_POST["status"];
    $description = $_POST["description"];
    $fecha = $_POST["date"] ? $_POST["date"] : date('Y-m-d'); // Si no se proporciona fecha, se usa la fecha actual
    $hora = isset($_POST["time"]) && !empty($_POST["time"]) ? $_POST["time"] : date('H:i:s'); // Si no se proporciona hora, se usa la hora actual
    $fecha .= " " . $hora; // Combinar fecha y hora en un solo valor
    


    if ($type == 'Withdrawal' || $type == 'Trade') {
        // Check if an entry exists for this currency and wallet in the "activos" table
        $checkSQL = "SELECT amount FROM activos WHERE walletId = ? AND currency = ?";
        $checkStmt = $conn->prepare($checkSQL);
        $checkStmt->bind_param("is", $walletId, $currency);
        $checkStmt->execute();
        $checkStmt->store_result();

        if (!$checkStmt->num_rows > 0) {
            // Entry does not exist, cannot withdraw
            echo "<script>alert('Error: No hay suficientes $currency.');window.location.href='movimientos.php';</script>";
            $checkStmt->close();
            exit();
        } else {
            // Entry exists, update the balance
            $checkStmt->bind_result($currentBalance);
            $checkStmt->fetch();
            $newBalance = $currentBalance - abs($amount);

            if ($newBalance < 0) {
                // Prevent overdraft
                echo "<script>alert('Error: Fondos insuficientes.');window.location.href='movimientos.php';</script>";
                $checkStmt->close();
                exit();
            }
        }
        $checkStmt->close();
    }

    // INSERT SQL
    $insertAmount = ($type == "Withdrawal" || $type == "Trade") ? $amount - ($amount * 2) : $amount;
    $insertSQL = "INSERT INTO Transactions (walletId, transactionType, transactionAmount, transactionCurrency, newCurrency, transactionStatus, transactionDescription, transactionDate, transactionTo)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    //PROTECCION ANTI SQL INJECTION
    $stmt = $conn->prepare($insertSQL);
    $fecha = date('Y-m-d H:i:s', strtotime($fecha . ' +1 second'));
    $stmt->bind_param("isdssssss", $walletId, $type, $insertAmount, $currency, $newCurrency, $status, $description, $fecha, $destination);

    if ($stmt->execute()) {
        // Retrieve the ID of the newly inserted transaction
        $lastTransactionId = $conn->insert_id;
    } else {
        echo "<script>alert('Error: No se pudo insertar la transacción.');window.location.href='movimientos.php';</script>";
        exit();
    }

    // Now $lastTransactionId can be used in subsequent queries
    switch($type) {
        case 'Deposit':
            // Check if an entry exists for this currency and wallet in the "activos" table
            $checkSQL = "SELECT amount FROM activos WHERE walletId = ? AND currency = ?";
            $checkStmt = $conn->prepare($checkSQL);
            $checkStmt->bind_param("is", $walletId, $currency);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                // Entry exists, update the balance
                $checkStmt->bind_result($currentBalance);
                $checkStmt->fetch();
                $newBalance = $currentBalance + $amount;

                $updateSQL = "UPDATE activos SET amount = ?, lastTransactionId = ? WHERE walletId = ? AND currency = ?";
                $updateStmt = $conn->prepare($updateSQL);
                $updateStmt->bind_param("diis", $newBalance, $lastTransactionId, $walletId, $currency);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Entry does not exist, create a new one
                $insertActivosSQL = "INSERT INTO activos (walletId, currency, amount, lastTransactionId) VALUES (?, ?, ?, ?)";
                $insertActivosStmt = $conn->prepare($insertActivosSQL);
                $insertActivosStmt->bind_param("isdi", $walletId, $currency, $amount, $lastTransactionId);
                $insertActivosStmt->execute();
                $insertActivosStmt->close();
            }

            $checkStmt->close();

            $msg = "$rol $firstName con ID $userId ha depositado $amount $currency en la cartera con ID $walletId.";
            $checkSQL = "SELECT max_amount FROM Users WHERE userId = ?";
            $checkStmt = $conn->prepare($checkSQL);
            $checkStmt->bind_param("i", $userId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $checkStmt->bind_result($maxAmount);
                $checkStmt->fetch();
                if ($maxAmount < $amount) {
                    $action = "LimitExceeded";
                } else {
                    $action = "RegularTransaction";
                }
                $checkStmt->close();
            } else {
                echo "<script>alert('Error: Usuario no tiene limite de transacción.')window.location.href='movimientos.php';</script>";
                $checkStmt->close();
                exit();
            }

            break;
        case 'Withdrawal':
            
            $updateSQL = "UPDATE activos SET amount = ?, lastTransactionId = ? WHERE walletId = ? AND currency = ?";
            $updateStmt = $conn->prepare($updateSQL);
            $updateStmt->bind_param("diis", $newBalance, $lastTransactionId, $walletId, $currency);
            $updateStmt->execute();
            $updateStmt->close();

            $msg = "$rol $firstName con ID $userId ha retirado $amount $currency de la cartera con ID $walletId.";
            $checkSQL = "SELECT max_amount FROM Users WHERE userId = ?";
            $checkStmt = $conn->prepare($checkSQL);
            $checkStmt->bind_param("i", $userId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $checkStmt->bind_result($maxAmount);
                $checkStmt->fetch();
                if ($maxAmount < $amount) {
                    $action = "LimitExceeded";
                } else {
                    $action = "RegularTransaction";
                }
                $checkStmt->close();
            } else {
                echo "<script>alert('Error: Usuario no tiene limite de transacción.');window.location.href='movimientos.php';</script>";
                $checkStmt->close();
                exit();
            }

            break;
        case 'Trade':
            // Check if an entry exists for this currency and wallet in the "activos" table
            $checkSQL = "SELECT amount FROM activos WHERE walletId = ? AND currency = ?";
            $checkStmt = $conn->prepare($checkSQL);
            $checkStmt->bind_param("is", $walletId, $currency);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                // Entry exists, update the balance
                $checkStmt->bind_result($currentBalance);
                $checkStmt->fetch();
                $newBalance = $currentBalance - $amount;

                if ($newBalance < 0) {
                    // Prevent overdraft
                    echo "<script>alert('Error: No tienes suficiente $currency en la cartera seleccionada.');window.location.href='movimientos.php';</script>";
                    $checkStmt->close();
                    exit();
                }

                $updateSQL = "UPDATE activos SET amount = ?, lastTransactionId = ? WHERE walletId = ? AND currency = ?";
                $updateStmt = $conn->prepare($updateSQL);
                $updateStmt->bind_param("diis", $newBalance, $lastTransactionId, $walletId, $currency);
                $updateStmt->execute();
                $updateStmt->close();

                // Check if an entry exists for the new currency in the "activos" table
                $checkSQL = "SELECT amount FROM activos WHERE walletId = ? AND currency = ?";
                $checkStmt = $conn->prepare($checkSQL);
                $checkStmt->bind_param("is", $destination, $newCurrency);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows <= 0) {
                    $createAmountEntrySQL = "INSERT INTO activos (walletId, currency, amount, lastTransactionId) VALUES (?, ?, ?, ?)";
                    $createAmountEntryStmt = $conn->prepare($createAmountEntrySQL);
                    $defaultAmount = 0;
                    $createAmountEntryStmt->bind_param("isdi", $destination, $newCurrency, $defaultAmount, $lastTransactionId);
                    $createAmountEntryStmt->execute();
                    $createAmountEntryStmt->close();
                    $newBalance = 0;
                } else {
                    $checkStmt->bind_result($newBalance);
                    $checkStmt->fetch();                
                }

                $amountIn = $amount; // Parece raro pero es porque ibamos a implementar comisiones, antes era $amount - $fee.

                // Criba entre 3 Casos: defaultCurrency -> cualquiera | cualquiera -> defaultCurrency | cualquiera -> cualquiera.

                if ($currency == $defaultCurrency)           {$tradeCase = "defaultOther";}
                else if ($newCurrency == $defaultCurrency)   {$tradeCase = "otherDefault";}
                else                                        {$tradeCase = "otherOther";}

                switch ($tradeCase) {
                    case "defaultOther":
                        $column = strtoupper($defaultCurrency) . "XVAL";
                        $table = strtolower($defaultCurrency . "_values_hist");
                        $query = $conn->prepare("SELECT $column FROM $table WHERE currency = ? AND date_reg = ?");
                        $formattedFecha = date('Y-m-d', strtotime($fecha));
                        //echo("SELECT $column FROM $table WHERE currency = $currency AND date_reg = $formattedFecha");
                        $query->bind_param("ss", $newCurrency, $formattedFecha);
                        if (!$query->execute()) {
                            die("Error recibiendo el valor de $newCurrency: " . $conn->error);
                        }

                        $result = $query->get_result();
                        $row = $result->fetch_assoc();
                        $change = (double)$row["$column"];
                        $amountIn = round($amountIn / $change, 10);

                        $insertSQL = "INSERT INTO Transactions (walletId, transactionType, transactionAmount, transactionCurrency, newCurrency, transactionStatus, transactionDescription, transactionDate, transactionFrom)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        break;
                    case "otherDefault":
                        $column = $defaultCurrency . "XVAL";
                        $table = strtolower($defaultCurrency . "_values_hist");

                        $query = $conn->prepare("SELECT $column FROM $table WHERE currency = ? AND date_reg = ?");


                        $formattedFecha = date('Y-m-d', strtotime($fecha));
                        //echo("SELECT $column FROM $table WHERE currency = $currency AND date_reg = $formattedFecha");

                        $query->bind_param("ss", $currency, $formattedFecha);
                        if (!$query->execute()) {
                            die("Error recibiendo el valor de $currency: " . $conn->error);
                        }

                        $result = $query->get_result();
                        $row = $result->fetch_assoc();
                        if ($row === null) {
                            echo "<script>alert('Error: No se encontró un valor para la moneda $currency en la tabla $table.');window.location.href='movimientos.php';</script>";
                            exit();
                        }
                        $change = (double)$row["$column"];
                        $amountIn = round($amountIn * $change, 10);

                        $insertSQL = "INSERT INTO Transactions (walletId, transactionType, transactionAmount, transactionCurrency, newCurrency, transactionStatus, transactionDescription, transactionDate, transactionFrom)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        break;
                    case "otherOther";
                        $column = $defaultCurrency . "XVAL";
                        $table = strtolower($defaultCurrency . "_values_hist");

                        $query = $conn->prepare("SELECT $column FROM $table WHERE currency = ? AND date_reg = ?");

                        $formattedFecha = date('Y-m-d', strtotime($fecha));
                        //echo("SELECT $column FROM $table WHERE currency = $currency AND date_reg = $formattedFecha");

                        $query->bind_param("ss", $currency, $formattedFecha);
                        if (!$query->execute()) {
                            die("Error recibiendo el valor de $currency: " . $conn->error);
                        }

                        $result = $query->get_result();
                        $row = $result->fetch_assoc();
                        if ($row === null) {
                            echo "<script>alert('Error: No se encontró un valor para la moneda $currency en la tabla $table.');window.location.href='movimientos.php';</script>";
                            exit();
                        }
                        $change = (double)$row["$column"];

                        $query = $conn->prepare("SELECT $column FROM $table WHERE currency = ? AND date_reg = ?");

                        $query->bind_param("ss", $newCurrency, $fecha);
                        if (!$query->execute()) {
                            die("Error recibiendo el valor de $newCurrency: " . $conn->error);
                        }

                        $result = $query->get_result();
                        $row = $result->fetch_assoc();
                        if ($row === null) {
                            echo "<script>alert('Error: No se encontró un valor para la moneda $newCurrency en la tabla $table.');window.location.href='movimientos.php';</script>";
                            exit();
                        }
                        $secondChange = (double)$row["$column"];

                        //die("$amountIn, $change");
                        $amountIn = round($amountIn * $change / $secondChange, 10);

                        $insertSQL = "INSERT INTO Transactions (walletId, transactionType, transactionAmount, transactionCurrency, newCurrency, transactionStatus, transactionDescription, transactionDate, transactionFrom)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        break;
                }

                //PROTECCION ANTI SQL INJECTION
                $stmt = $conn->prepare($insertSQL);
                $stmt->bind_param("isdssssss", $destination, $type, $amountIn, $newCurrency, $newCurrency, $status, $description, $fecha, $walletId);
                if ($stmt->execute()) {
                    $lastTransactionId = $conn->insert_id;
                }

                $updateSQL = "UPDATE activos SET amount = ? + amount, lastTransactionId = ? WHERE walletId = ? AND currency = ?";
                $updateStmt = $conn->prepare($updateSQL);
                $updateStmt->bind_param("diis", $amountIn, $lastTransactionId, $destination, $newCurrency);
                //die("amountIn: $amountIn, destination: $destination, newCurrency: $newCurrency.");
                if (!$updateStmt->execute()) {
                    echo "<script>alert('Error: Ha ocurrido algún problema en la actualización del balance de $newCurrency.');window.location.href='movimientos.php';</script>";
                    $checkStmt->close();
                    exit();
                }

            
                $checkStmt->close();

                

                $amount = $amount - ($amount * 2);

                $msg = "$rol $firstName con ID $userId ha swapeado $amount $currency desde la cartera con ID $walletId.";
                $checkSQL = "SELECT max_amount FROM Users WHERE userId = ?";
                $checkStmt = $conn->prepare($checkSQL);
                $checkStmt->bind_param("i", $userId);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    $checkStmt->bind_result($maxAmount);
                    $checkStmt->fetch();
                    if ($maxAmount < $amount) {
                        $action = "LimitExceeded";
                    } else {
                        $action = "RegularTransaction";
                    }
                    $checkStmt->close();
                } else {
                    echo "<script>alert('Error: Usuario no tiene limite de transacción.');window.location.href='movimientos.php';</script>";
                    $checkStmt->close();
                    exit();
                }



            } else {
                // Entry does not exist, cannot trade
                echo "<script>alert('Error: No tienes suficiente $currency en la cartera seleccionada.');window.location.href='movimientos.php';</script>";
                $checkStmt->close();
                exit();
            }
            break;
        case 'Transfer':
            // Check if an entry exists for the source wallet in the "activos" table
            $checkSourceSQL = "SELECT amount FROM activos WHERE walletId = ? AND currency = ?";
            $checkSourceStmt = $conn->prepare($checkSourceSQL);
            $checkSourceStmt->bind_param("is", $walletId, $currency);
            $checkSourceStmt->execute();
            $checkSourceStmt->store_result();

            if ($checkSourceStmt->num_rows > 0) {
                // Entry exists, update the balance
                $checkSourceStmt->bind_result($currentSourceBalance);
                $checkSourceStmt->fetch();
                $newSourceBalance = $currentSourceBalance - $amount;

                if ($newSourceBalance < 0) {
                    // Prevent overdraft
                    echo "<script>alert('Error: Fondos insuficientes en la cartera de origen.');window.location.href='movimientos.php';</script>";
                    $checkSourceStmt->close();
                    exit();
                }

                $updateSourceSQL = "UPDATE activos SET amount = ?, lastTransactionId = ? WHERE walletId = ? AND currency = ?";
                $updateSourceStmt = $conn->prepare($updateSourceSQL);
                $updateSourceStmt->bind_param("diis", $newSourceBalance, $lastTransactionId, $walletId, $currency);
                $updateSourceStmt->execute();
                $updateSourceStmt->close();
            } else {
                // Entry does not exist, cannot transfer
                echo "<script>alert('Error: No existen fondos en la cartera de origen.');window.location.href='movimientos.php';</script>";
                $checkSourceStmt->close();
                exit();
            }
            $checkSourceStmt->close();

            // Check if an entry exists for the destination wallet in the "activos" table
            $checkDestinationSQL = "SELECT amount FROM activos WHERE walletId = ? AND currency = ?";
            $checkDestinationStmt = $conn->prepare($checkDestinationSQL);
            $checkDestinationStmt->bind_param("is", $destination, $currency);
            $checkDestinationStmt->execute();
            $checkDestinationStmt->store_result();

            if ($checkDestinationStmt->num_rows > 0) {
                // Entry exists, update the balance
                $checkDestinationStmt->bind_result($currentamountIn);
                $checkDestinationStmt->fetch();
                $newamountIn = $currentamountIn + $amount;

                $updateDestinationSQL = "UPDATE activos SET amount = ?, lastTransactionId = ? WHERE walletId = ? AND currency = ?";
                $updateDestinationStmt = $conn->prepare($updateDestinationSQL);
                $updateDestinationStmt->bind_param("diis", $newamountIn, $lastTransactionId, $destination, $currency);
                $updateDestinationStmt->execute();
                $updateDestinationStmt->close();
            } else {
                // Entry does not exist, create a new one
                $insertDestinationSQL = "INSERT INTO activos (walletId, currency, amount, lastTransactionId) VALUES (?, ?, ?, ?)";
                $insertDestinationStmt = $conn->prepare($insertDestinationSQL);
                $insertDestinationStmt->bind_param("isdi", $destination, $currency, $amount, $lastTransactionId);
                $insertDestinationStmt->execute();
                $insertDestinationStmt->close();
            }
            $checkDestinationStmt->close();
            break;
    }

    $response = updateBalance($userId, $defaultCurrency);
    //echo json_encode($response);

    $response = appendToAuditLog($userId, $clientIP, $action, $msg,);
    echo json_encode($response);

    if ($type == "Transfer") {
        $transferInsertSQL = "INSERT INTO Transactions (walletId, transactionType, transactionAmount, transactionCurrency, newCurrency, transactionStatus, transactionDescription, transactionDate, transactionFrom)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($transferInsertSQL);
        $stmt2->bind_param("isdssssss", $destination, $type, $amount, $currency, $newCurrency, $status, $description, $fecha, $walletId);
        $stmt2->execute();
    }

    header("Location: movimientos.php");
    exit();


}
//RECOGEMOS DATOS DE CARTERAS
$walletsSelect = "SELECT * FROM Wallets w, Portfolios p WHERE w.portfolioId = p.portfolioId AND p.userId = $userId";
$walletsResult = $conn->query($walletsSelect);
//RECOGEMOS DATOS DE TRANSACCIONES
$transactionsSelect = "SELECT * FROM Transactions t, Wallets w, Portfolios p WHERE t.walletId = w.walletId AND w.portfolioId = p.portfolioId AND p.userId = $userId ORDER BY transactionDate DESC";
$transactionsResult = $conn->query($transactionsSelect);

$orden = isset($_POST["sort"]) ? $_POST["sort"] : 'default';
$direction = isset($_POST["direction"]) ? $_POST["direction"] : 'asc'; // ORDEN ASCENDENTE O DESCENDENTE
switch ($orden) {
    case 'walletName':
        $orden_sql = "ORDER BY w.walletName $direction";
        break;
    case 'transactionType':
        $orden_sql = "ORDER BY transactionType $direction";
        break;
    case 'transactionDate':
        $orden_sql = "ORDER BY transactionDate $direction";
        break;
    case 'transactionAmount':
        $orden_sql = "ORDER BY transactionAmount $direction";
        break;
    case 'transactionCurrency':
        $orden_sql = "ORDER BY transactionCurrency $direction";
        break;
    case 'transactionStatus':
        $orden_sql = "ORDER BY transactionStatus $direction";
        break;
    case 'transactionDescription':
        $orden_sql = "ORDER BY transactionDescription $direction";
        break;
    case 'default':
        $orden_sql = 'ORDER BY transactionDate'; // ORDEN POR DEFECTO (FECHA)
        break;
}
// SI SE ENVIA UNA SOLICITUD AJAX PARA ORDENAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sort'])) {
    // RECOGEMOS DATOS DE TRANSACCIONES
    $orderSelect = "SELECT * FROM Transactions t, Wallets w, Portfolios p WHERE t.walletId = w.walletId AND w.portfolioId = p.portfolioId AND p.userId = $userId $orden_sql";
    $orderResult = $conn->query($orderSelect);

    if ($orderResult->num_rows > 0) {
        while ($row = $orderResult->fetch_assoc()) {
            if ($row["transactionType"] == "Transfer") {
                if ($row["transactionTo"]) {
                    $transactionType = "Transfer Out";
                } else {
                    $transactionType = "Transfer In";
                }
            } else if ($row["transactionType"] == "Trade") {
                if ($row["transactionTo"]) {
                    $transactionType = "Trade Out";
                } else {
                    $transactionType = "Trade In";
                }
            } else {
                $transactionType = $row["transactionType"];
            }
            echo "<tr class='transaction' data-id=$row[transactionId]>";
            echo "<td>" . $row["walletName"] . "</td>";
            echo "<td>" . $transactionType . "</td>";
            echo "<td>" . date('Y-m-d', strtotime($row["transactionDate"])) . "</td>";
            echo "<td>" . (($row["transactionCurrency"] == "EUR" || $row["transactionCurrency"] == "USD") ? number_format($row["transactionAmount"], 2) : rtrim(rtrim($row["transactionAmount"], "0"), ".")) . "</td>";
            echo "<td>" . $row["transactionCurrency"] . "</td>";
            echo "<td>" . $row["transactionStatus"] . "</td>";
            echo "<td>" . $row["transactionDescription"] . "</td>";
            echo "<td class='actions'>
                    <button class='delete-btn' id='delete-btn'>
                        Eliminar
                    </button> 
                    <button class='edit-btn' id='edit-btn'>
                        Editar
                    </button>
                </td>"; 
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='8' style='text-align: center'>No hay transacciones disponibles.</td></tr>";
    }
    $conn->close();
    exit();
}

// Count active and pending alerts for the logged-in user
$alertCount = 0;
$sql = "SELECT COUNT(*) AS alert_count FROM user_alerts WHERE userId = ? AND (active = 1 AND pending = 1)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($alertCount);
$stmt->fetch();
$stmt->close();


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/movimientos.css">
    <link rel="shortcut icon" href="./img/icon.png">
    <title>Nessun Dorma - Movimientos</title>
</head>
<body>
    <div display="none" id='userId' data-userId='<?php echo $userId ?>'></div>

    <!--HEADER MOBILE-->
    <header>
        <div class="header-content">
            <h2 class="header-title">NESSUN DORMA</h2>
            <button class="menu-btn">☰</button>
        </div>
        <div class="header-aside" id="header-aside">
            <div class="aside-content">
                <button class="close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <img class="header-title" src="./img/logo.png" style="height:100px;"></img>
                <ul class="aside-menu">
                    <li class="link-menu"><a href="home.php">Inicio</a></li>
                    <li class="link-menu"><a href="movimientos.php">Movimientos</a></li>
                    <li class="link-menu"><a href="mercado.php">Mercado
                        <?php if ($alertCount > 0): ?>
                            <strong><?php echo $alertCount; ?></strong>
                        <?php endif; ?>
                    </a></li>
                    <li class="link-menu"><a href="carteras.php">Carteras</a></li>
                    <li class="link-menu"><a href="configuracion.php">Configuración</a></li>
                    <?php
                        if ($rol == "Manager") {
                            echo "<li class='link-menu'><a href='gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='auditoria.php'>Auditoria</a></li>";
                        }
                        if ($rol == "Admin") {
                            echo "<li class='link-menu'><a href='gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='auditoria.php'>Auditoria</a></li>
                                  <li class='link-menu'><a href='administracion.php'>Administración</a></li>";
                        }
                    ?>
                </ul>
            </div>
        </div>
    </header>
    <!--ASIDE DESKTOP-->
    <aside class="aside" id="aside">
        <div class="aside-content">
            <img class="sidebarLogo" src="./img/logo.png"></img>
            <div class="aside-main-content">
                <ul class="aside-menu">
                    <li class="link-menu"><a href="home.php">Inicio</a></li>
                    <li class="link-menu"><a href="movimientos.php">Movimientos</a></li>
                    <li class="link-menu"><a href="mercado.php">Mercado
                        <?php if ($alertCount > 0): ?>
                            <strong><?php echo $alertCount; ?></strong>
                        <?php endif; ?>
                    </a></li>
                    <li class="link-menu"><a href="carteras.php">Carteras</a></li>
                    <li class="link-menu"><a href="configuracion.php">Configuración</a></li>
                    <?php
                        if ($rol == "Admin" || $rol == "Manager") {
                            echo "<li class='link-menu'><a href='gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='auditoria.php'>Auditoria</a></li>
                                  <li class='link-menu'><a href='administracion.php'>Administración</a></li>";
                        }
                    ?>
                </ul>
                <div class="close-session-container">
                    <a href="./login/logout.php">
                        <button class="close-session" id="close-session">
                            <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22.5 4.742a13 13 0 11-13 0M16 3v10"></path> </g></svg>
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </aside>
    <!--MAIN CONTENT-->
    <section class="main-content" id="main-content">
        <div class="main-header">
            <h1>Movimientos.</h1>
        </div>
        <!--REVISAR FORMULARIO-->
        <div class="add-container">
            <form class="add-form" id="add-form" method="POST" action="movimientos.php">
                <div class="form-header">
                    <button class="close-btn" type="button" id="close-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                    <label for="wallet">Carteras</label>
                    <select id="wallet" name="wallet">
                        <?php
                            if ($walletsResult->num_rows > 0) {
                                while ($row = $walletsResult->fetch_assoc()) {
                                    echo "<option value='" . $row["walletId"] . "'>" . $row["walletName"] . "</option>";
                                }
                            } else {
                                echo "<option value=''>No hay carteras disponibles.</option>";
                            }
                        ?>
                    </select>
                    <label for="type">Tipo</label>
                    <select id="type" name="type">
                        <option value="Deposit">Deposit</option>
                        <option value="Withdrawal">Withdrawal</option>
                        <option value="Trade">Trade</option>
                        <option value="Transfer">Transfer</option>
                    </select>
                    <label for="amount">Cantidad</label>
                    <input type="number" id="amount" name="amount" step="0.0000000001" required>
                    <label for="currency">Moneda</label>
                    <select id="currency" name="currency" required disabled>
                        <option value="">Selecciona una cartera primero</option>
                    </select>
                    <label for="new_currency" id="new_currency_label" style="display: none;">Moneda de destino</label>
                    <select id="new_currency" name="new_currency" style="display: none;">
                        <option value=""></option>
                    </select>
                    <label for="destination" id="destination_label" style="display: none;">Cartera de destino</label>
                    <select id="destination" name="destination" style="display: none;">
                    </select>
                    <input type="text" id="destination_other" name="destination_other" style="display: none;" placeholder="Especifica el destino">
                    <label for="date">Fecha</label>
                    <input type="date" id="date" name="date">
                    <label for="status">Estado</label>
                    <select id="status" name="status">
                        <option value="Completed">Completed</option>
                        <option value="Pending">Pending</option>
                        <option value="Failed">Failed</option>
                    </select>
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" rows="4" cols="50"></textarea>
                <button type="submit" class="add-button">Agregar</button>
            </form>
        </div>
        <div class="main-btns">
            <!--SORT DROPDOWN-->
            <div class="custom-dropdown">
                <div class="dropdown-toggle" id="sort-btn">
                    Ordenar por
                    <!--ICONO SVG-->
                    <svg class="dropdown-icon" fill="#FFFFFF" viewBox="0 0 32.00 32.00" xmlns="http://www.w3.org/2000/svg" stroke="#FFFFFF" stroke-width="0.00032">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <path d="M16.003 18.626l7.081-7.081L25 13.46l-8.997 8.998-9.003-9 1.917-1.916z"></path>
                        </g>
                    </svg>
                </div>
                <ul class="dropdown-menu">
                    <li data-value="default">Ordenar por</li>
                    <li data-value="walletName">Cartera</li>
                    <li data-value="transactionType">Tipo</li>
                    <li data-value="transactionDate">Fecha</li>
                    <li data-value="transactionAmount">Cantidad</li>
                    <li data-value="transactionCurrency">Divisa</li>
                    <li data-value="transactionStatus">Estado</li>
                    <li data-value="transactionDescription">Descripción</li>
                </ul>
            </div>
            <!--ADD BUTTON-->
            <button class="add-btn" id="add-btn">+</button>
        </div>
        <!--TRANSACTIONS-->
        <div class="transactions-container">
            <div class="transactions-header">
                <h2>Movimientos</h2>
            </div>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Carteras</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Cantidad</th>
                        <th>Divisa</th>
                        <th>Estado</th>
                        <th>Descripción</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="transactions-grid">
                    <?php
                        if ($transactionsResult->num_rows > 0) {
                            while ($row = $transactionsResult->fetch_assoc()) {
                                if ($row["transactionType"] == "Transfer") {
                                    if ($row["transactionTo"]) {
                                        $transactionType = "Transfer Out";
                                    } else {
                                        $transactionType = "Transfer In";
                                    }
                                } else if ($row["transactionType"] == "Trade") {
                                    if ($row["transactionTo"]) {
                                        $transactionType = "Trade Out";
                                    } else {
                                        $transactionType = "Trade In";
                                    }
                                } else {
                                    $transactionType = $row["transactionType"];
                                }
                                echo "<tr class='transaction' data-id=$row[transactionId]>";
                                echo "<td>" . $row["walletName"] . "</td>";
                                echo "<td>" . $transactionType . "</td>";
                                echo "<td>" . date('Y-m-d', strtotime($row["transactionDate"])) . "</td>";
                                echo "<td>" . (($row["transactionCurrency"] == "EUR" || $row["transactionCurrency"] == "USD") ? number_format($row["transactionAmount"], 2) : rtrim(rtrim($row["transactionAmount"], "0"), ".")) . "</td>";
                                echo "<td>" . $row["transactionCurrency"] . "</td>";
                                echo "<td>" . $row["transactionStatus"] . "</td>";
                                echo "<td>" . $row["transactionDescription"] . "</td>";
                                echo "<td class='actions'>
                                        <button class='delete-btn' id='delete-btn'>
                                            Eliminar
                                        </button> 
                                        <button class='edit-btn' id='edit-btn'>
                                            Editar
                                        </button>
                                    </td>"; 
                                echo "</tr>";
                            }
                        }
                        else {
                            echo "<tr><td colspan='8' style='text-align: center'>No hay transacciones disponibles.</td></tr>";
                        }
                        $conn->close();
                    ?>
                    <!-- Las transacciones se generarán dinámicamente aquí -->
                </tbody>
            </table>
        </div>
            <!-- DELETE ALERT -->
            <div class="delete-alert-container" id="delete-alert-container">
                <form class="delete-alert" id="delete-alert">
                    <h3>¿Estás seguro de que deseas eliminar esta transacción?</h3>
                    <p>Esta acción no se puede deshacer.</p>
                    <input type="hidden" id="delete-transaction-id" name="delete-transaction-id">
                    <div class="delete-alert-btns">
                        <button type="submit" class="delete-btn" id="confirm-btn">Eliminar</button>
                        <button type="button" class="cancel-btn" id="cancel-btn">Cancelar</button>
                    </div>
                </form>
            </div>
    </section>
    <script src="./js/movimientos.js" type="module"></script>
</body>
</html>