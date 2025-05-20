import { iniciarInactividad } from './inactividad.js';
import { plotEURDataChart, plotUSDDataChart } from './plotly.js';
import * as d3 from "https://cdn.jsdelivr.net/npm/d3@7/+esm";


/* CODIGO REESCRITO MAS LEGIBLE Y ESCALABLE */
class UI {
    constructor() {
        this.cacheDOM();
        this.bindEvents();
        this.init();
    }

    cacheDOM() {
        this.summaryChart = document.getElementById('chart');
        // SIDEBAR ELEMENTS
        this.menuBtn = document.querySelector('.menu-btn');
        this.closeBtn = document.querySelector('.close-btn');
        this.sidebar = document.getElementById('header-aside');
        // DROPDOWN ELEMENTS
        this.dropdown = document.querySelector('.custom-dropdown');
        this.toggle = this.dropdown.querySelector('.dropdown-toggle');
        this.menu = this.dropdown.querySelector('.dropdown-menu');
        this.options = this.menu.querySelectorAll('li');
        
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
        document.addEventListener('click', this.closeDropdown.bind(this));
    }

    init() {
        // Inicializa el gráfico
        if (this.summaryChart) {
            const default_currency = this.summaryChart.getAttribute('currency')
            if (default_currency == 'EUR') {
                plotEURDataChart(default_currency);
                d3.json("./PHP/EUR_rates_to_json.php")
                    .then(data => {
                        this.data = data; // Guarda los datos en la variable de instancia
                        this.updateTable(default_currency);
                        this.sortTable('name'); // Ordena por nombre después de actualizar la tabla
                    });
            } else if (default_currency == 'USD') {
                plotUSDDataChart(default_currency);
                d3.json("./PHP/USD_rates_to_json.php")
                    .then(data => {
                        this.data = data; // Guarda los datos en la variable de instancia
                        this.updateTable(default_currency);
                        this.sortTable('name'); // Ordena por nombre después de actualizar la tabla
                    });
            }
        }

        // Inicia el temporizador de inactividad
        iniciarInactividad();
    }

    // SIDEBAR METHODS
    toggleSidebar() {
        this.sidebar.classList.toggle('show');
    }

    // DROPDOWN METHODS
    // Método para abrir el dropdown
    toggleDropdown(e) {
        e.stopPropagation(); // Evita que el clic en el dropdown cierre el sidebar
        this.dropdown.classList.toggle('open');
    }

    // Método para cerrar el dropdown y el sidebar si se hace clic fuera de ellos
    closeDropdown(e) {
        if (this.dropdown && !this.dropdown.contains(e.target)) {
            this.dropdown.classList.remove('open');
        }
        
        if (!this.sidebar.contains(e.target) && !e.target.closest('.menu-btn')) {
            this.sidebar.classList.remove('show');
        }
    }

    //MÉTODOS DE LA TABLA

