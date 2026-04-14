/**
 * FORAPIX - Storage Service
 * Gerenciamento de dados locais (localStorage)
 */

const Storage = {
    /**
     * Get item from localStorage
     * @param {string} key - Storage key
     */
    get(key) {
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
     * @param {string} key - Storage key
     * @param {any} value - Value to store
     */
    set(key, value) {
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
     * @param {string} key - Storage key
     */
    remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Storage remove error:', error);
            return false;
        }
    },

    /**
     * Clear all app data from localStorage
     */
    clear() {
        Object.values(CONFIG.STORAGE).forEach(key => {
            localStorage.removeItem(key);
        });
    },

    /**
     * Initialize user data if not exists
     */
    initUser() {
        let user = this.get(CONFIG.STORAGE.USER);
        
        if (!user) {
            user = {
                id: Date.now(),
                name: 'Usuário',
                email: 'usuario@email.com',
                balance: 0,
                createdAt: new Date().toISOString()
            };
            this.set(CONFIG.STORAGE.USER, user);
        }

        return user;
    },

    /**
     * Get current user
     */
    getUser() {
        return this.get(CONFIG.STORAGE.USER) || this.initUser();
    },

    /**
     * Update user balance
     * @param {number} newBalance - New balance value
     */
    updateBalance(newBalance) {
        const user = this.getUser();
        user.balance = newBalance;
        this.set(CONFIG.STORAGE.USER, user);
        
        // Update UI
        const balanceEl = document.getElementById('userBalance');
        if (balanceEl) {
            balanceEl.textContent = Utils.formatCurrency(newBalance);
        }

        return user;
    },

    /**
     * Get user balance
     */
    getBalance() {
        const user = this.getUser();
        return user.balance || 0;
    },

    /**
     * Get user bets
     */
    getBets() {
        return this.get(CONFIG.STORAGE.BETS) || [];
    },

    /**
     * Get user transactions
     */
    getTransactions() {
        return this.get(CONFIG.STORAGE.TRANSACTIONS) || [];
    },

    /**
     * Get theme preference
     */
    getTheme() {
        return this.get(CONFIG.STORAGE.THEME) || 'dark';
    },

    /**
     * Set theme preference
     * @param {string} theme - Theme name
     */
    setTheme(theme) {
        this.set(CONFIG.STORAGE.THEME, theme);
    }
};
