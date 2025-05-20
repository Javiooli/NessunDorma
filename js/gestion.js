import { plotBalanceHistoryChart } from './plotly.js';

class UI {
    constructor() {
        this.cacheDOM();
        this.bindEvents();
        this.updateCheckBoxsStates();
        const addWalletForm = document.getElementById('add-form');
        if (addWalletForm) {
            addWalletForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.addWallet();
            });
        }
    }

    cacheDOM() {
        // SIDEBAR ELEMENTS
        this.menuBtn = document.getElementById('mobile-menu-btn');
        this.closeBtn = document.getElementById('close-mobile-menu');
        this.sidebar = document.getElementById('header-aside');
        // ADD CLIENT ELEMENTS
        this.addClientBtn = document.getElementById('add-client');
        this.addClientPopup = document.querySelector('.add-client-container');
        this.closeClientFormBtn = document.getElementById('close-client-btn');
        // ADD CLIENT CHECK
        this.clientList = document.getElementById('client-list');
        // DROPDOWN CLIENTS ELEMENTS
        this.clientDropdown = document.querySelector('#client-dropdown');
        this.clientToggle = this.clientDropdown.querySelector('#client-dropdown-toggle');
        this.clientMenu = this.clientDropdown.querySelector('#client-dropdown-menu');
        this.clientOptions = this.clientMenu.querySelectorAll('li');
        // DROPDOWN WALLETS ELEMENTS
        this.dropdown = document.querySelector('#wallet-dropdown');
        this.toggle = this.dropdown.querySelector('#wallet-dropdown-toggle');
        this.menu = this.dropdown.querySelector('#wallet-dropdown-menu');
        this.options = this.menu.querySelectorAll('li');
        // ADD WALLET ELEMENTS
        this.addWalletBtn = document.querySelector('#add-wallet-btn');
        this.addWalletSection = document.querySelector('.add-wallet-container');
        this.closeWalletFormBtn = document.getElementById('close-wallet-btn');
        // DELETE WALLET ELEMENTS
        this.deleteButtons = document.getElementById('delete-btn');
        this.deleteAlert = document.querySelector('.delete-alert-container');
        this.confirmDelete = document.getElementById('confirm-delete');
        this.cancelDelete = document.getElementById('cancel-delete');
        this.deleteWalletId = document.getElementById('delete-wallet-id');
        // MAS COSAS
        this.summaryChart = document.getElementById('chart');
        this.mainTitle = document.querySelector('.main-title');
        this.walletTBody = document.querySelector('.actives-tbody');
        this.saldoDisplay = document.getElementById('saldo');
        this.gainsDisplay = document.getElementById('gains');
        //TRANSACTIONS
        this.transactionsDisplay = document.getElementById('transactions-tbody');
        //PDF'S
        this.exportBtn = document.getElementById('export-btn');
    }

    bindEvents() {
        // SIDEBAR EVENTS
        this.menuBtn.addEventListener('click', this.toggleSidebar.bind(this));
        this.closeBtn.addEventListener('click', this.toggleSidebar.bind(this));
        // ADD CLIENT EVENTS
        this.addClientBtn.addEventListener('click', this.toggleClientForm.bind(this));
        this.closeClientFormBtn.addEventListener('click', this.closeClientForm.bind(this));

        // DROPDOWN CLIENTS EVENTS
        this.clientToggle.addEventListener('click', this.toggleClientDropdown.bind(this));
        this.clientOptions.forEach(option => {
            option.addEventListener('click', this.selectClientOption.bind(this));
        });
        // DROPDOWN WALLETS EVENTS
        this.toggle.addEventListener('click', this.toggleDropdown.bind(this));
        this.options.forEach(option => {
            option.addEventListener('click', this.selectOption.bind(this));
        });
        // ADD WALLET EVENTS
        this.addWalletBtn.addEventListener('click', this.toggleWalletForm.bind(this));
        this.closeWalletFormBtn.addEventListener('click', this.toggleWalletForm.bind(this));
        // DELETE WALLET EVENTS
        this.deleteButtons.addEventListener('click', () => {
            this.deleteAlert.classList.toggle('show');
        });
        this.cancelDelete.addEventListener('click', () => {
            this.deleteAlert.classList.toggle('show');
        });
        this.confirmDelete.addEventListener('click', () => {
            this.deleteWallet();
        });
        //EXPORT PDF
        this.exportBtn.addEventListener('click', () => {
            this.exportPDF();
        });
    }

    // SIDEBAR METHODS
    toggleSidebar() {
        this.sidebar.classList.toggle('show');
    }

    // ADD CLIENT METHODS
    toggleClientForm() {
        this.addClientPopup.classList.toggle('show');
    }

    closeClientForm() {
        this.addClientPopup.classList.remove('show');
    }

    // CHECKBOXES METHODS
    updateCheckBoxsStates() {
        // ADD CLIENT CHECK
        this.clientChecks = document.querySelectorAll('.client-check');
        this.clientChecks.forEach(checkbox => {
            checkbox.removeEventListener('click', this.handleCheckboxClick); // Remove any existing listener
            checkbox.addEventListener('click', this.handleCheckboxClick.bind(this)); // Add the new listener
        });
    }

    handleCheckboxClick(e) {
        e.stopPropagation(); // Evita que el clic en el checkbox cierre el sidebar
        const checkbox = e.target.closest('input[type="checkbox"]');
        if (checkbox) {
            const isChecked = checkbox.checked;
            if (isChecked) {
                checkbox.classList.remove('unassigned-check');
                checkbox.classList.add('assigned-check');
                console.log(`Cliente asignado`);
                this.assignUser(e); // Llama a la función para asignar el usuario
            } else {
                checkbox.classList.remove('assigned-check');
                checkbox.classList.add('unassigned-check');
                console.log(`Cliente desasignado`);
                this.unassignUser(e); // Llama a la función para desasignar el usuario
            }
        }
    }

    // AJAX METHODS
    assignUser(event) {
        const checkbox = event.target;
        const userId = checkbox.dataset.userid;
        
        fetch('./PHP/assign_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${userId}`
        })
        .then(response => response.text())
        .then(data => {
            console.log(`RAW DATA: ${data}`);
            if (data.includes("correctamente")) {
                console.log("Cliente seleccionado");
                this.updateCheckBoxsStates();
            } else {
                console.log("Selección no correcta");
            }
        })
    }

    unassignUser(event) {
        const checkbox = event.target;
        const userId = checkbox.dataset.userid;
        
        fetch('./PHP/unassign_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${userId}`
        })
        .then(response => response.text())
        .then(data => {
            console.log(`RAW DATA: ${data}`);
            if (data.includes("correctamente")) {
                console.log("Cliente desasignado.")
                this.updateCheckBoxsStates();
            } else {
                console.log("Desasignación no correcta.");
            }
        })
    }

    // DROPDOWN CLIENT METHODS
    toggleClientDropdown(e) {
        e.stopPropagation();
        this.clientDropdown.classList.toggle('open');
    }

    selectClientOption(e) {
        e.stopPropagation();
        const option = e.target;
        this.clientId = option.getAttribute('data-user-id');
        this.clientToggle.childNodes[0].textContent = option.textContent;
        this.clientDropdown.classList.remove('open');
        console.log('Valor seleccionado:', this.clientId);
        this.defaultCurrency = option.getAttribute('data-user-currency');
        this.getWalletsOfSelectedClient();
        this.getTransactionsOfSelectedClient();
    }

    closeClientDropdown(e) {
        if (this.clientDropdown && !this.clientDropdown.contains(e.target)) {
            this.clientDropdown.classList.remove('open');
        }
        
        if (!this.sidebar.contains(e.target) && !e.target.closest('.menu-btn')) {
            this.sidebar.classList.remove('show');
        }
    }

    getTransactionsOfSelectedClient() {
        const selectedClientId = this.clientId;
        fetch(`PHP/get_transactions_ofClient.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${selectedClientId}`
        })
        .then(response => response.text())
        .then(html => {
            this.transactionsDisplay.innerHTML = "";
            this.transactionsDisplay.innerHTML = html;
        })
        .catch(error => {
            console.error('Error en la solicitud AJAX:', error)
        });
    }

    // GET WALLETS OF SELECTED CLIENT
    getWalletsOfSelectedClient() {
        const selectedClientId = this.clientId;
        console.log(`ID del cliente seleccionado: ${selectedClientId}`);
        fetch(`PHP/fetch_wallets_of_client.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${selectedClientId}`
        })
        .then(response => response.text())
        .then(html => {
            // Aquí debe ser this.menu, no this.clientMenu
            console.log(`HTML: ${html}`);
            this.menu.innerHTML = html;
            this.options = this.menu.querySelectorAll('li');
            this.options.forEach(option => {
                option.addEventListener('click', this.selectOption.bind(this));
            });
        })
        .catch(error => {
            console.error('Error en la solicitud AJAX:', error);
        });
    }

    exportPDF() {
        const selectedClientId = this.clientId;
        if (!selectedClientId) {
            alert("Selecciona un cliente primero.");
            return;
        }
        // Abre el PDF en una nueva pestaña
        window.open(`./PHP/generar_informe_gestion.php?userId=${selectedClientId}`, '_blank');
    }

    // DROPDOWN WALLET METHODS
    toggleDropdown(e) {
        e.stopPropagation();
        this.dropdown.classList.toggle('open');
    }

    selectOption(e) {
        e.stopPropagation();
        const option = e.target;
        this.walletId = option.getAttribute('data-wallet-id');
        // Change only the text inside the <span>
        const span = this.toggle.querySelector('span');
        if (span) {
            span.textContent = option.textContent;
        }
        this.dropdown.classList.remove('open');
        this.deleteWalletId.innerHTML = this.walletId;
        this.selectedWallet = this.walletId;
        this.selectedWalletName = option.textContent;
        this.updateWalletView();
        this.updateChart();
        console.log('Cartera seleccionado:', this.walletId);
    }

    closeDropdown(e) {
        if (this.dropdown && !this.dropdown.contains(e.target)) {
            this.dropdown.classList.remove('open');
        }
        
        if (!this.sidebar.contains(e.target) && !e.target.closest('.menu-btn')) {
            this.sidebar.classList.remove('show');
        }
    }
    // ADD WALLET METHODS
    toggleWalletForm() {
        this.addWalletSection.classList.toggle('show');
    }

    // ADD WALLET AJAX
    addWallet() {
        const selectedClientId = this.clientId;
        const walletName = document.getElementById('wallet-name').value;
        const walletDirection = document.getElementById('wallet-address').value;
        const walletType = document.getElementById('wallet-type').value;

        if (!selectedClientId || !walletName || !walletType) {
            console.error('Faltan datos para crear la cartera');
            return;
        }
        fetch('./PHP/crear_cartera_a_cliente.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=${selectedClientId}&walletName=${walletName}&walletAddress=${walletDirection}&walletType=${walletType}`
        })
        .then(response => response.text())
        .then(data => {
            console.log(`RAW DATA: ${data}`);
            if (data.includes("correctamente")) {
                console.log("Cartera creada correctamente");
                window.location.reload();
            } else {
                console.log("Error al crear la cartera");
            }
        })
        .catch(error => {
            console.error('Error en la solicitud AJAX:', error);
        });
    }

    // DELETE ALERT AJAX
    deleteWallet() {
        if (!this.walletId) {
            console.error('No wallet ID provided');
            return;
        } else {
        fetch(`PHP/delete_wallet.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded', // <-- fixed typo
            },
            body: `walletId=${encodeURIComponent(this.walletId)}` // <-- encode value
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

    updateChart() {
        if (!this.selectedWallet) {
            this.summaryChart.innerHTML = '';
            return;
        }
        if (this.summaryChart) {
            fetch(`./PHP/fetch_balance_hist.php?defaultCurrency=${this.defaultCurrency}&walletId=${this.selectedWallet}`)
                .then(response => response.text())
                .then(data => {
                    try {
                        const balanceData = JSON.parse(data);
                        if (balanceData.error) {
                            this.summaryChart.innerHTML = '';
                            return;
                        }
                        plotBalanceHistoryChart(balanceData, this.defaultCurrency);
                    } catch (e) {
                        this.summaryChart.innerHTML = '';
                    }
                })
                .catch(error => {
                    this.summaryChart.innerHTML = '';
                });
        }
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
}

document.addEventListener('DOMContentLoaded', () => {
    const ui = new UI();
});