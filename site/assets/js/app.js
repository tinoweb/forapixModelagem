/**
 * FORAPIX - Main Application
 * Controlador principal do aplicativo
 */

const App = {
    currentPage: 'home',
    pageHistory: [],
    pageParams: {},

    /**
     * Initialize application
     */
    init() {
        // Initialize user data
        Storage.initUser();

        // Update balance display
        this.updateBalanceDisplay();

        // Setup navigation
        this.setupNavigation();

        // Load initial page
        this.navigateTo('home');

        console.log(`${CONFIG.APP.NAME} v${CONFIG.APP.VERSION} initialized`);
    },

    /**
     * Setup bottom navigation
     */
    setupNavigation() {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const page = e.currentTarget.dataset.page;
                if (page) {
                    this.navigateTo(page);
                }
            });
        });
    },

    /**
     * Navigate to page
     * @param {string} page - Page name
     * @param {object} params - Page parameters
     */
    navigateTo(page, params = {}) {
        // Save to history
        if (this.currentPage !== page) {
            this.pageHistory.push({
                page: this.currentPage,
                params: this.pageParams
            });
        }

        this.currentPage = page;
        this.pageParams = params;

        // Update navigation
        this.updateNavigation(page);

        // Render page
        this.renderPage(page, params);
    },

    /**
     * Go back to previous page
     */
    goBack() {
        if (this.pageHistory.length > 0) {
            const prev = this.pageHistory.pop();
            this.currentPage = prev.page;
            this.pageParams = prev.params;
            this.updateNavigation(prev.page);
            this.renderPage(prev.page, prev.params);
        } else {
            this.navigateTo('home');
        }
    },

    /**
     * Update navigation active state
     * @param {string} page - Current page
     */
    updateNavigation(page) {
        document.querySelectorAll('.nav-item').forEach(item => {
            const itemPage = item.dataset.page;
            item.classList.toggle('active', itemPage === page);
        });
    },

    /**
     * Render page content
     * @param {string} page - Page name
     * @param {object} params - Page parameters
     */
    renderPage(page, params = {}) {
        const mainContent = document.getElementById('mainContent');
        let html = '';
        let pageModule = null;

        switch (page) {
            case 'home':
                pageModule = HomePage;
                html = HomePage.render();
                break;
            case 'games':
                pageModule = GamesPage;
                html = GamesPage.render();
                break;
            case 'matches':
                pageModule = MatchesPage;
                html = MatchesPage.render(params);
                break;
            case 'bet':
                pageModule = BetPage;
                html = BetPage.render(params);
                break;
            case 'sinuca':
                pageModule = SinucaPage;
                html = SinucaPage.render();
                break;
            case 'deposit':
                pageModule = DepositPage;
                html = DepositPage.render();
                break;
            case 'wallet':
                pageModule = WalletPage;
                html = WalletPage.render();
                break;
            case 'menu':
                pageModule = MenuPage;
                html = MenuPage.render();
                break;
            default:
                html = `
                    <div class="p-4">
                        ${Components.renderEmptyState('fa-question', 'Página não encontrada', 'A página solicitada não existe.')}
                    </div>
                `;
        }

        mainContent.innerHTML = html;

        // Initialize page module
        if (pageModule && typeof pageModule.init === 'function') {
            setTimeout(() => pageModule.init(params), 0);
        }

        // Scroll to top
        mainContent.scrollTop = 0;
    },

    /**
     * Update balance display
     */
    updateBalanceDisplay() {
        const balance = Storage.getBalance();
        const balanceEl = document.getElementById('userBalance');
        if (balanceEl) {
            balanceEl.textContent = Utils.formatCurrency(balance);
        }
    },

    /**
     * Refresh current page
     */
    refresh() {
        this.renderPage(this.currentPage, this.pageParams);
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Handle back button
window.addEventListener('popstate', () => {
    App.goBack();
});

// Service Worker registration (for PWA support)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Service worker registration failed
        });
    });
}
