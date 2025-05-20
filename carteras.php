<?php
include './PHP/convert_currency.php';
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

//RECOGEMOS DATOS DE LA CARTERA
$walletsSelect = "SELECT * FROM Wallets w, Portfolios p WHERE w.portfolioId = p.portfolioId AND p.userId = $userId";
$walletsResult = $conn->query($walletsSelect);



if ($walletsResult->num_rows > 0) {
    $firstWallet = $walletsResult->fetch_assoc();
    $firstWalletName = $firstWallet["walletName"];
    $firstWalletId = $firstWallet["walletId"];
} else {
    $firstWalletId = null;
}

//CALCULAR SALDO
if ($firstWalletId) {
    $date = date("Y-m-d");
    $walletBalanceQuery = "SELECT balance FROM Wallets WHERE walletId = $firstWalletId";
    $result = $conn->query($walletBalanceQuery);
    if ($result && $result->num_rows > 0) {
        $walletBalanceResult = $result->fetch_assoc()["balance"];
    } else {
        $walletBalanceResult = 50; // Default value if no rows are returned
    }
} else {
    $walletBalanceResult = 100; // Default value if walletId is not set
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $walletName = $_POST["wallet-name"];
    $walletAddress = $_POST["wallet-address"] ? $_POST["wallet-address"] : "";
    $walletType = $_POST["wallet-type"];
    $balance = 0;

    $portfolioSelect = "SELECT portfolioId FROM Portfolios WHERE userId = $userId";
    $portfolioId = $conn->query($portfolioSelect)->fetch_assoc()["portfolioId"];

    //COMPROBAMOS SI YA EXISTE UNA CARTERA CON EL NOMBRE
    $checkWalletQuery = "SELECT * FROM Wallets WHERE walletName = '$walletName' AND portfolioId = $portfolioId";
    $checkWalletResult = $conn->query($checkWalletQuery);
    if ($checkWalletResult->num_rows > 0) {
        echo "<script>alert('Ya existe una cartera con ese nombre.');</script>";
        exit();
    }

    //COMPROBAMOS SI YA EXISTE UNA CARTERA CON LA DIRECCION
    if (isset($walletAddress) && $walletAddress != "") {
        $checkAddressQuery = "SELECT * FROM Wallets WHERE walletAddress = '$walletAddress'";
        $checkAddressResult = $conn->query($checkAddressQuery);
        if ($checkAddressResult->num_rows > 0) {
            echo "<script>alert('Ya existe una cartera con esa dirección.');</script>";
            exit();
        }
    }

    //INSERTAMOS LA NUEVA CARTERA
    $insertWalletQuery = "INSERT INTO Wallets (portfolioId, walletName, walletAddress, walletType, balance, gains) 
                          VALUES ('$portfolioId', '$walletName', '$walletAddress', '$walletType', '$balance', '$balance')";
    $conn->query($insertWalletQuery);
    $walletId = $conn->insert_id;

    header("Location: carteras.php");
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
    <script src="https://cdn.plot.ly/plotly-3.0.1.min.js" charset="utf-8"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js'></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/carteras.css">
    <link rel="shortcut icon" href="./img/icon.png">
    <title>Nessun Dorma - Carteras</title>
</head>
<body>
    <div display="none" id='userId' data-userId='<?php echo $userId ?>'></div>
    <div display="none" id='defaultCurrency' data-defaultCurrency='<?php echo $defaultCurrency ?>'></div>
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
            <?php
                if ($walletsResult->num_rows > 0) {
                    echo "<h1 class='main-title'>Cartera: </h1>";
                } else {
                    echo "<h1 class='main-title'>No tienes carteras creadas</h1>";
                }
            ?>
            <div class="button-group">            
                <div class="custom-dropdown">
                    <div class="dropdown-toggle" id="wallet-btn">
                        <?php
                            if ($walletsResult->num_rows > 0) {
                                echo "Seleccionar Cartera";
                            } else {
                                echo "No hay carteras";
                            }
                        ?>
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
                        <?php
                            if ($walletsResult->num_rows > 0) {
                                $walletsResult->data_seek(0);
                                while ($row = $walletsResult->fetch_assoc()) {
                                    $walletId = $row["walletId"];
                                    $walletName = $row["walletName"];
                                    echo "<li data-wallet-id='$walletId'>$walletName</li>";
                                }
                            } else {
                                echo "<li>No hay carteras</li>";
                            }
                        ?>
                    </ul>
                </div>
                <!--ADD BUTTON-->
                <button class="add-btn" id="add-btn">+</button>
                <button class="delete-btn" id="delete-btn">-</button>
            </div>    
        </div>
        <!-- ADD CARTERA FORM -->
        <div class="add-wallet-container" >
            <form action="carteras.php" method="POST" class="add-form" id="add-form">
                <div class="form-header">
                    <button class="close-btn" type="button" id="close-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <h1 class="form-title">Crear Nueva Cartera</h1>
                <div class="form-content">
                    <div class="form-group">
                        <label for="wallet-name">Nombre de la Cartera</label>
                        <input type="text" id="wallet-name" name="wallet-name" required>
                    </div>
                    <div class="form-group">
                        <label for="wallet-address">Dirección de la Cartera</label>
                        <input type="text" id="wallet-address" name="wallet-address">
                    </div>
                    <div class="form-group">
                        <label for="wallet-type">Tipo de Cartera</label>
                        <select id="wallet-type" name="wallet-type" required>
                            <option value="Gold">Gold</option>
                            <option value="Fiat">Fiat</option>
                            <option value="Crypto">Crypto</option>
                        </select>
                    </div>
                    <button type="submit" class="add-button">Crear cartera</button>
                </div>
            </form>
        </div>
        <div class="wallet-summary">
            <div class="wallet-balance">
                <h3>Saldo Total</h3>
                <p id="saldo">-</p>
            </div>
            <div class="wallet-performance">
                <h3>Ganancias/Pérdidas</h3>
                <p id="gains">-</p>
            </div>
            <div class="wallet-actions">
                <button class="export-btn">Exportar CSV</button>
                <a href="generar_informe.php" target="_blank">
                    <button class="export-btn">Exportar PDF</button>
                </a>
            </div>
        </div>
        <div class="summary-container">
            <div id="chart" currency="<?php echo $defaultCurrency; ?>" style="margin-top:25px;width:100%;max-height:350px"></div>
        </div>
        <!-- Saldo Actual de la cartera en Diferentes Monedas -->
        <div class="main-header">
            <h1 class="main-title">Activos:</h1>
        </div>
        <div class="actives-container">
            <table class="actives-table">
                <thead>
                    <tr>
                        <th>Moneda</th>
                        <th>Saldo</th>
                        <th>Valor Actual</th>
                    </tr>
                </thead>
                <tbody class="actives-tbody">
                </tbody>
            </table>
        </div>
        <!-- DELETE WALLET ALERT -->
        <div class="delete-alert-container" id="delete-alert">
            <div class="delete-alert-content">
                <h2>¿Estás seguro de que quieres eliminar la cartera <span id="delete-wallet-id"></span>?</h2>
                <div class="delete-alert-buttons">
                    <button class="cancel-delete" id="cancel-delete">Cancelar</button>
                    <button class="confirm-delete" id="confirm-delete">Eliminar</button>
                </div>
            </div>
        </div>
    </section>
    <script src="./js/inactividad.js" type="module"></script>
    <script src="./js/carteras.js" type="module"></script>
</body>
</html>