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

// DATOS DE SESIÓN
$userId = $_SESSION["userId"];
$firstName = $_SESSION["firstName"];
$email = $_SESSION["email"];
$username = $_SESSION["username"];
$rol = $_SESSION["rol"];
$currency = $_SESSION["currency"];
$country = $_SESSION["country"] ?? '';

$max_amount = null;
// Obtener el importe máximo de la cuenta
$result = $conn->query("SELECT max_amount FROM Users WHERE userId = $userId"); 
if ($result && $row = $result->fetch_assoc()) {
    $max_amount = $row["max_amount"];
}
// Obtener el país del usuario
$result = $conn->query("SELECT country FROM Users WHERE userId = $userId");
if ($result && $row = $result->fetch_assoc()) {
    $country = $row["country"];
}
// Obtener número de alertas activas y pendientes
$alertCount = 0;
$sql = "SELECT COUNT(*) AS alert_count FROM user_alerts WHERE userId = ? AND (active = 1 AND pending = 1)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($alertCount);
$stmt->fetch();
$stmt->close();

// Consultamos el nombre de usuario actualizado
$result = $conn->query("SELECT username FROM Users WHERE userId = $userId");
if ($result && $row = $result->fetch_assoc()) {
    $username = $row["username"];
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["accion"] === "cambiar_divisa_directa") {
  $nuevaDivisa = $conn->real_escape_string($_POST["nueva_divisa_directa"] ?? '');
  
  if (in_array($nuevaDivisa, ['EUR', 'USD'])) {
      $update = $conn->query("UPDATE Users SET default_currency = '$nuevaDivisa' WHERE userId = $userId");
      if ($update) {
          $_SESSION["currency"] = $nuevaDivisa;
          echo "<script>window.location.href='configuracion.php';</script>";
          exit();
      } else {
          echo "<script>alert('Error al actualizar la divisa'); window.location.href='configuracion.php';</script>";
          exit();
      }
  }
}
// ----- GESTIÓN DE FORMULARIOS POST -----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"])) {
    $accion = $_POST["accion"];
    $inputPassword = $_POST["confirm-password"];

    // Obtener contraseña actual del usuario
    $result = $conn->query("SELECT passw0rd FROM Users WHERE userId = $userId");

    if (!$result || !$row = $result->fetch_assoc()) {
        echo "<script>alert('Error al obtener los datos del usuario');</script>";
        return;
    }

    $hashedPassword = $row["passw0rd"];

    if (!password_verify($inputPassword, $hashedPassword)) {
      header("Location: configuracion.php?error=contrasena"); // si no coincide redirigimos al mismo archivo para que de tiempo a cargar
      exit(); //salimos del script en el caso de que la contraseña introducida sea incorrecta
    }

    switch ($accion) {
      case "editar_nombre":
          $nuevoUsername = $conn->real_escape_string($_POST["nuevo_nombre"] ?? '');
          if ($nuevoUsername) {
              $update = $conn->query("UPDATE Users SET username = '$nuevoUsername' WHERE userId = $userId");
              if ($update) {
                  $_SESSION["username"] = $nuevoUsername;
                  echo "<script>alert('Nombre de usuario actualizado correctamente'); window.location.href='configuracion.php';</script>";
                  exit();
              } else {
                  echo "<script>alert('Error al actualizar el nombre de usuario');</script>";
              }
          }
          break;
  
      case "editar_correo":
          $nuevoCorreo = $conn->real_escape_string($_POST["nuevo_correo"] ?? '');
          if ($nuevoCorreo) {
              $update = $conn->query("UPDATE Users SET email = '$nuevoCorreo' WHERE userId = $userId");
              if ($update) {
                  echo "<script>alert('Correo actualizado correctamente'); window.location.href='configuracion.php';</script>";
                  exit();
              } else {
                  echo "<script>alert('Error al actualizar el correo');</script>";
              }
          }
          break;
  
      case "editar_contrasena":
          $nuevaContrasena = $_POST["nueva_contrasena"] ?? '';
          if ($nuevaContrasena && strlen($nuevaContrasena) >= 2) {
              $hashedNueva = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
              $update = $conn->query("UPDATE Users SET passw0rd = '$hashedNueva' WHERE userId = $userId");
              if ($update) {
                  echo "<script>alert('Contraseña actualizada correctamente'); window.location.href='configuracion.php';</script>";
                  exit();
              } else {
                  echo "<script>alert('Error al actualizar la contraseña');</script>";
              }
          } else {
              echo "<script>alert('La nueva contraseña es demasiado corta');</script>";
          }
          break;
        case "editar_importe":
    $nuevoImporte = floatval($_POST["nuevo_importe"] ?? -1);
    if ($nuevoImporte >= 0) {
        $update = $conn->query("UPDATE Users SET max_amount = $nuevoImporte WHERE userId = $userId");
        if ($update) {
            echo "<script>
                    alert('Importe máximo actualizado correctamente');
                    window.location.href='configuracion.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Error al actualizar el importe máximo');
                    window.location.href='configuracion.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('El importe ingresado no es válido');
                window.location.href='configuracion.php';
              </script>";
    }
    exit();
      case "cambiar_pais_directo":
          $nuevoPais = $conn->real_escape_string($_POST["nuevo_pais_directo"] ?? '');
          if ($nuevoPais) {
              $update = $conn->query("UPDATE Users SET country = '$nuevoPais' WHERE userId = $userId");
              if ($update) {
                  $_SESSION["country"] = $nuevoPais;
                  echo "<script>alert('País actualizado correctamente'); window.location.href='configuracion.php';</script>";
                  exit();
              } else {
                  echo "<script>alert('Error al actualizar el país');</script>";
              }
          }
          break;
          case "editar_pais":
            $nuevoPais = $conn->real_escape_string($_POST["nuevo_pais"] ?? '');
            if ($nuevoPais) {
                $update = $conn->query("UPDATE Users SET country = '$nuevoPais' WHERE userId = $userId");
                if ($update) {
                    $_SESSION["country"] = $nuevoPais;
                    echo "<script>alert('País actualizado correctamente'); window.location.href='configuracion.php';</script>";
                    exit();
                } else {
                    echo "<script>alert('Error al actualizar el país');</script>";
                }
            }
            break;
            case "eliminar_cuenta":
              $stmt = $conn->prepare("DELETE FROM Users WHERE userId = ?");
              $stmt->bind_param("i", $userId);
          
              if ($stmt->execute()) {
                  $stmt->close();
                  session_unset();
                  session_destroy();
                  echo "<script>alert('Cuenta eliminada correctamente'); window.location.href='./index.html';</script>";
                  exit();
              } else {
                  $stmt->close();
                  echo "<script>alert('Error al eliminar la cuenta'); window.location.href='./configuracion.php';</script>";
                  exit();
              }
          default:
              echo "<script>alert('Acción no reconocida');</script>";
              break;
          }
          
        } 
          

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/configuracion.css">
    <link rel="shortcut icon" href="./img/icon.png">
    <script src="./js/qrcode.js"></script> <!-- Asegúrate de que la ruta sea correcta -->
    <title>Nessun Dorma - Configuración</title>
