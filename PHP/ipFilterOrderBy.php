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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $filter = isset($_POST['filter']) ? $_POST['filter'] : 'default';
    $sort = isset($_POST['sort']) ? $_POST['sort'] : 'lastUse';
    $sortDirection = isset($_POST['sortDirection']) ? ($_POST['sortDirection']) : 'DESC';

    // Definir la consulta según el filtro
    $ipQuery = "SELECT * FROM ipTable";
    if ($filter === "default") {
        $ipQuery .= " WHERE DATE(lastUse) = CURDATE()";
    } elseif ($filter === "verified") {
        $ipQuery .= " WHERE verified = 1";
    } elseif ($filter === "unverified") {
        $ipQuery .= " WHERE verified = 0";
    } elseif ($filter === "all") {
        // Sin WHERE para mostrar todos los registros
    } else {
        $ipQuery .= " WHERE DATE(lastUse) = CURDATE()"; // Fallback
    }

    $ipQuery .= " ORDER BY $sort $sortDirection";

    $ipResult = $conn->query($ipQuery);
    if ($ipResult->num_rows > 0) {
        while ($row = $ipResult->fetch_assoc()) {
            $id = htmlspecialchars($row["id"]);
            $userShowId = htmlspecialchars($row["userId"]);
            $ip = htmlspecialchars($row["ip"]);
            $verified = $row["verified"];
            $country = htmlspecialchars($row["country"]);
            $city = htmlspecialchars($row["city"]);
            $firstUse = htmlspecialchars($row["firstUse"]);
            $lastUse = htmlspecialchars($row["lastUse"]);
            echo "<tr>
                    <td>$id</td>
                    <td>$userShowId</td>
                    <td style='text-align: center;'>$ip</td>";
            if ($verified == 1) {
                echo "<td style='text-align: center;'><input type='checkbox' checked disabled></td>";
            } else {
                echo "<td style='text-align: center;'><input type='checkbox' disabled></td>";
            }
            echo    "<td>$country</td>
                    <td>$city</td>
                    <td>$firstUse</td>
                    <td>$lastUse</td></tr>";
        }
    } else {
        echo "<tr><td colspan='8' style='text-align:center;'>No hay conexiones disponibles.</td></tr>";
    }
}

$conn->close();
exit();
?>