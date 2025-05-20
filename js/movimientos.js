import { iniciarInactividad } from './inactividad.js';
import { plotEURDataChart, plotUSDDataChart } from './plotly.js';



/* CODIGO REESCRITO MAS LEGIBLE Y ESCALABLE */
class UI {
    constructor() {
        this.lastSort = 'transactionDate';
        this.sortDirection = 'asc';
        this.cacheDOM();
        this.bindEvents();
        this.init();
    }

    cacheDOM() {
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
        this.addSection = document.querySelector('.add-container');
        this.closeFormBtn = document.getElementById('close-btn');
        // TABLE ELEMENTS
        this.deleteBtn = document.getElementById('delete-btn');
        this.editBtn = document.getElementById('edit-btn');
        this.deleteAlert = document.getElementById('delete-alert-container');
        this.editForm = document.getElementById('edit-form');
        this.closeAlertBtn = document.getElementById('cancel-btn');
        this.transactionsGrid = document.getElementById('transactions-grid');
        // ADD FORM ELEMENTS
        this.walletSelect = document.getElementById('wallet');
        this.currencySelect = document.getElementById('currency');
        this.newCurrencySelect = document.getElementById('new_currency');
        this.newCurrencyLabel = document.getElementById('new_currency_label');

        this.destinationWalletSelect = document.getElementById('destination');
        this.destinationLabel = document.getElementById('destination_label');


        this.transactionTypeSelect = document.getElementById('type');
        

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
        // FORM EVENTS
        // TABLE EVENTS
        if (this.transactionsGrid) {
            this.bindDeleteTransactions();
        }
        if (this.closeAlertBtn) {
            this.closeAlertBtn.addEventListener('click', this.deleteTransaction.bind(this));
        }
        this.walletSelect.addEventListener('change', (event) => {
            const walletId = event.target.value;
            if (walletId) {
                this.updateAddForm();
            } else {
                this.currencySelect.innerHTML = '<option value="">Selecciona una cartera primero</option>';
                this.currencySelect.disabled = true;
            }
        });

        this.transactionTypeSelect.addEventListener('change', this.updateAddForm.bind(this));
        this.newCurrencySelect.addEventListener('change', this.updateDestinationWallet.bind(this));

    }

    init() {
        // Inicializa el gráfico
        if (this.summaryChart && this.summaryChart.getAttribute('currency') == 'EUR') {
            plotEURDataChart();
        } else if (this.summaryChart && this.summaryChart.getAttribute('currency') == 'USD') {
            plotUSDDataChart();
        }

        // Inicia el temporizador de inactividad
        iniciarInactividad();

        if (this.walletSelect.value) {
            this.updateAddForm();
        } else {
            this.currencySelect.innerHTML = '<option value="">Selecciona una cartera primero</option>';
            this.currencySelect.disabled = true;
        }
    }