    // Función para actualizar la tabla con los datos de monedas
    updateTable(default_currency) {
        const tbody = document.querySelector('.portfolio-table tbody');
        tbody.innerHTML = ""; // Limpiar tabla
    
        for (const currency in this.data) {
            const fila = document.createElement('tr');
    
            // Nombre de la divisa
            const tdNombre = document.createElement('td');
            tdNombre.setAttribute('data-label', 'currency');
            tdNombre.textContent = currency;
            const colors = {
                'XAU': 'gold',
                'XAG': 'silver',
                'BTC': 'orange',
                'ETH': 'DarkViolet',
                'EUR': 'green',
                'USD': 'green'
            };
            tdNombre.style = `color: ${colors[currency]};`; // Cambia el color del texto según la divisa
    
            // Valor de la divisa
            const tdValor = document.createElement('td');
            tdValor.setAttribute('data-label', 'value');
            tdValor.textContent = `${(this.data[currency].values[this.data[currency].values.length - 1]).toFixed(2)} ${default_currency == "EUR" ? "€" : "$"}`; // Último valor
    
            // Variación 1 día
            const tdVariacion1d = document.createElement('td');
            tdVariacion1d.setAttribute('data-label', 'variation');
            const calc1d = (((this.data[currency].values[this.data[currency].values.length - 1] / this.data[currency].values[this.data[currency].values.length - 2])* 100) - 100).toFixed(2);
            tdVariacion1d.textContent = `${calc1d} %`; // Diferencia entre el último y el penúltimo

            //Verde o rojo dependiendo de si es positivo o negativo
            if (calc1d > 0) {
                tdVariacion1d.style = 'color: green;';
            } else if (calc1d < 0) {
                tdVariacion1d.style = 'color: red;';
            }
    
            // Variación 7 días
            const tdVariacion7d = document.createElement('td');
            tdVariacion7d.setAttribute('data-label', 'variation');
            const calc7d = (((this.data[currency].values[this.data[currency].values.length - 1] / this.data[currency].values[this.data[currency].values.length - 8])* 100) - 100).toFixed(2);
            tdVariacion7d.textContent = `${calc7d} %`; // Diferencia entre el último y el de hace 7 días

            //Verde o rojo dependiendo de si es positivo o negativo
            if (calc7d > 0) {
                tdVariacion7d.style = 'color: green;';
            } else if (calc7d < 0) {
                tdVariacion7d.style = 'color: red;';
            }

            //Botón de alerta
            const tdAlerta = document.createElement('td');
            const userId = document.getElementById('userId').dataset.userid; // Obtiene el userId del input oculto
            fetch(`./PHP/check_alert_status.php?userId=${userId}&currency=${currency}`)
                .then(response => response.json())
                .then(data => {
                    const isActive = data.active === 1; // Verifica si la alerta está activa
                    tdAlerta.innerHTML = `<label class="container">
                        <input type="checkbox" ${isActive ? 'checked' : ''}/>
                        <svg fill="red" viewBox="0 0 448 512" height="1em" xmlns="http://www.w3.org/2000/svg" class="bell-regular">
                            <path d="M224 0c-17.7 0-32 14.3-32 32V49.9C119.5 61.4 64 124.2 64 200v33.4c0 45.4-15.5 89.5-43.8 124.9L5.3 377c-5.8 7.2-6.9 17.1-2.9 25.4S14.8 416 24 416H424c9.2 0 17.6-5.3 21.6-13.6s2.9-18.2-2.9-25.4l-14.9-18.6C399.5 322.9 384 278.8 384 233.4V200c0-75.8-55.5-138.6-128-150.1V32c0-17.7-14.3-32-32-32zm0 96h8c57.4 0 104 46.6 104 104v33.4c0 47.9 13.9 94.6 39.7 134.6H72.3C98.1 328 112 281.3 112 233.4V200c0-57.4 46.6-104 104-104h8zm64 352H224 160c0 17 6.7 33.3 18.7 45.3s28.3 18.7 45.3 18.7s33.3-6.7 45.3-18.7s18.7-28.3 18.7-45.3z"></path>
                        </svg>
                        <svg fill="green" viewBox="0 0 448 512" height="1em" xmlns="http://www.w3.org/2000/svg" class="bell-solid">
                            <path d="M224 0c-17.7 0-32 14.3-32 32V51.2C119 66 64 130.6 64 208v18.8c0 47-17.3 92.4-48.5 127.6l-7.4 8.3c-8.4 9.4-10.4 22.9-5.3 34.4S19.4 416 32 416H416c12.6 0 24-7.4 29.2-18.9s3.1-25-5.3-34.4l-7.4-8.3C401.3 319.2 384 273.9 384 226.8V208c0-77.4-55-142-128-156.8V32c0-17.7-14.3-32-32-32zm45.3 493.3c12-12 18.7-28.3 18.7-45.3H224 160c0 17 6.7 33.3 18.7 45.3s28.3 18.7 45.3 18.7s33.3-6.7 45.3-18.7z"></path>
                        </svg>
                    </label>`;
                })
                .catch(error => console.error('Error:', error));

            tdAlerta.addEventListener('click', (e) => {
                e.stopPropagation(); // Evita que el clic en el checkbox cierre el sidebar
                const checkbox = e.target.closest('input[type="checkbox"]');
                if (checkbox) {
                    const isChecked = checkbox.checked;
                    if (isChecked) {
                        console.log(`Alerta activada para ${currency}`);
                        const userId = document.getElementById('userId').dataset.userid; // Obtiene el userId del input oculto
                        this.activateAlert(userId, currency); // Llama a la función para activar la alerta
                    } else {
                        console.log(`Alerta desactivada para ${currency}`);
                        const userId = document.getElementById('userId').dataset.userid; // Obtiene el userId del input oculto
                        this.disableAlert(userId, currency); // Llama a la función para desactivar la alerta
                    }
                }
            });
            
    
            // Agregar las celdas a la fila
            fila.appendChild(tdNombre);
            fila.appendChild(tdValor);
            fila.appendChild(tdVariacion1d);
            fila.appendChild(tdVariacion7d);
            fila.appendChild(tdAlerta);
    
            // Agregar la fila al tbody
            tbody.appendChild(fila);
        };
    }

