<?php
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
$currency = $_SESSION["currency"];


// Update all alerts for the user to set pending = 0
$updateStmt = $conn->prepare("UPDATE user_alerts SET pending = 0 WHERE userId = ?");
$updateStmt->bind_param("i", $userId);
if ($updateStmt->execute()) {
    error_log("Alerts updated successfully for userId: $userId");
} else {
    error_log("Error updating alerts for userId: $userId - " . $updateStmt->error);
}
$updateStmt->close();


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
<script src="https://cdn.plot.ly/plotly-3.0.1.min.js" charset="utf-8"></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js'></script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/mercado.css">
    <link rel="shortcut icon" href="./img/icon.png">
    <title>Nessun Dorma - Mercado</title>
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
                                  <li class='link-menu'><a href='alertas.php'>Auditoria</a></li>";
                        }
                        if ($rol == "Admin") {
                            echo "<li class='link-menu'><a href='gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='alertas.php'>Auditoria</a></li>
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
        <!--Mostrar el nombre de usuario-->
        <div class="main-header">
            <h1>Bienvenido, <?php echo $firstName; ?>.</h1>
        </div>

        <div class="summary-container">
            <div id="chart" currency="<?php echo $currency; ?>" style="margin-top:25px;width:100%;max-height:350px"></div>
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
                    <li data-value="currency">Divisa</li>
                    <li data-value="value">Valor Actual</li>
                    <li data-value="variation-1d">Variación 1d</li>
                    <li data-value="variation-7d">Variación 7d</li>
                </ul>
            </div>
            <!--ADD BUTTON-->
        </div>

        <div class="portfolio-container">
        <table class="portfolio-table">
            <thead>
                <tr>
                    <th>Divisa</th>
                    <th>Valor actual</th>
                    <th>Variación 1 día</th>
                    <th>Variación 7 días</th>
                    <th>Alerta</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    </section>
    
    <script src="./js/mercado.js" type="module"></script>
</body>
</html>