    // SIDEBAR METHODS
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
        this.sortTransactions(value);
    }

    closeDropdown(e) {
        if (this.dropdown && !this.dropdown.contains(e.target)) {
            this.dropdown.classList.remove('open');
        }
        
        if (!this.sidebar.contains(e.target) && !e.target.closest('.menu-btn')) {
            this.sidebar.classList.remove('show');
        }
    }
    // AJAX METOD
    sortTransactions(value) {
        fetch('movimientos.php', {
            method: 'POST',
            headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `sort=${encodeURIComponent(value)}&direction=${encodeURIComponent(this.sortDirection)}` // Enviamos el valor de la opción seleccionada y la dirección
        })
        .then(response => response.text())
        .then(data => {
            // Actualizamos el contenido de la tabla con las transacciones ordenadas
            document.getElementById('transactions-grid').innerHTML = data;
            // Actualizamos la última dirección de ordenación
            this.sortDirection = this.lastSort === value ? (this.sortDirection === 'desc' ? 'asc' : 'desc') : 'asc';
            this.lastSort = value;
        })
        .catch(error => {
            console.error('Error al ordenar las transacciones:', error);
        });
    }

    // FORM METODS
    toggleForm() {
        this.addSection.classList.toggle('show');
    }

    updateAddForm() {
        const walletId = this.walletSelect.value;
        const userId = document.getElementById('userId').dataset.userid; // Assuming there's an input or element with the user's ID

        fetch(`PHP/get_wallet_currencies.php?walletId=${walletId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    this.currencySelect.innerHTML = '<option value="">Error al cargar las divisas</option>';
                    this.newCurrencySelect.innerHTML = '<option value="">Error al cargar las divisas</option>';
                    this.currencySelect.disabled = true;
                    return;
                }
    
                // Limpiar el select de currency
                this.currencySelect.innerHTML = '';
                this.newCurrencySelect.innerHTML = '';
    
                // Añadir las divisas permitidas según el tipo de cartera
                const currencies = data.currencies;
                currencies.forEach(currency => {
                    const option = document.createElement('option');
                    option.value = currency;
                    option.text = currency;
                    this.currencySelect.appendChild(option);
                });

                const currencyList = ['USD', 'EUR', 'ETH', 'BTC', 'XAU', 'XAG'];
                currencyList.forEach(currency => {
                    const option = document.createElement('option');
                    option.value = currency;
                    option.text = currency;
                    this.newCurrencySelect.appendChild(option);
                });
    
                // Habilitar el select
                this.currencySelect.disabled = false;
            })
            .catch(error => {
                console.error('Error en la solicitud AJAX:', error);
                this.currencySelect.innerHTML = '<option value="">Error al cargar las divisas</option>';
                this.newCurrencySelect.innerHTML = '<option value="">Error al cargar las divisas</option>';
                this.currencySelect.disabled = true;
            });


            this.updateDestinationWallet();

            if (this.transactionTypeSelect.value === 'Trade' || this.transactionTypeSelect.value === 'Transfer') {
                this.newCurrencySelect.style.display = 'block';
                this.newCurrencyLabel.style.display = 'block';
                this.newCurrencySelect.disabled = false;
                this.newCurrencySelect.required = true;
                this.newCurrencySelect.innerHTML = this.currencySelect.innerHTML; // Copy the options from currencySelect
            } else {
                this.newCurrencySelect.style.display = 'none';
                this.newCurrencyLabel.style.display = 'none';
                this.newCurrencySelect.disabled = true;
            }

            if (this.transactionTypeSelect.value === 'Transfer') {
                this.destinationWalletSelect.style.display = 'block';
                this.destinationLabel.style.display = 'block';
                this.destinationWalletSelect.disabled = false;
                this.destinationWalletSelect.required = true;
                this.destinationWalletSelect.value = this.walletSelect.value; // Set the value to the selected wallet
                this.destinationWalletSelect.innerHTML = this.walletSelect.innerHTML; // Copy the options from walletSelect

            } else {
                this.destinationWalletSelect.style.display = 'none';
                this.destinationLabel.style.display = 'none';
                this.destinationWalletSelect.disabled = true;
            }
    }

    updateDestinationWallet() {
        const userId = document.getElementById('userId').dataset.userid; // Assuming there's an input or element with the user's ID
        const newCurrency = this.newCurrencySelect.value;

        if (!this.evalCurrencyExchange()) {
            this.destinationWalletSelect.style.display = 'block';
            this.destinationLabel.style.display = 'block';
            this.destinationWalletSelect.disabled = false;
            this.destinationWalletSelect.required = true;
        } else {
            this.destinationWalletSelect.style.display = 'none';
            this.destinationLabel.style.display = 'none';
            this.destinationWalletSelect.disabled = true;
            this.destinationWalletSelect.value = null; // Set the value to the selected wallet
        }

        if (newCurrency) {
            fetch(`PHP/get_wallets.php?userId=${userId}&newCurrency=${newCurrency}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    this.destinationWalletSelect.innerHTML = '<option value="">Error al cargar las carteras</option>';
                    return;
                }

                // Limpiar el select de destinationWallet
                this.destinationWalletSelect.innerHTML = '';

                // Añadir las carteras del usuario
                const wallets = data.wallets;
                wallets.forEach(wallet => {
                    console.log(wallet.walletName);
                    const option = document.createElement('option');
                    option.value = wallet.walletId; // Bind walletId as value
                    option.text = wallet.walletName; // Bind walletName as text
                    this.destinationWalletSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error en la solicitud AJAX:', error);
                this.destinationWalletSelect.innerHTML = '<option value="">Error al cargar las carteras</option>';
            });
        }
    }

    evalCurrencyExchange() {
        const selectedCurrency = this.currencySelect.value;
        const newCurrency = this.newCurrencySelect.value;
        const walletTypes = {
            'USD': 'USD',
            'EUR': 'EUR',
            'ETH': 'Crypto',
            'BTC': 'Crypto',
            'XAU': 'Gold',
            'XAG': 'Gold',
        };

        if (walletTypes[selectedCurrency] === walletTypes[newCurrency]) {
            return true;
        } else {
            return false;
        }
    }

    // FORM METODS
    toggleForm() {
        this.addSection.classList.toggle('show');
    }

    // TABLE METODS
    bindDeleteTransactions() {
        // Use event delegation on the parent container
        this.transactionsGrid.addEventListener('click', (e) => {
            const target = e.target;
    
            // Check if the clicked element is the delete button
            if (target.classList.contains('delete-btn')) {
                this.deleteTransaction(target);
            }
            // Check if the clicked element is the edit button
            else if (target.classList.contains('edit-btn')) {
                this.editTransaction(target);
            }
        });
    }

    deleteTransaction(element) {
        const transactionID = element.closest('[data-id]').getAttribute('data-id');
    
        // Confirm deletion
        if (confirm(`Are you sure you want to delete transaction ID: ${transactionID}?`)) {
            // Send a request to the server to delete the transaction
            fetch('./PHP/delete_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${encodeURIComponent(transactionID)}` // Send the transaction ID
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the transaction row from the DOM
                    alert('Transacción eliminada con éxito.');
                    location.reload();
                } else {
                    alert('ERROR: ' + data.error);
                }
            })
            .catch(error => {
                //console.error('ERROR:', error);
                //alert(error);
                alert('Transacción eliminada con éxito.');
                    location.reload();
            });
        }
    }

    editTransaction(element) {
        this.editForm.classList.toggle('show');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const ui = new UI();
});