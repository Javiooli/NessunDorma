<?php
include './append_to_audit_log.php';
include './update_balance.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Establecer la zona horaria
date_default_timezone_set('Europe/Madrid');
$date = date('m/d/Y h:i:s a', time());

// Verificar si la fecha es válida
echo("Fecha de consulta: $date\n");


// URL de la API
/*
Histórico ayer
https://api.metalpriceapi.com/v1/yesterday?api_key=f56612e3a881d7d4c99340decd0b6c0c&base=EUR&currencies=USD,XAU,XAG,BTC,ETH

Últimos valores
https://api.metalpriceapi.com/v1/latest?api_key=f56612e3a881d7d4c99340decd0b6c0c&base=EUR&currencies=USD,XAU,XAG,BTC,ETH

Franja de tiempo
https://api.metalpriceapi.com/v1/timeframe?api_key=f56612e3a881d7d4c99340decd0b6c0c&start_date=2021-04-22&end_date=yesterday&base=EUR&currencies=USD,XAU,XAG,BTC,ETH
*/

// insertar datos antiguos para triggerear alertas
//$api_url_eur = "https://api.metalpriceapi.com/v1/2025-05-09?api_key=f56612e3a881d7d4c99340decd0b6c0c&base=EUR&currencies=USD,XAU,XAG,BTC,ETH";
//$api_url_usd = "https://api.metalpriceapi.com/v1/2025-05-09?api_key=f56612e3a881d7d4c99340decd0b6c0c&base=USD&currencies=EUR,XAU,XAG,BTC,ETH";


$api_url_eur = "https://api.metalpriceapi.com/v1/latest?api_key=f56612e3a881d7d4c99340decd0b6c0c&base=EUR&currencies=USD,XAU,XAG,BTC,ETH";
$api_url_usd = "https://api.metalpriceapi.com/v1/latest?api_key=f56612e3a881d7d4c99340decd0b6c0c&base=USD&currencies=EUR,XAU,XAG,BTC,ETH";


// Hacer la solicitud a la API usando cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url_eur);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

// Verificar si la solicitud fue exitosa
if ($response === false) {
    $response = appendToAuditLog(null, null, "APIFail", "API: No se ha podido acceder a la API.");
    echo json_encode($response);
    die("Error al acceder a la API: " . $curl_error);
}

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Verificar si la decodificación fue exitosa y si la API tuvo éxito
if (json_last_error() !== JSON_ERROR_NONE || !$data['success']) {
    $response = appendToAuditLog(null, null, "APIFail", "API: No se han podido recoger los datos de EUR de la API.");
    echo json_encode($response);
    die("Error al decodificar JSON o API fallida: " . json_last_error_msg());
}

// Extraer las tasas de cambio
$rates = $data['rates'];

// Monedas que nos interesan
$monedas = [
    'BTC' => ['VALXEUR' => $rates['BTC'], 'EURXVAL' => $rates['EURBTC']],
    'ETH' => ['VALXEUR' => $rates['ETH'], 'EURXVAL' => $rates['EURETH']],
    'USD' => ['VALXEUR' => $rates['USD'], 'EURXVAL' => $rates['EURUSD']],
    'XAG' => ['VALXEUR' => $rates['XAG'], 'EURXVAL' => $rates['EURXAG']],
    'XAU' => ['VALXEUR' => $rates['XAU'], 'EURXVAL' => $rates['EURXAU']]
];

// Preparar la consulta SQL para insertar datos
$sql = "INSERT INTO eur_values_hist (currency, VALXEUR, EURXVAL) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}

// Fetch the most recent data for each currency from the previous day
$previousDaySql = "
    SELECT currency, VALXEUR, EURXVAL 
    FROM eur_values_hist 
    WHERE DATE(date_reg) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$previousDayResult = $conn->query($previousDaySql);

if (!$previousDayResult) {
    die("Error al obtener los datos del día anterior: " . $conn->error);
}

// Store previous day's data in an associative array
$previousDayData = [];
while ($row = $previousDayResult->fetch_assoc()) {
    $previousDayData[$row['currency']] = [
        'VALXEUR' => $row['VALXEUR'],
        'EURXVAL' => $row['EURXVAL']
    ];
}

