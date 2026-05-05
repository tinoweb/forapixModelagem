/**
 * ForaPix - Configuration
 * Configurações globais do sistema
 */

const Config = {
    // API Configuration
    API: {
        BASE_URL: 'https://apostacasada.net/api/index.php/api',
        TIMEOUT: 10000,
        HEADERS: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    },

    // App Configuration
    APP: {
        NAME: 'ForaPix',
        VERSION: '1.0.0',
        THEME: 'dark',
        LANGUAGE: 'pt-BR',
        CURRENCY: 'BRL',
        TIMEZONE: 'America/Sao_Paulo'
    },

    // LocalStorage Keys
    STORAGE_KEYS: {
        USER: 'forapix_user',
        TOKEN: 'forapix_token',
        BALANCE: 'forapix_balance',
        THEME: 'forapix_theme',
        BETS: 'forapix_bets',
        TRANSACTIONS: 'forapix_transactions',
        SETTINGS: 'forapix_settings'
    },

    // Sports Configuration
    SPORTS: [
        { id: 1, name: 'MMA/UFC', slug: 'mma-ufc', icon: 'fa-hand-fist' },
        { id: 2, name: 'Futebol', slug: 'futebol', icon: 'fa-futbol' },
        { id: 3, name: 'Basquete', slug: 'basquete', icon: 'fa-basketball' },
        { id: 4, name: 'Tênis', slug: 'tenis', icon: 'fa-baseball' },
        { id: 5, name: 'Boxe', slug: 'boxe', icon: 'fa-hand-fist' },
        { id: 6, name: 'Sinuca', slug: 'sinuca', icon: 'fa-8ball' }
    ],

    // Betting Configuration
    BETTING: {
        MIN_BET: 1.00,
        MAX_BET: 10000.00,
        QUICK_VALUES: [5, 10, 20, 50, 100, 200],
        COMMISSION: 0.05
    },

    // Toast Configuration
    TOAST: {
        DURATION: 4000,
        POSITION: 'top-right'
    },

    // Modal Configuration
    MODAL: {
        CLOSE_ON_BACKDROP: true,
        ANIMATION_DURATION: 300
    },

    // Pages Configuration
    PAGES: {
        DEFAULT: 'home',
        PROTECTED: ['wallet', 'bet', 'deposit', 'menu']
    },

    // Demo Configuration
    DEMO: {
        ENABLED: true,
        USER: {
            name: 'Carlos Silva',
            email: 'carlos@demo.com',
            balance: 100.00
        }
    }
};

const API_BASE_ORIGIN = Config.API.BASE_URL.replace(/\/api$/, '');
Config.MEDIA = {
    BASE_URL: API_BASE_ORIGIN,
    STORAGE_URL: `${API_BASE_ORIGIN}/storage`
};
