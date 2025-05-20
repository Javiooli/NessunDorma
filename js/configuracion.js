class ConfiguracionUI {
    constructor() {
        this.cacheDOM();
        this.addEventListeners();
    }

    // Método para almacenar referencias al DOM
    cacheDOM() {
        // Modales y popups
        this.modalEditar = document.getElementById("popup-editar-perfil");
        this.popupConfirmacion = document.getElementById("popup-confirmacion");
        this.popup2fa = document.getElementById("popup-setup-otp");
        this.otpContainer = document.querySelector('.container-otp');

        // Botones
        this.botonAbrirEditarPerfil = document.querySelector(".boton-editarperfil");
        this.botonesEditar = document.querySelectorAll(".boton-editar");
        this.botonSetupOTP = document.getElementById("boton-setup-otp");
        this.cerrarPopups = document.querySelectorAll(".cerrar-popup");
        this.cancelarAccion = document.getElementById("cancelar-accion");
        this.cancelarEdicion = document.getElementById("cancelar-edicion");
        this.cerrarPopupOTP = document.getElementById("close-otp");
        this.botonEliminar = document.querySelector(".boton-eliminar");

        // Inputs
        this.inputAccion = document.getElementById("accion");
        this.inputNuevoNombre = document.getElementById("nuevo-nombre");
        this.inputNuevaDivisa = document.getElementById("nueva-divisa");
        this.inputNuevoCorreo = document.getElementById("nuevo-correo");
        this.inputNuevaContrasena = document.getElementById("nueva-contrasena");
        this.inputVisibleNueva = document.getElementById("input-nueva-contrasena");
        this.selectPais = document.getElementById("country-select"); //
        this.inputNuevoPais = document.getElementById("nuevo-pais"); //

        // Grupos
        this.grupoNuevaContrasena = document.getElementById("grupo-nueva-contrasena");

        // Otros elementos
        this.popupTitulo = document.getElementById("popup-titulo");
        this.popupMensaje = document.getElementById("popup-mensaje");
        this.qrCodeContainer = document.getElementById("qrcode");

        // Formularios
        this.formConfirmacion = document.getElementById("form-confirmacion");
    }

    // Inicializar todos los eventos
    addEventListeners() {
      this.botonSetupOTP?.addEventListener('click', this.setupOTP.bind(this));
      this.cerrarPopupOTP.addEventListener('click', this.togglePopupOTP.bind(this));

      this.setupEditarPerfilModal();
      this.setupConfirmarPopup();
      this.setupCambioPais(); // LLamar al método para cambiar el país (si no lo he entendido mal)
      this.setupFormSubmit();
    }

    // Configurar el modal para editar perfil
    setupEditarPerfilModal() {
        this.botonAbrirEditarPerfil?.addEventListener("click", () => {
            this.modalEditar.style.display = "block";
        });

        this.modalEditar?.querySelector(".cerrar-popup")?.addEventListener("click", () => {
            this.modalEditar.style.display = "none";
        });

        this.cancelarEdicion?.addEventListener("click", () => {
            this.modalEditar.style.display = "none";
        });

        window.addEventListener("click", (e) => {
            if (e.target === this.modalEditar) {
                this.modalEditar.style.display = "none";
            }
        });
    }

    // Configurar el popup de confirmación
    setupConfirmarPopup() {
        const abrirPopup = (tipo, campo) => {
            this.popupConfirmacion.style.display = "block";
            if (tipo === "editar") {
                this.popupTitulo.textContent = `Editar ${campo}`;
                this.popupMensaje.textContent = `Introduce tu contraseña para editar ${campo.toLowerCase()}.`;
            } else if (tipo === "eliminar") {
                this.popupTitulo.textContent = `Eliminar cuenta`;
                this.popupMensaje.textContent = `Introduce tu contraseña para eliminar tu cuenta. Esta acción no se puede deshacer.`;
            }
        };
        this.botonEliminar?.addEventListener("click", () => {
            this.inputAccion.value = "eliminar_cuenta";
            this.popupConfirmacion.style.display = "block";
            this.popupTitulo.textContent = `Eliminar cuenta`;
            this.popupMensaje.textContent = `Introduce tu contraseña para eliminar tu cuenta. Esta acción no se puede deshacer.`;
        });
        
        this.botonesEditar.forEach((btn) => {
            const campo = btn.dataset.campo;

            btn.addEventListener("click", () => {
                // Limpiar todos los valores anteriores
                this.inputAccion.value = "";
                this.inputNuevoNombre.value = "";
                this.inputNuevaDivisa.value = "";
                this.inputNuevoCorreo.value = "";
                this.inputNuevaContrasena.value = "";
                this.inputVisibleNueva.value = "";
                this.grupoNuevaContrasena.style.display = "none";

                if (campo === "username") {
                    const nuevoNombre = prompt("Introduce tu nuevo nombre de usuario:");
                    if (nuevoNombre) {
                        this.inputNuevoNombre.value = nuevoNombre;
                        this.inputAccion.value = "editar_nombre";
                        abrirPopup("editar", "nombre de usuario");
                    }
                } else if (campo === "correo") {
                    const nuevoCorreo = prompt("Introduce tu nuevo correo electrónico:");
                    if (nuevoCorreo && nuevoCorreo.includes("@")) {
                        this.inputNuevoCorreo.value = nuevoCorreo;
                        this.inputAccion.value = "editar_correo";
                        abrirPopup("editar", "correo electrónico");
                    } else {
                        alert("Introduce un correo válido.");
                    }
                } else if (campo === "contrasena") {
                    this.inputAccion.value = "editar_contrasena";
                    this.grupoNuevaContrasena.style.display = "block";
                    this.inputVisibleNueva.value = "";
                    abrirPopup("editar", "contraseña");
               } else if (campo === "importe") {
                    const nuevoImporte = prompt("Introduce el nuevo importe máximo de la cuenta:");

                    if (nuevoImporte && !isNaN(nuevoImporte) && parseFloat(nuevoImporte) >= 0) {
                        document.getElementById("nuevo-importe").value = parseFloat(nuevoImporte).toFixed(2);
                        this.inputAccion.value = "editar_importe";
                        abrirPopup("editar", "importe máximo de la cuenta");
                    } else {
                        alert("Introduce un número válido mayor o igual a 0.");
                    }
                }
            });
        });

        this.cerrarPopups.forEach((btn) => {
            btn.addEventListener("click", () => {
                this.popupConfirmacion.style.display = "none";
                this.grupoNuevaContrasena.style.display = "none";
                this.inputVisibleNueva.value = "";
            });
        });

        this.cancelarAccion?.addEventListener("click", () => {
            this.popupConfirmacion.style.display = "none";
            this.grupoNuevaContrasena.style.display = "none";
            this.inputVisibleNueva.value = "";
        });

        window.addEventListener("click", (e) => {
            if (e.target === this.popupConfirmacion) {
                this.popupConfirmacion.style.display = "none";
                this.grupoNuevaContrasena.style.display = "none";
                this.inputVisibleNueva.value = "";
            }
        });
    }

    // Configurar el popup para OTP
    setupOTP() {
        fetch('./PHP/setup_2fa.php')
            .then(response => response.json())
            .then(data => {
                const qrCodeUrl = data.url; // URL del código QR
                const secret = data.secret; // Contraseña secreta
                console.log("URL usada para QR:", qrCodeUrl);
                console.log("Contraseña:", secret);

                this.qrCodeContainer.innerHTML = ""; // Limpiar el contenedor del QR

                new QRCode(this.qrCodeContainer, {
                    text: qrCodeUrl,
                    width: 200,
                    height: 200
                });

                this.togglePopupOTP();
            })
            .catch(error => console.error("Error al configurar OTP:", error));
    }

    togglePopupOTP() {
        this.otpContainer.classList.toggle('show');
    }
    // Metodo para cambiar el pais (Grasias por ordenar el codigo :P)
    setupCambioPais() {
        this.selectPais?.addEventListener("change", (e) => {
            const selected = e.target.value;
            if (selected) {
                this.inputNuevoPais.value = selected;
                this.inputAccion.value = "editar_pais";
                this.popupConfirmacion.style.display = "block";
                this.popupTitulo.textContent = `Editar país`;
                this.popupMensaje.textContent = `Introduce tu contraseña para editar el país.`;
            }
        });
    }


    // Configurar el envío del formulario
    setupFormSubmit() {
        this.formConfirmacion?.addEventListener("submit", (e) => {
            if (this.inputAccion.value === "editar_contrasena") {
                const nueva = this.inputVisibleNueva.value;
                if (!nueva || nueva.length < 2) {
                    e.preventDefault();
                    alert("La nueva contraseña debe tener al menos 2 caracteres.");
                    return;
                }
                this.inputNuevaContrasena.value = nueva;
            }
        });
    }
}

// Inicializar la clase ConfiguracionUI cuando el DOM esté cargado
document.addEventListener("DOMContentLoaded", () => {
    new ConfiguracionUI();
});

