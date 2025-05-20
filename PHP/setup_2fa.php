<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../login/scripts/GoogleAuthenticator.php';

$ga = new PHPGangsta_GoogleAuthenticator();
$userId = (int)($_SESSION['userId'] ?? 1); // Asegura que sea un entero
$username = $_SESSION['username'] ?? 'defaultUser'; // Usa un nombre por defecto si no está en sesión
$secret = $ga->createSecret(); // Genera una clave secreta

// Conexión a la base de datos
$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Guardar la clave secreta en la base de datos
$stmt = $conn->prepare("UPDATE Users SET otp_code = ? WHERE userId = ?");
$stmt->bind_param("si", $secret, $userId);
if (!$stmt->execute()) {
    die("Error al guardar la clave secreta: " . $stmt->error);
}
$stmt->close();
$conn->close();

// Generar la URL TOTP para el QR
$issuer = 'Nessun_Dorma';
$url = "otpauth://totp/" . urlencode("$issuer:$username") . "?secret=$secret&issuer=" . urlencode($issuer);

// Enviar respuesta JSON con claves significativas
echo json_encode([
    'secret' => $secret,
    'url' => $url
]);
?>