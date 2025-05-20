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
$clientIP = $_SESSION["clientIP"];


$auditoryQuery = "SELECT * FROM auditoria a, ipTable i WHERE a.userId = i.userId AND a.ipId = i.id AND DATE(a.date) = CURDATE() ORDER BY a.date DESC";
$auditoryResult = $conn->query($auditoryQuery);

$ipQuery = "SELECT * FROM ipTable WHERE DATE(lastUse) = CURDATE() ORDER BY lastUse DESC";
$ipResult = $conn->query($ipQuery);

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
    <link rel="stylesheet" href="./css/auditoria.css">
    <link rel="shortcut icon" href="./img/icon.png">
    <title>Nessun Dorma - Administración</title>
</head>
<body>
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
    <section class="main-content" id="main-content">
        <!--Mostrar el nombre de usuario-->
        <div class="main-header">
            <h1>Bienvenido, <?php echo $rol;?> <?php echo $firstName; ?>.</h1>
            <div class="btns-container">
                <div class="filter-dropdown">
                    <div class="filter-toggle" id="filter-btn">
                        Filtrar por
                        <!--ICONO SVG-->
                        <svg class="filter-icon" fill="#FFFFFF" viewBox="0 0 32.00 32.00" xmlns="http://www.w3.org/2000/svg" stroke="#FFFFFF" stroke-width="0.00032">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path d="M16.003 18.626l7.081-7.081L25 13.46l-8.997 8.998-9.003-9 1.917-1.916z"></path>
                            </g>
                        </svg>
                    </div>
                    <ul class="filter-menu">
                        <li class="filter-item" data-value="default">Filtrar por</li>
                        <li class="filter-item" data-value="myClients">Mis clientes</li>
                        <li class="filter-item" data-value="all">Todo</li>
                    </ul>
                </div>
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
                        <li class="dropdown-item" data-value="default">Ordenar por</li>
                        <li class="dropdown-item" data-value="regId">RegID</li>
                        <li class="dropdown-item" data-value="a.userId">UserID</li>
                        <li class="dropdown-item" data-value="date">Fecha y Hora</li>
                        <li class="dropdown-item" data-value="action">Acción</li>
                        <li class="dropdown-item" data-value="level">Nivel</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="main-body">
            <!-- Tabla de Auditoría -->
            <div id="auditoriaSection">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>RegID</th>
                            <th>UserID</th>
                            <th>Fecha y Hora</th> 
                            <th>Acción</th>
                            <th>Mensaje</th>
                            <th style='text-align: center;'>Nivel</th>
                            <th style='text-align: center;'>IP</th>
                        </tr>
                    </thead>
                    <tbody id="auditory-body">
                        <?php
                            if ($auditoryResult->num_rows > 0){
                                while ($row = $auditoryResult->fetch_assoc()){
                                    $RegId = $row["regId"];
                                    $userShowId = $row["userId"];
                                    $date = $row["date"];
                                    $action = $row["action"];
                                    $message = $row["msg"];
                                    $level = $row["level"];
                                    $Ip = $row["ip"];
                                    if ($level == 1) {
                                        $case = "Bajo";
                                    } elseif ($level == 2) {
                                        $case = "Medio";
                                    } elseif ($level == 3) {
                                        $case = "Alto";
                                    }
                                    if ($userShowId == 0) {
                                        $userShowId = "API";
                                    } else {
                                        $userShowId = $userShowId;
                                    }
                                    if ($level == 1) {
                                        echo "<tr class='warning-level1'>
                                            <td>{$RegId}</td>
                                            <td>{$userShowId}</td>
                                            <td>{$date}</td>
                                            <td>{$action}</td>
                                            <td>{$message}</td>
                                            <td style='text-align: center;'>{$case}</td>
                                            <td style='text-align: center;'>{$Ip}</td>";
                                    } elseif ($level == 2) {
                                        echo "<tr class='warning-level2'>
                                            <td>{$RegId}</td>
                                            <td>{$userShowId}</td>
                                            <td>{$date}</td>
                                            <td>{$action}</td>
                                            <td>{$message}</td>
                                            <td style='text-align: center;'>{$case}</td>
                                            <td style='text-align: center;'>{$Ip}</td>";
                                    } elseif ($level == 3) {
                                        echo "<tr class='warning-level3'>
                                            <td>{$RegId}</td>
                                            <td>{$userShowId}</td>
                                            <td>{$date}</td>
                                            <td>{$action}</td>
                                            <td>{$message}</td>
                                            <td style='text-align: center;'>{$case}</td>
                                            <td style='text-align: center;'>{$Ip}</td>";
                                    }
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center;'>No hay registros del dia de hoy.</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="main-header">
            <h1>Conexiones:</h1>
            <div class="btns-container">
                <div class="filterIP-dropdown">
                    <div class="filterIP-toggle" id="filterIP-btn">
                        Filtrar por
                        <!--ICONO SVG-->
                        <svg class="filterIP-icon" fill="#FFFFFF" viewBox="0 0 32.00 32.00" xmlns="http://www.w3.org/2000/svg" stroke="#FFFFFF" stroke-width="0.00032">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path d="M16.003 18.626l7.081-7.081L25 13.46l-8.997 8.998-9.003-9 1.917-1.916z"></path>
                            </g>
                        </svg>
                    </div>
                    <ul class="filterIP-menu">
                        <li class="filterIP-item" data-value="default">Filtrar por</li>
                        <li class="filterIP-item" data-value="verified">Verificado</li>
                        <li class="filterIP-item" data-value="all">Todo</li>
                    </ul>
                </div>
                <div class="customIP-dropdown">
                    <div class="dropdownIP-toggle" id="sort-btn">
                        Ordenar por
                        <!--ICONO SVG-->
                        <svg class="dropdownIP-icon" fill="#FFFFFF" viewBox="0 0 32.00 32.00" xmlns="http://www.w3.org/2000/svg" stroke="#FFFFFF" stroke-width="0.00032">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path d="M16.003 18.626l7.081-7.081L25 13.46l-8.997 8.998-9.003-9 1.917-1.916z"></path>
                            </g>
                        </svg>
                    </div>
                    <ul class="dropdownIP-menu">
                        <li class="dropdownIP-item" data-value="default">Ordenar por</li>
                        <li class="dropdownIP-item" data-value="id">ConnectionID</li>
                        <li class="dropdownIP-item" data-value="userID">UserID</li>
                        <li class="dropdownIP-item" data-value="verified">Verificado</li>
                        <li class="dropdownIP-item" data-value="country">País</li>
                        <li class="dropdownIP-item" data-value="firstUse">Primera Conexión</li>
                        <li class="dropdownIP-item" data-value="lastUse">Ultima Conexión</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="main-body">
            <!-- Tabla de Auditoría -->
            <div id="auditoriaIPSection">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ConnectionID</th>
                            <th>UserID</th>
                            <th style="text-align: center;">IP</th>
                            <th style="text-align: center;">Verificado</th> 
                            <th>País</th>
                            <th>Ciudad</th>
                            <th>Primera Conexión</th>
                            <th>Ultima Conexión</th>
                        </tr>
                    </thead>
                    <tbody id="auditoryIP-body">
                        <?php
                            if ($ipResult->num_rows > 0){
                                while ($row = $ipResult->fetch_assoc()){
                                    $id = $row["id"];
                                    $userShowId = $row["userId"];
                                    $ip = $row["ip"];
                                    $verified = $row["verified"];
                                    $country = $row["country"];
                                    $city = $row["city"];
                                    $firstUse = $row["firstUse"];
                                    $lastUse = $row["lastUse"];
                                    echo "<tr>
                                            <td>{$id}</td>
                                            <td>{$userShowId}</td>
                                            <td style='text-align: center;'>{$ip}</td>";
                                    if ($verified == 1) {
                                        echo "<td style='text-align: center;'><input type='checkbox' checked disabled></td>";
                                    } else {
                                        echo "<td style='text-align: center;'><input type='checkbox' disabled></td>";
                                    }
                                    echo    "<td>{$country}</td>
                                            <td>{$city}</td>
                                            <td>{$firstUse}</td>
                                            <td>{$lastUse}</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align:center;'>No hay conexiones del dia de hoy.</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <script src="./js/auditoria.js"></script>
</body>
</html>