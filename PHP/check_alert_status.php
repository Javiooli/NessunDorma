<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => "Database connection failed: " . $conn->connect_error]));
}

// Check if userId and currency are provided
if (isset($_GET['userId']) && isset($_GET['currency'])) {
    $userId = (int)$_GET['userId'];
    $currency = trim($_GET['currency']);

    // Prepare the SQL query to check the alert status
    $sql = "SELECT active FROM user_alerts WHERE userId = ? AND currency = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die(json_encode(['error' => "Failed to prepare statement: " . $conn->error]));
    }

    $stmt->bind_param("is", $userId, $currency);
    $stmt->execute();
    $stmt->bind_result($active);
    $stmt->fetch();
    $stmt->close();

    // Return the alert status as JSON
    echo json_encode(['active' => $active]);
} else {
    // Return an error if required parameters are missing
    echo json_encode(['error' => "Missing userId or currency parameter"]);
}

$conn->close();
?>