</head>
<body>
    <!--HEADER MOBILE-->
    <header>
        <div class="header-content">
            <h2 class="header-title">NESSUN DORMA</h2>
            <button class="menu-btn">☰</button>
        </div>
        <div class="header-aside" id="header-aside">
            <div class="aside-content">
                <button class="close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <img class="header-title" src="./img/logo.png" style="height:100px;"></img>
                <ul class="aside-menu">
                    <li class="link-menu"><a href="home.php">Inicio</a></li>
                    <li class="link-menu"><a href="movimientos.php">Movimientos</a></li>
                    <li class="link-menu"><a href="mercado.php">Mercado
                        <?php if ($alertCount > 0): ?>
                            <strong><?php echo $alertCount; ?></strong>
                        <?php endif; ?>
                    </a></li>
                    <li class="link-menu"><a href="carteras.php">Carteras</a></li>
                    <li class="link-menu"><a href="configuracion.php">Configuración</a></li>
                    <?php
                        if ($rol == "Manager") {
                            echo "<li class='link-menu'><a href='gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='auditoria.php'>Auditoria</a></li>";
                        }
                        if ($rol == "Admin") {
                            echo "<li class='link-menu'><a href='gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='auditoria.php'>Auditoria</a></li>
                                  <li class='link-menu'><a href='administracion.php'>Administración</a></li>";
                        }
                    ?>
                </ul>
            </div>
        </div>
    </header>
    <!--ASIDE DESKTOP-->
    <aside class="aside" id="aside">
        <div class="aside-content">
            <img class="sidebarLogo" src="./img/logo.png"></img>
            <div class="aside-main-content">
                <ul class="aside-menu">
                    <li class="link-menu"><a href="home.php">Inicio</a></li>
                    <li class="link-menu"><a href="movimientos.php">Movimientos</a></li>
                    <li class="link-menu"><a href="mercado.php">Mercado
                        <?php if ($alertCount > 0): ?>
                            <strong><?php echo $alertCount; ?></strong>
                        <?php endif; ?>
                    </a></li>
                    <li class="link-menu"><a href="carteras.php">Carteras</a></li>
                    <li class="link-menu"><a href="configuracion.php">Configuración</a></li>
                    <?php
                        if ($rol == "Admin" || $rol == "Manager") {
                            echo "<li class='link-menu'><a href='gestion.php'>Gestionar</a></li>
                                  <li class='link-menu'><a href='auditoria.php'>Auditoria</a></li>
                                  <li class='link-menu'><a href='administracion.php'>Administración</a></li>";
                        }
                    ?>
                </ul>
                <div class="close-session-container">
                    <a href="./login/logout.php">
                        <button class="close-session" id="close-session">
                            <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22.5 4.742a13 13 0 11-13 0M16 3v10"></path> </g></svg>
                        </button>
                    </a>
                </div>
            </div>
        </div>        
    </aside>
    <section class="main-content" id="main-content">
    <!--Mostrar el nombre de usuario-->
    <div class="main-header">
        <h1>Configuración</h1>
    </div>