// Check for 5% changes
foreach ($monedas as $currency => $valores) {
    $currentValXEUR = $valores['VALXEUR'];
    $currentEURXVal = $valores['EURXVAL'];

    if (isset($previousDayData[$currency])) {
        $previousValXEUR = $previousDayData[$currency]['VALXEUR'];
        $previousEURXVal = $previousDayData[$currency]['EURXVAL'];

        // Calculate percentage changes
        $changeValXEUR = (($currentValXEUR - $previousValXEUR) / $previousValXEUR) * 100;
        $changeEURXVal = (($currentEURXVal - $previousEURXVal) / $previousEURXVal) * 100;

        if (abs($changeValXEUR) >= 5 || abs($changeEURXVal) >= 5) {
            // Check the user_alerts table for active and pending alerts for the current currency
            $updateAlertSql = "
                UPDATE user_alerts 
                SET pending = 1 
                WHERE currency = ? AND active = 1";
            $updateStmt = $conn->prepare($updateAlertSql);
            if (!$updateStmt) {
                die("Error al preparar la consulta de actualización: " . $conn->error);
            }

            $updateStmt->bind_param("s", $currency);
            if (!$updateStmt->execute()) {
                echo "Error al actualizar alertas para $currency: " . $updateStmt->error . "\n";
            } else {
                echo "Alertas activas para $currency marcadas como pendientes.\n";
            }

            $updateStmt->close();
        }   
    } else {
        echo "No hay datos del día anterior para $currency.\n";
    }
}

// Insertar los datos de cada moneda

foreach ($monedas as $currency => $valores) {
    $val_x_eur = number_format($valores['VALXEUR'], 9, '.', '');
    $eur_x_val = number_format($valores['EURXVAL'], 9, '.', '');
    
    $stmt->bind_param("sdd", $currency, $val_x_eur, $eur_x_val);
    if (!$stmt->execute()) {
        echo "Error al insertar $currency: " . $stmt->error . "\n";
    } else {
        echo "Datos de $currency guardados correctamente.\n";
    }
}

// Obtener datos en base a USD

// Hacer la solicitud a la API usando cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url_usd);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

// Verificar si la solicitud fue exitosa
if ($response === false) {
    $auditionResponse = appendToAuditLog(null, null, "APIFail", "API: No se han podido recoger los datos de EUR de la API.");
    echo json_encode($auditionResponse);
    die("Error al acceder a la API: " . $curl_error);
}

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Verificar si la decodificación fue exitosa y si la API tuvo éxito
if (json_last_error() !== JSON_ERROR_NONE || !$data['success']) {
    die("Error al decodificar JSON o API fallida: " . json_last_error_msg());
}

// Extraer las tasas de cambio
$rates = $data['rates'];

// Monedas que nos interesan
$monedas = [
    'BTC' => ['VALXUSD' => $rates['BTC'], 'USDXVAL' => $rates['USDBTC']],
    'ETH' => ['VALXUSD' => $rates['ETH'], 'USDXVAL' => $rates['USDETH']],
    'EUR' => ['VALXUSD' => $rates['EUR'], 'USDXVAL' => $rates['USDEUR']],
    'XAG' => ['VALXUSD' => $rates['XAG'], 'USDXVAL' => $rates['USDXAG']],
    'XAU' => ['VALXUSD' => $rates['XAU'], 'USDXVAL' => $rates['USDXAU']]
];

// Preparar la consulta SQL para insertar datos
$sql = "INSERT INTO usd_values_hist (currency, VALXUSD, USDXVAL) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}

$response = appendToAuditLog(null, null, "API", "API: Se han registrado los datos correctamente.");
echo json_encode($response);

// Fetch the most recent data for each currency from the previous day
$previousDaySql = "
    SELECT currency, VALXUSD, USDXVAL 
    FROM usd_values_hist 
    WHERE DATE(date_reg) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$previousDayResult = $conn->query($previousDaySql);

if (!$previousDayResult) {
    die("Error al obtener los datos del día anterior: " . $conn->error);
}

// Store previous day's data in an associative array
$previousDayData = [];
while ($row = $previousDayResult->fetch_assoc()) {
    $previousDayData[$row['currency']] = [
        'VALXUSD' => $row['VALXUSD'],
        'USDXVAL' => $row['USDXVAL']
    ];
}

