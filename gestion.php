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

//RECOGEMOS CLIENTES DEL MANAGER
$myClientsQuery = "SELECT * FROM Users WHERE rol = 'Client' AND managerId = $userId";
$myClientsResult = $conn->query($myClientsQuery);

// RECOGEMOS CLIENTES DISPONIBLES PARA ASIGNAR
$clientQuery = "SELECT * FROM Users WHERE rol = 'Client' AND managerId IS NULL OR managerId = $userId ORDER BY managerId, username";
$clientResult = $conn->query($clientQuery);


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
    <link rel="stylesheet" href="./css/gestion.css">
    <link rel="shortcut icon" href="./img/icon.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.plot.ly/plotly-3.0.1.min.js" charset="utf-8"></script>
    <title>Nessun Dorma - Gestion</title>
</head>
<body>
    <div display="none" id='userId' data-userId='<?php echo $userId ?>' data-defaultCurrency='<'></div>
    <!--HEADER MOBILE-->
    <header>
        <div class="header-content">
            <h2 class="header-title">NESSUN DORMA</h2>
            <button class="menu-btn" id="mobile-menu-btn">☰</button>
        </div>
        <div class="header-aside" id="header-aside">
            <div class="aside-content">
                <button class="close-btn" id="close-mobile-menu">
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
            <!--NOMBRE DE CARTERA DEL CLIENTE-->
            <h1 class="main-title">Gestion</h1>
            <div class="button-group">
                <!--ADD CLIENT BTN-->
                <button class="add-btn" id="add-client">+</button>
                <!--DROPDOWN CLIENT-->
                <div class="custom-dropdown" id="client-dropdown">
                    <div class="dropdown-toggle" id="client-dropdown-toggle">
                        <?php
                            // Check if there are clients to show
                            if ($myClientsResult->num_rows > 0) {
                                $firstClient = $myClientsResult->fetch_assoc();
                                echo "Seleccionar Cliente";
                            } else {
                                echo "No tienes clientes";
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
                    <ul class="dropdown-menu" id="client-dropdown-menu">
                        <!--GENERAR CLIENTES DEL MANAGER SELECCIONADO MEDIANTE AJAX JS-->
                        <?php
                            if ($myClientsResult->num_rows > 0) {
                                $myClientsResult->data_seek(0);
                                while ($row = $myClientsResult->fetch_assoc()) {
                                    $userToShowId = $row["userId"];
                                    $username = $row["username"];
                                    $clientCurrency = $row["default_currency"];
                                    echo "<li class='dropdown-item' data-user-currency='$clientCurrency' data-user-id='$userToShowId'>$userToShowId - $username</li>";
                                }
                            } else {
                                echo "<li class='dropdown-item'>No tienes clientes</li>";
                            }
                        ?>
                    </ul>
                </div>
                <!--DROPDOWN WALLET-->
                <div class="custom-dropdown" id="wallet-dropdown">
                    <div class="dropdown-toggle" id="wallet-dropdown-toggle">
                        <!--SELECCIONAR CARTERA DEL CLIENTE-->
                        <span>No hay carteras</span>
                        <!--ICONO SVG-->
                        <svg class="dropdown-icon" fill="#FFFFFF" viewBox="0 0 32.00 32.00" xmlns="http://www.w3.org/2000/svg" stroke="#FFFFFF" stroke-width="0.00032">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path d="M16.003 18.626l7.081-7.081L25 13.46l-8.997 8.998-9.003-9 1.917-1.916z"></path>
                            </g>
                        </svg>
                    </div>
                    <ul class="dropdown-menu " id="wallet-dropdown-menu">
                        <!--GENERAR CARTERAS DEL CLIENTE SELECCIONADO MEDIANTE AJAX JS-->
                    </ul>
                </div>
                <!--ADD BUTTON-->
                <button class="add-btn" id="add-wallet-btn">+</button>
                <!--DELETE BUTTON-->
                <button class="delete-btn" id="delete-btn">-</button>
            </div>    
        </div>
        <div class="wallet-summary">
            <div class="wallet-balance">
                <h3>Saldo Total</h3>
                <p id=saldo></p>
            </div>
            <div class="wallet-performance">
                <h3>Ganancias/Pérdidas</h3>
                <p class="positive" id="gains"></p>
            </div>
            <div class="wallet-actions">
                <button class="export-btn">Exportar CSV</button>
                <a>
                    <button class="export-btn" id="export-btn">Exportar PDF</button>
                </a>
            </div>
        </div>
        <!-- Saldo Actual de la cartera en Diferentes Monedas -->
        <div class="summary-container">
            <div id="chart" currency="<?php echo $defaultCurrency; ?>" style="margin-top:25px;width:100%;max-height:350px"></div>
        </div>
        <div class="main-header">
            <h1 class="main-title">Activos:</h1>
        </div>
        <div class="actives-container">
            <table class="actives-table">
                <thead>
                    <tr>
                        <th>Moneda</th>
                        <th>Saldo Actual</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody class="actives-tbody">
                </tbody>
            </table>
        </div>
        <div class="main-header">
            <h1 class="main-title">Movimientos:</h1>
        </div>
        <div class="actives-container">
            <table class="actives-table">
                <thead>
                    <tr>
                        <th>Cartera</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Cantidad</th>
                        <th>Divisa</th>
                        <th>Estado</th>
                        <th>Descripción</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody class="actives-tbody" id="transactions-tbody">
                    <!--MOSTRAR MOVIMIENTOS DEL CLIENTE-->
                </tbody>
            </table>
        </div>
        <!-- ADD CLIENT FORM -->
        <div class="add-client-container">
            <div class="add-client-content">
                <div class="form-header">
                    <button class="close-btn" type="button" id="close-client-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="client-container">
                    <h2>Agregar Cliente</h2>
                    <table class="client-table">
                        <thead>
                            <tr>
                                <th>userId</th>
                                <th>Username</th>
                                <th style="text-align: center;">Asignado</th>
                            </tr>
                        </thead>
                        <tbody id="client-list">
                            <?php
                                if ($clientResult->num_rows > 0) {
                                    while ($row = $clientResult->fetch_assoc()) {
                                        $userToShowId = $row["userId"];
                                        $username = $row["username"];
                                        echo "<tr data-user-id='$userToShowId'>
                                                <td>$userToShowId</td>
                                                <td>$username</td>
                                                <td style='text-align: center;'>";
                                                if ($row["managerId"] == NULL) {
                                                    echo "<input class='client-check' type='checkbox' class='unassigned-check' data-userid='$userToShowId'/>";
                                                } elseif ($row["managerId"] == $userId) {
                                                    echo "<input class='client-check' type='checkbox' class='assigned-check' data-userid='$userToShowId' checked/>";
                                                }
                                        echo    "</td>
                                            </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3'>No hay clientes disponibles</td></tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- ADD WALLET FORM -->
        <div class="add-wallet-container" id="add-wallet-container">
            <form class="add-form" id="add-form">
                <div class="form-header">
                    <button class="close-btn" type="button" id="close-wallet-btn">
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
                    <button type="submit" class="add-button" id="confirm-add-wallet">Crear cartera</button>
                </div>
            </form>
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
    <script src="./js/gestion.js" type="module"></script>
</body>
</html>