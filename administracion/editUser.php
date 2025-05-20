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
            <div class="user-info">
                <h2 class="main-title">Información del usuario <?php echo $userIdToView ?>.</h2>
                <?php
                if ($userResult->num_rows > 0) {
                    while ($row = $userResult->fetch_assoc()) {
                        echo "<div class='inf-field'><strong>UserId: </strong><p>" . $row["userId"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Usuario: </strong><p>" . $row["username"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Email: </strong><p>" . $row["email"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Rol: </strong><p>" . $row["rol"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Fecha de creación: </strong><p>" . $row["creation_date"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Verificado: </strong><p>" . ($row["verified"] ? "Sí" : "No") . "</p></div>";
                        echo "<div class='inf-field'><strong>Nombre: </strong><p>" . $row["firstName"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Apellidos: </strong><p>" . $row["lastName1"] . " " . $row["lastName2"] . "</p></div>";
                        echo "<div class='inf-field'><strong>NIF: </strong><p>" . $row["nif"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Fecha de nacimiento: </strong><p>" . $row["birthDate"] . "</p></div>";
                        echo "<div class='inf-field'><strong>País: </strong><p>" . $row["country"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Importe Maximo: </strong><p>" . $row["max_amount"] . "</p></div>";
                        echo "<div class='inf-field'><strong>Moneda: </strong><p>" . $currency . "</p></div>";
                    }
                } else {
                    echo "<p>No se encontró información del usuario.</p>";
                }
                ?>
            </div>
            <div class="user-actions">
                <h2 class="main-title">Editar usuario: <?php echo $userIdToView ?></h2>
                <form method="POST" class="user-form">
                    <?php
                    if ($userResult->num_rows > 0) {
                        $userResult->data_seek(0);
                        while ($row = $userResult->fetch_assoc()) {
                            $userUsername = $row["username"];
                            $userEmail = $row["email"];
                            $userRol = $row["rol"];
                            $userVerified = $row["verified"];
                            $userFirstName = $row["firstName"];
                            $userLastName1 = $row["lastName1"];
                            $userLastName2 = $row["lastName2"];
                            $userNif = $row["nif"];
                            $userBirthDate = $row["birthDate"];
                            $userCountry = $row["country"];
                            $userMaxAmount = $row["max_amount"];
                        }
                    } else {
                        echo "<p>No se encontró información del usuario.</p>";
                    }
                    ?>
                    <div class="user-section">
                        <div class="edit-field">
                            <label for="username">*Usuario:</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userUsername); ?>" required>
                        </div>
                        <div class="edit-field">
                            <label for="email">*Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" required>
                        </div>
                        <div class="edit-field">
                            <label for="rol">*Rol:</label>
                            <select id="rol" name="rol">
                                <option value="Client" <?php if ($userRol == "Client") echo "selected"; ?>>Client</option>
                                <option value="Manager" <?php if ($userRol == "Manager") echo "selected"; ?>>Manager</option>
                                <option value="Admin" <?php if ($userRol == "Admin") echo "selected"; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="user-section">
                        <div class="edit-field">
                            <label for="firstName">*Nombre:</label>
                            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($userFirstName); ?>" required>
                        </div>
                    </div>
                    <div class="user-section">
                        <div class="edit-field">
                            <label for="lastName">*Primer apellido:</label>
                            <input type="text" id="lastName" name="lastName1" value="<?php echo htmlspecialchars($userLastName1); ?>" required>
                        </div>
                        <div class="edit-field">
                            <label for="lastName2">Segundo apellido:</label>
                            <input type="text" id="lastName2" name="lastName2" value="<?php echo htmlspecialchars($userLastName2 ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="edit-field">
                            <label for="birthDate">*Fecha de nacimiento:</label>
                            <input type="date" id="birthDate" name="birthDate" value="<?php echo htmlspecialchars($userBirthDate); ?>" required>
                        </div>
                    </div>
                    <div class="user-section">
                        <div class="edit-field">
                            <label for="nif">*NIF:</label>
                            <input type="text" id="nif" name="nif" value="<?php echo htmlspecialchars($userNif); ?>" required>
                        </div>
                        <div class="edit-field">
                            <label for="country">*País:</label>
                            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($userCountry); ?>" required>
                        </div>
                    </div>
                    <div class="user-section">
                        <div class="edit-field">
                            <label for="currency">*Moneda:</label>
                            <input type="text" id="currency" name="currency" value="<?php echo htmlspecialchars($currency); ?>" readonly>
                        </div>
                        <div class="edit-field">
                            <label for="max_amount">*Importe máximo:</label>
                            <input type="number" id="max_amount" name="max_amount" value="<?php echo htmlspecialchars($userMaxAmount); ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="button">Actualizar</button>
                </form>
            </div>
        </div>
        <div class="main-footer">
            <a href="./../administracion.php"><button class="button">Volver</button></a>
        </div>
    </section>
</body>
</html>