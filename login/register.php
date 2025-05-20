<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

# CONECTAMOS A LA DB
$host = "localhost";
$dbname = "Nessun_DormaDB";
$user = "root";
$password = "";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

# FUNCIÓN PARA OBTENER LA IP DEL CLIENTE
function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

# DECLARAMOS VARIABLES
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    # OBTENEMOS LOS DATOS DEL FORMULARIO
    $username = $_POST["username"];
    $email = $_POST["email"];
    $passw0rd = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $first_name = $_POST["first_name"];
    $last_name1 = $_POST["last_name1"];
    $last_name2 = isset($_POST["last_name2"]) && $_POST["last_name2"] !== '' ? $_POST["last_name2"] : ''; //posible error, no aceptra null
    $birth_date = $_POST["birth_date"];
    $rol = "Client";
    $country = $_POST["country"];
    $nif = $_POST["nif"];
    $currency = $_POST["currency"];

    # VERIFICAMOS SI LAS CONTRASEÑAS COINCIDEN
    if ($passw0rd !== $confirm_password) {
        $error = "Las contraseñas no coinciden. Inténtalo de nuevo.";
    } else {
        # HASHEAMOS LA CONTRASEÑA
        $password_hashed = password_hash($passw0rd, PASSWORD_DEFAULT);

        # VERIFICAMOS SI EL USUARIO O EL CORREO YA ESTÁN EN USO
        $checkUserSql = "SELECT * FROM Users WHERE username = ?";
        $stmt = $conn->prepare($checkUserSql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $checkUserResult = $stmt->get_result();

        $checkEmailSql = "SELECT * FROM Users WHERE email = ?";
        $stmt = $conn->prepare($checkEmailSql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $checkEmailResult = $stmt->get_result();

        $stmt->close();

        if ($checkUserResult->num_rows > 0) {
            $error = "El nombre de usuario ya está en uso. Por favor, elija otro.";
        } elseif ($checkEmailResult->num_rows > 0) {
            $error = "Esta dirección de correo electrónico ya está en uso. Por favor, elija otra.";
        } else {
            # REGISTRAMOS EL NUEVO USUARIO
            $sql = "INSERT INTO Users (username, email, passw0rd, rol, firstName, lastName1, lastName2, birthDate, country, nif, default_currency) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";;
            $stmt = $conn->prepare($sql);
             if (!$stmt) {
                die("Error al preparar la sentencia: " . $conn->error);
            }
            $stmt->bind_param("sssssssssss", $username, $email, $password_hashed, $rol, $first_name, $last_name1, $last_name2, $birth_date, $country, $nif, $currency);

            if ($stmt->execute()) {
                # OBTENEMOS EL ID DEL USUARIO REGISTRADO
                $userId = $conn->insert_id;

                # OBTENEMOS LA IP Y VALIDAMOS
                $client_ip = get_client_ip();
                if ($client_ip === 'UNKNOWN' || strpos($client_ip, '192.168.') === 0 || strpos($client_ip, '10.') === 0 || strpos($client_ip, '172.') === 0) {
                    $error = "No se pudo obtener una IP pública válida.";
                } else {
                    # OBTENEMOS PAÍS Y CIUDAD CON ip-api.com
                    $geo_url = "http://ip-api.com/json/{$client_ip}";
                    $geo_data = @file_get_contents($geo_url);
                    $country = null;
                    $city = null;
                    if ($geo_data !== false) {
                        $geo_json = json_decode($geo_data, true);
                        if (isset($geo_json['status']) && $geo_json['status'] === 'success') {
                            $country = $geo_json['country'] ?? null;
                            $city = $geo_json['city'] ?? null;
                        }
                    }

                    # REGISTRAMOS LA IP EN ipTable
                    $verified = 1; // Verificada por ser la IP del registro
                    $ipSql = "INSERT INTO ipTable (userId, ip, verified, verifyIp, lastuse, firstuse, country, city) VALUES (?, ?, ?, NULL, NOW(), NOW(), ?, ?)";
                    $ipStmt = $conn->prepare($ipSql);
                    $ipStmt->bind_param("isiss", $userId, $client_ip, $verified, $country, $city);
                    if ($ipStmt->execute()) {
                        $message = "Registro realizado correctamente";
                        header("Location: ./login.php");
                        exit();
                    } else {
                        $error = "Error al registrar la IP: " . $conn->error;
                    }
                }
            } else {
                $error = "Error al registrar el usuario: " . $conn->error;
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./../css/registro.css">
    <link rel="shortcut icon" href="./../img/icon.png">
    <title>Nessun Dorma - Registro</title>
</head>
<div id="background"></div>
<body class='register-page'>
    <div class="form-container">
        <img src="./../img/icon.png" class="logo">
        <h1 class="title">Registrarse en Nessun</h1>
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <img class="alert-circle" src="./../img/alert-circle.png"> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <hr>
        <form class="login-form" action="register.php" method="POST">
            <div class="form-content">
                <div class="inf-group">
                    <div class="information">
                        <div class="label-group">
                            <label>Nombre de usuario*</label>
                        </div>
                        <input type="text" name="username" placeholder="Nombre de usuario" required>
                    </div>
                    <div class="information">
                        <div class="label-group">
                            <label>Correo electrónico*</label>
                        </div>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="information">
                        <div class="label-group">
                            <label>Contraseña*</label>
                        </div>
                        <div class="password-container">
                            <input type="password" name="password" id="password" placeholder="Contraseña" required>
                            <div class="eye-icon-container">
                                    <svg id="eye" class="eye-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M12 8.25C9.92893 8.25 8.25 9.92893 8.25 12C8.25 14.0711 9.92893 15.75 12 15.75C14.0711 15.75 15.75 14.0711 15.75 12C15.75 9.92893 14.0711 8.25 12 8.25ZM9.75 12C9.75 10.7574 10.7574 9.75 12 9.75C13.2426 9.75 14.25 10.7574 14.25 12C14.25 13.2426 13.2426 14.25 12 14.25C10.7574 14.25 9.75 13.2426 9.75 12Z"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.25C7.48587 3.25 4.44529 5.9542 2.68057 8.24686L2.64874 8.2882C2.24964 8.80653 1.88206 9.28392 1.63269 9.8484C1.36564 10.4529 1.25 11.1117 1.25 12C1.25 12.8883 1.36564 13.5471 1.63269 14.1516C1.88206 14.7161 2.24964 15.1935 2.64875 15.7118L2.68057 15.7531C4.44529 18.0458 7.48587 20.75 12 20.75C16.5141 20.75 19.5547 18.0458 21.3194 15.7531L21.3512 15.7118C21.7504 15.1935 22.1179 14.7161 22.3673 14.1516C22.6344 13.5471 22.75 12.8883 22.75 12C22.75 11.1117 22.6344 10.4529 22.3673 9.8484C22.1179 9.28391 21.7504 8.80652 21.3512 8.28818L21.3194 8.24686C19.5547 5.9542 16.5141 3.25 12 3.25ZM3.86922 9.1618C5.49864 7.04492 8.15036 4.75 12 4.75C15.8496 4.75 18.5014 7.04492 20.1308 9.1618C20.5694 9.73159 20.8263 10.0721 20.9952 10.4545C21.1532 10.812 21.25 11.2489 21.25 12C21.25 12.7511 21.1532 13.188 20.9952 13.5455C20.8263 13.9279 20.5694 14.2684 20.1308 14.8382C18.5014 16.9551 15.8496 19.25 12 19.25C8.15036 19.25 5.49864 16.9551 3.86922 14.8382C3.43064 14.2684 3.17374 13.9279 3.00476 13.5455C2.84684 13.188 2.75 12.7511 2.75 12C2.75 11.2489 2.84684 10.812 3.00476 10.4545C3.17374 10.0721 3.43063 9.73159 3.86922 9.1618Z"></path> </g></svg>
                                    <svg id="eye-closed" class="eye-icon-closed" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2.68936 6.70456C2.52619 6.32384 2.08528 6.14747 1.70456 6.31064C1.32384 6.47381 1.14747 6.91472 1.31064 7.29544L2.68936 6.70456ZM15.5872 13.3287L15.3125 12.6308L15.5872 13.3287ZM9.04145 13.7377C9.26736 13.3906 9.16904 12.926 8.82185 12.7001C8.47466 12.4742 8.01008 12.5725 7.78417 12.9197L9.04145 13.7377ZM6.37136 15.091C6.14545 15.4381 6.24377 15.9027 6.59096 16.1286C6.93815 16.3545 7.40273 16.2562 7.62864 15.909L6.37136 15.091ZM22.6894 7.29544C22.8525 6.91472 22.6762 6.47381 22.2954 6.31064C21.9147 6.14747 21.4738 6.32384 21.3106 6.70456L22.6894 7.29544ZM19 11.1288L18.4867 10.582V10.582L19 11.1288ZM19.9697 13.1592C20.2626 13.4521 20.7374 13.4521 21.0303 13.1592C21.3232 12.8663 21.3232 12.3914 21.0303 12.0985L19.9697 13.1592ZM11.25 16.5C11.25 16.9142 11.5858 17.25 12 17.25C12.4142 17.25 12.75 16.9142 12.75 16.5H11.25ZM16.3714 15.909C16.5973 16.2562 17.0619 16.3545 17.409 16.1286C17.7562 15.9027 17.8545 15.4381 17.6286 15.091L16.3714 15.909ZM5.53033 11.6592C5.82322 11.3663 5.82322 10.8914 5.53033 10.5985C5.23744 10.3056 4.76256 10.3056 4.46967 10.5985L5.53033 11.6592ZM2.96967 12.0985C2.67678 12.3914 2.67678 12.8663 2.96967 13.1592C3.26256 13.4521 3.73744 13.4521 4.03033 13.1592L2.96967 12.0985ZM12 13.25C8.77611 13.25 6.46133 11.6446 4.9246 9.98966C4.15645 9.16243 3.59325 8.33284 3.22259 7.71014C3.03769 7.3995 2.90187 7.14232 2.8134 6.96537C2.76919 6.87696 2.73689 6.80875 2.71627 6.76411C2.70597 6.7418 2.69859 6.7254 2.69411 6.71533C2.69187 6.7103 2.69036 6.70684 2.68957 6.70503C2.68917 6.70413 2.68896 6.70363 2.68892 6.70355C2.68891 6.70351 2.68893 6.70357 2.68901 6.70374C2.68904 6.70382 2.68913 6.70403 2.68915 6.70407C2.68925 6.7043 2.68936 6.70456 2 7C1.31064 7.29544 1.31077 7.29575 1.31092 7.29609C1.31098 7.29624 1.31114 7.2966 1.31127 7.2969C1.31152 7.29749 1.31183 7.2982 1.31218 7.299C1.31287 7.30062 1.31376 7.30266 1.31483 7.30512C1.31698 7.31003 1.31988 7.31662 1.32353 7.32483C1.33083 7.34125 1.34115 7.36415 1.35453 7.39311C1.38127 7.45102 1.42026 7.5332 1.47176 7.63619C1.57469 7.84206 1.72794 8.13175 1.93366 8.47736C2.34425 9.16716 2.96855 10.0876 3.8254 11.0103C5.53867 12.8554 8.22389 14.75 12 14.75V13.25ZM15.3125 12.6308C14.3421 13.0128 13.2417 13.25 12 13.25V14.75C13.4382 14.75 14.7246 14.4742 15.8619 14.0266L15.3125 12.6308ZM7.78417 12.9197L6.37136 15.091L7.62864 15.909L9.04145 13.7377L7.78417 12.9197ZM22 7C21.3106 6.70456 21.3107 6.70441 21.3108 6.70427C21.3108 6.70423 21.3108 6.7041 21.3109 6.70402C21.3109 6.70388 21.311 6.70376 21.311 6.70368C21.3111 6.70352 21.3111 6.70349 21.3111 6.7036C21.311 6.7038 21.3107 6.70452 21.3101 6.70576C21.309 6.70823 21.307 6.71275 21.3041 6.71924C21.2983 6.73223 21.2889 6.75309 21.2758 6.78125C21.2495 6.83757 21.2086 6.92295 21.1526 7.03267C21.0406 7.25227 20.869 7.56831 20.6354 7.9432C20.1669 8.69516 19.4563 9.67197 18.4867 10.582L19.5133 11.6757C20.6023 10.6535 21.3917 9.56587 21.9085 8.73646C22.1676 8.32068 22.36 7.9668 22.4889 7.71415C22.5533 7.58775 22.602 7.48643 22.6353 7.41507C22.6519 7.37939 22.6647 7.35118 22.6737 7.33104C22.6782 7.32097 22.6818 7.31292 22.6844 7.30696C22.6857 7.30398 22.6867 7.30153 22.6876 7.2996C22.688 7.29864 22.6883 7.29781 22.6886 7.29712C22.6888 7.29677 22.6889 7.29646 22.689 7.29618C22.6891 7.29604 22.6892 7.29585 22.6892 7.29578C22.6893 7.29561 22.6894 7.29544 22 7ZM18.4867 10.582C17.6277 11.3882 16.5739 12.1343 15.3125 12.6308L15.8619 14.0266C17.3355 13.4466 18.5466 12.583 19.5133 11.6757L18.4867 10.582ZM18.4697 11.6592L19.9697 13.1592L21.0303 12.0985L19.5303 10.5985L18.4697 11.6592ZM11.25 14V16.5H12.75V14H11.25ZM14.9586 13.7377L16.3714 15.909L17.6286 15.091L16.2158 12.9197L14.9586 13.7377ZM4.46967 10.5985L2.96967 12.0985L4.03033 13.1592L5.53033 11.6592L4.46967 10.5985Z"></path> </g></svg>
                            </div>
                        </div>
                    </div>
                    <div class="information">
                        <div class="label-group">
                            <label>Confirmar contraseña*</label>
                        </div>
                        <div class="password-container">
                            <input type="password" name="confirm_password" id="confirm-password" placeholder="Confirmar contraseña" required>
                            <div class="eye-icon-container">
                                    <svg id="eye-confirm" class="eye-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M12 8.25C9.92893 8.25 8.25 9.92893 8.25 12C8.25 14.0711 9.92893 15.75 12 15.75C14.0711 15.75 15.75 14.0711 15.75 12C15.75 9.92893 14.0711 8.25 12 8.25ZM9.75 12C9.75 10.7574 10.7574 9.75 12 9.75C13.2426 9.75 14.25 10.7574 14.25 12C14.25 13.2426 13.2426 14.25 12 14.25C10.7574 14.25 9.75 13.2426 9.75 12Z"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.25C7.48587 3.25 4.44529 5.9542 2.68057 8.24686L2.64874 8.2882C2.24964 8.80653 1.88206 9.28392 1.63269 9.8484C1.36564 10.4529 1.25 11.1117 1.25 12C1.25 12.8883 1.36564 13.5471 1.63269 14.1516C1.88206 14.7161 2.24964 15.1935 2.64875 15.7118L2.68057 15.7531C4.44529 18.0458 7.48587 20.75 12 20.75C16.5141 20.75 19.5547 18.0458 21.3194 15.7531L21.3512 15.7118C21.7504 15.1935 22.1179 14.7161 22.3673 14.1516C22.6344 13.5471 22.75 12.8883 22.75 12C22.75 11.1117 22.6344 10.4529 22.3673 9.8484C22.1179 9.28391 21.7504 8.80652 21.3512 8.28818L21.3194 8.24686C19.5547 5.9542 16.5141 3.25 12 3.25ZM3.86922 9.1618C5.49864 7.04492 8.15036 4.75 12 4.75C15.8496 4.75 18.5014 7.04492 20.1308 9.1618C20.5694 9.73159 20.8263 10.0721 20.9952 10.4545C21.1532 10.812 21.25 11.2489 21.25 12C21.25 12.7511 21.1532 13.188 20.9952 13.5455C20.8263 13.9279 20.5694 14.2684 20.1308 14.8382C18.5014 16.9551 15.8496 19.25 12 19.25C8.15036 19.25 5.49864 16.9551 3.86922 14.8382C3.43064 14.2684 3.17374 13.9279 3.00476 13.5455C2.84684 13.188 2.75 12.7511 2.75 12C2.75 11.2489 2.84684 10.812 3.00476 10.4545C3.17374 10.0721 3.43063 9.73159 3.86922 9.1618Z"></path> </g></svg>
                                    <svg id="eye-confirm-closed" class="eye-icon-closed" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2.68936 6.70456C2.52619 6.32384 2.08528 6.14747 1.70456 6.31064C1.32384 6.47381 1.14747 6.91472 1.31064 7.29544L2.68936 6.70456ZM15.5872 13.3287L15.3125 12.6308L15.5872 13.3287ZM9.04145 13.7377C9.26736 13.3906 9.16904 12.926 8.82185 12.7001C8.47466 12.4742 8.01008 12.5725 7.78417 12.9197L9.04145 13.7377ZM6.37136 15.091C6.14545 15.4381 6.24377 15.9027 6.59096 16.1286C6.93815 16.3545 7.40273 16.2562 7.62864 15.909L6.37136 15.091ZM22.6894 7.29544C22.8525 6.91472 22.6762 6.47381 22.2954 6.31064C21.9147 6.14747 21.4738 6.32384 21.3106 6.70456L22.6894 7.29544ZM19 11.1288L18.4867 10.582V10.582L19 11.1288ZM19.9697 13.1592C20.2626 13.4521 20.7374 13.4521 21.0303 13.1592C21.3232 12.8663 21.3232 12.3914 21.0303 12.0985L19.9697 13.1592ZM11.25 16.5C11.25 16.9142 11.5858 17.25 12 17.25C12.4142 17.25 12.75 16.9142 12.75 16.5H11.25ZM16.3714 15.909C16.5973 16.2562 17.0619 16.3545 17.409 16.1286C17.7562 15.9027 17.8545 15.4381 17.6286 15.091L16.3714 15.909ZM5.53033 11.6592C5.82322 11.3663 5.82322 10.8914 5.53033 10.5985C5.23744 10.3056 4.76256 10.3056 4.46967 10.5985L5.53033 11.6592ZM2.96967 12.0985C2.67678 12.3914 2.67678 12.8663 2.96967 13.1592C3.26256 13.4521 3.73744 13.4521 4.03033 13.1592L2.96967 12.0985ZM12 13.25C8.77611 13.25 6.46133 11.6446 4.9246 9.98966C4.15645 9.16243 3.59325 8.33284 3.22259 7.71014C3.03769 7.3995 2.90187 7.14232 2.8134 6.96537C2.76919 6.87696 2.73689 6.80875 2.71627 6.76411C2.70597 6.7418 2.69859 6.7254 2.69411 6.71533C2.69187 6.7103 2.69036 6.70684 2.68957 6.70503C2.68917 6.70413 2.68896 6.70363 2.68892 6.70355C2.68891 6.70351 2.68893 6.70357 2.68901 6.70374C2.68904 6.70382 2.68913 6.70403 2.68915 6.70407C2.68925 6.7043 2.68936 6.70456 2 7C1.31064 7.29544 1.31077 7.29575 1.31092 7.29609C1.31098 7.29624 1.31114 7.2966 1.31127 7.2969C1.31152 7.29749 1.31183 7.2982 1.31218 7.299C1.31287 7.30062 1.31376 7.30266 1.31483 7.30512C1.31698 7.31003 1.31988 7.31662 1.32353 7.32483C1.33083 7.34125 1.34115 7.36415 1.35453 7.39311C1.38127 7.45102 1.42026 7.5332 1.47176 7.63619C1.57469 7.84206 1.72794 8.13175 1.93366 8.47736C2.34425 9.16716 2.96855 10.0876 3.8254 11.0103C5.53867 12.8554 8.22389 14.75 12 14.75V13.25ZM15.3125 12.6308C14.3421 13.0128 13.2417 13.25 12 13.25V14.75C13.4382 14.75 14.7246 14.4742 15.8619 14.0266L15.3125 12.6308ZM7.78417 12.9197L6.37136 15.091L7.62864 15.909L9.04145 13.7377L7.78417 12.9197ZM22 7C21.3106 6.70456 21.3107 6.70441 21.3108 6.70427C21.3108 6.70423 21.3108 6.7041 21.3109 6.70402C21.3109 6.70388 21.311 6.70376 21.311 6.70368C21.3111 6.70352 21.3111 6.70349 21.3111 6.7036C21.311 6.7038 21.3107 6.70452 21.3101 6.70576C21.309 6.70823 21.307 6.71275 21.3041 6.71924C21.2983 6.73223 21.2889 6.75309 21.2758 6.78125C21.2495 6.83757 21.2086 6.92295 21.1526 7.03267C21.0406 7.25227 20.869 7.56831 20.6354 7.9432C20.1669 8.69516 19.4563 9.67197 18.4867 10.582L19.5133 11.6757C20.6023 10.6535 21.3917 9.56587 21.9085 8.73646C22.1676 8.32068 22.36 7.9668 22.4889 7.71415C22.5533 7.58775 22.602 7.48643 22.6353 7.41507C22.6519 7.37939 22.6647 7.35118 22.6737 7.33104C22.6782 7.32097 22.6818 7.31292 22.6844 7.30696C22.6857 7.30398 22.6867 7.30153 22.6876 7.2996C22.688 7.29864 22.6883 7.29781 22.6886 7.29712C22.6888 7.29677 22.6889 7.29646 22.689 7.29618C22.6891 7.29604 22.6892 7.29585 22.6892 7.29578C22.6893 7.29561 22.6894 7.29544 22 7ZM18.4867 10.582C17.6277 11.3882 16.5739 12.1343 15.3125 12.6308L15.8619 14.0266C17.3355 13.4466 18.5466 12.583 19.5133 11.6757L18.4867 10.582ZM18.4697 11.6592L19.9697 13.1592L21.0303 12.0985L19.5303 10.5985L18.4697 11.6592ZM11.25 14V16.5H12.75V14H11.25ZM14.9586 13.7377L16.3714 15.909L17.6286 15.091L16.2158 12.9197L14.9586 13.7377ZM4.46967 10.5985L2.96967 12.0985L4.03033 13.1592L5.53033 11.6592L4.46967 10.5985Z"></path> </g></svg>
                            </div>
                        </div>
                        <div class="strength-meter">
                            <div id="strength-bar"></div>
                        </div>
                        <div class="strength-text" id="strength-text"></div>
                    </div>
                </div>
                <div class="inf-group">
                    <div class="information">
                        <div class="label-group">
                            <label>Nombre*</label>
                        </div>
                        <input type="text" name="first_name" placeholder="Nombre" required>
                    </div>
                    <div class="information">
                        <div class="label-group">
                            <label>Primer apellido*</label>
                        </div>
                        <input type="text" name="last_name1" placeholder="Primer apellido" required>
                    </div>
                    <div class="information" id="lastName2Field">
                        <div class="label-group">
                            <label>Segundo apellido</label>
                        </div>
                        <input type="text" name="last_name2" placeholder="Segundo apellido">
                    </div>
                    <div class="information">
                        <div class="label-group">
                            <label>DNI/NIF*</label>
                        </div>
                        <input type="text" name="nif" placeholder="DNI/NIF">
                    </div>
                </div>
                <div class="inf-group">
                    <div class="information">
                        <div class="label-group">
                            <label>Fecha de nacimiento*</label>
                        </div>
                        <input type="date" name="birth_date" placeholder="Fecha de nacimiento" required>
                    </div>
                    <div class="information">
                        <div class="label-group">
                            <label>Teléfono</label>
                        </div>
                        <input type="number" name="number" placeholder="Numero de teléfono">
                    </div>
                    <div class="information">
                        <div class="label-group">
                            <label>País*</label>
                        </div>
                        <select id="country" name="country" placeholder="País" required>
                        <option value="" disabled selected>Selecciona tu país</option>
                            <option value="AF">Afganistán</option>
                            <option value="AL">Albania</option>
                            <option value="DZ">Argelia</option>
                            <option value="AD">Andorra</option>
                            <option value="AO">Angola</option>
                            <option value="AG">Antigua y Barbuda</option>
                            <option value="AR">Argentina</option>
                            <option value="AM">Armenia</option>
                            <option value="AU">Australia</option>
                            <option value="AT">Austria</option>
                            <option value="AZ">Azerbaiyán</option>
                            <option value="BS">Bahamas</option>
                            <option value="BH">Baréin</option>
                            <option value="BD">Bangladés</option>
                            <option value="BB">Barbados</option>
                            <option value="BY">Bielorrusia</option>
                            <option value="BE">Bélgica</option>
                            <option value="BZ">Belice</option>
                            <option value="BJ">Benín</option>
                            <option value="BT">Bután</option>
                            <option value="BO">Bolivia</option>
                            <option value="BA">Bosnia y Herzegovina</option>
                            <option value="BW">Botsuana</option>
                            <option value="BR">Brasil</option>
                            <option value="BN">Brunéi</option>
                            <option value="BG">Bulgaria</option>
                            <option value="BF">Burkina Faso</option>
                            <option value="BI">Burundi</option>
                            <option value="CV">Cabo Verde</option>
                            <option value="KH">Camboya</option>
                            <option value="CM">Camerún</option>
                            <option value="CA">Canadá</option>
                            <option value="TD">Chad</option>
                            <option value="CL">Chile</option>
                            <option value="CN">China</option>
                            <option value="CO">Colombia</option>
                            <option value="KM">Comoras</option>
                            <option value="CG">Congo</option>
                            <option value="CR">Costa Rica</option>
                            <option value="HR">Croacia</option>
                            <option value="CU">Cuba</option>
                            <option value="CY">Chipre</option>
                            <option value="CZ">Chequia</option>
                            <option value="DK">Dinamarca</option>
                            <option value="DJ">Yibuti</option>
                            <option value="DM">Dominica</option>
                            <option value="DO">República Dominicana</option>
                            <option value="EC">Ecuador</option>
                            <option value="EG">Egipto</option>
                            <option value="SV">El Salvador</option>
                            <option value="GQ">Guinea Ecuatorial</option>
                            <option value="ER">Eritrea</option>
                            <option value="EE">Estonia</option>
                            <option value="SZ">Esuatini</option>
                            <option value="ET">Etiopía</option>
                            <option value="FJ">Fiyi</option>
                            <option value="FI">Finlandia</option>
                            <option nueva="FR">Francia</option>
                            <option value="GA">Gabón</option>
                            <option value="GM">Gambia</option>
                            <option value="GE">Georgia</option>
                            <option value="DE">Alemania</option>
                            <option value="GH">Ghana</option>
                            <option value="GR">Grecia</option>
                            <option value="GD">Granada</option>
                            <option value="GT">Guatemala</option>
                            <option value="GN">Guinea</option>
                            <option value="GW">Guinea-Bisáu</option>
                            <option value="GY">Guyana</option>
                            <option value="HT">Haití</option>
                            <option value="HN">Honduras</option>
                            <option value="HU">Hungría</option>
                            <option value="IS">Islandia</option>
                            <option value="IN">India</option>
                            <option value="ID">Indonesia</option>
                            <option value="IR">Irán</option>
                            <option value="IQ">Irak</option>
                            <option value="IE">Irlanda</option>
                            <option value="IL">Israel</option>
                            <option value="IT">Italia</option>
                            <option value="JM">Jamaica</option>
                            <option value="JP">Japón</option>
                            <option value="JO">Jordania</option>
                            <option value="KZ">Kazajistán</option>
                            <option value="KE">Kenia</option>
                            <option value="KI">Kiribati</option>
                            <option value="KR">Corea del Sur</option>
                            <option value="KW">Kuwait</option>
                            <option value="KG">Kirguistán</option>
                            <option value="LA">Laos</option>
                            <option value="LV">Letonia</option>
                            <option value="LB">Líbano</option>
                            <option value="LS">Lesoto</option>
                            <option value="LR">Liberia</option>
                            <option value="LY">Libia</option>
                            <option value="LI">Liechtenstein</option>
                            <option value="LT">Lituania</option>
                            <option value="LU">Luxemburgo</option>
                            <option value="MG">Madagascar</option>
                            <option value="MW">Malaui</option>
                            <option value="MY">Malasia</option>
                            <option value="MV">Maldivas</option>
                            <option value="ML">Malí</option>
                            <option value="MT">Malta</option>
                            <option value="MX">México</option>
                            <option value="MC">Mónaco</option>
                            <option value="MN">Mongolia</option>
                            <option value="ME">Montenegro</option>
                            <option value="MA">Marruecos</option>
                            <option value="MZ">Mozambique</option>
                            <option value="MM">Birmania</option>
                            <option value="NA">Namibia</option>
                            <option value="NP">Nepal</option>
                            <option value="NL">Países Bajos</option>
                            <option value="NZ">Nueva Zelanda</option>
                            <option value="NI">Nicaragua</option>
                            <option value="NE">Níger</option>
                            <option value="NG">Nigeria</option>
                            <option value="NO">Noruega</option>
                            <option value="OM">Omán</option>
                            <option value="PK">Pakistán</option>
                            <option value="PA">Panamá</option>
                            <option value="PG">Papúa Nueva Guinea</option>
                            <option value="PY">Paraguay</option>
                            <option value="PE">Perú</option>
                            <option value="PH">Filipinas</option>
                            <option value="PL">Polonia</option>
                            <option value="PT">Portugal</option>
                            <option value="QA">Catar</option>
                            <option value="RO">Rumanía</option>
                            <option value="RU">Rusia</option>
                            <option value="RW">Ruanda</option>
                            <option value="WS">Samoa</option>
                            <option value="SM">San Marino</option>
                            <option value="SA">Arabia Saudita</option>
                            <option value="SN">Senegal</option>
                            <option value="RS">Serbia</option>
                            <option value="SC">Seychelles</option>
                            <option value="SL">Sierra Leona</option>
                            <option value="SG">Singapur</option>
                            <option value="SK">Eslovaquia</option>
                            <option value="SI">Eslovenia</option>
                            <option value="SB">Islas Salomón</option>
                            <option value="SO">Somalia</option>
                            <option value="ZA">Sudáfrica</option>
                            <option value="ES">España</option>
                            <option value="LK">Sri Lanka</option>
                            <option value="SD">Sudán</option>
                            <option value="SR">Surinam</option>
                            <option value="SE">Suecia</option>
                            <option value="CH">Suiza</option>
                            <option value="SY">Siria</option>
                            <option value="TW">Taiwán</option>
                            <option value="TJ">Tayikistán</option>
                            <option value="TZ">Tanzania</option>
                            <option value="TH">Tailandia</option>
                            <option value="TL">Timor Oriental</option>
                            <option value="TG">Togo</option>
                            <option value="TO">Tonga</option>
                            <option value="TT">Trinidad y Tobago</option>
                            <option value="TN">Túnez</option>
                            <option value="TR">Turquía</option>
                            <option value="TM">Turkmenistán</option>
                            <option value="UG">Uganda</option>
                            <option value="UA">Ucrania</option>
                            <option value="AE">Emiratos Árabes Unidos</option>
                            <option value="GB">Reino Unido</option>
                            <option value="US">Estados Unidos</option>
                            <option value="UY">Uruguay</option>
                            <option value="UZ">Uzbekistán</option>
                            <option value="VU">Vanuatu</option>
                            <option value="VE">Venezuela</option>
                            <option value="VN">Vietnam</option>
                            <option value="YE">Yemen</option>
                            <option value="ZM">Zambia</option>
                            <option value="ZW">Zimbabue</option>
                        </select>
                    </div>
                    <div class="information">
                        <div class="label-group">
                            <label>Divisa predeterminada*</label>
                        </div>
                        <select id="currency" name="currency" placeholder="Divisa" required>
                        <option value="" disabled selected>Selecciona tu divisa predeterminada</option>
                            <option value="EUR">€</option>
                            <option value="USD">$</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="login-button-container">
                <button class="login-button" type="submit"><span>Registrarse</span></button>
            </div>
            <div class="divider">
                <span>O</span>
            </div>
            <div class="login-container">
                <p>¿Ya tienes cuenta? <a href="../login/login.php">Inicia sesión</a></p>
            </div>
        </form>
    </div>
    <script src="../js/register.js"></script>
</body>
</html>