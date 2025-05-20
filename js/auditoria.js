class auditoriaUI {
    constructor() {
        //AUDITORY SECTION
        this.lastSort = 'date';
        this.sortDirection = 'desc';
        this.lastFilterSelected = 'default';
        //IP SECTION
        this.isVerifiedFilter = true;
        this.ipLastFilterSelected = 'default';
        this.ipLastSort = 'lastUse';
        this.ipSortDirection = 'desc';
        this.cacheDOM();
        this.bindEvents();
    }

    cacheDOM() {
        this.menuBtn = document.querySelector('.menu-btn');
        this.closeBtn = document.querySelector('.close-btn');
        this.sidebar = document.getElementById('header-aside');
        
        // Dropdown Sort Auditoría
        this.dropdown = document.querySelector('.custom-dropdown');
        this.toggle = this.dropdown.querySelector('.dropdown-toggle');
        this.menu = this.dropdown.querySelector('.dropdown-menu');
        this.options = this.menu.querySelectorAll('li');
        
        // Dropdown Filter Auditoría
        this.filterDropdown = document.querySelector('.filter-dropdown');
        this.filterToggle = this.filterDropdown.querySelector('.filter-toggle');
        this.filterMenu = this.filterDropdown.querySelector('.filter-menu');
        this.filterOptions = this.filterMenu.querySelectorAll('li');
        
        // Dropdown Sort IP
        this.ipDropdown = document.querySelector('.customIP-dropdown');
        this.ipToggle = this.ipDropdown.querySelector('.dropdownIP-toggle');
        this.ipMenu = this.ipDropdown.querySelector('.dropdownIP-menu');
        this.ipOptions = this.ipMenu.querySelectorAll('li');
        
        // Dropdown Filter IP
        this.filterDropdownIP = document.querySelector('.filterIP-dropdown');
        this.filterToggleIP = this.filterDropdownIP.querySelector('.filterIP-toggle');
        this.filterMenuIP = this.filterDropdownIP.querySelector('.filterIP-menu');
        this.filterOptionsIP = this.filterMenuIP.querySelectorAll('li');
    }

    bindEvents() {
        // Sidebar Events
        this.menuBtn.addEventListener('click', this.toggleSidebar.bind(this));
        this.closeBtn.addEventListener('click', this.toggleSidebar.bind(this));
        
        // Dropdown Sort Auditoría
        this.toggle.addEventListener('click', this.toggleDropdown.bind(this));
        this.options.forEach(option => {
            option.addEventListener('click', this.selectOption.bind(this));
        });
        
        // Dropdown Filter Auditoría
        this.filterToggle.addEventListener('click', this.toggleFilterDropdown.bind(this));
        this.filterOptions.forEach(option => {
            option.addEventListener('click', this.selectFilterOption.bind(this));
        });
        
        // Dropdown Sort IP
        this.ipToggle.addEventListener('click', this.toggleIPDropdown.bind(this));
        this.ipOptions.forEach(option => {
            option.addEventListener('click', this.selectIPOption.bind(this));
        });
        
        // Dropdown Filter IP
        this.filterToggleIP.addEventListener('click', this.toggleFilterIPDropdown.bind(this));
        this.filterOptionsIP.forEach(option => {
            option.addEventListener('click', this.selectFilterIPOption.bind(this));
        });
    }

    // Sidebar Methods
    toggleSidebar() {
        this.sidebar.classList.toggle('show');
    }

    // Dropdown Sort Auditoría
    toggleDropdown(e) {
        e.stopPropagation();
        this.dropdown.classList.toggle('open');
    }

    selectOption(e) {
        e.stopPropagation();
        const option = e.target;
        const value = option.getAttribute('data-value');
        this.toggle.childNodes[0].textContent = option.textContent;
        this.dropdown.classList.remove('open');
        this.lastSort = value === 'default' ? 'date' : value;
        this.sortDirection = this.lastSort === value && this.sortDirection === 'desc' ? 'asc' : 'desc';
        this.filterOrder(value);
    }

    // Dropdown Filter Auditoría
    toggleFilterDropdown(e) {
        e.stopPropagation();
        this.filterDropdown.classList.toggle('open');
    }

    selectFilterOption(e) {
        e.stopPropagation();
        const option = e.target;
        const value = option.getAttribute('data-value');
        this.filterToggle.childNodes[0].textContent = option.textContent;
        this.filterDropdown.classList.remove('open');
        this.lastFilterSelected = value; // Actualizar el estado
        this.filterOrder(value);
    }

    //AJAX METHODS AUDITORY
    filterOrder(filterValue) {
        const data = new URLSearchParams();
        data.append('filter', filterValue);
        data.append('sort', this.lastSort);
        data.append('sortDirection', this.sortDirection);

        fetch('./PHP/filterOrderBy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data.toString()
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('auditory-body').innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Dropdown Sort IP
    toggleIPDropdown(e) {
        e.stopPropagation();
        this.ipDropdown.classList.toggle('open');
    }

    selectIPOption(e) {
        e.stopPropagation();
        const option = e.target;
        const value = option.getAttribute('data-value');
        this.ipToggle.childNodes[0].textContent = option.textContent;
        this.ipDropdown.classList.remove('open');
        this.ipLastSort = value === 'default' ? 'lastUse' : value;
        this.ipSortDirection = this.ipLastSort === value && this.ipSortDirection === 'desc' ? 'asc' : 'desc';
        this.ipFilterOrder(this.ipLastFilterSelected);
    }

    // Dropdown Filter IP
    toggleFilterIPDropdown(e) {
        e.stopPropagation();
        this.filterDropdownIP.classList.toggle('open');
    }

    selectFilterIPOption(e) {
        e.stopPropagation();
        const option = e.target;
        const value = option.getAttribute('data-value');
        
        let ipFilterValue = value;
        let displayText = option.textContent;

        if (value === 'verified') {
            if (this.ipLastFilterSelected === 'verified') {
                this.isVerifiedFilter = !this.isVerifiedFilter;
            } else {
                this.isVerifiedFilter = true;
            }
            ipFilterValue = this.isVerifiedFilter ? 'verified' : 'unverified';
            displayText = this.isVerifiedFilter ? 'Verificado' : 'No verificado';
        } else {
            this.isVerifiedFilter = true;
        }

        this.filterToggleIP.childNodes[0].textContent = displayText;
        this.filterDropdownIP.classList.remove('open');
        this.ipLastFilterSelected = ipFilterValue;
        this.ipFilterOrder(ipFilterValue);
    }


    // AJAX METHODS IP
    ipFilterOrder(ipFilterValue) {
        const data = new URLSearchParams();
        data.append('filter', ipFilterValue);
        data.append('sort', this.ipLastSort);
        data.append('sortDirection', this.ipSortDirection);

        fetch('./PHP/ipFilterOrderBy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data.toString()
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('auditoryIP-body').innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const ui = new auditoriaUI();
});