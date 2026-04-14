/**
 * FORAPIX - UI Components
 * Componentes reutilizáveis da interface
 */

const Components = {
    /**
     * Show toast notification
     * @param {string} message - Toast message
     * @param {string} type - Toast type (success, error, warning)
     */
    showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const icons = {
            success: 'fa-check',
            error: 'fa-times',
            warning: 'fa-exclamation'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="icon">
                <i class="fas ${icons[type]}"></i>
            </div>
            <div class="message">${message}</div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
            setTimeout(() => toast.remove(), 300);
        }, CONFIG.TOAST.DURATION);
    },

    /**
     * Show loading overlay
     * @param {boolean} show - Show or hide
     */
    showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        if (show) {
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        } else {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }
    },

    /**
     * Show modal
     * @param {string} content - Modal HTML content
     */
    showModal(content) {
        const container = document.getElementById('modalContainer');
        const modalContent = document.getElementById('modalContent');
        
        modalContent.innerHTML = content;
        container.classList.remove('hidden');
        
        // Animate in
        setTimeout(() => {
            modalContent.style.transform = 'translateY(0)';
        }, 10);
    },

    /**
     * Close modal
     */
    closeModal() {
        const container = document.getElementById('modalContainer');
        const modalContent = document.getElementById('modalContent');
        
        modalContent.style.transform = 'translateY(100%)';
        
        setTimeout(() => {
            container.classList.add('hidden');
        }, 300);
    },

    /**
     * Render match card
     * @param {object} match - Match data
     */
    renderMatchCard(match) {
        const deadline = new Date(match.betting_deadline);
        const timeStr = Utils.formatDate(deadline, 'relative');
        const sport = CONFIG.SPORTS[match.sport?.id] || CONFIG.SPORTS[1];

        return `
            <div class="match-card" data-match-id="${match.id}" onclick="App.navigateTo('bet', { matchId: ${match.id} })">
                <div class="match-header">
                    <div class="match-sport">
                        <i class="fas ${sport.icon}"></i>
                        <span>${sport.name}</span>
                    </div>
                    <div class="match-time">
                        <i class="fas fa-clock"></i> ${timeStr}
                    </div>
                </div>
                <div class="match-fighters">
                    <div class="fighter">
                        <div class="fighter-avatar">
                            ${match.first_athlete?.photo_url 
                                ? `<img src="${match.first_athlete.photo_url}" alt="${match.first_athlete.name}" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-user\\'></i>'">`
                                : `<i class="fas fa-user"></i>`
                            }
                        </div>
                        <div class="fighter-name">${Utils.truncate(match.first_athlete?.name || 'Lutador 1', 15)}</div>
                        <div class="fighter-odds">${match.first_athlete_odds?.toFixed(2) || '1.00'}</div>
                    </div>
                    <div class="match-vs">VS</div>
                    <div class="fighter">
                        <div class="fighter-avatar">
                            ${match.second_athlete?.photo_url 
                                ? `<img src="${match.second_athlete.photo_url}" alt="${match.second_athlete.name}" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-user\\'></i>'">`
                                : `<i class="fas fa-user"></i>`
                            }
                        </div>
                        <div class="fighter-name">${Utils.truncate(match.second_athlete?.name || 'Lutador 2', 15)}</div>
                        <div class="fighter-odds">${match.second_athlete_odds?.toFixed(2) || '1.00'}</div>
                    </div>
                </div>
                <div class="match-action">
                    <button class="btn-bet">
                        <i class="fas fa-ticket"></i> APOSTAR
                    </button>
                </div>
            </div>
        `;
    },

    /**
     * Render service item
     * @param {object} service - Service data
     */
    renderServiceItem(service) {
        return `
            <div class="service-item" onclick="${service.action}">
                <div class="icon">
                    <i class="fas ${service.icon}"></i>
                </div>
                <span>${service.name}</span>
            </div>
        `;
    },

    /**
     * Render game card
     * @param {object} game - Game data
     */
    renderGameCard(game) {
        return `
            <div class="game-card" onclick="${game.action}">
                <img src="${game.image}" alt="${game.name}" onerror="this.src='assets/images/placeholder-game.png'">
                <div class="title">${game.name}</div>
            </div>
        `;
    },

    /**
     * Render transaction item
     * @param {object} transaction - Transaction data
     */
    renderTransactionItem(transaction) {
        const icons = {
            deposit: 'fa-arrow-down',
            withdraw: 'fa-arrow-up',
            bet: 'fa-ticket'
        };

        const isPositive = transaction.amount > 0;

        return `
            <div class="transaction-item ${transaction.type}">
                <div class="icon">
                    <i class="fas ${icons[transaction.type] || 'fa-exchange-alt'}"></i>
                </div>
                <div class="info">
                    <div class="title">${transaction.description}</div>
                    <div class="date">${Utils.formatDate(transaction.createdAt, 'full')}</div>
                </div>
                <div class="amount ${isPositive ? 'positive' : 'negative'}">
                    ${isPositive ? '+' : ''}${Utils.formatCurrency(transaction.amount, true)}
                </div>
            </div>
        `;
    },

    /**
     * Render empty state
     * @param {string} icon - Icon class
     * @param {string} title - Title text
     * @param {string} message - Message text
     */
    renderEmptyState(icon, title, message) {
        return `
            <div class="empty-state">
                <div class="icon">
                    <i class="fas ${icon}"></i>
                </div>
                <h3>${title}</h3>
                <p>${message}</p>
            </div>
        `;
    },

    /**
     * Render sport tabs
     * @param {number} activeSportId - Active sport ID
     */
    renderSportTabs(activeSportId = 1) {
        let html = '<div class="sport-tabs">';
        
        Object.entries(CONFIG.SPORTS).forEach(([id, sport]) => {
            const isActive = parseInt(id) === activeSportId;
            html += `
                <button class="sport-tab ${isActive ? 'active' : ''}" data-sport-id="${id}">
                    <i class="fas ${sport.icon}"></i>
                    <span>${sport.name}</span>
                </button>
            `;
        });

        html += '</div>';
        return html;
    },

    /**
     * Render quick value buttons
     * @param {string} inputId - Input element ID to update
     */
    renderQuickValues(inputId) {
        let html = '<div class="quick-values">';
        
        CONFIG.BET.QUICK_VALUES.forEach(value => {
            html += `
                <button type="button" class="quick-value-btn" onclick="document.getElementById('${inputId}').value = '${value}'; document.getElementById('${inputId}').dispatchEvent(new Event('input'))">
                    R$ ${value}
                </button>
            `;
        });

        html += '</div>';
        return html;
    },

    /**
     * Render bet summary
     * @param {object} data - Bet summary data
     */
    renderBetSummary(data) {
        const potentialWin = Utils.calculatePotentialWin(data.amount, data.odds);

        return `
            <div class="bet-summary">
                <div class="bet-summary-row">
                    <span class="label">Lutador</span>
                    <span class="value">${data.fighterName}</span>
                </div>
                <div class="bet-summary-row">
                    <span class="label">Odd</span>
                    <span class="value text-accent">${data.odds.toFixed(2)}</span>
                </div>
                <div class="bet-summary-row">
                    <span class="label">Valor da Aposta</span>
                    <span class="value">${Utils.formatCurrency(data.amount, true)}</span>
                </div>
                <div class="bet-summary-row">
                    <span class="label">Ganho Potencial</span>
                    <span class="value highlight">${Utils.formatCurrency(potentialWin, true)}</span>
                </div>
            </div>
        `;
    },

    /**
     * Render fighter selection option
     * @param {object} fighter - Fighter data
     * @param {number} odds - Fighter odds
     * @param {string} position - Position (first or second)
     * @param {boolean} selected - Is selected
     */
    renderFighterOption(fighter, odds, position, selected = false) {
        return `
            <div class="bet-fighter-option ${selected ? 'selected' : ''}" data-position="${position}" data-odds="${odds}">
                <div class="avatar">
                    ${fighter.photo_url 
                        ? `<img src="${fighter.photo_url}" alt="${fighter.name}" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-user text-2xl\\'></i>'">`
                        : `<i class="fas fa-user text-2xl"></i>`
                    }
                </div>
                <div class="info">
                    <div class="name">${fighter.name}</div>
                    <div class="odds">Odd: ${odds.toFixed(2)}</div>
                </div>
                <div class="check">
                    <i class="fas fa-check text-xs ${selected ? '' : 'hidden'}"></i>
                </div>
            </div>
        `;
    }
};

// Global function for closing modal
function closeModal() {
    Components.closeModal();
}
