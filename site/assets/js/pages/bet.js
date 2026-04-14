/**
 * FORAPIX - Bet Page
 * Página de realização de apostas
 */

const BetPage = {
    currentMatch: null,
    selectedFighter: null,
    selectedOdds: 0,
    betAmount: 0,

    /**
     * Render bet page
     * @param {object} params - Page parameters
     */
    render(params = {}) {
        const matchId = params.matchId;
        
        return `
            <div class="page-enter p-4">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-4">
                    <button onclick="App.goBack()" class="w-10 h-10 bg-card-bg rounded-full flex-center">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h2 class="text-xl font-bold">Fazer Aposta</h2>
                </div>

                <!-- Match Info -->
                <div id="betMatchInfo">
                    <div class="flex-center py-8">
                        <div class="w-8 h-8 border-4 border-accent border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Initialize bet page
     * @param {object} params - Page parameters
     */
    async init(params = {}) {
        const matchId = params.matchId;
        
        if (!matchId) {
            Components.showToast('Partida não encontrada', 'error');
            App.goBack();
            return;
        }

        await this.loadMatch(matchId);
    },

    /**
     * Load match details
     * @param {number} matchId - Match ID
     */
    async loadMatch(matchId) {
        const container = document.getElementById('betMatchInfo');

        try {
            // Try to get from cache first
            let match = MatchesPage.getMatch(matchId);

            // If not in cache, fetch from API
            if (!match) {
                const response = await API.getMatch(matchId);
                if (response.success && response.data) {
                    const parsed = Utils.parseApiResponse(response.data);
                    match = parsed[0];
                }
            }

            if (match) {
                this.currentMatch = match;
                this.renderBetForm();
            } else {
                container.innerHTML = Components.renderEmptyState(
                    'fa-exclamation-circle',
                    'Partida não encontrada',
                    'Não foi possível carregar os dados da partida.'
                );
            }
        } catch (error) {
            console.error('Error loading match:', error);
            container.innerHTML = Components.renderEmptyState(
                'fa-exclamation-triangle',
                'Erro ao carregar',
                'Tente novamente mais tarde.'
            );
        }
    },

    /**
     * Render bet form
     */
    renderBetForm() {
        const container = document.getElementById('betMatchInfo');
        const match = this.currentMatch;
        const deadline = new Date(match.betting_deadline);
        const timeStr = Utils.formatDate(deadline, 'relative');
        const balance = Storage.getBalance();

        container.innerHTML = `
            <!-- Match Header -->
            <div class="bg-card-bg rounded-2xl p-4 mb-4">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm text-gray-400">
                        <i class="fas fa-hand-fist mr-2"></i>MMA/UFC
                    </span>
                    <span class="text-sm text-warning">
                        <i class="fas fa-clock mr-1"></i>${timeStr}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <div class="text-center flex-1">
                        <div class="w-16 h-16 bg-secondary rounded-full mx-auto mb-2 overflow-hidden flex-center">
                            ${match.first_athlete?.photo_url 
                                ? `<img src="${match.first_athlete.photo_url}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-user text-2xl\\'></i>'">`
                                : `<i class="fas fa-user text-2xl"></i>`
                            }
                        </div>
                        <p class="font-semibold text-sm">${Utils.truncate(match.first_athlete?.name || 'Lutador 1', 12)}</p>
                    </div>
                    <div class="text-2xl font-bold text-gray-500 px-4">VS</div>
                    <div class="text-center flex-1">
                        <div class="w-16 h-16 bg-secondary rounded-full mx-auto mb-2 overflow-hidden flex-center">
                            ${match.second_athlete?.photo_url 
                                ? `<img src="${match.second_athlete.photo_url}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-user text-2xl\\'></i>'">`
                                : `<i class="fas fa-user text-2xl"></i>`
                            }
                        </div>
                        <p class="font-semibold text-sm">${Utils.truncate(match.second_athlete?.name || 'Lutador 2', 12)}</p>
                    </div>
                </div>
            </div>

            <!-- Fighter Selection -->
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-400 mb-3 uppercase">Escolha o vencedor</h3>
                <div id="fighterOptions">
                    ${Components.renderFighterOption(
                        match.first_athlete || { name: 'Lutador 1' },
                        match.first_athlete_odds || 1,
                        'first',
                        false
                    )}
                    ${Components.renderFighterOption(
                        match.second_athlete || { name: 'Lutador 2' },
                        match.second_athlete_odds || 1,
                        'second',
                        false
                    )}
                </div>
            </div>

            <!-- Bet Amount -->
            <div class="mb-4" id="betAmountSection" style="display: none;">
                <h3 class="text-sm font-semibold text-gray-400 mb-3 uppercase">Valor da aposta</h3>
                <div class="bg-card-bg rounded-2xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-400">Seu saldo:</span>
                        <span class="font-semibold ${balance > 0 ? 'text-success' : 'text-danger'}">
                            ${Utils.formatCurrency(balance, true)}
                        </span>
                    </div>
                    <div class="input-group mb-0">
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">R$</span>
                            <input 
                                type="number" 
                                id="betAmountInput" 
                                class="input-field pl-12" 
                                placeholder="0,00"
                                min="${CONFIG.BET.MIN_VALUE}"
                                max="${Math.min(CONFIG.BET.MAX_VALUE, balance)}"
                                step="0.01"
                            >
                        </div>
                    </div>
                    ${Components.renderQuickValues('betAmountInput')}
                </div>
            </div>

            <!-- Bet Summary -->
            <div id="betSummary" class="mb-4" style="display: none;"></div>

            <!-- Action Buttons -->
            <div id="betActions">
                <button id="btnConfirmBet" class="btn btn-primary" disabled onclick="BetPage.confirmBet()">
                    <i class="fas fa-ticket"></i> CONFIRMAR APOSTA
                </button>
            </div>

            <!-- Insufficient Balance Warning -->
            <div id="insufficientBalance" class="hidden mt-4">
                <div class="bg-danger/10 border border-danger/30 rounded-xl p-4 text-center">
                    <i class="fas fa-exclamation-triangle text-danger text-2xl mb-2"></i>
                    <p class="text-danger font-semibold mb-3">Saldo Insuficiente</p>
                    <p class="text-sm text-gray-400 mb-4">Você não possui saldo suficiente para esta aposta.</p>
                    <button class="btn btn-success" onclick="App.navigateTo('deposit')">
                        <i class="fas fa-plus"></i> Adicionar Saldo
                    </button>
                </div>
            </div>
        `;

        this.initBetForm();
    },

    /**
     * Initialize bet form interactions
     */
    initBetForm() {
        // Fighter selection
        document.querySelectorAll('.bet-fighter-option').forEach(option => {
            option.addEventListener('click', (e) => {
                this.selectFighter(e.currentTarget);
            });
        });

        // Bet amount input
        const amountInput = document.getElementById('betAmountInput');
        if (amountInput) {
            amountInput.addEventListener('input', (e) => {
                this.updateBetAmount(parseFloat(e.target.value) || 0);
            });
        }
    },

    /**
     * Select fighter
     * @param {HTMLElement} element - Fighter option element
     */
    selectFighter(element) {
        const position = element.dataset.position;
        const odds = parseFloat(element.dataset.odds);
        const match = this.currentMatch;

        // Update selection
        document.querySelectorAll('.bet-fighter-option').forEach(opt => {
            opt.classList.remove('selected');
            opt.querySelector('.check i').classList.add('hidden');
        });

        element.classList.add('selected');
        element.querySelector('.check i').classList.remove('hidden');

        // Store selection
        this.selectedFighter = position === 'first' ? match.first_athlete : match.second_athlete;
        this.selectedOdds = odds;

        // Show bet amount section
        document.getElementById('betAmountSection').style.display = 'block';

        // Update summary if amount is set
        if (this.betAmount > 0) {
            this.updateBetSummary();
        }

        Utils.vibrate(30);
    },

    /**
     * Update bet amount
     * @param {number} amount - Bet amount
     */
    updateBetAmount(amount) {
        this.betAmount = amount;
        const balance = Storage.getBalance();
        const validation = Utils.validateBetAmount(amount, balance);

        const btnConfirm = document.getElementById('btnConfirmBet');
        const insufficientEl = document.getElementById('insufficientBalance');

        if (!this.selectedFighter) {
            btnConfirm.disabled = true;
            return;
        }

        if (amount > balance) {
            insufficientEl.classList.remove('hidden');
            btnConfirm.disabled = true;
        } else {
            insufficientEl.classList.add('hidden');
            btnConfirm.disabled = !validation.valid;
        }

        if (amount > 0 && this.selectedFighter) {
            this.updateBetSummary();
        }
    },

    /**
     * Update bet summary
     */
    updateBetSummary() {
        const summaryEl = document.getElementById('betSummary');
        
        if (!this.selectedFighter || this.betAmount <= 0) {
            summaryEl.style.display = 'none';
            return;
        }

        summaryEl.style.display = 'block';
        summaryEl.innerHTML = Components.renderBetSummary({
            fighterName: this.selectedFighter.name,
            odds: this.selectedOdds,
            amount: this.betAmount
        });
    },

    /**
     * Confirm bet
     */
    async confirmBet() {
        if (!this.selectedFighter || this.betAmount <= 0) {
            Components.showToast('Selecione um lutador e valor', 'warning');
            return;
        }

        const balance = Storage.getBalance();
        if (this.betAmount > balance) {
            Components.showToast('Saldo insuficiente', 'error');
            return;
        }

        // Show confirmation modal
        const potentialWin = Utils.calculatePotentialWin(this.betAmount, this.selectedOdds);

        Components.showModal(`
            <div class="modal-header">
                <h3>Confirmar Aposta</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-accent/20 rounded-full flex-center mx-auto mb-4">
                    <i class="fas fa-ticket text-4xl text-accent"></i>
                </div>
                <p class="text-gray-400">Você está apostando em</p>
                <p class="text-xl font-bold mt-2">${this.selectedFighter.name}</p>
            </div>

            ${Components.renderBetSummary({
                fighterName: this.selectedFighter.name,
                odds: this.selectedOdds,
                amount: this.betAmount
            })}

            <div class="flex gap-3">
                <button class="btn btn-secondary flex-1" onclick="closeModal()">
                    Cancelar
                </button>
                <button class="btn btn-primary flex-1" onclick="BetPage.placeBet()">
                    Confirmar
                </button>
            </div>
        `);
    },

    /**
     * Place bet
     */
    async placeBet() {
        Components.closeModal();
        Components.showLoading(true);

        const betData = {
            matchId: this.currentMatch.id,
            fighterId: this.selectedFighter.id,
            fighterName: this.selectedFighter.name,
            odds: this.selectedOdds,
            amount: this.betAmount,
            potentialWin: Utils.calculatePotentialWin(this.betAmount, this.selectedOdds)
        };

        try {
            const result = await API.placeBet(betData);

            Components.showLoading(false);

            if (result.success) {
                this.showSuccessModal(betData);
                Utils.vibrate(100);
            } else {
                Components.showToast(result.error || 'Erro ao realizar aposta', 'error');
            }
        } catch (error) {
            Components.showLoading(false);
            Components.showToast('Erro ao processar aposta', 'error');
        }
    },

    /**
     * Show success modal
     * @param {object} betData - Bet data
     */
    showSuccessModal(betData) {
        Components.showModal(`
            <div class="text-center py-4">
                <div class="w-24 h-24 bg-success/20 rounded-full flex-center mx-auto mb-4">
                    <i class="fas fa-check text-5xl text-success"></i>
                </div>
                <h3 class="text-2xl font-bold mb-2">Aposta Realizada!</h3>
                <p class="text-gray-400 mb-6">Sua aposta foi registrada com sucesso.</p>
                
                <div class="bg-secondary rounded-xl p-4 mb-6 text-left">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-400">Lutador:</span>
                        <span class="font-semibold">${betData.fighterName}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-400">Valor:</span>
                        <span class="font-semibold">${Utils.formatCurrency(betData.amount, true)}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Ganho potencial:</span>
                        <span class="font-semibold text-success">${Utils.formatCurrency(betData.potentialWin, true)}</span>
                    </div>
                </div>

                <button class="btn btn-primary" onclick="closeModal(); App.navigateTo('home');">
                    <i class="fas fa-home"></i> Voltar ao Início
                </button>
            </div>
        `);
    },

    /**
     * Reset bet form
     */
    reset() {
        this.currentMatch = null;
        this.selectedFighter = null;
        this.selectedOdds = 0;
        this.betAmount = 0;
    }
};