<!--
<!--CONFIGURACION DE LA CUENTA -->
    <div class="encabezado-configuracion">
  <h2>Configuración de la cuenta</h2>
                      
</div>
  <div class="tarjeta-configuracion">
  <div class="seccion-configuracion">
    <div class="campo-configuracion">
      <label>Nombre de usuario:</label>
      <span><?php echo $username; ?></span>
    </div>
    <div class="campo-configuracion">
      <label>Rol:</label>
      <span><?php echo $rol; ?></span>  
    </div>

    <div class="campo-configuracion">
      <label>Tiempo de inactividad</label>
      <span>La sesión se cerrará tras 5 minutos de inactividad</span>
    </div>

<div class="seccion-configuracion">
  <div class="campo-configuracion">
        <div>
          <label>Nombre de usuario</label>
          <p><?php echo $username; ?></p>
        </div>
        <button class="boton-editar" data-campo="username">Editar nombre de usuario</button>
      </div>
    </div>

    <!-- Maximo importe -->
  <div class="seccion-configuracion">
    <div class="campo-configuracion">
      <div>
        <label>Importe maximo de la cuenta</label>
        <p><?php echo $max_amount; ?></p>
      </div>
      <button class="boton-editar" data-campo="importe">Editar importe maximo de la cuenta</button>
    </div>
  </div>

<div class="seccion-configuracion">
  <div class="campo-configuracion">
    <div>
      <label>Divisa actual</label>
      <p><?php echo $currency; ?></p>
    </div>
    <form method="POST" style="margin-top: 10px;">
      <select name="nueva_divisa_directa" onchange="this.form.submit()" required>
        <option value="EUR" <?php if ($currency === 'EUR') echo 'selected'; ?>>€ - EUR</option>
        <option value="USD" <?php if ($currency === 'USD') echo 'selected'; ?>>$ - USD</option>
      </select>
      <input type="hidden" name="accion" value="cambiar_divisa_directa">
    </form>
  </div>
</div>

  <!-- Correo -->
  <div class="seccion-configuracion">
    <div class="campo-configuracion">
      <div>
        <label>Correo Electrónico</label>
        <p><?php echo $email; ?></p>
      </div>
      <button class="boton-editar" data-campo="correo">Editar correo electrónico</button>
    </div>
  </div>
  <!-- Contraseña -->
  <div class="seccion-configuracion">
    <div class="campo-configuracion">
      <div>
        <label>Contraseña</label>
        <p>********</p>
      </div>
      <button class="boton-editar" data-campo="contrasena">Cambiar contraseña</button>
    </div>
  </div>

