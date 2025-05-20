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
//RECOGEMOS DATOS DE USUARIO
$userId = $_SESSION["userId"];
$firstName = $_SESSION["firstName"];
$rol = $_SESSION["rol"];
$currency = $_SESSION["currency"];
$clientIP = $_SESSION["clientIP"];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $filter = isset($_POST['filter']) ? $_POST['filter'] : 'default';
    $sort = isset($_POST['sort']) ? $_POST['sort'] : 'date';
    $sortDirection = isset($_POST['sortDirection']) ? ($_POST['sortDirection']) : 'DESC';

    $clientsQuery = "SELECT * FROM Users WHERE userId = $userId";
    $clientsResult = $conn->query($clientsQuery);
    if ($clientsResult->num_rows > 0) {
        while ($row = $clientsResult->fetch_assoc()) {
            $client1Id = $row["client1Id"] ? $row["client1Id"] : 0;
            $client2Id = $row["client2Id"] ? $row["client2Id"] : 0;
            $client3Id = $row["client3Id"] ? $row["client3Id"] : 0;
        }
    }
    
    // Definir la consulta según el filtro
    $audQuery = "SELECT * FROM auditoria a, ipTable i, Users u WHERE a.userId = i.userId AND a.ipId = i.id AND a.userId = u.userId";
    if ($filter === "default") {
        $audQuery .= " AND DATE(a.date) = CURDATE()";
    } elseif ($filter === "myClients") {
        $audQuery .= " AND (a.userId = $client1Id OR a.userId = $client2Id OR a.userId = $client3Id)";
    } elseif ($filter === "all") {
        // Sin WHERE para mostrar todos los registros
    }

    $audQuery .= " ORDER BY $sort $sortDirection";
    $audResult = $conn->query($audQuery);
    if ($audResult->num_rows > 0){
        while ($row = $audResult->fetch_assoc()){
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
}
die();
$conn->close();
exit();
?>