    // Métodos para ordenar la tabla
    // Método para seleccionar una opción del dropdown
    selectOption(e) {
        e.stopPropagation(); // Evita que el clic en una opción cierre el sidebar
        const option = e.target; // Obtiene el elemento li que desencadenó el evento
        const value = option.getAttribute('data-value');
        this.toggle.childNodes[0].textContent = option.textContent; // Actualiza solo el texto, no el SVG
        this.dropdown.classList.remove('open');

        console.log('Valor seleccionado:', value);
        this.sortTable(value);
    }

    // Método para ordenar las transacciones según el valor seleccionado (nombre, valor, variación 1d, variación 7d)
    sortTable(value) {
        const tbody = document.querySelector('.portfolio-table tbody');
        const rows = Array.from(tbody.querySelectorAll('tr')); // Convierte la NodeList en un array
        const oldRows = [...rows]; // Guarda las filas originales para invertir si son iguales

        // Ordena las filas según el valor seleccionado
        switch (value) {
            case 'name':
                rows.sort((a, b) => b.cells[0].textContent.localeCompare(a.cells[0].textContent));
                if (this.compareRows(oldRows, rows)) { // Si las filas originales son iguales a las ordenadas
                    rows.reverse(); // Si son iguales, invierte el orden
                }
                break;
            case 'value':
                rows.sort((a, b) => {
                    const valueA = parseFloat(a.cells[1].textContent.slice(0, -2)); // Elimina el símbolo de moneda
                    const valueB = parseFloat(b.cells[1].textContent.slice(0, -2)); // Elimina el símbolo de moneda
                    return valueB - valueA; // Ordena de mayor a menor
                });
                if (this.compareRows(oldRows, rows)) { // Si las filas originales son iguales a las ordenadas
                    rows.reverse(); // Si son iguales, invierte el orden
                }
                break;
            case 'variation-1d':
                rows.sort((a, b) => {
                    const valueA = parseFloat(a.cells[2].textContent.slice(0, -2)); // Elimina el porcentaje
                    const valueB = parseFloat(b.cells[2].textContent.slice(0, -2)); // Elimina el porcentaje
                    return valueB - valueA; // Ordena de mayor a menor
                });
                if (this.compareRows(oldRows, rows)) { // Si las filas originales son iguales a las ordenadas
                    rows.reverse(); // Si son iguales, invierte el orden
                }
                break;
            case 'variation-7d':
                rows.sort((a, b) => {
                    const valueA = parseFloat(a.cells[3].textContent.slice(0, -2)); // Elimina el porcentaje
                    const valueB = parseFloat(b.cells[3].textContent.slice(0, -2)); // Elimina el porcentaje
                    return valueB - valueA; // Ordena de mayor a menor
                });
                if (this.compareRows(oldRows, rows)) { // Si las filas originales son iguales a las ordenadas
                    rows.reverse(); // Si son iguales, invierte el orden
                }
                break;
            default:
                break;
        }

        

        // Vuelve a agregar las filas ordenadas al tbody
        rows.forEach(row => tbody.appendChild(row));
    }

    // Método para comparar las filas originales con las filas ordenadas
    compareRows(oldRows, newRows) {
        let areEqual = true;
        if (oldRows.length !== newRows.length) {
            areEqual = false;
        } else {
            for (let i = 0; i < oldRows.length; i++) {
                if (oldRows[i].innerHTML !== newRows[i].innerHTML) {
                    areEqual = false;
                    break;
                }
            }
        }
        return areEqual;
    }

    // METODOS ALERTAS
    activateAlert(userId, currency) {
        fetch('./PHP/activate_alert.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${userId}&currency=${currency}`
        })
        .then(response => response.text())
        .then(data => console.log(data))
        .catch(error => console.error('Error:', error));
    }

    disableAlert(userId, currency) {
        fetch('./PHP/disable_alert.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${userId}&currency=${currency}`
        })
        .then(response => response.text())
        .then(data => console.log(data))
        .catch(error => console.error('Error:', error));
    }


}

document.addEventListener('DOMContentLoaded', () => {
    const ui = new UI();
});