<!-- CAMBIAR PAÍS -->
<div class="seccion-configuracion">
  <div class="campo-configuracion">
    <div>
      <label>País actual</label>
      <p><?php echo $country; ?></p>
    </div>
    <form>
    <select id="country-select" style="margin-top: 10px;"> 
      <option value="" disabled selected>Selecciona tu país</option>
      <option value="ES" <?php if ($country === 'ES') echo 'selected'; ?>>España</option>
      <option value="US" <?php if ($country === 'US') echo 'selected'; ?>>Estados Unidos</option>
      <option value="FR" <?php if ($country === 'FR') echo 'selected'; ?>>Francia</option>
      <option value="DE" <?php if ($country === 'DE') echo 'selected'; ?>>Alemania</option>
      <option value="MX" <?php if ($country === 'MX') echo 'selected'; ?>>México</option>
      <option value="AF" <?php if ($country === 'AF') echo 'selected';?>'>Afganistán</option>
      <option value="AL" <?php if ($country === 'AL') echo 'selected';?>'>Albania</option>
      <option value="DZ" <?php if ($country === 'DZ') echo 'selected'; ?>>Argelia</option>
      <option value="AD" <?php if ($country === 'AD') echo 'selected'; ?>>Andorra</option>
      <option value="AO" <?php if ($country === 'AO') echo 'selected'; ?>>Angola</option>
      <option value="AG" <?php if ($country === 'AG') echo 'selected'; ?>>Antigua y Barbuda</option>
      <option value="AR" <?php if ($country === 'AR') echo 'selected'; ?>>Argentina</option>
      <option value="AM" <?php if ($country === 'AM') echo 'selected'; ?>>Armenia</option>
      <option value="AU" <?php if ($country === 'AU') echo 'selected'; ?>>Australia</option>
      <option value="AT" <?php if ($country === 'AT') echo 'selected'; ?>>Austria</option>
      <option value="AZ" <?php if ($country === 'AZ') echo 'selected'; ?>>Azerbaiyán</option>
      <option value="BS" <?php if ($country === 'BS') echo 'selected'; ?>>Bahamas</option>
      <option value="BH" <?php if ($country === 'BH') echo 'selected'; ?>>Baréin</option>
      <option value="BD" <?php if ($country === 'BD') echo 'selected'; ?>>Bangladés</option>
      <option value="BB" <?php if ($country === 'BB') echo 'selected'; ?>>Barbados</option>
      <option value="BY" <?php if ($country === 'BY') echo 'selected'; ?>>Bielorrusia</option>
      <option value="BE" <?php if ($country === 'BE') echo 'selected'; ?>>Bélgica</option>
      <option value="BZ" <?php if ($country === 'BZ') echo 'selected'; ?>>Belice</option>
      <option value="BJ" <?php if ($country === 'BJ') echo 'selected'; ?>>Benín</option>
      <option value="BT" <?php if ($country === 'BT') echo 'selected'; ?>>Bután</option>
      <option value="BO" <?php if ($country === 'BO') echo 'selected'; ?>>Bolivia</option>
      <option value="BA" <?php if ($country === 'BA') echo 'selected'; ?>>Bosnia y Herzegovina</option>
      <option value="BW" <?php if ($country === 'BW') echo 'selected'; ?>>Botsuana</option>
      <option value="BR" <?php if ($country === 'BR') echo 'selected'; ?>>Brasil</option>
      <option value="BN" <?php if ($country === 'BN') echo 'selected'; ?>>Brunéi</option>
      <option value="BG" <?php if ($country === 'BG') echo 'selected'; ?>>Bulgaria</option>
      <option value="BF" <?php if ($country === 'BF') echo 'selected'; ?>>Burkina Faso</option>
      <option value="BI" <?php if ($country === 'BI') echo 'selected'; ?>>Burundi</option>
      <option value="CV" <?php if ($country === 'CV') echo 'selected'; ?>>Cabo Verde</option>
      <option value="KH" <?php if ($country === 'KH') echo 'selected'; ?>>Camboya</option>
      <option value="CM" <?php if ($country === 'CM') echo 'selected'; ?>>Camerún</option>
      <option value="CA" <?php if ($country === 'CA') echo 'selected'; ?>>Canadá</option>
      <option value="TD" <?php if ($country === 'TD') echo 'selected'; ?>>Chad</option>
      <option value="CL" <?php if ($country === 'CL') echo 'selected'; ?>>Chile</option>
      <option value="CN" <?php if ($country === 'CN') echo 'selected'; ?>>China</option>
      <option value="CO" <?php if ($country === 'CO') echo 'selected'; ?>>Colombia</option>
      <option value="KM" <?php if ($country === 'KM') echo 'selected'; ?>>Comoras</option>
      <option value="CG" <?php if ($country === 'CG') echo 'selected'; ?>>Congo</option>
      <option value="CR" <?php if ($country === 'CR') echo 'selected'; ?>>Costa Rica</option>
      <option value="HR" <?php if ($country === 'HR') echo 'selected'; ?>>Croacia</option>
      <option value="CU" <?php if ($country === 'CU') echo 'selected'; ?>>Cuba</option>
      <option value="CY" <?php if ($country === 'CY') echo 'selected'; ?>>Chipre</option>
      <option value="CZ" <?php if ($country === 'CZ') echo 'selected'; ?>>Chequia</option>
      <option value="DK" <?php if ($country === 'DK') echo 'selected'; ?>>Dinamarca</option>
      <option value="DJ" <?php if ($country === 'DJ') echo 'selected'; ?>>Yibuti</option>
      <option value="DM" <?php if ($country === 'DM') echo 'selected'; ?>>Dominica</option>
      <option value="DO" <?php if ($country === 'DO') echo 'selected'; ?>>República Dominicana</option>
      <option value="EC" <?php if ($country === 'EC') echo 'selected'; ?>>Ecuador</option>
      <option value="EG" <?php if ($country === 'EG') echo 'selected'; ?>>Egipto</option>
      <option value="SV" <?php if ($country === 'SV') echo 'selected'; ?>>El Salvador</option>
      <option value="GQ" <?php if ($country === 'GQ') echo 'selected'; ?>>Guinea Ecuatorial</option>
      <option value="ER" <?php if ($country === 'ER') echo 'selected'; ?>>Eritrea</option>
      <option value="EE" <?php if ($country === 'EE') echo 'selected'; ?>>Estonia</option>
      <option value="SZ" <?php if ($country === 'SZ') echo 'selected'; ?>>Esuatini</option>
      <option value="ET" <?php if ($country === 'ET') echo 'selected'; ?>>Etiopía</option>
      <option value="FJ" <?php if ($country === 'FJ') echo 'selected'; ?>>Fiyi</option>
      <option value="FI" <?php if ($country === 'FI') echo 'selected'; ?>>Finlandia</option>
      <option value="FR" <?php if ($country === 'FR') echo 'selected'; ?>>Francia</option>
      <option value="GA" <?php if ($country === 'GA') echo 'selected'; ?>>Gabón</option>
      <option value="GM" <?php if ($country === 'GM') echo 'selected'; ?>>Gambia</option>
      <option value="GE" <?php if ($country === 'GE') echo 'selected'; ?>>Georgia</option>
      <option value="DE" <?php if ($country === 'DE') echo 'selected'; ?>>Alemania</option>
      <option value="GH" <?php if ($country === 'GH') echo 'selected'; ?>>Ghana</option>
      <option value="GR" <?php if ($country === 'GR') echo 'selected'; ?>>Grecia</option>
      <option value="GD" <?php if ($country === 'GD') echo 'selected'; ?>>Granada</option>
      <option value="GT" <?php if ($country === 'GT') echo 'selected'; ?>>Guatemala</option>
      <option value="GN" <?php if ($country === 'GN') echo 'selected'; ?>>Guinea</option>
      <option value="GW" <?php if ($country === 'GW') echo 'selected'; ?>>Guinea-Bisáu</option>
      <option value="GY" <?php if ($country === 'GY') echo 'selected'; ?>>Guyana</option>
      <option value="HT" <?php if ($country === 'HT') echo 'selected'; ?>>Haití</option>
      <option value="HN" <?php if ($country === 'HN') echo 'selected'; ?>>Honduras</option>
      <option value="HU" <?php if ($country === 'HU') echo 'selected'; ?>>Hungría</option>
      <option value="IS" <?php if ($country === 'IS') echo 'selected'; ?>>Islandia</option>
      <option value="IN" <?php if ($country === 'IN') echo 'selected'; ?>>India</option>
      <option value="ID" <?php if ($country === 'ID') echo 'selected'; ?>>Indonesia</option>
      <option value="IR" <?php if ($country === 'IR') echo 'selected'; ?>>Irán</option>
      <option value="IQ" <?php if ($country === 'IQ') echo 'selected'; ?>>Irak</option>
      <option value="IE" <?php if ($country === 'IE') echo 'selected'; ?>>Irlanda</option>
      <option value="IL" <?php if ($country === 'IL') echo 'selected'; ?>>Israel</option>
      <option value="IT" <?php if ($country === 'IT') echo 'selected'; ?>>Italia</option>
      <option value="JM" <?php if ($country === 'JM') echo 'selected'; ?>>Jamaica</option>
      <option value="JP" <?php if ($country === 'JP') echo 'selected'; ?>>Japón</option>
      <option value="JO" <?php if ($country === 'JO') echo 'selected'; ?>>Jordania</option>
      <option value="KZ" <?php if ($country === 'KZ') echo 'selected'; ?>>Kazajistán</option>
      <option value="KE" <?php if ($country === 'KE') echo 'selected'; ?>>Kenia</option>
      <option value="KI" <?php if ($country === 'KI') echo 'selected'; ?>>Kiribati</option>
      <option value="KR" <?php if ($country === 'KR') echo 'selected'; ?>>Corea del Sur</option>
      <option value="KW" <?php if ($country === 'KW') echo 'selected'; ?>>Kuwait</option>
      <option value="KG" <?php if ($country === 'KG') echo 'selected'; ?>>Kirguistán</option>
      <option value="LA" <?php if ($country === 'LA') echo 'selected'; ?>>Laos</option>
      <option value="LV" <?php if ($country === 'LV') echo 'selected'; ?>>Letonia</option>
      <option value="LB" <?php if ($country === 'LB') echo 'selected'; ?>>Líbano</option>
      <option value="LS" <?php if ($country === 'LS') echo 'selected'; ?>>Lesoto</option>
      <option value="LR" <?php if ($country === 'LR') echo 'selected'; ?>>Liberia</option>
      <option value="LY" <?php if ($country === 'LY') echo 'selected'; ?>>Libia</option>
      <option value="LI" <?php if ($country === 'LI') echo 'selected'; ?>>Liechtenstein</option>
      <option value="LT" <?php if ($country === 'LT') echo 'selected'; ?>>Lituania</option>
      <option value="LU" <?php if ($country === 'LU') echo 'selected'; ?>>Luxemburgo</option>
      <option value="MG" <?php if ($country === 'MG') echo 'selected'; ?>>Madagascar</option>
      <option value="MW" <?php if ($country === 'MW') echo 'selected'; ?>>Malaui</option>
      <option value="MY" <?php if ($country === 'MY') echo 'selected'; ?>>Malasia</option>
      <option value="MV" <?php if ($country === 'MV') echo 'selected'; ?>>Maldivas</option>
      <option value="ML" <?php if ($country === 'ML') echo 'selected'; ?>>Malí</option>
      <option value="MT" <?php if ($country === 'MT') echo 'selected'; ?>>Malta</option>
      <option value="MX" <?php if ($country === 'MX') echo 'selected'; ?>>México</option>
      <option value="MC" <?php if ($country === 'MC') echo 'selected'; ?>>Mónaco</option>
      <option value="MN" <?php if ($country === 'MN') echo 'selected'; ?>>Mongolia</option>
      <option value="ME" <?php if ($country === 'ME') echo 'selected'; ?>>Montenegro</option>
      <option value="MA" <?php if ($country === 'MA') echo 'selected'; ?>>Marruecos</option>
      <option value="MZ" <?php if ($country === 'MZ') echo 'selected'; ?>>Mozambique</option>
      <option value="MM" <?php if ($country === 'MM') echo 'selected'; ?>>Birmania</option>
      <option value="NA" <?php if ($country === 'NA') echo 'selected'; ?>>Namibia</option>
      <option value="NP" <?php if ($country === 'NP') echo 'selected'; ?>>Nepal</option>
      <option value="NL" <?php if ($country === 'NL') echo 'selected'; ?>>Países Bajos</option>
      <option value="NZ" <?php if ($country === 'NZ') echo 'selected'; ?>>Nueva Zelanda</option>
      <option value="NI" <?php if ($country === 'NI') echo 'selected'; ?>>Nicaragua</option>
      <option value="NE" <?php if ($country === 'NE') echo 'selected'; ?>>Níger</option>
      <option value="NG" <?php if ($country === 'NG') echo 'selected'; ?>>Nigeria</option>
      <option value="NO" <?php if ($country === 'NO') echo 'selected'; ?>>Noruega</option>
      <option value="OM" <?php if ($country === 'OM') echo 'selected'; ?>>Omán</option>
      <option value="PK" <?php if ($country === 'PK') echo 'selected'; ?>>Pakistán</option>
      <option value="PA" <?php if ($country === 'PA') echo 'selected'; ?>>Panamá</option>
      <option value="PG" <?php if ($country === 'PG') echo 'selected'; ?>>Papúa Nueva Guinea</option>
      <option value="PY" <?php if ($country === 'PY') echo 'selected'; ?>>Paraguay</option>
      <option value="PE" <?php if ($country === 'PE') echo 'selected'; ?>>Perú</option>
      <option value="PH" <?php if ($country === 'PH') echo 'selected'; ?>>Filipinas</option>
      <option value="PL" <?php if ($country === 'PL') echo 'selected'; ?>>Polonia</option>
      <option value="PT" <?php if ($country === 'PT') echo 'selected'; ?>>Portugal</option>
      <option value="QA" <?php if ($country === 'QA') echo 'selected'; ?>>Catar</option>
      <option value="RO" <?php if ($country === 'RO') echo 'selected'; ?>>Rumanía</option>
      <option value="RU" <?php if ($country === 'RU') echo 'selected'; ?>>Rusia</option>
      <option value="RW" <?php if ($country === 'RW') echo 'selected'; ?>>Ruanda</option>
      <option value="WS" <?php if ($country === 'WS') echo 'selected'; ?>>Samoa</option>
      <option value="SM" <?php if ($country === 'SM') echo 'selected'; ?>>San Marino</option>
      <option value="SA" <?php if ($country === 'SA') echo 'selected'; ?>>Arabia Saudita</option>
      <option value="SN" <?php if ($country === 'SN') echo 'selected'; ?>>Senegal</option>
      <option value="RS" <?php if ($country === 'RS') echo 'selected'; ?>>Serbia</option>
      <option value="SC" <?php if ($country === 'SC') echo 'selected'; ?>>Seychelles</option>
      <option value="SL" <?php if ($country === 'SL') echo 'selected'; ?>>Sierra Leona</option>
      <option value="SG" <?php if ($country === 'SG') echo 'selected'; ?>>Singapur</option>
      <option value="SK" <?php if ($country === 'SK') echo 'selected'; ?>>Eslovaquia</option>
      <option value="SI" <?php if ($country === 'SI') echo 'selected'; ?>>Eslovenia</option>
      <option value="SB" <?php if ($country === 'SB') echo 'selected'; ?>>Islas Salomón</option>
      <option value="SO" <?php if ($country === 'SO') echo 'selected'; ?>>Somalia</option>
      <option value="ZA" <?php if ($country === 'ZA') echo 'selected'; ?>>Sudáfrica</option>
      <option value="ES" <?php if ($country === 'ES') echo 'selected'; ?>>España</option>
      <option value="LK" <?php if ($country === 'LK') echo 'selected'; ?>>Sri Lanka</option>
      <option value="SD" <?php if ($country === 'SD') echo 'selected'; ?>>Sudán</option>
      <option value="SR" <?php if ($country === 'SR') echo 'selected'; ?>>Surinam</option>
      <option value="SE" <?php if ($country === 'SE') echo 'selected'; ?>>Suecia</option>
      <option value="CH" <?php if ($country === 'CH') echo 'selected'; ?>>Suiza</option>
      <option value="SY" <?php if ($country === 'SY') echo 'selected'; ?>>Siria</option>
      <option value="TW" <?php if ($country === 'TW') echo 'selected'; ?>>Taiwán</option>
      <option value="TJ" <?php if ($country === 'TJ') echo 'selected'; ?>>Tayikistán</option>
      <option value="TZ" <?php if ($country === 'TZ') echo 'selected'; ?>>Tanzania</option>
      <option value="TH" <?php if ($country === 'TH') echo 'selected'; ?>>Tailandia</option>
      <option value="TL" <?php if ($country === 'TL') echo 'selected'; ?>>Timor Oriental</option>
      <option value="TG" <?php if ($country === 'TG') echo 'selected'; ?>>Togo</option>
      <option value="TO" <?php if ($country === 'TO') echo 'selected'; ?>>Tonga</option>
      <option value="TT" <?php if ($country === 'TT') echo 'selected'; ?>>Trinidad y Tobago</option>
      <option value="TN" <?php if ($country === 'TN') echo 'selected'; ?>>Túnez</option>
      <option value="TR" <?php if ($country === 'TR') echo 'selected'; ?>>Turquía</option>
      <option value="TM" <?php if ($country === 'TM') echo 'selected'; ?>>Turkmenistán</option>
      <option value="UG" <?php if ($country === 'UG') echo 'selected'; ?>>Uganda</option>
      <option value="UA" <?php if ($country === 'UA') echo 'selected'; ?>>Ucrania</option>
      <option value="AE" <?php if ($country === 'AE') echo 'selected'; ?>>Emiratos Árabes Unidos</option>
      <option value="GB" <?php if ($country === 'GB') echo 'selected'; ?>>Reino Unido</option>
      <option value="US" <?php if ($country === 'US') echo 'selected'; ?>>Estados Unidos</option>
      <option value="UY" <?php if ($country === 'UY') echo 'selected'; ?>>Uruguay</option>
      <option value="UZ" <?php if ($country === 'UZ') echo 'selected'; ?>>Uzbekistán</option>
      <option value="VU" <?php if ($country === 'VU') echo 'selected'; ?>>Vanuatu</option>
      <option value="VE" <?php if ($country === 'VE') echo 'selected'; ?>>Venezuela</option>
      <option value="VN" <?php if ($country === 'VN') echo 'selected'; ?>>Vietnam</option>
      <option value="YE" <?php if ($country === 'YE') echo 'selected'; ?>>Yemen</option>
      <option value="ZM" <?php if ($country === 'ZM') echo 'selected'; ?>>Zambia</option>
      <option value="ZW" <?php if ($country === 'ZW') echo 'selected'; ?>>Zimbabue</option>
    </select>
    </form>
  </div>
