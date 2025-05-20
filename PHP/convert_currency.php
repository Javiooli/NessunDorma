<?php
// filepath: /run/user/1000/gvfs/sftp:host=79.158.88.180,port=4022,user=javi/Nessun_Dorma/PHP/convert_currency.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}

function convertCurrency($currency, $date, $amount, $defaultCurrency) {
    global $conn;

    if (!strtotime($date)) $date = date('Y-m-d');
    if ($defaultCurrency != "EUR" && $defaultCurrency != "USD") $defaultCurrency = 'EUR';

    $table = strtolower($defaultCurrency) . "_values_hist";
    $column = strtoupper($defaultCurrency) . "XVAL";

    $sql = "SELECT $column FROM $table WHERE currency = ? AND date_reg = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $currency, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $exchange = $row[$column] ?? null;
    $stmt->close();

    if ($exchange === null) {
        $exchange = 1;
    } 

    $convertedAmount = $amount * $exchange;
    return ['convertedAmount' => $convertedAmount, 'defaultCurrency' => $defaultCurrency];
}

// Handle the incoming request
if (isset($_GET['amount'], $_GET['currency'], $_GET['defaultCurrency'])) {
    $amount = (float)$_GET['amount'];
    $currency = $_GET['currency'];
    $defaultCurrency = $_GET['defaultCurrency'];
    $date = $_GET['date'] ?? date('Y-m-d');

    $result = convertCurrency($currency, $date, $amount, $defaultCurrency);
    echo json_encode($result);
}

$conn->close();
?>