// Check for 5% changes
foreach ($monedas as $currency => $valores) {
    $currentValXUSD = $valores['VALXUSD'];
    $currentUSDXVal = $valores['USDXVAL'];

    if (isset($previousDayData[$currency])) {
        $previousValXUSD = $previousDayData[$currency]['VALXUSD'];
        $previousUSDXVal = $previousDayData[$currency]['USDXVAL'];

        // Calculate percentage changes
        $changeValXUSD = (($currentValXUSD - $previousValXUSD) / $previousValXUSD) * 100;
        $changeUSDXVal = (($currentUSDXVal - $previousUSDXVal) / $previousUSDXVal) * 100;

        if (abs($changeValXUSD) >= 5 || abs($changeUSDXVal) >= 5) {

            $updateAlertSql = "
                UPDATE user_alerts 
                SET pending = 1 
                WHERE currency = ? AND active = 1";
            $updateStmt = $conn->prepare($updateAlertSql);
            if (!$updateStmt) {
                die("Error al preparar la consulta de actualización: " . $conn->error);
            }

            $updateStmt->bind_param("s", $currency);
            if (!$updateStmt->execute()) {
                echo "Error al actualizar alertas para $currency: " . $updateStmt->error . "\n";
            } else {
                echo "Alertas activas para $currency marcadas como pendientes.\n";
            }

            # ENVIAMOS EMAIL A LOS USUARIOS CON MUTT
            $userQuery = "SELECT * FROM Users u, user_alerts ua 
                            WHERE u.userId = ua.userId AND ua.pending = 1";
            $userResult = $conn->query($userQuery);
            if ($userResult->num_rows > 0) {
                while ($userRow = $userResult->fetch_assoc()) {
                    $email = $userRow['email'];
                    $ip_file = "/home/david/Scripts/.last_ipv4";
                    $server_ip = trim(file_get_contents($ip_file));

                    // Generar el contenido HTML del correo
                    $htmlMessage = <<<HTML
                    <!DOCTYPE html>
                    <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Verifica tu nueva IP</title>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                background-color: #181818;
                                margin: 0;
                                padding: 20px;
                            }
                            .container {
                                max-width: 600px;
                                margin: auto;
                                background-color: #2e2e2e;
                                padding: 20px;
                                border-radius: 10px;
                                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
                            }
                            h1 {
                                color: #30d695;
                                font-size: 24px;
                                margin-bottom: 20px;
                            }
                            p {
                                color: #ffffff;
                                line-height: 1.6;
                            }
                            .verification-code {
                                font-size: 18px;
                                font-weight: bold;
                                color: #30d695;
                            }
                            a {
                                display: inline-block;
                                background-color: #30d695;
                                color: #ffffff;
                                text-align: center;
                                padding: 10px 20px;
                                font-size: 16px;
                                border-radius: 5px;
                                text-decoration: none;
                                transition: background-color 300ms ease, transform 200ms ease;
                            }
                            a:hover {
                                background-color: #269c6f;
                            }
                            .footer {
                                font-size: 12px;
                                color: #888;
                                margin-top: 20px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h1>¡Variación de valor de {$currency}!</h1>
                            <p>El valor ha cambiado un <span class="verification-code">" . number_format($changeValXUSD, 2) . "%</span> respecto al día anterior.</p>
                            <p>Para más información, visita el siguiente enlace:</p>
                            <a href="http://{$server_ip}:4081/Nessun_Dorma/mercado.php">Más Información</a>
                            <p>Si no fuiste tú, contacta con nuestro equipo de soporte inmediatamente.</p>
                            <div class="footer">
                                <p>Saludos,<br>El equipo de Nessun Dorma</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    HTML;

                    // Guardar en archivo temporal
                    $temp_file = tempnam(sys_get_temp_dir(), 'CurrencyNotification_');
                    file_put_contents($temp_file, $htmlMessage);

                    // Comando para enviar como HTML usando mutt
                    $command = "mutt -F /var/www/.muttrc -e 'set content_type=text/html' -s 'Variación de valor de $currency' {$email} < $temp_file";

                    shell_exec($command);
                    unlink($temp_file);
                    $updateStmt->close();
                }
            }
        }
    } else {
        echo "No hay datos del día anterior para $currency.\n";
    }
}

// Insertar los datos de cada moneda

foreach ($monedas as $currency => $valores) {
    $val_x_usd = number_format($valores['VALXUSD'], 9, '.', '');
    $usd_x_val = number_format($valores['USDXVAL'], 9, '.', '');
    
    $stmt->bind_param("sdd", $currency, $val_x_usd, $usd_x_val);
    if (!$stmt->execute()) {
        echo "Error al insertar $currency: " . $stmt->error . "\n";
    } else {
        echo "Datos de $currency guardados correctamente.\n";
    }
}
// Actualizar el balance para cada usuario
$userQuery = "SELECT userId, default_currency FROM Users";
$userResult = $conn->query($userQuery);

if ($userResult->num_rows > 0) {
    while ($userRow = $userResult->fetch_assoc()) {
        $userId = $userRow['userId'];
        $defaultCurrency = $userRow['default_currency'];

        // Llamar a la función updateBalance
        updateBalance($userId, $defaultCurrency);
    }
} else {
    echo "No se encontraron usuarios para actualizar el balance.\n";
}



// Cerrar la conexión
$stmt->close();
$conn->close();
?>