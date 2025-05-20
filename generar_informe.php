<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// CONEXIÓN BD
$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";
$conn = new mysqli($host, $user, $password, $dbname);


// SESIÓN
session_start();


$userId = $_SESSION["userId"];

// CONSULTA DATOS DEL USUARIO ACTUAL
$sql = "SELECT firstName, lastName1, lastName2, nif, birthDate, country FROM Users WHERE userId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();



$user = $result->fetch_assoc();
$nombreCompleto = "{$user['firstName']} {$user['lastName1']} {$user['lastName2']}";
$nif = $user['nif'];
$birthDate = $user['birthDate'];
$country = $user['country'];
$fecha = date("d/m/Y");

// GENERAR HTML PARA EL PDF
$html = "
<!DOCTYPE html>
<html lang='es'>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: DejaVu Sans, sans-serif; }
    h1 { text-align: center; }
    p { font-size: 14px; }
    .footer { margin-top: 40px; font-size: 12px; text-align: center; color: #555; }
  </style>
</head>
<body>
  <h1>Informe – NESSUN DORMA</h1>
  <p><strong>Nombre completo:</strong> $nombreCompleto</p>
  <p><strong>NIF:</strong> $nif</p>
  <p><strong>País:</strong> $country</p>
  <p><strong>Fecha de nacimiento:</strong> $birthDate</p>
  <p><strong>Fecha de generación:</strong> $fecha</p>
  <p style='margin-top:30px;'><em>Actualmente no hay movimientos disponibles.</em></p>
  <div class='footer'>
    <p>Informe generado automáticamente desde la plataforma.</p>
  </div>
</body>
</html>
";

// GENERAR Y ENVIAR PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("informe_fiscal.pdf", ["Attachment" => true]);
?>

