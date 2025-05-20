<?php
// Conexión a la base de datos
$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Consulta para obtener los datos
$query = "SELECT currency, date_reg, EURXVAL FROM eur_values_hist ORDER BY date_reg ASC";
$result = $conn->query($query);

if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

// Organizar los datos por moneda
$data = [];
while ($row = $result->fetch_assoc()) {
    $currency = $row['currency'];
    if (!isset($data[$currency])) {
        $data[$currency] = [
            'dates' => [],
            'values' => []
        ];
    }
    $data[$currency]['dates'][] = $row['date_reg'];
    $data[$currency]['values'][] = (float)$row['EURXVAL'];
}

// Cerrar la conexión
$result->free();
$conn->close();

// Devolver los datos en formato JSON
header('Content-Type: application/json');
echo json_encode($data);
?>