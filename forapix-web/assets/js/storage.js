/**
 * ApostaCasada - Storage Service
 * Gerenciamento de dados no localStorage
 */

const Storage = {
    /**
     * Get item from localStorage
     */
    getItem(key) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (error) {
            console.error('Storage get error:', error);
            return null;
        }
    },

    /**
     * Set item in localStorage
     */
    setItem(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('Storage set error:', error);
            return false;
        }
    },

    /**
     * Remove item from localStorage
     */
    removeItem(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Storage remove error:', error);
            return false;
        }
    },

    /**
     * Clear all storage
     */
    clear() {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Storage clear error:', error);
            return false;
        }
    },

    /**
     * Initialize default data
     */
    init() {
        // Initialize demo user if not exists
        if (!this.getUser() && Config.DEMO.ENABLED) {
            this.setUser(Config.DEMO.USER);
            this.setBalance(Config.DEMO.USER.balance);
        }

        // Initialize empty arrays if not exist
        if (!this.getBets()) {
            this.setBets([]);
        }

        if (!this.getTransactions()) {
            this.setTransactions([]);
        }
    },

    /**
     * User management
     */
    getUser() {
        return this.getItem(Config.STORAGE_KEYS.USER);
    },

    setUser(user) {
        return this.setItem(Config.STORAGE_KEYS.USER, user);
    },

    /**
     * Token management
     */
    getToken() {
        return this.getItem(Config.STORAGE_KEYS.TOKEN);
    },

    setToken(token) {
        return this.setItem(Config.STORAGE_KEYS.TOKEN, token);
    },

    /**
     * Balance management
     */
    getBalance() {
        return this.getItem(Config.STORAGE_KEYS.BALANCE) || 0;
    },

    setBalance(balance) {
        return this.setItem(Config.STORAGE_KEYS.BALANCE, parseFloat(balance));
    },

    /**
     * Bets management
     */
    getBets() {
        return this.getItem(Config.STORAGE_KEYS.BETS) || [];
    },

    setBets(bets) {
        return this.setItem(Config.STORAGE_KEYS.BETS, bets);
    },

    /**
     * Transactions management
     */
    getTransactions() {
        return this.getItem(Config.STORAGE_KEYS.TRANSACTIONS) || [];
    },

    setTransactions(transactions) {
        return this.setItem(Config.STORAGE_KEYS.TRANSACTIONS, transactions);
    },

    /**
     * Theme management
     */
    getTheme() {
        return this.getItem(Config.STORAGE_KEYS.THEME) || Config.APP.THEME;
    },

    setTheme(theme) {
        return this.setItem(Config.STORAGE_KEYS.THEME, theme);
    },

    /**
     * Settings management
     */
    getSettings() {
        return this.getItem(Config.STORAGE_KEYS.SETTINGS) || {};
    },

    setSettings(settings) {
        return this.setItem(Config.STORAGE_KEYS.SETTINGS, settings);
    },

    /**
     * Check if user is logged in
     */
    isLoggedIn() {
        return !!(this.getUser() && this.getToken());
    },

    /**
     * Logout user
     */
    logout() {
        this.removeItem(Config.STORAGE_KEYS.USER);
        this.removeItem(Config.STORAGE_KEYS.TOKEN);
        this.removeItem(Config.STORAGE_KEYS.BALANCE);
        this.removeItem(Config.STORAGE_KEYS.BETS);
        this.removeItem(Config.STORAGE_KEYS.TRANSACTIONS);
    }
};
