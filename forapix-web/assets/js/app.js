/**
 * ForaPix - Main Application
 * Controlador principal da aplicação
 */

const App = {
    currentPage: 'home',
    history: [],
    
    /**
     * Initialize application
     */
    init() {
        console.log('🚀 ForaPix App iniciando...');
        
        // Initialize storage
        Storage.init();
        
        // Setup navigation
        this.setupNavigation();
        
        // Setup theme toggle
        this.setupThemeToggle();
        
        // Setup profile button
        this.setupProfileButton();
        
        // Load initial page
        this.navigateTo('home');
        
        // Update balance display
        this.updateBalance();
        
        // Test API connection
        this.testApiConnection();
        
        console.log('✅ ForaPix App iniciado com sucesso!');
    },

    /**
     * Setup navigation
     */
    setupNavigation() {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', () => {
                const page = item.dataset.page;
                if (page) {
                    this.navigateTo(page);
                }
            });
        });
    },

    /**
     * Setup theme toggle
     */
    setupThemeToggle() {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                // Theme toggle functionality (placeholder)
                Components.showToast('Tema alterado!', 'success');
            });
        }
    },

    /**
     * Setup profile button
     */
    setupProfileButton() {
        const profileBtn = document.getElementById('profileBtn');
        if (profileBtn) {
            profileBtn.addEventListener('click', () => {
                this.navigateTo('menu');
            });
        }
    },

    /**
     * Navigate to page
     */
    navigateTo(page, params = {}) {
        console.log(`📍 Navegando para: ${page}`, params);
        
        // Add to history
        if (this.currentPage !== page) {
            this.history.push(this.currentPage);
        }
        
        this.currentPage = page;
        
        // Update navigation
        this.updateNavigation(page);
        
        // Render page
        this.renderPage(page, params);
    },

    /**
     * Go back
     */
    goBack() {
        if (this.history.length > 0) {
            const previousPage = this.history.pop();
            this.navigateTo(previousPage);
        } else {
            this.navigateTo('home');
        }
    },

    /**
     * Update navigation
     */
    updateNavigation(activePage) {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            const page = item.dataset.page;
            item.classList.toggle('active', page === activePage);
        });
    },

    /**
     * Controla a visibilidade do header e do bottom nav conforme a página
     */
    updateShellVisibility(page) {
        const header = document.getElementById('header');
        const bottomNav = document.getElementById('bottomNav');
        const pagesWithoutShell = [];
        const shouldHideShell = pagesWithoutShell.includes(page);

        if (header) {
            header.classList.toggle('hidden', shouldHideShell);
        }

        if (bottomNav) {
            bottomNav.classList.toggle('hidden', shouldHideShell);
        }

        this.updateHeaderBrand(page);
    },

    /**
     * Atualiza área da marca no header conforme a página atual.
     * Páginas internas exibem back + título truncado.
     */
    updateHeaderBrand(page) {
        const brand = document.getElementById('headerBrand');
        if (!brand) return;

        const titles = {
            matches: 'PARTIDAS',
            bet: 'APOSTAR',
            deposit: 'DEPOSITAR',
            wallet: 'CARTEIRA',
            menu: 'PERFIL',
            games: 'JOGOS',
            sinuca: 'PARTIDA'
        };

        if (titles[page]) {
            brand.innerHTML = `
                <button class="header-back" onclick="App.goBack()" aria-label="Voltar">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="header-title">${titles[page]}</span>
            `;
        } else {
            brand.innerHTML = `
                <span class="logo-icon"><i class="fas fa-leaf"></i></span>
                <span class="logo-text">FORAPIX</span>
            `;
        }
    },

    /**
     * Render page
     */
    renderPage(page, params = {}) {
        const mainContent = document.getElementById('mainContent');
        let html = '';
        let pageModule = null;

        try {
            switch (page) {
                case 'home':
                    pageModule = HomePage;
                    html = HomePage.render();
                    break;
                    
                case 'games':
                    pageModule = GamesPage;
                    html = GamesPage.render(params);
                    break;
                    
                case 'matches':
                    pageModule = MatchesPage;
                    html = MatchesPage.render(params);
                    break;
                    
                case 'bet':
                    html = this.renderPlaceholderPage('Apostar', 'fa-dice', 'Página de apostas em desenvolvimento');
                    break;
                    
                case 'sinuca':
                    pageModule = SinucaPage;
                    html = SinucaPage.render(params);
                    break;
                    
                case 'deposit':
                    pageModule = DepositPage;
                    html = DepositPage.render();
                    break;
                    
                case 'wallet':
                    pageModule = WalletPage;
                    html = WalletPage.render(params);
                    break;
                    
                case 'menu':
                    pageModule = ProfilePage;
                    html = ProfilePage.render(params);
                    break;
                    
                default:
                    html = this.renderNotFoundPage();
            }

            // Atualiza a visibilidade dos elementos globais conforme a página
            this.updateShellVisibility(page);

            mainContent.innerHTML = html;

            // Initialize page if it has init method
            if (pageModule && typeof pageModule.init === 'function') {
                pageModule.init();
            }

        } catch (error) {
            console.error('Error rendering page:', error);
            mainContent.innerHTML = this.renderErrorPage(error.message);
        }
    },

    /**
     * Render placeholder page
     */
    renderPlaceholderPage(title, icon, message) {
        return `
            <div class="page-enter p-4">
                <div class="text-center py-20">
                    <div class="w-24 h-24 bg-accent/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas ${icon} text-5xl text-accent"></i>
                    </div>
                    <h2 class="text-2xl font-bold mb-4">${title}</h2>
                    <p class="text-gray-400 mb-8">${message}</p>
                    <button class="btn btn-primary" onclick="App.goBack()">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Render error page
     */
    renderErrorPage(message) {
        return `
            <div class="page-enter p-4">
                <div class="text-center py-20">
                    <div class="w-24 h-24 bg-danger/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-exclamation-triangle text-5xl text-danger"></i>
                    </div>
                    <h2 class="text-2xl font-bold mb-4">Erro</h2>
                    <p class="text-gray-400 mb-8">${message}</p>
                    <button class="btn btn-primary" onclick="App.navigateTo('home')">
                        <i class="fas fa-home"></i> Ir para Início
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Render not found page
     */
    renderNotFoundPage() {
        return `
            <div class="page-enter p-4">
                <div class="text-center py-20">
                    <div class="w-24 h-24 bg-gray-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-question text-5xl text-gray-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold mb-4">Página não encontrada</h2>
                    <p class="text-gray-400 mb-8">A página que você procura não existe.</p>
                    <button class="btn btn-primary" onclick="App.navigateTo('home')">
                        <i class="fas fa-home"></i> Ir para Início
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Update balance display
     */
    updateBalance() {
        const balance = Storage.getBalance();
        const balanceElement = document.getElementById('userBalance');
        if (balanceElement) {
            balanceElement.textContent = Utils.formatCurrency(balance);
        }
    },

    /**
     * Test API connection
     */
    async testApiConnection() {
        try {
            const response = await API.test();
            console.log('✅ API conectada:', response.message);
        } catch (error) {
            console.warn('⚠️ API não disponível, usando modo demo');
        }
    },

    /**
     * Logout user
     */
    logout() {
        Storage.logout();
        Components.showToast('Logout realizado com sucesso!', 'success');
        this.navigateTo('home');
    }
};

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
