/**
 * JRpix - Main Application
 * Controlador principal da aplicação
 */

const App = {
    currentPage: 'home',
    
    /**
     * Initialize application
     */
    init() {
        console.log('🚀 JRpix App iniciando...');
        
        // Initialize storage
        Storage.init();
        
        // Setup navigation
        this.setupNavigation();
        
        // Setup theme toggle
        this.setupThemeToggle();
        
        // Setup profile button
        this.setupProfileButton();
        
        // Escuta botão voltar/avançar do browser
        this.setupPopState();

        // Detecta link de redefinição de senha (?reset-token=...&email=...)
        const handledReset = this._handlePasswordResetLink();

        // Determina página inicial pelo hash da URL (deep link / refresh)
        if (!handledReset) {
            const { page: initialPage, params: initialParams } = this._parseHash();
            // Garante que o estado inicial está registrado no histórico do browser
            window.history.replaceState(
                { page: initialPage, params: initialParams },
                '',
                this._buildHash(initialPage, initialParams)
            );
            this.currentPage = initialPage;
            this.renderPage(initialPage, initialParams);
        }
        
        // Update auth UI (logged in or not)
        this.updateAuthUI();
        
        // Test API connection
        this.testApiConnection();
        
        console.log('✅ JRpix App iniciado com sucesso!');
    },

    /**
     * Se a URL contém parâmetros de reset de senha, abre a tela de
     * redefinição automaticamente. Retorna true se foi tratado.
     */
    _handlePasswordResetLink() {
        try {
            const params = new URLSearchParams(window.location.search);
            const token  = params.get('reset-token');
            const email  = params.get('email');
            if (!token || !email) return false;

            if (typeof ProfilePage !== 'undefined') {
                ProfilePage.resetToken = token;
                ProfilePage.resetEmail = email;
                ProfilePage.authTab = 'reset';
            }
            this.navigateTo('menu');
            return true;
        } catch (_) {
            return false;
        }
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
     * Navigate to page — registra no histórico do browser
     */
    navigateTo(page, params = {}) {
        console.log(`📍 Navegando para: ${page}`, params);

        const hash = this._buildHash(page, params);
        window.history.pushState({ page, params }, '', hash);

        this.currentPage = page;
        this.updateNavigation(page);
        this.renderPage(page, params);
    },

    /**
     * Volta para a página anterior usando o histórico do browser
     */
    goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            this.navigateTo('home');
        }
    },

    /**
     * Escuta eventos popstate (botão voltar/avançar do browser)
     */
    setupPopState() {
        window.addEventListener('popstate', (event) => {
            let page, params;
            if (event.state && event.state.page) {
                page   = event.state.page;
                params = event.state.params || {};
            } else {
                // Fallback: lê o hash atual
                ({ page, params } = this._parseHash());
            }
            this.currentPage = page;
            this.updateNavigation(page);
            this.updateShellVisibility(page);
            this.renderPage(page, params);
        });
    },

    /**
     * Constrói o hash da URL a partir da página e parâmetros.
     * Ex: page='sinuca', params={matchId:5} → '#sinuca?id=5'
     */
    _buildHash(page, params = {}) {
        const base = `#${page}`;
        const normalized = { ...params };
        if (normalized.matchId) {
            normalized.id = normalized.matchId;
            delete normalized.matchId;
        }
        const query = Object.entries(normalized)
            .filter(([, v]) => v !== null && v !== undefined && v !== '')
            .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
            .join('&');
        return query ? `${base}?${query}` : base;
    },

    /**
     * Lê e decodifica o hash atual da URL.
     * Ex: '#sinuca?id=5' → { page:'sinuca', params:{id:'5', matchId:'5'} }
     */
    _parseHash() {
        const raw = window.location.hash || '#home';
        const withoutHash = raw.slice(1);
        const [pagePart, queryPart] = withoutHash.split('?');
        const page = pagePart || 'home';
        const params = {};
        if (queryPart) {
            queryPart.split('&').forEach(part => {
                const eqIdx = part.indexOf('=');
                if (eqIdx === -1) return;
                const k = decodeURIComponent(part.slice(0, eqIdx));
                const v = decodeURIComponent(part.slice(eqIdx + 1));
                params[k] = v;
            });
        }
        if (params.id && page === 'sinuca') {
            params.matchId = params.id;
        }
        return { page, params };
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
                <img src="assets/images/loGOJRpix.png" alt="JRpix" class="h-8">
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
     * Atualiza header conforme estado de autenticação
     */
    updateAuthUI() {
        const loggedIn = Storage.isLoggedIn();
        const authEl  = document.getElementById('headerAuth');
        const guestEl = document.getElementById('headerGuest');
        if (authEl)  authEl.classList.toggle('hidden', !loggedIn);
        if (guestEl) guestEl.classList.toggle('hidden', loggedIn);
        if (loggedIn) this.updateBalance();
    },

    /**
     * Chamado após login bem-sucedido
     */
    handleLogin(data) {
        const { user, token } = data;
        Storage.setUser(user);
        Storage.setItem(Config.STORAGE_KEYS.TOKEN, token);
        Storage.setBalance(user.balance);
        Storage.setBets([]);
        Storage.setTransactions([]);
        this.updateAuthUI();
        Components.showToast(`Bem-vindo, ${user.name}!`, 'success');
    },

    /**
     * Chamado em 401 — limpa sessão e redireciona para login
     */
    handleUnauthorized() {
        Storage.logout();
        this.updateAuthUI();
        this.navigateTo('menu');
        Components.showToast('Sessão expirada. Faça login novamente.', 'warning');
    },

    /**
     * Logout
     */
    async logout() {
        try { await API.logout(); } catch (_) {}
        Storage.logout();
        this.updateAuthUI();
        this.navigateTo('home');
        Components.showToast('Logout realizado!', 'success');
    },

    /**
     * Update balance display — busca da API e atualiza localStorage
     */
    async updateBalance() {
        const balanceElement = document.getElementById('userBalance');

        if (!Storage.isLoggedIn()) {
            if (balanceElement) balanceElement.textContent = '0,00';
            return;
        }

        // Exibe imediatamente o valor em cache enquanto busca o real
        const cached = Storage.getBalance();
        if (balanceElement) {
            balanceElement.textContent = Utils.formatCurrency(cached);
        }

        try {
            const res = await API.getBalance();
            if (res && res.success && res.data) {
                const fresh = parseFloat(res.data.balance) || 0;
                Storage.setBalance(fresh);
                if (balanceElement) {
                    balanceElement.textContent = Utils.formatCurrency(fresh);
                }
            }
        } catch (_) {}
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

};

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
