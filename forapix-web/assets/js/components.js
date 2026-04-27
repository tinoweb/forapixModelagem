/**
 * ForaPix - UI Components
 * Componentes reutilizáveis da interface
 */

const Components = {
    /**
     * Show toast notification
     */
    showToast(message, type = 'info', duration = Config.TOAST.DURATION) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = this.getToastIcon(type);
        
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="toast-content">
                <p class="text-sm font-medium">${message}</p>
            </div>
        `;
        
        container.appendChild(toast);
        
        // Auto remove
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, duration);
        
        // Click to remove
        toast.addEventListener('click', () => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        });
    },

    /**
     * Get toast icon
     */
    getToastIcon(type) {
        const icons = {
            success: 'fa-check',
            error: 'fa-times',
            warning: 'fa-exclamation',
            info: 'fa-info'
        };
        return icons[type] || icons.info;
    },

    /**
     * Show/hide loading overlay
     */
    showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.toggle('hidden', !show);
        }
    },

    /**
     * Show modal
     */
    showModal(content, options = {}) {
        const container = document.getElementById('modalsContainer');
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                ${content}
            </div>
        `;
        
        container.appendChild(modal);
        
        // Close on backdrop click
        if (options.closeOnBackdrop !== false) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });
        }
        
        // Close on escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeModal(modal);
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
        
        return modal;
    },

    /**
     * Close modal
     */
    closeModal(modal = null) {
        if (!modal) {
            const container = document.getElementById('modalsContainer');
            const modals = container.querySelectorAll('.modal-overlay');
            modal = modals[modals.length - 1]; // Close last opened modal
        }
        
        if (modal && modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
    },

    /**
     * Render match card (mobile-first compact layout)
     */
    renderMatchCard(match) {
        const firstPlayer = match.first_player || match.firstPlayer || {};
        const secondPlayer = match.second_player || match.secondPlayer || {};
        const firstName = firstPlayer.name || match.fighter1 || 'Jogador 1';
        const secondName = secondPlayer.name || match.fighter2 || 'Jogador 2';
        const firstOdds = match.first_player_odds ?? match.odds1 ?? null;
        const secondOdds = match.second_player_odds ?? match.odds2 ?? null;
        const sportLabel = (match.game?.sport?.name || match.game?.name || match.sport?.name || match.sport || 'Evento').toUpperCase();
        const matchId = match.id || match.match_id || 0;
        const isLive = match.status === 'live';
        const isFinished = match.status === 'finished';
        const canBet = match.status !== 'finished' && match.betting_deadline && new Date(match.betting_deadline) > new Date();

        const statusDot = isLive
            ? '<span class="live-dot"></span>'
            : (isFinished ? '<i class="fas fa-circle-check text-xs"></i>' : '<i class="far fa-clock text-xs"></i>');
        const statusText = isLive ? 'AO VIVO' : (isFinished ? 'ENCERRADA' : Utils.formatDate(match.match_start, 'time'));
        const statusClass = isLive ? 'live' : (isFinished ? 'finished' : 'scheduled');

        const bgImg   = Utils.getMatchBgImage(match);
        const bgStyle = bgImg ? `style="background-image:url('${bgImg}')"` : '';
        const bgClass = bgImg ? 'match-list-card--bg' : '';

        return `
            <div class="match-list-card ${bgClass}" ${bgStyle} onclick="App.navigateTo('sinuca', { matchId: ${matchId} })">
                <div class="match-list-header">
                    <div class="match-list-sport">
                        <span class="match-list-sport-icon"><i class="fas fa-gamepad"></i></span>
                        <span class="match-list-sport-name">${sportLabel}</span>
                    </div>
                    <div class="match-list-status ${statusClass}">
                        ${statusDot}
                        <span>${statusText}</span>
                    </div>
                </div>
                <div class="match-list-body">
                    <div class="match-list-player">
                        <div class="match-list-player-info">
                            <div class="match-list-avatar">
                                <img src="${Utils.getPlayerPhoto(firstPlayer, '#22c55e')}" alt="${firstName}">
                            </div>
                            <div class="match-list-player-text">
                                <span class="match-list-player-name">${Utils.truncate(firstName, 18)}</span>
                                ${firstOdds ? `<span class="match-list-odds">${parseFloat(firstOdds).toFixed(2)}x</span>` : ''}
                            </div>
                        </div>
                        <div class="match-list-score">${match.first_player_score ?? 0}</div>
                    </div>
                    <div class="match-list-vs">VS</div>
                    <div class="match-list-player">
                        <div class="match-list-player-info">
                            <div class="match-list-avatar">
                                <img src="${Utils.getPlayerPhoto(secondPlayer, '#ef4444')}" alt="${secondName}">
                            </div>
                            <div class="match-list-player-text">
                                <span class="match-list-player-name">${Utils.truncate(secondName, 18)}</span>
                                ${secondOdds ? `<span class="match-list-odds">${parseFloat(secondOdds).toFixed(2)}x</span>` : ''}
                            </div>
                        </div>
                        <div class="match-list-score">${match.second_player_score ?? 0}</div>
                    </div>
                </div>
                <div class="match-list-footer">
                    <div class="match-list-meta">
                        <span><i class="far fa-clock"></i> ${Utils.formatDate(match.betting_deadline || match.match_start, 'time')}</span>
                    </div>
                    <div class="match-list-action ${canBet ? 'bet-open' : ''}">
                        ${canBet ? '<i class="fas fa-bolt"></i> Apostar' : (isFinished ? '<i class="fas fa-eye"></i> Ver' : '<i class="fas fa-lock"></i>')}
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Render service item
     */
    renderServiceItem(service) {
        return `
            <div class="service-item" onclick="${service.action}">
                <span class="service-icon"><i class="fas ${service.icon}"></i></span>
                <span class="service-label">${service.name}</span>
            </div>
        `;
    },

    /**
     * Render game card
     */
    renderGameCard(game) {
        return `
            <div class="game-card" onclick="App.navigateTo('matches', { gameId: ${game.id} })">
                <img src="${game.image}" alt="${game.name}" onerror="this.src='https://via.placeholder.com/200x100/7c3aed/ffffff?text=${encodeURIComponent(game.name)}'">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent rounded-2xl"></div>
                <div class="absolute bottom-4 left-4 right-4">
                    <h3 class="text-white font-bold text-lg">${game.name}</h3>
                </div>
            </div>
        `;
    },

    /**
     * Render transaction item
     */
    renderTransactionItem(transaction) {
        const isCredit = transaction.amount > 0;
        const icon = this.getTransactionIcon(transaction.type);
        const color = isCredit ? 'text-success' : 'text-danger';
        
        return `
            <div class="transaction-item bg-card-bg rounded-xl p-4 mb-3 border border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-secondary rounded-full flex items-center justify-center">
                            <i class="fas ${icon} text-accent"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">${transaction.description}</p>
                            <p class="text-xs text-gray-400">${Utils.formatDate(transaction.date)}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold ${color}">${isCredit ? '+' : ''}${Utils.formatCurrency(Math.abs(transaction.amount), true)}</p>
                        <p class="text-xs text-gray-400">${this.getTransactionStatus(transaction.status)}</p>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Get transaction icon
     */
    getTransactionIcon(type) {
        const icons = {
            deposit: 'fa-arrow-down',
            withdraw: 'fa-arrow-up',
            bet: 'fa-dice',
            win: 'fa-trophy'
        };
        return icons[type] || 'fa-exchange-alt';
    },

    /**
     * Get transaction status
     */
    getTransactionStatus(status) {
        const statuses = {
            completed: 'Concluída',
            pending: 'Pendente',
            failed: 'Falhou',
            cancelled: 'Cancelada'
        };
        return statuses[status] || status;
    },

    /**
     * Render empty state
     */
    renderEmptyState(icon, title, message) {
        return `
            <div class="empty-state text-center py-12">
                <div class="w-20 h-20 bg-secondary rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas ${icon} text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">${title}</h3>
                <p class="text-gray-400 text-sm">${message}</p>
            </div>
        `;
    },

    /**
     * Render sport tabs
     */
    renderSportTabs(sports, activeId = null) {
        return sports.map(sport => `
            <button class="sport-tab ${sport.id === activeId ? 'active' : ''}" data-sport="${sport.id}">
                <i class="fas ${sport.icon}"></i>
                <span>${sport.name}</span>
            </button>
        `).join('');
    },

    /**
     * Render quick values
     */
    renderQuickValues(inputId) {
        return `
            <div class="quick-values">
                ${Config.BETTING.QUICK_VALUES.map(value => `
                    <button class="quick-value" onclick="document.getElementById('${inputId}').value = ${value}; document.getElementById('${inputId}').dispatchEvent(new Event('input'));">
                        ${Utils.formatCurrency(value, true)}
                    </button>
                `).join('')}
            </div>
        `;
    },

    /**
     * Render bet summary
     */
    renderBetSummary(bet) {
        return `
            <div class="bet-summary bg-secondary rounded-xl p-4">
                <h4 class="font-semibold mb-3">Resumo da Aposta</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Lutador:</span>
                        <span class="font-semibold">${bet.fighterName}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Odd:</span>
                        <span class="font-semibold text-accent">${bet.odds}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Valor:</span>
                        <span class="font-semibold">${Utils.formatCurrency(bet.amount, true)}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-600 pt-2">
                        <span class="text-gray-400">Ganho potencial:</span>
                        <span class="font-bold text-success">${Utils.formatCurrency(bet.potentialWin, true)}</span>
                    </div>
                </div>
            </div>
        `;
    }
};

// Global function to close modals (for onclick handlers)
window.closeModal = () => Components.closeModal();
