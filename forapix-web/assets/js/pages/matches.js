/**
 * ForaPix - Matches Page
 * Lista de partidas replicando o layout do app de referência
 */

const MatchesPage = {
    currentStatus: 'scheduled',
    currentGameId: null,
    selectedSport: null,
    listEl: null,

    render(params = {}) {
        this.currentStatus = params.status || this.currentStatus || 'live';
        this.currentGameId = params.game_id || params.gameId || null;
        this.selectedSport = params.sport_id || params.sportId || null;

        return `
            <div class="page-enter matches-page">
                ${this.renderFilterHeader()}
                <div id="matchesList" class="matches-list">
                    ${this.renderSkeletons()}
                </div>
            </div>
        `;
    },

    init() {
        this.listEl = document.getElementById('matchesList');
        this.bindStatusEvents();
        this.loadMatches();
    },

    renderFilterHeader() {
        const filters = [
            { key: 'scheduled', label: 'Agendadas', icon: 'fa-calendar' },
            { key: 'live', label: 'Ao vivo', icon: 'fa-trophy' },
            { key: 'finished', label: 'Encerradas', icon: 'fa-clock-rotate-left' }
        ];

        return `
            <div class="matches-filter">
                ${filters.map(filter => `
                    <button class="match-status-pill ${this.currentStatus === filter.key ? 'active' : ''}" data-status="${filter.key}">
                        <i class="fas ${filter.icon}"></i>
                        <span>${filter.label}</span>
                    </button>
                `).join('')}
            </div>
        `;
    },

    bindStatusEvents() {
        document.querySelectorAll('.match-status-pill').forEach(button => {
            button.addEventListener('click', () => {
                const { status } = button.dataset;
                if (status === this.currentStatus) return;
                this.currentStatus = status;

                document.querySelectorAll('.match-status-pill').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.status === this.currentStatus);
                });

                this.loadMatches();
            });
        });
    },

    renderSkeletons() {
        return `
            <div class="matches-skeleton">
                ${Array.from({ length: 6 }).map(() => `
                    <div class="skeleton-row"></div>
                `).join('')}
            </div>
        `;
    },

    renderEmptyState() {
        const labels = {
            live: 'Nenhuma partida em andamento',
            finished: 'Nenhuma partida encerrada',
            scheduled: 'Nenhuma partida programada'
        };
        const message = labels[this.currentStatus] || 'Nenhuma partida encontrada';

        return `
            <div class="matches-empty">
                <i class="fas fa-flag-checkered"></i>
                <p>${message}</p>
            </div>
        `;
    },

    async loadMatches() {
        if (!this.listEl) return;
        this.listEl.innerHTML = this.renderSkeletons();

        const filters = {};
        if (this.currentStatus && this.currentStatus !== 'all') {
            filters.status = this.currentStatus;
        }
        if (this.currentGameId) {
            filters.game_id = this.currentGameId;
        }
        if (this.selectedSport) {
            filters.sport_id = this.selectedSport;
        }

        try {
            const response = await API.getMatches(filters);
            const payload = Array.isArray(response.data?.data)
                ? response.data.data
                : (Array.isArray(response.data) ? response.data : []);

            if (!payload.length) {
                this.listEl.innerHTML = this.renderEmptyState();
                return;
            }

            this.listEl.innerHTML = payload.map(match => this.renderMatchCard(match)).join('');
        } catch (error) {
            console.error('Erro ao carregar partidas', error);
            this.listEl.innerHTML = this.renderEmptyState();
        }
    },

    renderMatchCard(match) {
        const sportName = (match.game?.sport?.name || match.game?.name || 'Sinuca').toUpperCase();
        const firstPlayer = match.first_player || match.firstPlayer || {};
        const secondPlayer = match.second_player || match.secondPlayer || {};
        const firstScore = match.first_player_score ?? match.firstPlayerScore ?? 0;
        const secondScore = match.second_player_score ?? match.secondPlayerScore ?? 0;
        const isFinished = match.status === 'finished';
        const isLive = match.status === 'live';
        const canBet = this.isBettingOpen(match);
        const winnerIs = this.resolveWinner(match, firstScore, secondScore);
        const matchId = match.id || match.match_id || 0;
        const firstOdds = match.first_player_odds ?? match.firstPlayerOdds ?? null;
        const secondOdds = match.second_player_odds ?? match.secondPlayerOdds ?? null;
        const totalBets = match.total_bets_count ?? 0;
        const totalAmount = match.total_bets_amount ?? 0;

        const statusDot = isLive
            ? '<span class="live-dot"></span>'
            : (isFinished ? '<i class="fas fa-circle-check text-xs"></i>' : '<i class="far fa-clock text-xs"></i>');
        const statusText = isLive ? 'AO VIVO' : (isFinished ? 'ENCERRADA' : this.formatDate(match.match_start, 'time'));
        const statusClass = isLive ? 'live' : (isFinished ? 'finished' : 'scheduled');

        const bgImg  = Utils.getMatchBgImage(match);
        const bgStyle = bgImg ? `style="background-image:url('${bgImg}')"` : '';
        const bgClass = bgImg ? 'match-list-card--bg' : '';

        return `
            <div class="match-list-card ${bgClass}" ${bgStyle} onclick="MatchesPage.openMatch(${matchId})">
                <!-- Header do card -->
                <div class="match-list-header">
                    <div class="match-list-sport">
                        <span class="match-list-sport-icon"><i class="fas ${this.getSportIcon(match)}"></i></span>
                        <span class="match-list-sport-name">${sportName}</span>
                    </div>
                    <div class="match-list-status ${statusClass}">
                        ${statusDot}
                        <span>${statusText}</span>
                    </div>
                </div>

                <!-- Jogadores e Odds -->
                <div class="match-list-body">
                    <div class="match-list-player ${winnerIs === 'first' ? 'winner' : ''}">
                        <div class="match-list-player-info">
                            <div class="match-list-avatar">
                                <img src="${Utils.getPlayerPhoto(firstPlayer, '#22c55e')}" alt="${firstPlayer.name || 'Jogador 1'}">
                                ${winnerIs === 'first' ? '<span class="avatar-winner"><i class="fas fa-trophy"></i></span>' : ''}
                            </div>
                            <div class="match-list-player-text">
                                <span class="match-list-player-name">${Utils.truncate(firstPlayer.name || 'Jogador 1', 18)}</span>
                                ${firstOdds ? `<span class="match-list-odds">${parseFloat(firstOdds).toFixed(2)}x</span>` : ''}
                            </div>
                        </div>
                        <div class="match-list-score ${winnerIs === 'first' ? 'winner' : ''}">${firstScore}</div>
                    </div>

                    <div class="match-list-vs">VS</div>

                    <div class="match-list-player ${winnerIs === 'second' ? 'winner' : ''}">
                        <div class="match-list-player-info">
                            <div class="match-list-avatar">
                                <img src="${Utils.getPlayerPhoto(secondPlayer, '#ef4444')}" alt="${secondPlayer.name || 'Jogador 2'}">
                                ${winnerIs === 'second' ? '<span class="avatar-winner"><i class="fas fa-trophy"></i></span>' : ''}
                            </div>
                            <div class="match-list-player-text">
                                <span class="match-list-player-name">${Utils.truncate(secondPlayer.name || 'Jogador 2', 18)}</span>
                                ${secondOdds ? `<span class="match-list-odds">${parseFloat(secondOdds).toFixed(2)}x</span>` : ''}
                            </div>
                        </div>
                        <div class="match-list-score ${winnerIs === 'second' ? 'winner' : ''}">${secondScore}</div>
                    </div>
                </div>

                <!-- Footer do card -->
                <div class="match-list-footer">
                    <div class="match-list-meta">
                        ${totalBets > 0 ? `<span><i class="fas fa-users"></i> ${totalBets}</span>` : ''}
                        ${totalAmount > 0 ? `<span><i class="fas fa-coins"></i> ${Utils.formatCurrency(totalAmount, true)}</span>` : ''}
                        <span><i class="far fa-clock"></i> ${this.formatDate(match.betting_deadline || match.match_start, 'time')}</span>
                    </div>
                    <div class="match-list-action ${canBet ? 'bet-open' : ''}">
                        ${canBet ? '<i class="fas fa-bolt"></i> Apostar' : (isFinished ? '<i class="fas fa-eye"></i> Ver' : '<i class="fas fa-lock"></i>')}
                    </div>
                </div>
            </div>
        `;
    },

    getSportIcon(match) {
        const sportId = match.game?.sport?.id || match.game?.sport_id;
        const sport = Config.SPORTS.find(s => s.id === sportId);
        return sport ? sport.icon : 'fa-gamepad';
    },

    resolveWinner(match, firstScore, secondScore) {
        if (match.status !== 'finished') return null;
        if (match.winner_player_id) {
            if (match.winner_player_id === (match.first_player?.id || match.firstPlayer?.id)) return 'first';
            if (match.winner_player_id === (match.second_player?.id || match.secondPlayer?.id)) return 'second';
        }
        if (firstScore > secondScore) return 'first';
        if (secondScore > firstScore) return 'second';
        return null;
    },

    isBettingOpen(match) {
        if (typeof match.can_bet !== 'undefined') {
            return !!match.can_bet;
        }
        if (!match.betting_deadline) return false;
        const deadline = new Date(match.betting_deadline);
        const now = new Date();
        return match.status !== 'finished' && deadline > now;
    },

    formatDate(date, mode = 'datetime') {
        if (!date) return '--';
        if (mode === 'time') {
            return Utils.formatDate(date, 'time');
        }
        return Utils.formatDate(date);
    },

    openMatch(id) {
        if (!id) return;
        App.navigateTo('sinuca', { matchId: id });
    }
};
