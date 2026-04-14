/**
 * FORAPIX - API Service
 * Gerenciamento de chamadas à API externa
 */

const API = {
    /**
     * Get authorization headers
     */
    getHeaders() {
        const token = Storage.get(CONFIG.STORAGE.TOKEN);
        const headers = {
            ...CONFIG.API.HEADERS,
            't-id': CONFIG.API.TERMINAL_ID,
            't-serial': CONFIG.API.TERMINAL_SERIAL
        };

        if (token) {
            headers['authorization'] = `Bearer ${token}`;
        }

        return headers;
    },

    /**
     * Generic fetch wrapper
     */
    async request(endpoint, options = {}) {
        const url = `${CONFIG.API.BASE_URL}${endpoint}`;
        
        const config = {
            method: options.method || 'GET',
            headers: this.getHeaders(),
            mode: 'cors',
            credentials: 'include',
            ...options
        };

        if (options.body && typeof options.body === 'object') {
            config.body = JSON.stringify(options.body);
        }

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return { success: true, data };
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * Get head-to-head matches
     * @param {number} sportId - Sport ID filter
     * @param {string} scope - Scope filter (current, upcoming, etc)
     */
    async getMatches(sportId = 1, scope = 'current') {
        const params = new URLSearchParams({
            'filter[sport_id]': sportId,
            'filter[scope]': scope,
            'sort': 'betting_deadline',
            'include': 'first_athlete,second_athlete,sport'
        });

        return this.request(`/head_to_head_matches?${params}`);
    },

    /**
     * Get single match details
     * @param {number} matchId - Match ID
     */
    async getMatch(matchId) {
        return this.request(`/head_to_head_matches/${matchId}?include=first_athlete,second_athlete,sport`);
    },

    /**
     * Get sports list
     */
    async getSports() {
        return this.request('/sports');
    },

    /**
     * Place a bet (simulated - local storage)
     * @param {object} betData - Bet information
     */
    async placeBet(betData) {
        return new Promise((resolve) => {
            setTimeout(() => {
                const user = Storage.getUser();
                
                if (user.balance < betData.amount) {
                    resolve({ 
                        success: false, 
                        error: 'Saldo insuficiente para realizar esta aposta.' 
                    });
                    return;
                }

                // Deduct balance
                const newBalance = user.balance - betData.amount;
                Storage.updateBalance(newBalance);

                // Save bet
                const bet = {
                    id: Date.now(),
                    ...betData,
                    status: 'pending',
                    createdAt: new Date().toISOString()
                };
                
                const bets = Storage.get(CONFIG.STORAGE.BETS) || [];
                bets.unshift(bet);
                Storage.set(CONFIG.STORAGE.BETS, bets);

                // Save transaction
                const transaction = {
                    id: Date.now(),
                    type: 'bet',
                    description: `Aposta: ${betData.fighterName}`,
                    amount: -betData.amount,
                    createdAt: new Date().toISOString()
                };

                const transactions = Storage.get(CONFIG.STORAGE.TRANSACTIONS) || [];
                transactions.unshift(transaction);
                Storage.set(CONFIG.STORAGE.TRANSACTIONS, transactions);

                resolve({ success: true, data: bet });
            }, 1000);
        });
    },

    /**
     * Simulate deposit (local storage)
     * @param {number} amount - Deposit amount
     */
    async deposit(amount) {
        return new Promise((resolve) => {
            setTimeout(() => {
                const user = Storage.getUser();
                const newBalance = user.balance + amount;
                Storage.updateBalance(newBalance);

                // Save transaction
                const transaction = {
                    id: Date.now(),
                    type: 'deposit',
                    description: 'Depósito via PIX',
                    amount: amount,
                    createdAt: new Date().toISOString()
                };

                const transactions = Storage.get(CONFIG.STORAGE.TRANSACTIONS) || [];
                transactions.unshift(transaction);
                Storage.set(CONFIG.STORAGE.TRANSACTIONS, transactions);

                resolve({ success: true, data: { balance: newBalance } });
            }, 1500);
        });
    },

    /**
     * Simulate withdrawal (local storage)
     * @param {number} amount - Withdrawal amount
     */
    async withdraw(amount) {
        return new Promise((resolve) => {
            setTimeout(() => {
                const user = Storage.getUser();
                
                if (user.balance < amount) {
                    resolve({ 
                        success: false, 
                        error: 'Saldo insuficiente para saque.' 
                    });
                    return;
                }

                const newBalance = user.balance - amount;
                Storage.updateBalance(newBalance);

                // Save transaction
                const transaction = {
                    id: Date.now(),
                    type: 'withdraw',
                    description: 'Saque via PIX',
                    amount: -amount,
                    createdAt: new Date().toISOString()
                };

                const transactions = Storage.get(CONFIG.STORAGE.TRANSACTIONS) || [];
                transactions.unshift(transaction);
                Storage.set(CONFIG.STORAGE.TRANSACTIONS, transactions);

                resolve({ success: true, data: { balance: newBalance } });
            }, 1500);
        });
    }
};
