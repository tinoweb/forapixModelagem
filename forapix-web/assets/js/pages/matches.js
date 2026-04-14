/**
 * ForaPix - Matches Page
 * Lista responsiva de partidas baseada no layout do app de referência
 */

const MatchesPage = {
    currentStatus: 'scheduled',
    currentGameId: null,
    selectedSport: null,
    listEl: null,

    render(params = {}) {
        this.currentStatus = params.status || this.currentStatus || 'scheduled';
        this.currentGameId = params.game_id || params.gameId || null;
        this.selectedSport = params.sport_id || params.sportId || null;

        return `
            <div class="page-enter p-4 space-y-4">
                ${this.renderFilterHeader()}
                <div id="matchesList" class="space-y-4">
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
            { key: 'live', label: 'Em andamento', icon: 'fa-play' },
            { key: 'finished', label: 'Encerradas', icon: 'fa-history' }
        ];

        return `
            <div class="px-4 py-3 bg-dark sticky top-14 z-30">
                <div class="flex gap-3">
                    ${filters.map(filter => `
                        <button class="match-status-pill flex-1 inline-flex items-center justify-center gap-2 py-3 rounded-full text-sm font-bold transition-all ${this.currentStatus === filter.key ? 'bg-[#e88b20] text-white shadow-lg active' : 'bg-[#1e2235] text-gray-400 border border-gray-700/50 hover:bg-[#252a40]'}" data-status="${filter.key}">
                            <i class="fas ${filter.icon}"></i> ${filter.label}
                        </button>
                    `).join('')}
                </div>
            </div>
        `;
    },

    bindStatusEvents() {
        document.querySelectorAll('.match-status-pill').forEach(button => {
            button.addEventListener('click', () => {
                const { status } = button.dataset;
                if (status === this.currentStatus) return;
                this.currentStatus = status;
                
                // Update visual immediately
                document.querySelectorAll('.match-status-pill').forEach(btn => {
                    const isSelected = btn.dataset.status === this.currentStatus;
                    btn.className = `match-status-pill flex-1 inline-flex items-center justify-center gap-2 py-3 rounded-full text-sm font-bold transition-all ${isSelected ? 'bg-[#e88b20] text-white shadow-lg active' : 'bg-[#1e2235] text-gray-400 border border-gray-700/50 hover:bg-[#252a40]'}`;
                });
                
                this.loadMatches();
            });
        });
    },

    updateStatusPills() {
        // Handled in bindStatusEvents
    },

    renderSkeletons() {
        return Array.from({ length: 2 }).map(() => `
            <div class="bg-[#1f213a] rounded-3xl overflow-hidden border border-gray-800/50 shadow-2xl mx-4 my-2 animate-pulse min-h-[400px] flex flex-col">
                <div class="h-48 bg-[#2a2e4c]"></div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div class="flex justify-center gap-3 mb-8">
                        <div class="w-24 h-8 bg-[#2a2e4c] rounded-full"></div>
                        <div class="w-24 h-8 bg-[#2a2e4c] rounded-full"></div>
                    </div>
                    <div class="flex justify-between px-4 mb-8">
                        <div class="w-16 h-16 bg-[#2a2e4c] rounded-full"></div>
                        <div class="w-16 h-16 bg-[#2a2e4c] rounded-full"></div>
                    </div>
                    <div class="h-14 bg-[#2a2e4c] rounded-2xl w-full"></div>
                </div>
            </div>
        `).join('');
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
                this.listEl.innerHTML = Components.renderEmptyState(
                    'fa-circle-info',
                    'Nenhuma partida encontrada',
                    'Assim que novas partidas forem cadastradas, elas aparecerão aqui.'
                );
                return;
            }

            this.listEl.innerHTML = payload.map(match => this.renderMatchCard(match)).join('');
        } catch (error) {
            console.error('Erro ao carregar partidas', error);
            this.listEl.innerHTML = Components.renderEmptyState(
                'fa-triangle-exclamation',
                'Erro ao carregar partidas',
                'Tente novamente em instantes.'
            );
        }
    },

    renderMatchCard(match) {
        const sportName = match.game?.sport?.name || match.game?.name || 'Sinuca';
        const cover = this.getMatchImage(match);
        const firstPlayer = match.first_player || match.firstPlayer || {};
        const secondPlayer = match.second_player || match.secondPlayer || {};
        const firstScore = match.first_player_score ?? match.firstPlayerScore ?? 0;
        const secondScore = match.second_player_score ?? match.secondPlayerScore ?? 0;
        const canBet = this.isBettingOpen(match);
        const typeTag = this.formatGameType(match.game?.type || match.type);

        return `
            <div class="bg-[#1f213a] rounded-3xl overflow-hidden border border-gray-800/50 shadow-2xl mx-4 my-6">
                <!-- Imagem de topo -->
                <div class="relative h-48">
                    <img src="${cover}" alt="${sportName}" class="w-full h-full object-cover" onerror="this.src='assets/images/sinuca-game.jpg'">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#1f213a] via-transparent to-black/40"></div>
                    
                    <!-- Badge superior esquerdo -->
                    <div class="absolute top-4 left-4 bg-black/60 backdrop-blur-md border border-white/10 rounded-full px-3 py-1.5 flex items-center gap-2">
                        <i class="fas fa-billiards text-white text-xs"></i>
                        <span class="text-white text-xs font-bold tracking-wider uppercase">${sportName}</span>
                    </div>

                    <!-- Badge inferior centralizado na imagem -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 backdrop-blur-md border border-white/10 rounded-full px-4 py-1.5 flex items-center gap-2 shadow-lg whitespace-nowrap">
                        <div class="w-2 h-2 rounded-full ${canBet ? 'bg-green-400 animate-pulse' : 'bg-gray-500'}"></div>
                        <i class="far fa-clock text-white text-xs"></i>
                        <span class="text-white text-xs font-bold tracking-wider">${this.formatDate(match.match_start, 'datetime')}</span>
                    </div>
                </div>

                <!-- Área de conteúdo -->
                <div class="p-5 flex flex-col items-center">
                    
                    <!-- Abas de apostas (fictícias na listagem) -->
                    <div class="flex justify-center gap-3 mb-8 w-full">
                        <div class="bg-[#292e47] border border-[#3b4168] text-blue-400 px-6 py-2 rounded-full text-xs font-bold shadow-inner">
                            ${typeTag}
                        </div>
                        <div class="bg-transparent border border-[#2d324f] text-[#555b77] px-6 py-2 rounded-full text-xs font-bold">
                            QUEM FAZ 10
                        </div>
                    </div>

                    <!-- Seção dos jogadores e placar -->
                    <div class="flex items-center justify-between w-full mb-8">
                        
                        <!-- Jogador 1 -->
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-[72px] h-[72px] rounded-full overflow-hidden border-2 border-[#1f213a] ring-2 ring-transparent shadow-lg mb-3">
                                <img src="${Utils.getPlayerPhoto(firstPlayer, '#f97316')}" alt="${firstPlayer.name || 'Jogador 1'}" class="w-full h-full object-cover">
                            </div>
                            <span class="text-white text-[13px] font-bold text-center leading-tight max-w-[90px]">${Utils.truncate(firstPlayer.name || 'Jogador 1', 20)}</span>
                        </div>

                        <!-- Placar Central -->
                        <div class="flex items-center gap-3 px-2">
                            <div class="bg-[#2a2e4c] shadow-inner text-white font-black text-2xl w-12 h-12 flex items-center justify-center rounded-xl border border-white/5">
                                ${firstScore}
                            </div>
                            <span class="text-[#646a87] text-xs font-bold uppercase tracking-wider">VS</span>
                            <div class="bg-[#2a2e4c] shadow-inner text-white font-black text-2xl w-12 h-12 flex items-center justify-center rounded-xl border border-white/5">
                                ${secondScore}
                            </div>
                        </div>

                        <!-- Jogador 2 -->
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-[72px] h-[72px] rounded-full overflow-hidden border-2 border-[#1f213a] ring-2 ring-transparent shadow-lg mb-3">
                                <img src="${Utils.getPlayerPhoto(secondPlayer, '#ef4444')}" alt="${secondPlayer.name || 'Jogador 2'}" class="w-full h-full object-cover">
                            </div>
                            <span class="text-white text-[13px] font-bold text-center leading-tight max-w-[90px]">${Utils.truncate(secondPlayer.name || 'Jogador 2', 20)}</span>
                        </div>

                    </div>

                    <!-- Botão Apostar -->
                    <button class="w-full bg-[#e88b20] hover:bg-[#f09a30] text-white py-4 rounded-2xl font-bold text-base shadow-[0_4px_15px_rgba(232,139,32,0.3)] transition-all active:scale-[0.98]" onclick="MatchesPage.openMatch(${match.id})">
                        <i class="fas fa-bolt mr-2"></i>${canBet ? 'Apostar agora' : 'Ver detalhes'}
                    </button>
                    
                </div>
            </div>
        `;
    },

    getMatchImage(match) {
        const metadataImage = match.metadata?.banner_image || match.metadata?.banner;
        if (metadataImage) {
            return Utils.resolveImage(metadataImage, 'assets/images/sinuca-game.jpg');
        }
        if (match.game?.image) {
            return Utils.resolveImage(match.game.image, 'assets/images/sinuca-game.jpg');
        }
        return 'assets/images/sinuca-game.jpg';
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

    getStatusBadge(match, canBet) {
        if (match.status === 'finished') {
            return { label: 'Encerrada', bg: 'bg-red-500/15', color: 'text-red-200' };
        }
        if (match.status === 'live') {
            return { label: 'Ao vivo', bg: 'bg-green-500/20', color: 'text-green-200' };
        }
        if (canBet) {
            return { label: 'Apostas abertas', bg: 'bg-amber-500/20', color: 'text-amber-200' };
        }
        return { label: 'Programada', bg: 'bg-slate-500/30', color: 'text-slate-200' };
    },

    formatDate(date, mode = 'datetime') {
        if (!date) return '--';
        if (mode === 'time') {
            return Utils.formatDate(date, 'time');
        }
        return Utils.formatDate(date);
    },

    formatGameType(type) {
        if (!type) return 'Posta casada';
        return type.replace('_', ' ').toUpperCase();
    },

    openMatch(id) {
        App.navigateTo('sinuca', { matchId: id });
    }
};
