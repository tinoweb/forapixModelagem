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
        const pagesWithoutShell = ['sinuca'];
        const shouldHideShell = pagesWithoutShell.includes(page);

        if (header) {
            header.classList.toggle('hidden', shouldHideShell);
        }

        if (bottomNav) {
            bottomNav.classList.toggle('hidden', shouldHideShell);
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
                    html = this.renderPlaceholderPage('Jogos', 'fa-gamepad', 'Página de jogos em desenvolvimento');
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
                    html = this.renderDepositPage();
                    break;
                    
                case 'wallet':
                    html = this.renderWalletPage();
                    break;
                    
                case 'menu':
                    html = this.renderMenuPage();
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
     * Render deposit page
     */
    renderDepositPage() {
        return `
            <div class="page-enter p-4">
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-success/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-arrow-down text-5xl text-success"></i>
                    </div>
                    <h2 class="text-2xl font-bold mb-4">Depositar</h2>
                    <p class="text-gray-400 mb-8">Adicione saldo à sua conta</p>
                    <button class="btn btn-primary" onclick="App.goBack()">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Render wallet page
     */
    renderWalletPage() {
        const balance = Storage.getBalance();
        const transactions = Storage.getTransactions().slice(0, 10);

        return `
            <div class="page-enter p-4">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold mb-2">Carteira</h2>
                    <p class="text-4xl font-bold text-success">${Utils.formatCurrency(balance, true)}</p>
                    <p class="text-sm text-gray-400">Saldo disponível</p>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <button class="btn btn-success" onclick="App.navigateTo('deposit')">
                        <i class="fas fa-plus"></i> Depositar
                    </button>
                    <button class="btn btn-warning">
                        <i class="fas fa-minus"></i> Sacar
                    </button>
                </div>

                <div class="mb-4">
                    <h3 class="text-lg font-semibold mb-4">Transações Recentes</h3>
                    ${transactions.length > 0 ? 
                        transactions.map(t => Components.renderTransactionItem(t)).join('') :
                        Components.renderEmptyState('fa-receipt', 'Nenhuma transação', 'Suas transações aparecerão aqui')
                    }
                </div>
            </div>
        `;
    },

    /**
     * Render menu page
     */
    renderMenuPage() {
        const user = Storage.getUser();

        return `
            <div class="page-enter p-4">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold">${Utils.getInitials(user.name)}</span>
                    </div>
                    <h2 class="text-xl font-bold">${user.name}</h2>
                    <p class="text-sm text-gray-400">${user.email}</p>
                </div>

                <div class="space-y-3">
                    <button class="w-full bg-card-bg hover:bg-card-hover p-4 rounded-xl text-left transition">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-user text-accent"></i>
                            <span>Meu Perfil</span>
                        </div>
                    </button>
                    
                    <button class="w-full bg-card-bg hover:bg-card-hover p-4 rounded-xl text-left transition">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-history text-accent"></i>
                            <span>Histórico de Apostas</span>
                        </div>
                    </button>
                    
                    <button class="w-full bg-card-bg hover:bg-card-hover p-4 rounded-xl text-left transition">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-cog text-accent"></i>
                            <span>Configurações</span>
                        </div>
                    </button>
                    
                    <button class="w-full bg-card-bg hover:bg-card-hover p-4 rounded-xl text-left transition" onclick="HomePage.openSupport()">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-headset text-accent"></i>
                            <span>Suporte</span>
                        </div>
                    </button>
                    
                    <button class="w-full bg-danger hover:bg-red-600 p-4 rounded-xl text-left transition" onclick="App.logout()">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-sign-out-alt text-white"></i>
                            <span class="text-white">Sair</span>
                        </div>
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
