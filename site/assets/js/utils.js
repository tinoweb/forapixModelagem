/**
 * FORAPIX - Utility Functions
 * Funções utilitárias do sistema
 */

const Utils = {
    /**
     * Format currency value
     * @param {number} value - Value to format
     * @param {boolean} showSymbol - Show currency symbol
     */
    formatCurrency(value, showSymbol = false) {
        const formatted = new Intl.NumberFormat(CONFIG.APP.LOCALE, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);

        return showSymbol ? `${CONFIG.APP.CURRENCY_SYMBOL} ${formatted}` : formatted;
    },

    /**
     * Parse currency string to number
     * @param {string} value - Currency string
     */
    parseCurrency(value) {
        if (typeof value === 'number') return value;
        return parseFloat(value.replace(/[^\d,.-]/g, '').replace(',', '.')) || 0;
    },

    /**
     * Format date
     * @param {string|Date} date - Date to format
     * @param {string} format - Format type (short, long, time)
     */
    formatDate(date, format = 'short') {
        const d = new Date(date);
        
        const options = {
            short: { day: '2-digit', month: '2-digit', year: 'numeric' },
            long: { day: '2-digit', month: 'long', year: 'numeric' },
            time: { hour: '2-digit', minute: '2-digit' },
            full: { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' },
            relative: null
        };

        if (format === 'relative') {
            return this.getRelativeTime(d);
        }

        return d.toLocaleDateString(CONFIG.APP.LOCALE, options[format] || options.short);
    },

    /**
     * Get relative time string
     * @param {Date} date - Date to compare
     */
    getRelativeTime(date) {
        const now = new Date();
        const diff = date - now;
        const absDiff = Math.abs(diff);

        const minutes = Math.floor(absDiff / 60000);
        const hours = Math.floor(absDiff / 3600000);
        const days = Math.floor(absDiff / 86400000);

        if (diff > 0) {
            if (days > 0) return `em ${days} dia${days > 1 ? 's' : ''}`;
            if (hours > 0) return `em ${hours}h`;
            if (minutes > 0) return `em ${minutes}min`;
            return 'agora';
        } else {
            if (days > 0) return `há ${days} dia${days > 1 ? 's' : ''}`;
            if (hours > 0) return `há ${hours}h`;
            if (minutes > 0) return `há ${minutes}min`;
            return 'agora';
        }
    },

    /**
     * Calculate potential win
     * @param {number} amount - Bet amount
     * @param {number} odds - Odds value
     */
    calculatePotentialWin(amount, odds) {
        return amount * odds;
    },

    /**
     * Generate unique ID
     */
    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },

    /**
     * Debounce function
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in ms
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function
     * @param {Function} func - Function to throttle
     * @param {number} limit - Limit time in ms
     */
    throttle(func, limit = 300) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Validate bet amount
     * @param {number} amount - Amount to validate
     * @param {number} balance - User balance
     */
    validateBetAmount(amount, balance) {
        if (amount < CONFIG.BET.MIN_VALUE) {
            return { valid: false, message: `Valor mínimo: ${this.formatCurrency(CONFIG.BET.MIN_VALUE, true)}` };
        }
        if (amount > CONFIG.BET.MAX_VALUE) {
            return { valid: false, message: `Valor máximo: ${this.formatCurrency(CONFIG.BET.MAX_VALUE, true)}` };
        }
        if (amount > balance) {
            return { valid: false, message: 'Saldo insuficiente' };
        }
        return { valid: true };
    },

    /**
     * Truncate text
     * @param {string} text - Text to truncate
     * @param {number} length - Max length
     */
    truncate(text, length = 20) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    },

    /**
     * Get initials from name
     * @param {string} name - Full name
     */
    getInitials(name) {
        if (!name) return '?';
        return name.split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    },

    /**
     * Parse API response data (JSON:API format)
     * @param {object} response - API response
     */
    parseApiResponse(response) {
        if (!response || !response.data) return [];

        const { data, included } = response;
        const items = Array.isArray(data) ? data : [data];
        
        // Create lookup map for included resources
        const includedMap = {};
        if (included) {
            included.forEach(item => {
                const key = `${item.type}_${item.id}`;
                includedMap[key] = item;
            });
        }

        // Parse items with relationships
        return items.map(item => {
            const parsed = {
                id: item.id,
                ...item.attributes
            };

            // Resolve relationships
            if (item.relationships) {
                Object.keys(item.relationships).forEach(relName => {
                    const rel = item.relationships[relName];
                    if (rel.data) {
                        const relData = Array.isArray(rel.data) ? rel.data : [rel.data];
                        const resolved = relData.map(r => {
                            const key = `${r.type}_${r.id}`;
                            return includedMap[key] ? {
                                id: includedMap[key].id,
                                ...includedMap[key].attributes
                            } : r;
                        });
                        parsed[relName] = Array.isArray(rel.data) ? resolved : resolved[0];
                    }
                });
            }

            return parsed;
        });
    },

    /**
     * Sleep/delay function
     * @param {number} ms - Milliseconds to wait
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Check if device is mobile
     */
    isMobile() {
        return window.innerWidth <= 768;
    },

    /**
     * Vibrate device (if supported)
     * @param {number} duration - Vibration duration in ms
     */
    vibrate(duration = 50) {
        if ('vibrate' in navigator) {
            navigator.vibrate(duration);
        }
    }
};
