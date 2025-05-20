import { iniciarInactividad } from './inactividad.js';
import { plotEURDataChart, plotUSDDataChart } from './plotly.js';



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
        
    }

    bindEvents() {
        // SIDEBAR EVENTS
        this.menuBtn.addEventListener('click', this.toggleSidebar.bind(this));
        this.closeBtn.addEventListener('click', this.toggleSidebar.bind(this));
    }

    init() {
        // Inicializa el gráfico
        if (this.summaryChart && this.summaryChart.getAttribute('currency') == 'EUR') {
            plotEURDataChart(this.summaryChart.getAttribute('currency'));
        } else if (this.summaryChart && this.summaryChart.getAttribute('currency') == 'USD') {
            plotUSDDataChart(this.summaryChart.getAttribute('currency'));
        }

        // Inicia el temporizador de inactividad
        iniciarInactividad();
    }

    // SIDEBAR METODS
    toggleSidebar() {
        this.sidebar.classList.toggle('show');
    }

    // AJAX METOD
    sortTransactions(value) {
        fetch('home.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'sort=' + encodeURIComponent(value) // Enviamos el valor de la opción seleccionada
        })
        .then(response => response.text())
        .then(data => {
            // Actualizamos el contenido de la tabla con las transacciones ordenadas
            document.getElementById('transactions-grid').innerHTML = data;
        })
        .catch(error => {
            console.error('Error al ordenar las transacciones:', error);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const ui = new UI();
});