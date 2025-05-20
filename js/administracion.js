class UI {
    constructor() {
        this.lastSort = 'default';
        this.sortDirection = 'desc';
        this.cacheDOM();
        this.bindEvents();
    }

    cacheDOM() {
        // SIDEBAR ELEMENTS
        this.menuBtn = document.querySelector('.menu-btn');
        this.closeBtn = document.querySelector('.close-btn');
        this.sidebar = document.getElementById('header-aside');
        // DROPDOWN ELEMENTS
        this.dropdown = document.querySelector('.custom-dropdown');
        this.toggle = this.dropdown.querySelector('.dropdown-toggle');
        this.menu = this.dropdown.querySelector('.dropdown-menu');
        this.options = this.menu.querySelectorAll('li');
        // TABLE ELEMENTS
        this.verifiedChecks = document.querySelectorAll('.verified-check');
        this.unverifiedChecks = document.querySelectorAll('.unverified-check');
        // DELETE ELEMENTS
        this.deleteButtons = document.querySelectorAll('.delete-btn'); // Updated selector
        this.deleteAlert = document.querySelector('.delete-alert-container');
        this.confirmDelete = document.querySelector('.confirm-delete');
        this.cancelDelete = document.querySelector('.cancel-delete');
        this.deleteUserId = null; // To store the user ID to delete
    }

    bindEvents() {
        // SIDEBAR EVENTS
        this.menuBtn.addEventListener('click', this.toggleSidebar.bind(this));
        this.closeBtn.addEventListener('click', this.toggleSidebar.bind(this));
        // DROPDOWN EVENTS
        this.toggle.addEventListener('click', this.toggleDropdown.bind(this));
        this.options.forEach(option => {
            option.addEventListener('click', this.selectOption.bind(this));
        });
        // TABLE EVENTS
        this.updateTableEvents();
        this.cancelDelete.addEventListener('click', this.closeDeletePopup.bind(this));
        this.confirmDelete.addEventListener('click', this.deleteUser.bind(this));
    }

    updateTableEvents() {
        // Reasignar los selectores para incluir los nuevos elementos
        this.verifiedChecks = document.querySelectorAll('.verified-check');
        this.unverifiedChecks = document.querySelectorAll('.unverified-check');
        this.deleteButtons = document.querySelectorAll('.delete-btn');
    
        // Asociar eventos a los nuevos elementos
        this.verifiedChecks.forEach(checkbox => {
            checkbox.removeEventListener('change', this.unverifyUser.bind(this));
            checkbox.addEventListener('change', (event) => this.unverifyUser(event));
        });
    
        this.unverifiedChecks.forEach(checkbox => {
            checkbox.removeEventListener('change', this.verifyUser.bind(this));
            checkbox.addEventListener('change', (event) => this.verifyUser(event));
        });
    
        this.deleteButtons.forEach(button => {
            button.removeEventListener('click', this.openDeletePopup.bind(this));
            button.addEventListener('click', (event) => this.openDeletePopup(event));
        });
    }

    // SIDEBAR METODS
    toggleSidebar() {
        this.sidebar.classList.toggle('show');
    }

    // DROPDOWN METHODS
    toggleDropdown(e) {
        e.stopPropagation(); // Evita que el clic en el dropdown cierre el sidebar
        this.dropdown.classList.toggle('open');
    }

    selectOption(e) {
        e.stopPropagation(); // Evita que el clic en una opción cierre el sidebar
        const option = e.target; // Obtiene el elemento li que desencadenó el evento
        const value = option.getAttribute('data-value');
        this.toggle.childNodes[0].textContent = option.textContent; // Actualiza solo el texto, no el SVG
        this.dropdown.classList.remove('open');

        console.log('Valor seleccionado:', value);
        this.sortUsers(value);
    }

    closeDropdown(e) {
        if (this.dropdown && !this.dropdown.contains(e.target)) {
            this.dropdown.classList.remove('open');
        }
        
        if (!this.sidebar.contains(e.target) && !e.target.closest('.menu-btn')) {
            this.sidebar.classList.remove('show');
        }
    }

    // DELETE METHODS
    openDeletePopup(event) {
        const button = event.target.closest('.delete-btn');
        this.deleteUserId = button.closest('tr').querySelector('[data-userid]').getAttribute('data-userid');
        
        // Actualiza el contenido del span con el ID del usuario
        document.getElementById('delete-user-id').textContent = this.deleteUserId;
    
        // Muestra el popup
        this.deleteAlert.classList.add('show');
    }
    
    closeDeletePopup() {
        this.deleteAlert.classList.remove('show'); // Elimina la clase .show
        this.deleteUserId = null;
    }
    
    // AJAX METODS
    verifyUser(event){
        const unverifiedCheck = event.target.closest('.unverified-check');
        unverifiedCheck.checked = true;
        unverifiedCheck.classList.remove('unverified-check');
        unverifiedCheck.classList.add('verified-check');
        unverifiedCheck.removeEventListener('change', this.verifyUser.bind(this));
        unverifiedCheck.addEventListener('change', (event) => this.unverifyUser(event));
        const userId = unverifiedCheck.getAttribute('data-userId');
        fetch('./PHP/verifyUser.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'userId=' + encodeURIComponent(userId)
        })

    }

    unverifyUser(event){
        const verifiedCheck = event.target.closest('.verified-check');
        verifiedCheck.checked = false;
        verifiedCheck.classList.remove('verified-check');
        verifiedCheck.classList.add('unverified-check');
        verifiedCheck.removeEventListener('change', this.unverifyUser);
        verifiedCheck.addEventListener('change', (event) => this.verifyUser(event));
        const userId = verifiedCheck.getAttribute('data-userId');
        fetch('./PHP/unverifyUser.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'userId=' + encodeURIComponent(userId)
        })
    }


    sortUsers(value) {
        fetch('administracion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `sort=${encodeURIComponent(value)}&direction=${encodeURIComponent(this.sortDirection)}` // Enviamos el valor de la opción seleccionada y la dirección
        })
        .then(response => response.text())
        .then(data => {
            // Actualizamos el contenido de la tabla con los usuarios ordenados
            document.getElementById('users-table-body').innerHTML = data;
            // Actualizamos la última dirección de ordenación
            this.sortDirection = this.lastSort === value ? (this.sortDirection === 'desc' ? 'asc' : 'desc') : 'asc';
            this.lastSort = value;
            this.updateTableEvents();
        })
        .catch(error => {
            console.error('Error al ordenar los usuarios:', error);
        });
    }

    deleteUser() {
        if (!this.deleteUserId) return;
    
        // Enviar solicitud AJAX para eliminar el usuario
        fetch('./PHP/deleteUser.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${encodeURIComponent(this.deleteUserId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar la fila del usuario de la tabla
                document.querySelector(`[data-userid="${this.deleteUserId}"]`).closest('tr').remove();
            } else {
                alert('Error al eliminar el usuario: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error al eliminar el usuario:', error);
            alert('Ocurrió un error al eliminar el usuario.');
        })
        .finally(() => {
            this.closeDeletePopup(); // Cerrar el popup
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const ui = new UI();
});