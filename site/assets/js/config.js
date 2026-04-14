/**
 * FORAPIX - Configuration File
 * Configurações globais do sistema
 */

const CONFIG = {
    // API Configuration
    API: {
        BASE_URL: 'https://api.sispts.com/api/v1',
        HEADERS: {
            'accept': 'application/vnd.api+json',
            'content-type': 'application/vnd.api+json'
        },
        TERMINAL_ID: '121088',
        TERMINAL_SERIAL: 'f65e0eae-a381-4463-9b51-c0e1be6b4681'
    },

    // App Settings
    APP: {
        NAME: 'ForaPix',
        VERSION: '1.0.0',
        CURRENCY: 'BRL',
        CURRENCY_SYMBOL: 'R$',
        LOCALE: 'pt-BR'
    },

    // Storage Keys
    STORAGE: {
        USER: 'forapix_user',
        TOKEN: 'forapix_token',
        BALANCE: 'forapix_balance',
        THEME: 'forapix_theme',
        BETS: 'forapix_bets',
        TRANSACTIONS: 'forapix_transactions'
    },

    // Sports Configuration
    SPORTS: {
        1: { name: 'MMA/UFC', icon: 'fa-hand-fist', image: 'ufc.png' },
        2: { name: 'Futebol', icon: 'fa-futbol', image: 'football.png' },
        3: { name: 'Basquete', icon: 'fa-basketball', image: 'basketball.png' },
        4: { name: 'Tênis', icon: 'fa-baseball', image: 'tennis.png' },
        5: { name: 'Boxe', icon: 'fa-hand-fist', image: 'boxing.png' }
    },

    // Bet Configuration
    BET: {
        MIN_VALUE: 1.00,
        MAX_VALUE: 10000.00,
        QUICK_VALUES: [5, 10, 20, 50, 100, 200]
    },

    // Toast Configuration
    TOAST: {
        DURATION: 3000,
        POSITION: 'top'
    }
};

// Freeze config to prevent modifications
Object.freeze(CONFIG);
Object.freeze(CONFIG.API);
Object.freeze(CONFIG.APP);
Object.freeze(CONFIG.STORAGE);
Object.freeze(CONFIG.SPORTS);
Object.freeze(CONFIG.BET);
Object.freeze(CONFIG.TOAST);
