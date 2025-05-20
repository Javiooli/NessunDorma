//GESTIONAR LA INACTIVIDAD DEL USUARIO

export function iniciarInactividad() {
    function resetTimer() {
      clearTimeout(window._inactividadTimer);
      window._inactividadTimer = setTimeout(function () {
        alert("Sesión finalizada por inactividad.");
        window.location.href = "./login/logout.php";
      }, 300000); // TIEMPO DE INACTIVIDAD EN MILISEGUNDOS 5000 = 5 SEGUNDOS
    }
  
    resetTimer();
    document.onmousemove = resetTimer;
    document.onkeydown = resetTimer;
    document.onclick = resetTimer;
    document.onscroll = resetTimer;
  }
