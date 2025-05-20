<?php
# INICIAMOS UNA SESIÓN Y CONECTAMOS A LA DB
session_start();

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

# DECLARAMOS VARIABLE DE ERROR
$error = "";

# VERIFICAMOS QUE EXISTA LA SESIÓN DE VERIFICACIÓN
if (!isset($_SESSION["pending_verification"])) {
    header("Location: login.php");
    exit();
}

# OBTENEMOS DATOS DE LA SESIÓN
$userId = $_SESSION["pending_verification"]["userId"];
$email = $_SESSION["pending_verification"]["email"];
$client_ip = $_SESSION["pending_verification"]["ip"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    # OBTENEMOS EL CÓDIGO DEL FORMULARIO
    $code = trim($_POST["code"]);

    # VALIDAMOS EL CÓDIGO EN ipTable
    $sql = "SELECT verifyIp FROM ipTable WHERE userId = ? AND ip = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $client_ip);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if ($row["verifyIp"] == $code) {
            # CÓDIGO CORRECTO: MARCAR IP COMO VERIFICADA
            $updateSql = "UPDATE ipTable SET verified = 1, verifyIp = NULL, lastuse = NOW() WHERE userId = ? AND ip = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("is", $userId, $client_ip);
            $updateStmt->execute();

            # OBTENEMOS DATOS DEL USUARIO
            $userSql = "SELECT * FROM Users WHERE userId = ?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param("i", $userId);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $userRow = $userResult->fetch_assoc();

            # CONTINUAR CON LA LÓGICA DE LOGIN
            if ($userRow["verified"] == 0) {
                header("Location: ./../esperar_verificacion.html");
                exit();
            } else if ($userRow["verified"] == 1) {
                if (!empty($userRow["otp_code"])) {
                    # GUARDAMOS EL USUARIO TEMPORALMENTE HASTA VERIFICAR OTP
                    $_SESSION["pendingUser"] = $userRow;
                    $_SESSION["clientIP"] = $client_ip;
                    header("Location: verificar_otp.php");
                    exit();
                } else {
                    # USUARIO SIN 2FA
                    $_SESSION["userId"] = $userRow["userId"];
                    $_SESSION["username"] = $userRow["username"];
                    $_SESSION["email"] = $userRow["email"];
                    $_SESSION["rol"] = $userRow["rol"];
                    $_SESSION["firstName"] = $userRow["firstName"];
                    $_SESSION["currency"] = $userRow["default_currency"];
                    $_SESSION["clientIP"] = $client_ip;
                    unset($_SESSION["pending_verification"]);
                    header("Location: ./../home.php");
                    exit();
                }
            }
        } else {
            $error = "Código de verificación incorrecto.";
        }
    } else {
        $error = "Error al verificar la IP. Intenta de nuevo.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="./../img/icon.png">
    <link rel="stylesheet" href="./../css/login.css">
    <title>Nessun Dorma - Verificar IP</title>
</head>
<div id="background"></div>
<body class='login-page'>
    <div class="form-container">
        <img src="./../img/icon.png" class="logo">
        <h1 class="title">Verificar tu IP</h1>
        <p style="width:360px;">Ingresa el código de verificación enviado a: <?php echo htmlspecialchars($email); ?>.</p>
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <img class="alert-circle" src="./../img/alert-circle.png"> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <hr>
        <form class="login-form" action="verify_ip.php" method="POST">
            <div class="inf-group">
                <div class="information">
                    <div class="label-group">
                        <label>Código de verificación</label>
                    </div>
                    <input type="text" name="code" placeholder="Código de 6 dígitos" required>
                </div>
            </div>
            <div class="login-button-container">
                <button class="login-button" type="submit"><span>Verificar</span></button>
            </div>
        </form>
    </div>
</body>
</html>