</div>
</div>
  <!-- Eliminar cuenta -->
  <div class="seccion-configuracion peligro">
    <div class="campo-configuracion">
      <div>
        <label>Eliminar cuenta</label>
        <p>Esta acción no se puede deshacer.</p>
      </div>
      <button class="boton-eliminar">Eliminar</button>
    </div>
  </div>

  <div class="seccion-configuracion peligro">
    <div class="campo-configuracion">
      <div>
        <label>Establecer autentificación en 2 factores</label>
      </div>
      <button id="boton-setup-otp" class="boton-editar">Establecer OTP</button>
    </div>
  </div>
</div>
<!-- FIN DE TARJETA DE CONFIGURACION -->

 <!-- POP UP DE CONFIRMACION DE ACCION -->
<div id="popup-confirmacion" class="modal">
  <div class="modal-contenido">
  <span class="cerrar-popup">&times;</span>
      <h3 id="popup-titulo">Confirmar acción</h3>
    <p id="popup-mensaje">Por favor, introduce tu contraseña para continuar.</p>

<form id="form-confirmacion" method="POST">
  <input type="hidden" name="accion" id="accion" value="">
  <input type="hidden" name="nuevo_nombre" id="nuevo-nombre" value="">
  <input type="hidden" name="nueva_divisa" id="nueva-divisa" value="">
  <input type="hidden" name="nuevo_correo" id="nuevo-correo" value="">
  <input type="hidden" name="nueva_contrasena" id="nueva-contrasena" value="">
  <input type="hidden" name="nuevo_pais" id="nuevo-pais" value="">
  <input type="hidden"  name="nuevo_importe" id="nuevo-importe" name="">

  
  <!-- NUEVA CONTRASEÑA -->
  <div id="grupo-nueva-contrasena" class="grupo-form" style="display: none;">
    <label for="input-nueva-contrasena">Nueva contraseña</label>
    <input type="password"id="input-nueva-contrasena"name="nueva_contrasena_visible"class="campo-formulario" placeholder="Nueva contraseña"/>
  </div>

  <!-- CONTRASEÑA ACTUAL -->
  <label for="confirm-password">Contraseña</label>
  <input type="password" id="confirm-password" name="confirm-password" placeholder="Contraseña" required />

  <div class="botones-popup">
    <button type="submit" id="confirmar-accion">Confirmar</button>
    <button type="button" id="cancelar-accion">Cancelar</button>
  </div>
</form>


  </div>
</div>

</section>

<!-- Popup 2FA -->
<div class="container-otp" id="popup-setup-otp">

  <button class="close-btn" type="button" id="close-otp">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>

  <div class="content-otp">
    <h3 id="popup-titulo">Configuración de 2FA</h3>
    <p id="popup-mensaje">Por favor, escanea el código QR con tu aplicación de autenticación.</p>
    <div id="qrcode"></div>
    <p id="secret"></p>
  </div>
</div>
    

    <!-- EN EL CASO DE ERROR EN LA CONTRASEÑA DE TIEMPO DE VOLVER A CARGAR LA PAGINA -->
<?php if (isset($_GET["error"]) && $_GET["error"] === "contrasena"): ?>
  <script>
    alert("Contraseña incorrecta");
  </script>
<?php endif; ?>
<script src="./js/configuracion.js"></script>
</body>
</html>