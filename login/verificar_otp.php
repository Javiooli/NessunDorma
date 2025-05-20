<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/scripts/GoogleAuthenticator.php';

use PHPGangsta_GoogleAuthenticator;

if (!isset($_SESSION["pendingUser"])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION["pendingUser"];
$secret = $user["otp_code"];
$clientIP = $_SESSION["clientIP"];
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST["otp"];
    $ga = new PHPGangsta_GoogleAuthenticator();
    $checkResult = $ga->verifyCode($secret, $otp, 2); // 2 = margen de 2 pasos de 30s

    if ($checkResult) {
        // OTP válido ⇒ sesión completa
        $_SESSION["userId"] = $user["userId"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["rol"] = $user["rol"];
        $_SESSION["firstName"] = $user["firstName"];
        $_SESSION["currency"] = $user["default_currency"];
        $clientIP = $_SESSION["clientIP"];
        unset($_SESSION["pendingUser"]);

        header("Location: ../home.php");
        exit();
    } else {
        $error = "Código 2FA incorrecto";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación 2FA</title>
    <link rel="stylesheet" href="./../css/login.css">
</head>
<body>
<div id="background"></div>
    <div class="form-container">
        <h1 class="title">Verificación en dos pasos</h1>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="verificar_otp.php" method="POST">
            <label for="otp">Introduce el código 2FA:</label>
            <input type="text" name="otp" required>
            <button type="submit">Verificar</button>
        </form>
    </div>
</body>
</html>
