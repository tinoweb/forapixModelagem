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
        const modality = match.game?.name || '';
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
        const matchDate = this.formatDate(match.match_start, 'datetime');

        const bgImg = Utils.getMatchBgImage(match);
        const bgStyle = bgImg ? `background-image:url('${bgImg}')` : '';

        const p1Name = (firstPlayer.name || 'Jogador 1').split(' ')[0];
        const p2Name = (secondPlayer.name || 'Jogador 2').split(' ')[0];

        const statusBadge = isLive
            ? '<span class="mc-live-badge"><span class="live-dot"></span> AO VIVO</span>'
            : (isFinished ? '<span class="mc-finished-badge"><i class="fas fa-circle-check"></i> ENCERRADA</span>' : '');

        return `
            <div class="match-card-v2" onclick="MatchesPage.openMatch(${matchId})">

                <!-- IMAGEM TOPO -->
                <div class="mc-image" style="${bgStyle}">
                    <div class="mc-image-overlay"></div>
                    <div class="mc-image-top">
                        <span class="mc-badge-sport">
                            <i class="fas ${this.getSportIcon(match)}"></i>
                            ${sportName}
                        </span>
                        ${statusBadge}
                    </div>
                    <div class="mc-image-bottom">
                        <span class="mc-badge-date">
                            <i class="fas fa-clock"></i> ${matchDate}
                        </span>
                    </div>
                </div>

                <!-- BODY ESCURO -->
                <div class="mc-body">
                    <!-- Modalidade(s) -->
                    ${modality ? `<div class="mc-modalities"><span class="mc-modality-pill">${modality.toUpperCase()}</span></div>` : ''}

                    <!-- Jogadores e Score -->
                    <div class="mc-players">
                        <div class="mc-player">
                            <div class="mc-avatar ${winnerIs === 'first' ? 'winner' : ''}">
                                <img src="${Utils.getPlayerPhoto(firstPlayer)}" alt="${p1Name}" onerror="this.src='assets/images/jogador1.png'">
                            </div>
                            <span class="mc-player-name">${p1Name}</span>
                            ${firstOdds ? `<span class="mc-odds">${parseFloat(firstOdds).toFixed(2)}x</span>` : ''}
                        </div>

                        <div class="mc-score-area">
                            <span class="mc-score ${winnerIs === 'first' ? 'winner' : ''}">${firstScore}</span>
                            <span class="mc-vs">vs</span>
                            <span class="mc-score ${winnerIs === 'second' ? 'winner' : ''}">${secondScore}</span>
                        </div>

                        <div class="mc-player">
                            <div class="mc-avatar ${winnerIs === 'second' ? 'winner' : ''}">
                                <img src="${Utils.getPlayerPhoto(secondPlayer)}" alt="${p2Name}" onerror="this.src='assets/images/jogador2.png'">
                            </div>
                            <span class="mc-player-name">${p2Name}</span>
                            ${secondOdds ? `<span class="mc-odds">${parseFloat(secondOdds).toFixed(2)}x</span>` : ''}
                        </div>
                    </div>

                    <!-- Botão sempre visível -->
                    <button class="mc-bet-btn ${canBet ? '' : 'mc-bet-btn--locked'}" onclick="event.stopPropagation(); App.navigateTo('sinuca', { matchId: ${matchId} })">
                        ${canBet
                            ? '<i class="fas fa-bolt"></i> Apostar agora'
                            : (isFinished ? '<i class="fas fa-eye"></i> Ver resultado' : '<i class="fas fa-lock"></i> Apostas fechadas')}
                    </button>
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
