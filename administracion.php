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
if (!isset($_SESSION["userId"]) || $_SESSION["rol"] == "Client") {
    header("Location: ./home.php");
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

//RECOGEMOS DATOS DE USUARIOS
$selectUsers = "SELECT * FROM Users";
$usersResult = $conn->query($selectUsers);

$orden = isset($_POST["sort"]) ? $_POST["sort"] : 'default';
$direction = isset($_POST["direction"]) ? $_POST["direction"] : 'asc'; // Orden ascendente o descendente

switch ($orden) {
    case 'username':
        $orden_sql = "ORDER BY username $direction";
        break;
    case 'email':
        $orden_sql = "ORDER BY email $direction";
        break;
    case 'rol':
        $orden_sql = "ORDER BY rol $direction";
        break;
    case 'verified':
        $orden_sql = "ORDER BY verified $direction";
        break;
    case 'default':
        $orden_sql = "ORDER BY userId $direction"; // Orden por defecto
        break;
}

// Si se envía una solicitud AJAX para ordenar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sort'])) {
    $orderSelect = "SELECT * FROM Users $orden_sql";
    $orderResult = $conn->query($orderSelect);

    if ($orderResult->num_rows > 0) {
        while ($row = $orderResult->fetch_assoc()) {
            echo "<tr data-userid='" . $row["userId"] . "'>
                <td>" . $row["userId"] . "</td>
                <td>" . $row["username"] . "</td>
                <td>" . $row["firstName"] . " " . $row["lastName1"] . "</td>
                <td>" . $row["email"] . "</td>
                <td>" . $row["rol"] . "</td>
                <td style='text-align: center;'>";
                    if ($row["verified"] == 1 && $row["rol"] == "Admin" && $rol == "Admin") {
                        echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked>";
                    } elseif ($row["verified"] == 0 && $row["rol"] == "Admin" && $rol == "Admin") {
                        echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked>";
                    } elseif ($row["verified"] == 1 && $row["rol"] == "Admin") {
                        echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked disabled>";
                    } elseif ($row["verified"] == 0 && $row["rol"] == "Admin") {
                        echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' disabled>";
                    } elseif ($row["verified"] == 1 && $row["rol"] == "Manager" && $rol == "Manager") {
                        echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked disabled>";
                    } elseif ($row["verified"] == 1) {
                        echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked>";
                    } elseif ($row["verified"] == 0) {
                    echo "<input type='checkbox' class='unverified-check' data-userid='" . $row["userId"] . "'>";
                    }
                    echo "</td>
                    <td style='text-align: center;'> 
                        <a href='./administracion/newinfoUser.php?userId=" . $row["userId"] . "'>
                            <button class='info-btn'>Info</button>
                        </a> 
                        <a href='./administracion/editUser.php?userId=" . $row["userId"] . "'>
                            <button class='edit-btn'>Editar</button>
                        </a>
                        <a>
                            <button class='delete-btn'>Eliminar</button>
                        </a>
                    </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='7'>No hay usuarios registrados.</td></tr>";
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
    <link rel="stylesheet" href="./css/administracion.css">
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
                        <li class="dropdown-item" data-value="username">Username</li>
                        <li class="dropdown-item" data-value="email">Email</li>
                        <li class="dropdown-item" data-value="rol">Rol</li>
                        <li class="dropdown-item" data-value="verified">Verificado</li>
                    </ul>
                </div>
        </div>
        <div class="main-body">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>UserID</th>
                        <th>Username</th>
                        <th>Nombre</th>
                        <th>email</th>
                        <th>Rol</th>
                        <th style="text-align: center;">Verificado</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                <?php
                    if ($usersResult->num_rows > 0) {
                        while ($row = $usersResult->fetch_assoc()) {
                            echo "<tr data-userid='" . $row["userId"] . "'>
                                    <td>" . $row["userId"] . "</td>
                                    <td>" . $row["username"] . "</td>
                                    <td>" . $row["firstName"] . " " . $row["lastName1"] . "</td>
                                    <td>" . $row["email"] . "</td>
                                    <td>" . $row["rol"] . "</td>
                                    <td style='text-align: center;'>";
                                        if ($row["verified"] == 1 && $row["rol"] == "Admin" && $rol == "Admin") {
                                            echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked>";
                                        } elseif ($row["verified"] == 0 && $row["rol"] == "Admin" && $rol == "Admin") {
                                            echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked>";
                                        } elseif ($row["verified"] == 1 && $row["rol"] == "Admin") {
                                            echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked disabled>";
                                        } elseif ($row["verified"] == 0 && $row["rol"] == "Admin") {
                                            echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' disabled>";
                                        } elseif ($row["verified"] == 1 && $row["rol"] == "Manager" && $rol == "Manager") {
                                            echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked disabled>";
                                        } elseif ($row["verified"] == 1) {
                                            echo "<input type='checkbox' class='verified-check' data-userid='" . $row["userId"] . "' checked>";
                                        } elseif ($row["verified"] == 0) {
                                            echo "<input type='checkbox' class='unverified-check' data-userid='" . $row["userId"] . "'>";
                                        }
                                    echo "</td>
                                    <td style='text-align: center;'> 
                                        <a href='./administracion/newinfoUser.php?userId=" . $row["userId"] . "'>
                                            <button class='info-btn'>Info</button>
                                        </a> 
                                        <a href='./administracion/editUser.php?userId=" . $row["userId"] . "'>
                                            <button class='edit-btn'>Editar</button>
                                        </a>
                                        <a>
                                            <button class='delete-btn'>Eliminar</button>
                                        </a>
                                    </td>
                                </tr>";
                        }                                        
                    } else {
                        echo "<tr><td colspan='7'>No hay usuarios registrados.</td></tr>";
                    }
                ?>
                </tbody>
            </table>
        </div>
        <div class="delete-alert-container" id="delete-alert">
            <div class="delete-alert-content">
                <h2>¿Estás seguro de que quieres eliminar el usuario con ID <span id="delete-user-id"></span>?</h2>
                <div class="delete-alert-buttons">
                    <button class="cancel-delete">Cancelar</button>
                    <button class="confirm-delete">Eliminar</button>
                </div>
            </div>
        </div>
    </section>
</body>
<script src="./js/administracion.js"></script>
</html>