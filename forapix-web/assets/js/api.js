/**
 * ForaPix - API Service
 * Serviço para comunicação com a API Laravel
 */

const API = {
    /**
     * Generic API request wrapper
     */
    async request(endpoint, options = {}) {
        const url = `${Config.API.BASE_URL}${endpoint}`;
        const token = Storage.getItem(Config.STORAGE_KEYS.TOKEN);
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                ...Config.API.HEADERS,
                ...(token && { 'Authorization': `Bearer ${token}` })
            }
        };

        const finalOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            Components.showLoading(true);
            
            const response = await fetch(url, finalOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        } finally {
            Components.showLoading(false);
        }
    },

    /**
     * Authentication
     */
    async login(credentials) {
        return await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify(credentials)
        });
    },

    async register(userData) {
        return await this.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    },

    async logout() {
        return await this.request('/auth/logout', {
            method: 'POST'
        });
    },

    async getProfile() {
        return await this.request('/auth/profile');
    },

    /**
     * Games and Sports
     */
    async getSports() {
        return await this.request('/sports');
    },

    async getGames(filters = {}) {
        const params = new URLSearchParams(filters);
        return await this.request(`/games?${params}`);
    },

    async getGame(slug) {
        return await this.request(`/games/${slug}`);
    },

    /**
     * Matches
     */
    async getMatches(filters = {}) {
        const params = new URLSearchParams(filters);
        return await this.request(`/matches?${params}`);
    },

    async getMatch(id) {
        return await this.request(`/matches/${id}`);
    },

    async getLiveMatches() {
        return await this.request('/matches/live');
    },

    async getUpcomingMatches() {
        return await this.request('/matches/upcoming');
    },

    /**
     * Bets (API real + fallback simulado)
     */
    async placeBet(betData) {
        const token = Storage.getItem(Config.STORAGE_KEYS.TOKEN);

        if (token) {
            try {
                return await this.request('/bets', {
                    method: 'POST',
                    body: JSON.stringify({
                        match_id: betData.matchId,
                        bet_type: betData.option,
                        amount: betData.amount
                    })
                });
            } catch (e) {
                console.warn('API indisponível, usando fallback simulado');
            }
        }

        // Fallback simulado
        await new Promise(resolve => setTimeout(resolve, 1500));
        const user = Storage.getUser();
        const balance = Storage.getBalance();

        if (betData.amount > balance) {
            return { success: false, error: 'Saldo insuficiente' };
        }

        const newBalance = balance - betData.amount;
        Storage.setBalance(newBalance);

        const bet = {
            id: Utils.generateId(),
            ...betData,
            userId: user.id,
            placedAt: new Date().toISOString(),
            status: 'pending'
        };

        const bets = Storage.getBets();
        bets.push(bet);
        Storage.setBets(bets);

        const transaction = {
            id: Utils.generateId(),
            type: 'bet',
            amount: -betData.amount,
            description: `Aposta - ${betData.fighterName}`,
            date: new Date().toISOString(),
            status: 'completed'
        };

        const transactions = Storage.getTransactions();
        transactions.push(transaction);
        Storage.setTransactions(transactions);

        return { success: true, data: bet };
    },

    async getBets(filters = {}) {
        const token = Storage.getItem(Config.STORAGE_KEYS.TOKEN);

        if (token) {
            try {
                const params = new URLSearchParams(filters);
                return await this.request(`/bets?${params}`);
            } catch (e) {
                console.warn('API indisponível, usando dados locais');
            }
        }

        return { success: true, data: Storage.getBets() };
    },

    async cancelBet(betId) {
        return await this.request(`/bets/${betId}/cancel`, {
            method: 'POST'
        });
    },

    /**
     * Wallet (API real + fallback simulado)
     */
    async getBalance() {
        const token = Storage.getItem(Config.STORAGE_KEYS.TOKEN);

        if (token) {
            try {
                return await this.request('/wallet/balance');
            } catch (e) {
                console.warn('API indisponível, usando saldo local');
            }
        }

        return {
            success: true,
            data: {
                balance: Storage.getBalance(),
                total_deposited: 0,
                total_withdrawn: 0,
                total_bet: 0,
                total_won: 0
            }
        };
    },

    async getTransactions(filters = {}) {
        const token = Storage.getItem(Config.STORAGE_KEYS.TOKEN);

        if (token) {
            try {
                const params = new URLSearchParams(filters);
                return await this.request(`/wallet/transactions?${params}`);
            } catch (e) {
                console.warn('API indisponível, usando transações locais');
            }
        }

        return { success: true, data: Storage.getTransactions() };
    },

    async deposit(amount) {
        const token = Storage.getItem(Config.STORAGE_KEYS.TOKEN);

        if (token) {
            try {
                return await this.request('/wallet/deposit', {
                    method: 'POST',
                    body: JSON.stringify({ amount })
                });
            } catch (e) {
                console.warn('API indisponível, usando fallback simulado');
            }
        }

        // Fallback simulado
        await new Promise(resolve => setTimeout(resolve, 2000));
        const currentBalance = Storage.getBalance();
        const newBalance = currentBalance + amount;
        Storage.setBalance(newBalance);

        const transaction = {
            id: Utils.generateId(),
            type: 'deposit',
            amount: amount,
            description: 'Depósito via PIX',
            date: new Date().toISOString(),
            status: 'completed'
        };

        const transactions = Storage.getTransactions();
        transactions.push(transaction);
        Storage.setTransactions(transactions);

        return { success: true, data: { newBalance, transaction } };
    },

    async confirmDeposit(transactionId) {
        return await this.request('/wallet/deposit/confirm', {
            method: 'POST',
            body: JSON.stringify({ transaction_id: transactionId })
        });
    },

    async withdraw(amount, pixKey) {
        const token = Storage.getItem(Config.STORAGE_KEYS.TOKEN);

        if (token) {
            try {
                return await this.request('/wallet/withdraw', {
                    method: 'POST',
                    body: JSON.stringify({ amount, pix_key: pixKey })
                });
            } catch (e) {
                console.warn('API indisponível, usando fallback simulado');
            }
        }

        // Fallback simulado
        await new Promise(resolve => setTimeout(resolve, 2000));
        const currentBalance = Storage.getBalance();

        if (amount > currentBalance) {
            return { success: false, error: 'Saldo insuficiente' };
        }

        const newBalance = currentBalance - amount;
        Storage.setBalance(newBalance);

        const transaction = {
            id: Utils.generateId(),
            type: 'withdraw',
            amount: -amount,
            description: 'Saque via PIX',
            date: new Date().toISOString(),
            status: 'pending'
        };

        const transactions = Storage.getTransactions();
        transactions.push(transaction);
        Storage.setTransactions(transactions);

        return { success: true, data: { newBalance, transaction } };
    },

    /**
     * Test API connection
     */
    async test() {
        return await this.request('/test');
    }
};
