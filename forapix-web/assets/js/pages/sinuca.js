/**
 * ForaPix - Página de partida (Sinuca)
 * Consome a API real para exibir detalhes e permitir apostas
 */

const SinucaPage = {
    matchId: null,
    currentMatch: null,
    betOptions: [],
    selectedBet: null,

    render(params = {}) {
        this.matchId = params.matchId || params.id || this.matchId;
        this.betOptions = [];
        this.selectedBet = null;

        return `
            <div class="page-enter" id="sinucaPage">
                ${this.renderLoading()}
            </div>
        `;
    },

    init() {
        this.loadMatch();
    },

    async loadMatch() {
        const container = document.getElementById('sinucaPage');
        if (!this.matchId) {
            container.innerHTML = this.renderError('Partida não encontrada.');
            return;
        }

        try {
            const response = await API.getMatch(this.matchId);
            if (!response.success || !response.data) {
                throw new Error(response.message || 'Partida não encontrada.');
            }

            this.currentMatch = response.data;
            this.betOptions = this.buildBetOptions(response.data);
            container.innerHTML = this.renderContent();
        } catch (error) {
            console.error('Erro ao carregar partida', error);
            container.innerHTML = this.renderError(error.message || 'Erro ao carregar partida.');
        }
    },

    renderLoading() {
        return `
            <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                <div class="w-12 h-12 border-4 border-accent border-t-transparent rounded-full animate-spin mb-4"></div>
                <p>Carregando partida...</p>
            </div>
        `;
    },

    renderError(message) {
        return `
            <div class="p-6 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-red-500/10 text-red-300 flex items-center justify-center mx-auto">
                    <i class="fas fa-triangle-exclamation text-2xl"></i>
                </div>
                <p class="text-gray-300">${message}</p>
                <button class="btn btn-primary w-full" onclick="App.goBack()">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
            </div>
        `;
    },

    renderContent() {
        const match = this.currentMatch;
        return `
            ${this.renderHeader(match)}
            ${this.renderStatusTabs(match)}
            <div class="p-4 space-y-4">
                ${this.renderHero(match)}
                ${this.renderInfo(match)}
                ${this.renderBettingOptions()}
                ${this.renderStats(match)}
                ${this.renderHighlights(match)}
            </div>
        `;
    },

    renderHeader(match) {
        return `
            <div class="bg-primary px-4 py-3 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <button onclick="App.goBack()" class="text-white hover:text-accent transition">
                        <i class="fas fa-arrow-left text-lg"></i>
                    </button>
                    <div>
                        <p class="text-white font-bold text-lg leading-none">${match.game?.name || 'Partida'}</p>
                        <div class="flex items-center gap-2 text-xs text-success mt-1">
                            <span class="w-2 h-2 rounded-full ${match.status === 'live' ? 'bg-success animate-pulse' : 'bg-gray-500'}"></span>
                            <span>${this.getStatusText(match)}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-secondary px-3 py-1 rounded-lg text-sm font-semibold">
                        R$ <span>${Utils.formatCurrency(Storage.getBalance())}</span>
                    </div>
                    <button class="w-8 h-8 bg-accent rounded-full flex items-center justify-center" onclick="App.navigateTo('menu')">
                        <i class="fas fa-user text-sm"></i>
                    </button>
                </div>
            </div>
        `;
    },

    renderStatusTabs(match) {
        return `
            <div class="px-4 py-3 bg-dark">
                <div class="flex gap-3">
                    <button class="flex-1 inline-flex items-center justify-center gap-2 py-3 rounded-full text-sm font-bold transition-all ${match.status === 'live' ? 'bg-[#e88b20] text-white shadow-lg' : 'bg-[#1e2235] text-gray-400 border border-gray-700/50 hover:bg-[#252a40]'}">
                        <i class="fas fa-play"></i> Em andamento
                    </button>
                    <button class="flex-1 inline-flex items-center justify-center gap-2 py-3 rounded-full text-sm font-bold transition-all ${match.status === 'finished' ? 'bg-[#e88b20] text-white shadow-lg' : 'bg-[#1e2235] text-gray-400 border border-gray-700/50 hover:bg-[#252a40]'}">
                        <i class="fas fa-history"></i> Encerradas
                    </button>
                </div>
            </div>
        `;
    },

    renderHero(match) {
        const firstPlayer = match.first_player || {};
        const secondPlayer = match.second_player || {};
        const canBet = this.canBet(match);
        const image = this.getMatchImage(match);

        return `
            <div class="bg-[#1f213a] rounded-3xl overflow-hidden border border-gray-800/50 shadow-2xl mx-4 my-2">
                <!-- Imagem de topo -->
                <div class="relative h-48">
                    <img src="${image}" alt="${match.game?.name || 'Sinuca'}" class="w-full h-full object-cover" onerror="this.src='assets/images/sinuca-game.jpg'">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#1f213a] via-transparent to-black/40"></div>
                    
                    <!-- Badge superior esquerdo -->
                    <div class="absolute top-4 left-4 bg-black/60 backdrop-blur-md border border-white/10 rounded-full px-3 py-1.5 flex items-center gap-2">
                        <i class="fas fa-billiards text-white text-xs"></i>
                        <span class="text-white text-xs font-bold tracking-wider uppercase">SINUCA</span>
                    </div>

                    <!-- Badge inferior centralizado na imagem -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 backdrop-blur-md border border-white/10 rounded-full px-4 py-1.5 flex items-center gap-2 shadow-lg">
                        <div class="w-2 h-2 rounded-full ${canBet ? 'bg-green-400 animate-pulse' : 'bg-gray-500'}"></div>
                        <i class="far fa-clock text-white text-xs"></i>
                        <span class="text-white text-xs font-bold tracking-wider">${this.formatDate(match.match_start, 'datetime')}</span>
                    </div>
                </div>

                <!-- Área de conteúdo -->
                <div class="p-5 flex flex-col items-center">
                    
                    <!-- Abas de apostas -->
                    <div class="flex justify-center gap-3 mb-8 w-full">
                        <button class="bg-[#292e47] border border-[#3b4168] text-blue-400 hover:bg-[#343b5c] px-6 py-2 rounded-full text-xs font-bold transition-all shadow-inner">
                            BOLINHO
                        </button>
                        <button class="bg-transparent border border-[#2d324f] text-[#555b77] hover:text-gray-300 px-6 py-2 rounded-full text-xs font-bold transition-all">
                            QUEM FAZ 10
                        </button>
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
                                ${match.first_player_score ?? 4}
                            </div>
                            <span class="text-[#646a87] text-xs font-bold uppercase tracking-wider">VS</span>
                            <div class="bg-[#2a2e4c] shadow-inner text-white font-black text-2xl w-12 h-12 flex items-center justify-center rounded-xl border border-white/5">
                                ${match.second_player_score ?? 6}
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
                    <button class="w-full bg-[#e88b20] hover:bg-[#f09a30] text-white py-4 rounded-2xl font-bold text-base shadow-[0_4px_15px_rgba(232,139,32,0.3)] transition-all active:scale-[0.98]" onclick="SinucaPage.showBettingModal()" ${!canBet ? 'disabled style="opacity:0.5; cursor:not-allowed"' : ''}>
                        <i class="fas fa-bolt mr-2"></i>Apostar agora
                    </button>
                    
                </div>
            </div>
        `;
    },

    renderBettingTabs(match) {
        return `
            <div class="flex gap-2 mb-6">
                <button class="betting-tab active bg-blue-600 text-white px-4 py-2 rounded-full text-xs font-medium">
                    BOLINHO
                </button>
                <button class="betting-tab bg-gray-600 text-gray-300 px-4 py-2 rounded-full text-xs font-medium">
                    QUEM FAZ 10
                </button>
            </div>
        `;
    },

    renderPlayer(player, odds, color) {
        return `
            <div class="text-center">
                <div class="w-20 h-20 rounded-full overflow-hidden border-2" style="border-color:${color}">
                    <img src="${Utils.getPlayerPhoto(player, color)}" alt="${player.name || 'Jogador'}" class="w-full h-full object-cover">
                </div>
                <p class="mt-2 text-sm font-semibold">${Utils.truncate(player.name || 'A definir', 18)}</p>
                <p class="text-xs text-accent font-bold">${odds ? `${parseFloat(odds).toFixed(2)}x` : '--'}</p>
            </div>
        `;
    },

    renderBetButton(canBet) {
        if (!canBet) {
            return `
                <button class="w-full bg-gray-700 text-gray-400 py-4 rounded-xl font-bold text-lg" disabled>
                    <i class="fas fa-lock mr-2"></i>Apostas encerradas
                </button>
            `;
        }

        return `
            <button class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white py-4 rounded-xl font-bold text-lg transition-all" onclick="SinucaPage.showBettingModal()">
                <i class="fas fa-bolt mr-2"></i>Apostar agora
            </button>
        `;
    },

    renderInfo(match) {
        return `
            <div class="grid grid-cols-1 gap-3">
                <div class="bg-card-bg rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-accent/20 text-accent flex items-center justify-center">
                        <i class="fas fa-circle-info"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-widest">Informações</p>
                        <p class="text-sm text-white">${match.description || 'Aguarde a transmissão para mais detalhes.'}</p>
                    </div>
                </div>
                <div class="bg-card-bg rounded-2xl p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-widest">Prazo para apostas</p>
                        <p class="text-sm text-white">${this.canBet(match) ? 'Aberta' : 'Encerrada'} · ${this.formatDate(match.betting_deadline, 'time')}</p>
                    </div>
                </div>
            </div>
        `;
    },

    renderBettingOptions() {
        if (!this.betOptions.length) {
            return `
                <div class="bg-card-bg rounded-2xl p-4">
                    <h4 class="text-lg font-bold text-white mb-4">Opções de Aposta</h4>
                    ${Components.renderEmptyState('fa-ban', 'Nenhum mercado disponível', 'Assim que novos mercados forem liberados, aparecerão aqui.')}
                </div>
            `;
        }

        return `
            <div class="bg-card-bg rounded-2xl p-4">
                <h4 class="text-lg font-bold text-white mb-4">Opções de Aposta</h4>
                <div class="grid grid-cols-2 gap-3">
                    ${this.betOptions.map(option => `
                        <button class="betting-option" data-type="${option.type}" onclick="SinucaPage.selectBettingOption('${option.type}')">
                            <p class="text-sm text-gray-300">${option.label}</p>
                            <p class="text-xl font-bold">${option.odds.toFixed(2)}x</p>
                        </button>
                    `).join('')}
                </div>
            </div>
        `;
    },

    renderStats(match) {
        return `
            <div class="bg-card-bg stats-card rounded-2xl p-4 space-y-3">
                <h4 class="text-lg font-bold text-white">Estatísticas da Partida</h4>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Total apostado</span>
                    <span class="text-white font-semibold">${Utils.formatCurrency(match.total_bets_amount || 0, true)}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Apostadores</span>
                    <span class="text-white font-semibold">${match.total_bets_count || 0}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Limite de apostas</span>
                    <span class="text-white font-semibold">${this.formatDate(match.betting_deadline, 'time')}</span>
                </div>
                ${match.time_remaining ? `
                    <div class="flex justify-between text-sm border-t border-gray-700 pt-3">
                        <span class="text-gray-400">Tempo restante</span>
                        <span class="text-amber-300 font-semibold">${match.time_remaining}</span>
                    </div>
                ` : ''}
            </div>
        `;
    },

    renderHighlights(match) {
        if (!match.metadata?.stream_url) {
            return '';
        }

        return `
            <div class="bg-card-bg rounded-2xl overflow-hidden">
                <div class="px-4 py-3 flex items-center justify-between border-b border-gray-800">
                    <div class="flex items-center gap-2 text-sm text-red-400">
                        <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                        <span>Ao vivo</span>
                    </div>
                    <button class="text-xs text-gray-400" onclick="window.open('${match.metadata.stream_url}', '_blank')">
                        Abrir transmissão <i class="fas fa-external-link-alt ml-1"></i>
                    </button>
                </div>
                <div class="aspect-video bg-black">
                    <iframe src="${match.metadata.stream_url}" frameborder="0" allowfullscreen class="w-full h-full"></iframe>
                </div>
            </div>
        `;
    },

    selectBettingOption(type) {
        const option = this.betOptions.find(opt => opt.type === type);
        if (!option) {
            Components.showToast('Opção indisponível no momento.', 'warning');
            return;
        }

        document.querySelectorAll('.betting-option').forEach(btn => btn.classList.remove('selected'));
        const button = document.querySelector(`.betting-option[data-type="${type}"]`);
        button?.classList.add('selected');

        this.selectedBet = option;
        Components.showToast(`${option.label} selecionada. Confirme a aposta no modal.`, 'info');
    },

    showBettingModal() {
        if (!this.currentMatch || !this.canBet(this.currentMatch)) {
            Components.showToast('Apostas encerradas para esta partida.', 'warning');
            return;
        }
        if (!this.betOptions.length) {
            Components.showToast('Nenhum mercado disponível.', 'warning');
            return;
        }

        const balance = Storage.getBalance();
        Components.showModal(`
            <div class="modal-header">
                <h3>${this.currentMatch.first_player?.name || 'Jogador 1'} vs ${this.currentMatch.second_player?.name || 'Jogador 2'}</h3>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-6">
                ${this.betOptions.map(option => `
                    <button class="betting-modal-option" data-type="${option.type}" onclick="SinucaPage.selectModalOption('${option.type}')">
                        <p class="text-sm text-gray-300">${option.label}</p>
                        <p class="text-lg font-bold text-accent">${option.odds.toFixed(2)}x</p>
                    </button>
                `).join('')}
            </div>
            <div id="selectedOption" class="bg-secondary rounded-xl p-4 mb-4 hidden">
                <div class="flex justify-between">
                    <span class="text-gray-400">Opção selecionada</span>
                    <span id="selectedOptionText" class="font-bold text-accent"></span>
                </div>
            </div>
            <div class="input-group">
                <label class="input-label">Valor da aposta</label>
                <input type="number" id="betAmount" class="input-field" placeholder="50,00" min="1" step="0.01" oninput="SinucaPage.updatePotentialWin()">
                ${Components.renderQuickValues('betAmount')}
            </div>
            <div id="potentialWinDisplay" class="bg-success/20 border border-success/30 rounded-xl p-4 mb-4 hidden">
                <div class="flex justify-between">
                    <span class="text-gray-300">Ganho potencial:</span>
                    <span id="potentialWinAmount" class="text-success font-bold"></span>
                </div>
            </div>
            <div class="bg-secondary rounded-xl p-3 mb-4 flex justify-between text-sm">
                <span class="text-gray-400">Saldo disponível</span>
                <span class="text-white font-semibold">${Utils.formatCurrency(balance, true)}</span>
            </div>
            <div class="flex gap-3">
                <button class="btn btn-secondary flex-1" onclick="closeModal()">Cancelar</button>
                <button id="confirmBetBtn" class="btn btn-warning flex-1" onclick="SinucaPage.confirmBet()" disabled>Confirmar</button>
            </div>
        `);
    },

    selectModalOption(type) {
        document.querySelectorAll('.betting-modal-option').forEach(btn => btn.classList.remove('selected'));
        const button = document.querySelector(`.betting-modal-option[data-type="${type}"]`);
        button?.classList.add('selected');

        const option = this.betOptions.find(opt => opt.type === type);
        if (!option) return;

        this.selectedBet = option;
        document.getElementById('selectedOptionText').textContent = `${option.label} (${option.odds.toFixed(2)}x)`;
        document.getElementById('selectedOption').classList.remove('hidden');
        this.updatePotentialWin();
    },

    updatePotentialWin() {
        const amount = parseFloat(document.getElementById('betAmount')?.value) || 0;
        const display = document.getElementById('potentialWinDisplay');
        const label = document.getElementById('potentialWinAmount');

        if (amount > 0 && this.selectedBet) {
            const potential = amount * this.selectedBet.odds;
            display.classList.remove('hidden');
            label.textContent = Utils.formatCurrency(potential, true);
        } else {
            display?.classList.add('hidden');
        }

        this.validateBetForm();
    },

    validateBetForm() {
        const amount = parseFloat(document.getElementById('betAmount')?.value) || 0;
        const balance = Storage.getBalance();
        const confirmBtn = document.getElementById('confirmBetBtn');

        const validation = Utils.validateBetAmount(amount, balance);
        const valid = validation.valid && !!this.selectedBet;
        confirmBtn.disabled = !valid;
        confirmBtn.classList.toggle('opacity-50', !valid);

        if (!validation.valid && amount > 0) {
            Components.showToast(validation.error, 'warning');
        }
    },

    async confirmBet() {
        const amount = parseFloat(document.getElementById('betAmount').value);
        const validation = Utils.validateBetAmount(amount, Storage.getBalance());
        if (!validation.valid) {
            Components.showToast(validation.error, 'error');
            return;
        }
        if (!this.selectedBet) {
            Components.showToast('Selecione uma opção de aposta.', 'warning');
            return;
        }

        try {
            const betData = {
                matchId: this.currentMatch.id,
                option: this.selectedBet.type,
                amount,
                odds: this.selectedBet.odds,
                potentialWin: amount * this.selectedBet.odds,
                fighterName: `${this.selectedBet.label} - ${this.currentMatch.first_player?.name || 'Jogador 1'} vs ${this.currentMatch.second_player?.name || 'Jogador 2'}`
            };

            const result = await API.placeBet(betData);
            Components.closeModal();

            if (result.success) {
                Components.showToast('Aposta realizada com sucesso!', 'success');
                App.updateBalance();
                this.showBetConfirmation(betData);
            } else {
                Components.showToast(result.error || 'Erro ao realizar aposta', 'error');
            }
        } catch (error) {
            Components.showToast('Erro ao processar aposta.', 'error');
        }
    },

    showBetConfirmation(betData) {
        Components.showModal(`
            <div class="text-center">
                <div class="w-20 h-20 bg-success/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-4xl text-success"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Aposta confirmada!</h3>
                <p class="text-gray-400 mb-6">Sua aposta foi registrada com sucesso.</p>
            </div>
            ${Components.renderBetSummary(betData)}
            <div class="mt-6">
                <button class="btn btn-primary w-full" onclick="closeModal()">
                    <i class="fas fa-check"></i> OK
                </button>
            </div>
        `);
    },

    buildBetOptions(match) {
        const options = [];
        if (match.first_player_odds) {
            options.push({ type: 'first_player', label: match.first_player?.name || 'Jogador 1', odds: parseFloat(match.first_player_odds) });
        }
        if (match.second_player_odds) {
            options.push({ type: 'second_player', label: match.second_player?.name || 'Jogador 2', odds: parseFloat(match.second_player_odds) });
        }
        if (match.draw_odds) {
            options.push({ type: 'draw', label: 'Empate', odds: parseFloat(match.draw_odds) });
        }
        if (match.par_odds) {
            options.push({ type: 'par', label: 'Par', odds: parseFloat(match.par_odds) });
        }
        if (match.impar_odds) {
            options.push({ type: 'impar', label: 'Ímpar', odds: parseFloat(match.impar_odds) });
        }
        return options;
    },

    canBet(match) {
        if (typeof match.can_bet !== 'undefined') {
            return !!match.can_bet;
        }
        if (!match.betting_deadline) return false;
        return match.status !== 'finished' && new Date(match.betting_deadline) > new Date();
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

    getStatusText(match) {
        const statuses = {
            live: 'AO VIVO',
            scheduled: 'AGUARDANDO',
            finished: 'FINALIZADA'
        };
        return statuses[match.status] || 'INDEFINIDA';
    },

    formatDate(date, mode = 'short') {
        if (!date) return '--';
        if (mode === 'datetime') {
            const d = new Date(date);
            return `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}:${d.getSeconds().toString().padStart(2, '0')}`;
        }
        return Utils.formatDate(date, mode === 'time' ? 'time' : 'short');
    }
};
