/**
 * ApostaCasada - Utility Functions
 * Funções utilitárias do sistema
 */

const Utils = {
    /**
     * Format currency
     */
    formatCurrency(value, showSymbol = false) {
        const formatted = new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
        
        return showSymbol ? `R$ ${formatted}` : formatted;
    },

    /**
     * Format date
     */
    formatDate(date, format = 'short') {
        const d = new Date(date);
        
        if (format === 'short') {
            return d.toLocaleDateString('pt-BR');
        }
        
        if (format === 'long') {
            return d.toLocaleDateString('pt-BR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        if (format === 'time') {
            return d.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        return d.toLocaleString('pt-BR');
    },

    /**
     * Calculate potential win
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
     */
    debounce(func, wait) {
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
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Validate bet amount
     */
    validateBetAmount(amount, balance) {
        if (!amount || amount <= 0) {
            return { valid: false, error: 'Valor deve ser maior que zero' };
        }
        
        if (amount < Config.BETTING.MIN_BET) {
            return { valid: false, error: `Valor mínimo: ${this.formatCurrency(Config.BETTING.MIN_BET, true)}` };
        }
        
        if (amount > Config.BETTING.MAX_BET) {
            return { valid: false, error: `Valor máximo: ${this.formatCurrency(Config.BETTING.MAX_BET, true)}` };
        }
        
        if (amount > balance) {
            return { valid: false, error: 'Saldo insuficiente' };
        }
        
        return { valid: true };
    },

    /**
     * Truncate text
     */
    truncate(text, length = 50) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    },

    /**
     * Get initials from name
     */
    getInitials(name) {
        if (!name) return '';
        return name.split(' ')
            .map(word => word.charAt(0).toUpperCase())
            .join('')
            .substring(0, 2);
    },

    /**
     * Vibrate device (if supported)
     */
    vibrate(duration = 50) {
        if ('vibrate' in navigator) {
            navigator.vibrate(duration);
        }
    },

    /**
     * Copy text to clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (error) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                return true;
            } catch (err) {
                return false;
            } finally {
                document.body.removeChild(textArea);
            }
        }
    },

    /**
     * Parse API response (JSON:API format)
     */
    parseApiResponse(response) {
        if (response.data) {
            // Handle JSON:API format
            if (Array.isArray(response.data)) {
                return response.data.map(item => ({
                    id: item.id,
                    type: item.type,
                    ...item.attributes,
                    relationships: item.relationships
                }));
            } else {
                return {
                    id: response.data.id,
                    type: response.data.type,
                    ...response.data.attributes,
                    relationships: response.data.relationships
                };
            }
        }
        
        return response;
    },

    /**
     * Sleep function
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Check if device is mobile
     */
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },

    /**
     * Get device info
     */
    getDeviceInfo() {
        return {
            isMobile: this.isMobile(),
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine
        };
    },

    /**
     * Resolve media/storage URLs returned by the API
     */
    resolveImage(path, fallback = null) {
        if (!path) return fallback;
        if (path.startsWith('http')) return path;
        if (path.startsWith('/')) {
            return `${Config.MEDIA.BASE_URL}${path}`;
        }
        // Arquivos servidos via rota Laravel /api/uploads/{path}
        if (path.startsWith('uploads/')) {
            return `${Config.API.BASE_URL}/${path}`;
        }
        return `${Config.MEDIA.STORAGE_URL}/${path}`;
    },

    /**
     * Build inline SVG avatar for placeholders
     */
    buildAvatar(initials = '?', bgColor = '#10b981') {
        const safeInitials = initials.slice(0, 2).toUpperCase();
        const color = bgColor || '#10b981';
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160"><rect width="100%" height="100%" rx="80" fill="${color}"/><text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="'Inter', 'Segoe UI', sans-serif" font-size="64" fill="#ffffff">${safeInitials}</text></svg>`;
        return `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`;
    },

    /**
     * Build player avatar with fallback initials
     */
    getPlayerPhoto(player, fallbackColor = '#10b981') {
        if (!player) return this.buildAvatar('?', fallbackColor);
        const photoPath = player.photo_url || player.photo;
        if (photoPath) {
            return this.resolveImage(photoPath);
        }
        const initials = this.getInitials(player.name || 'J');
        return this.buildAvatar(initials, fallbackColor);
    },

    /**
     * Retorna a imagem de fundo correta para um card de partida.
     * Partidas de sinuca SEMPRE usam sinuca-game.png.
     */
    getMatchBgImage(match) {
        const sportSlug = (match.game?.sport?.slug || '').toLowerCase();
        const sportName = (match.game?.sport?.name || '').toLowerCase();
        const gameName  = (match.game?.name  || '').toLowerCase();
        const gameSlug  = (match.game?.slug  || '').toLowerCase();

        const isSinuca = sportSlug.includes('sinuca') || sportName.includes('sinuca')
                      || gameName.includes('sinuca')  || gameSlug.includes('sinuca');

        if (isSinuca) return 'assets/images/sinuca-game.png';

        const meta = match.metadata?.banner_image || match.metadata?.banner || match.game?.image;
        if (meta) return this.resolveImage(meta, null);

        return null;
    }
};
