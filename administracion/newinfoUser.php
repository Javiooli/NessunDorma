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
    header("Location: ./../login/login.php");
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


//RECOGEMOS DATOS DEL USUARIO QUE SE QUIERE VER
if (isset($_GET["userId"])) {
    $userIdToView = $_GET["userId"];
} else {
    header("Location: ./../administracion.php");
    exit();
}

$userSql = "SELECT * FROM Users WHERE userId = $userIdToView";
$userResult = $conn->query($userSql);


//ACTUALIZAR USUARIO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $rol = $_POST["rol"];
    $firstName = $_POST["firstName"];
    $lastName1 = $_POST["lastName1"];
    $lastName2 = $_POST["lastName2"];
    $nif = $_POST["nif"];
    $birthDate = $_POST["birthDate"];
    $country = $_POST["country"];

    // Actualizar el usuario en la base de datos
    $updateSql = "UPDATE Users SET username='$username', email='$email', rol='$rol', firstName='$firstName', lastName1='$lastName1', lastName2='$lastName2', nif='$nif', birthDate='$birthDate', country='$country' 
    WHERE userId=$userIdToView";
    
    if ($conn->query($updateSql) === TRUE) {
        header("Location: ./editUser.php?userId=$userIdToView");
        exit();
    } else {
        echo "<script>alert('Error al actualizar el usuario: " . $conn->error . "');</script>";
    }
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
    <link rel="stylesheet" href="./adminCSS/editUser.css">
    <link rel="shortcut icon" href="./../img/icon.png">
    <title>Nessun Dorma - Editando User, <?php echo $userIdToView; ?></title>
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
                <img class="header-title" src="./../img/logo.png" style="height:100px;"></img>
                <ul class="aside-menu">
                    <li class="link-menu"><a href="./../home.php">Inicio</a></li>
                    <li class="link-menu"><a href="./../movimientos.php">Movimientos</a></li>
                    <li class="link-menu"><a href="./../mercado.php">Mercado
                         <?php if ($alertCount > 0): ?>
                            <strong><?php echo $alertCount; ?></strong>
                        <?php endif; ?>
                    </a></li>
                    <li class="link-menu"><a href="./../carteras.php">Carteras</a></li>
                    <li class="link-menu"><a href="./../configuracion.php">Configuración</a></li>
                    <?php
                        if ($rol == "Manager") {
                            echo "<li class='link-menu'><a href='./../gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='./../auditoria.php'>Auditoria</a></li>";
                        }
                        if ($rol == "Admin") {
                            echo "<li class='link-menu'><a href='./../gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='./../auditoria.php'>Auditoria</a></li>
                                  <li class='link-menu'><a href='./../administracion.php'>Administración</a></li>";
                        }
                    ?>
                </ul>
            </div>
        </div>
    </header>
    <!--ASIDE DESKTOP-->
    <aside class="aside" id="aside">
        <div class="aside-content">
            <img class="sidebarLogo" src="./../img/logo.png"></img>
            <div class="aside-main-content">
                <ul class="aside-menu">
                    <li class="link-menu"><a href="./../home.php">Inicio</a></li>
                    <li class="link-menu"><a href="./../movimientos.php">Movimientos</a></li>
                    <li class="link-menu"><a href="./../mercado.php">Mercado
                         <?php if ($alertCount > 0): ?>
                            <strong><?php echo $alertCount; ?></strong>
                        <?php endif; ?>
                    </a></li>
                    <li class="link-menu"><a href="./../carteras.php">Carteras</a></li>
                    <li class="link-menu"><a href="./../configuracion.php">Configuración</a></li>
                    <?php
                        if ($rol == "Admin" || $rol == "Manager") {
                            echo "<li class='link-menu'><a href='gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='auditoria.php'>Auditoria</a></li>
                                  <li class='link-menu'><a href='administracion.php'>Administración</a></li>";
                        }
                    ?>
                </ul>
                <div class="close-session-container">
                    <a href="./../login/logout.php">
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
            <div class="main-titles">
                <h1>Bienvenido, <?php echo $rol;?> <?php echo $firstName; ?>.</h1>
            </div>
        </div>
        <div class="main-body">
            <?php
            $userData = []; // Array para almacenar los datos del usuario y poder reutilizarlos en el formulario
            if ($userResult->num_rows > 0) {
                while ($row = $userResult->fetch_assoc()) {
                    $userData[] = $row;
                }
            }
            ?>
            <div class="user-info">
                <h2 class="main-title">Información del usuario <?php echo $userIdToView ?>.</h2>
                <?php
                    if (count($userData) > 0) {
                        foreach ($userData as $row) {
                            echo "<div class='inf-field'><strong>UserId: </strong><p>" . htmlspecialchars($row["userId"]) . "</p></div>";
                            echo "<div class='inf-field'><strong>Usuario: </strong><p>" . htmlspecialchars($row["username"]) . "</p></div>";
                            echo "<div class='inf-field'><strong>Email: </strong><p>" . htmlspecialchars($row["email"]) . "</p></div>";
                            echo "<div class='inf-field'><strong>Rol: </strong><p>" . htmlspecialchars($row["rol"]) . "</p></div>";
                            echo "<div class='inf-field'><strong>Fecha de creación: </strong><p>" . htmlspecialchars($row["creation_date"]) . "</p></div>";
                            echo "<div class='inf-field'><strong>Verificado: </strong><p>" . ($row["verified"] ? "Sí" : "No") . "</p></div>";
                            echo "<div class='inf-field'><strong>2FA Activo: </strong><p>" . ($row["otp_code"] === NULL ? "No" : "Sí") . "</p></div>";
                        }
                    } else {
                    echo "<p>No se encontró información del usuario.</p>";
                }
                ?>
            </div>
            <div class="user-info">
                <h2 class="main-title">Información adicional del usuario <?php echo $userIdToView ?>.</h2>
                <?php
                if (count($userData) > 0) {
                    foreach ($userData as $row) {
                        echo "<div class='inf-field'><strong>Nombre: </strong><p>" . $row["firstName"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Apellidos: </strong><p>" . $row["lastName1"] . " " . $row["lastName2"] . "</p></div>";
                        echo "<div class='inf-field'><strong>NIF: </strong><p>" . $row["nif"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Fecha de nacimiento: </strong><p>" . $row["birthDate"] . "</p></div>";
                        echo "<div class='inf-field'><strong>País: </strong><p>" . $row["country"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Importe Máximo: </strong><p>" . $row["max_amount"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Moneda: </strong><p>" . $currency . "</p></div>";
                    }
                } else {
                    echo "<p>No se encontró información del usuario.</p>";
                }
                ?>
            </div>
        </div>
            <?php
                foreach ($userData as $row) { 
                    $rolToShow = $row["rol"];
                    $managerId = $row["managerId"];
                    $client1Id = $row["client1Id"];
                    $client2Id = $row["client2Id"];
                    $client3Id = $row["client3Id"];
                    if ($rolToShow == "Manager") { 
                        $clientes = '';
                        if ($client1Id == NULL) {
                            $clientes = 'Sin Clientes';
                        } elseif ($client2Id == NULL) {
                            $clientes = $client1Id;
                        } elseif ($client3Id == NULL) {
                            $clientes = $client1Id . ", " . $client2Id;
                        } else {
                            $clientes = $client1Id . ", " . $client2Id . ", " . $client3Id;
                        }
                        echo "<div class='main-body'><div class='user-info'><h2>Clientes Asignados:</h2><div class='inf-field'><strong>Clientes: </strong><p>" . $clientes . "</p></div></div></div>";
                    } elseif ($rolToShow == "Client") { 
                        echo "<div class='main-body'><div class='user-info'><h2>Gestor Asignado:</h2><div class='inf-field'><strong>Gestor: </strong><p>" . ($managerId == NULL ? 'Sin Gestor' : $managerId) . "</p></div></div></div>";
                    }
                }
            ?>
        <div class="main-footer">
            <a href="./../administracion.php"><button class="button">Volver</button></a>
        </div>
    </section>
</body>
</html>