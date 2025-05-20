import { iniciarInactividad } from './inactividad.js';
import { plotBalanceHistoryChart } from './plotly.js';



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
        // FORM ELEMENTS
        this.addBtn = document.querySelector('.add-btn');
        this.addSection = document.querySelector('.add-wallet-container');
        this.closeFormBtn = document.getElementById('close-btn');
        // TABLE ELEMENTS
        this.deleteAlert = document.getElementById('delete-alert');
        this.closeAlertBtn = document.getElementById('cancel-delete');
        this.confirmDeleteBtn = document.getElementById('confirm-delete');
        this.deleteWalletId = document.getElementById('delete-wallet-id');
        this.deleteBtn = document.getElementById('delete-btn');
        // JAVI
        this.mainTitle = document.querySelector('.main-title');
        this.walletTBody = document.querySelector('.actives-tbody');
        this.userId = document.getElementById('userId').dataset.userid;
        this.defaultCurrency = document.getElementById('defaultCurrency').dataset.defaultcurrency;
        this.saldoDisplay = document.getElementById('saldo');
        this.gainsDisplay = document.getElementById('gains');
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
        this.addBtn.addEventListener('click', this.toggleForm.bind(this));
        this.closeFormBtn.addEventListener('click', this.toggleForm.bind(this));
        document.addEventListener('click', this.closeDropdown.bind(this));
        //DELETE ALERT EVENTS
        this.deleteBtn.addEventListener('click', () => {
            this.deleteAlert.classList.toggle('show');
        });
        this.closeAlertBtn.addEventListener('click', () => {
            this.deleteAlert.classList.toggle('show');
        });
        this.confirmDeleteBtn.addEventListener('click', () => {
            this.deleteWallet();
        });
    }

    init() {
        
        this.updateChart();
        // Inicia el temporizador de inactividad
        iniciarInactividad();
    }

    updateChart() {
        if (!this.selectedWallet) {
            console.error('Error: No wallet selected');
            return;
        }
        // Inicializa el gráfico
        if (this.summaryChart) {
            // Fetch balance history and plot the chart
            console.log(`./PHP/fetch_balance_hist.php?defaultCurrency=${this.defaultCurrency}&walletId=${this.selectedWallet}`);
                fetch(`./PHP/fetch_balance_hist.php?defaultCurrency=${this.defaultCurrency}&walletId=${this.selectedWallet}`)
                .then(response => response.text()) // Use .text() to inspect the raw response
                .then(data => {
                    console.log('Raw response:', data); // Log the raw response
                    const balanceData = JSON.parse(data); // Parse the JSON manually
                    if (balanceData.error) {
                        console.error('Error:', balanceData.error);
                        return;
                    }
                    plotBalanceHistoryChart(balanceData, this.defaultCurrency);
                })
                .catch(error => {
                    console.error('Error en la solicitud AJAX:', error);
                });
        }
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
        const value = option.getAttribute('data-wallet-id');
        this.toggle.childNodes[0].textContent = option.textContent; // Actualiza solo el texto, no el SVG
        this.dropdown.classList.remove('open');

        this.selectedWallet = value;
        this.selectedWalletName = option.innerHTML;

        console.log('Valor seleccionado:', this.selectedWallet);
        this.updateChart();
        this.updateWalletView();
        this.deleteWalletId.innerHTML = this.selectedWallet;   
    }

    closeDropdown(e) {
        if (this.dropdown && !this.dropdown.contains(e.target)) {
            this.dropdown.classList.remove('open');
        }
        
        if (!this.sidebar.contains(e.target) && !e.target.closest('.menu-btn')) {
            this.sidebar.classList.remove('show');
        }
    }

    // FORM METODS
    toggleForm() {
        this.addSection.classList.toggle('show');
    }

    updateWalletView() {
        const currencies = {
            EUR: '€',
            USD: '$',
            BTC: 'BTC',
            ETH: 'ETH',
            XAU: 'XAU',
            XAG: 'XAG'
        };

        fetch(`PHP/fetch_wallet_balance.php?walletId=${this.selectedWallet}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            this.saldoDisplay.innerHTML = `${parseFloat(data.balance).toFixed(2)} ${currencies[this.defaultCurrency]}`;
            this.gainsDisplay.innerHTML = `${parseFloat(data.gains) >= 0 ? '+' : '-'} ${parseFloat(Math.abs(data.gains)).toFixed(2)} ${currencies[this.defaultCurrency]}`;
            if (data.gains >= 0) {
                this.gainsDisplay.classList.remove('negative');
                this.gainsDisplay.classList.add('positive');
            } else {
                this.gainsDisplay.classList.remove('positive');
                this.gainsDisplay.classList.add('negative');
            }

            
            })
            .catch(error => {
                console.error('Error en la solicitud AJAX:', error);
            });
        this.mainTitle.innerHTML = `Cartera: ${this.selectedWalletName}`;
        if (this.selectedWallet) {
            this.walletTBody.innerHTML = ''; // Delete all child elements
            console.log(`${this.selectedWallet}`);
            fetch(`PHP/fetch_wallet_info.php?walletId=${this.selectedWallet}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }

                    // Handle the array of wallet data
                    data.forEach(wallet => {
                        console.log(`Currency: ${wallet.currency}, Amount: ${wallet.amount}, defaultCurrency: ${this.defaultCurrency}`);
                        // You can update the UI here, for example:
                        const row = document.createElement('tr');
                        fetch(`PHP/convert_currency.php?amount=${wallet.amount}&currency=${wallet.currency}&defaultCurrency=${this.defaultCurrency}`)
                            .then(response => response.json())
                            .then(convertedData => {
                                if (convertedData.error) {
                                    console.error('Error:', convertedData.error);
                                    this.summaryChart.innerHTML = '';
                                    row.innerHTML = `
                                    <td>${wallet.currency}</td>
                                    <td>${wallet.currency == 'EUR' || wallet.currency == 'USD'  ? parseFloat(wallet.amount).toFixed(2) : wallet.amount} ${currencies[wallet.currency]}</td>
                                    <td>${wallet.currency == this.defaultCurrency ? parseFloat(wallet.amount).toFixed(2) : parseFloat(convertedData.convertedAmount).toFixed(2)} ${currencies[this.defaultCurrency]}</td>
                                `;
                                this.walletTBody.appendChild(row);
                                    return;
                                }

                                row.innerHTML = `
                                    <td>${wallet.currency}</td>
                                    <td>${wallet.currency == 'EUR' || wallet.currency == 'USD'  ? parseFloat(wallet.amount).toFixed(2) : wallet.amount} ${currencies[wallet.currency]}</td>
                                    <td>${wallet.currency == this.defaultCurrency ? parseFloat(wallet.amount).toFixed(2) : parseFloat(convertedData.convertedAmount).toFixed(2)} ${currencies[this.defaultCurrency]}</td>
                                `;
                                this.walletTBody.appendChild(row);
                                })
                                .catch(error => {
                                    console.error('Error en la solicitud AJAX:', error);
                                });
                        });
                    })
                    .catch(error => {
                        console.error('Error en la solicitud AJAX:', error);
                    });
            }
        }

    // DELETE ALERT AJAX
        deleteWallet() {
        const walletId = this.deleteWalletId.innerHTML;
        fetch(`PHP/delete_wallet.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded', // <-- fixed typo
            },
            body: `walletId=${encodeURIComponent(walletId)}` // <-- encode value
        })
        .then(response => response.text())
        .then(data => {
            // Optionally, check for success or error in the response
            console.log('Wallet deleted successfully');
            this.deleteAlert.classList.toggle('show');
            window.location.reload();
        })
        .catch(error => {
            console.error('Error en la solicitud AJAX:', error);
        });
    }
}


document.addEventListener('DOMContentLoaded', () => {
    const ui = new UI();
});