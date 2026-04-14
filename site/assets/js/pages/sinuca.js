/**
 * FORAPIX - Sinuca/Pool Page
 * Página de apostas em jogos de sinuca
 */

const SinucaPage = {
    currentMatches: [],
    selectedMatch: null,

    /**
     * Render sinuca page
     */
    render() {
        return `
            <div class="page-enter">
                <!-- Header -->
                <header class="bg-primary px-4 py-3 flex items-center justify-between sticky top-0 z-50">
                    <div class="flex items-center gap-3">
                        <button onclick="App.goBack()" class="w-10 h-10 bg-card-bg rounded-full flex-center">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <h2 class="text-lg font-bold">SINUCA</h2>
                    </div>
                </header>

                <!-- Status Tabs -->
                <div class="px-4 py-3 bg-secondary">
                    <div class="flex gap-3">
                        <button class="status-tab active" data-status="live">
                            <i class="fas fa-play-circle text-warning"></i>
                            Em andamento
                        </button>
                        <button class="status-tab" data-status="finished">
                            <i class="fas fa-flag-checkered"></i>
                            Encerradas
                        </button>
                    </div>
                </div>

                <!-- Matches List -->
                <div id="sinucaMatches" class="p-4">
                    ${this.renderMatches()}
                </div>
            </div>
        `;
    },

    /**
     * Render matches list
     */
    renderMatches() {
        // Mock data for demonstration
        const matches = [
            {
                id: 1,
                sport: 'SINUCA',
                status: 'live',
                game: 'PAR OU ÍMPAR',
                date: 'JOGO DIA 13/04',
                player1: {
                    name: 'Maycon de Teixeira',
                    avatar: 'https://via.placeholder.com/60x60/4f46e5/ffffff?text=MT',
                    score: 0
                },
                player2: {
                    name: 'Fábio Cabeludo',
                    avatar: 'https://via.placeholder.com/60x60/059669/ffffff?text=FC',
                    score: 0
                },
                betsOpen: true
            }
        ];

        return matches.map(match => this.renderMatchCard(match)).join('');
    },

    /**
     * Render single match card
     */
    renderMatchCard(match) {
        return `
            <div class="sinuca-match-card bg-card-bg rounded-2xl p-4 mb-4">
                <!-- Match Header -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-warning rounded-full animate-pulse"></div>
                        <span class="text-xs font-semibold text-warning uppercase">${match.sport}</span>
                    </div>
                    ${match.betsOpen ? `
                        <div class="flex items-center gap-2 text-xs text-success">
                            <i class="fas fa-circle text-success"></i>
                            Apostas abertas
                        </div>
                    ` : ''}
                </div>

                <!-- Game Info -->
                <div class="text-center mb-4">
                    <h3 class="text-sm font-semibold text-gray-300 mb-1">${match.game}</h3>
                    <p class="text-xs text-gray-500">${match.date}</p>
                </div>

                <!-- Players -->
                <div class="flex items-center justify-between mb-6">
                    <div class="text-center flex-1">
                        <div class="w-16 h-16 rounded-full overflow-hidden mx-auto mb-2 border-2 border-gray-600">
                            <img src="${match.player1.avatar}" alt="${match.player1.name}" class="w-full h-full object-cover">
                        </div>
                        <p class="text-sm font-semibold">${Utils.truncate(match.player1.name, 12)}</p>
                    </div>

                    <div class="text-center px-6">
                        <div class="flex items-center gap-4">
                            <span class="text-3xl font-bold">${match.player1.score}</span>
                            <span class="text-lg text-gray-500">VS</span>
                            <span class="text-3xl font-bold">${match.player2.score}</span>
                        </div>
                    </div>

                    <div class="text-center flex-1">
                        <div class="w-16 h-16 rounded-full overflow-hidden mx-auto mb-2 border-2 border-gray-600">
                            <img src="${match.player2.avatar}" alt="${match.player2.name}" class="w-full h-full object-cover">
                        </div>
                        <p class="text-sm font-semibold">${Utils.truncate(match.player2.name, 12)}</p>
                    </div>
                </div>

                <!-- Bet Button -->
                ${match.betsOpen ? `
                    <button class="w-full bg-warning hover:bg-yellow-500 text-black py-3 px-4 rounded-xl font-bold flex items-center justify-center gap-2 transition-all" onclick="SinucaPage.openBetModal(${match.id})">
                        <i class="fas fa-bolt"></i>
                        Apostar agora
                    </button>
                ` : `
                    <div class="w-full bg-gray-700 text-gray-400 py-3 px-4 rounded-xl text-center">
                        Apostas encerradas
                    </div>
                `}
            </div>
        `;
    },

    /**
     * Initialize sinuca page
     */
    init() {
        // Add event listeners for status tabs
        document.querySelectorAll('.status-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const status = e.currentTarget.dataset.status;
                this.changeStatus(status);
            });
        });
    },

    /**
     * Change status filter
     */
    changeStatus(status) {
        // Update active tab
        document.querySelectorAll('.status-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.status === status);
        });

        // Filter matches (mock implementation)
        const matchesContainer = document.getElementById('sinucaMatches');
        if (status === 'finished') {
            matchesContainer.innerHTML = Components.renderEmptyState(
                'fa-flag-checkered',
                'Nenhum jogo encerrado',
                'Não há jogos encerrados no momento.'
            );
        } else {
            matchesContainer.innerHTML = this.renderMatches();
        }
    },

    /**
     * Open bet modal
     */
    openBetModal(matchId) {
        const balance = Storage.getBalance();

        Components.showModal(`
            <div class="modal-header">
                <h3>Apostar em Sinuca</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-warning/20 rounded-full flex-center mx-auto mb-4">
                    <i class="fas fa-8ball text-4xl text-warning"></i>
                </div>
                <h4 class="text-lg font-semibold mb-2">PAR OU ÍMPAR</h4>
                <p class="text-sm text-gray-400">Maycon vs Fábio Cabeludo</p>
            </div>

            <!-- Bet Options -->
            <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-400 mb-3 uppercase">Escolha sua aposta</h4>
                <div class="grid grid-cols-2 gap-3">
                    <button class="bet-option" data-option="par" onclick="SinucaPage.selectBetOption(this, 'par')">
                        <div class="text-center p-4 bg-secondary rounded-xl border-2 border-transparent hover:border-accent transition-all">
                            <div class="text-2xl font-bold mb-1">PAR</div>
                            <div class="text-sm text-accent">Odd: 1.85</div>
                        </div>
                    </button>
                    <button class="bet-option" data-option="impar" onclick="SinucaPage.selectBetOption(this, 'impar')">
                        <div class="text-center p-4 bg-secondary rounded-xl border-2 border-transparent hover:border-accent transition-all">
                            <div class="text-2xl font-bold mb-1">ÍMPAR</div>
                            <div class="text-sm text-accent">Odd: 1.95</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Bet Amount -->
            <div class="mb-4" id="betAmountSection" style="display: none;">
                <h4 class="text-sm font-semibold text-gray-400 mb-3 uppercase">Valor da aposta</h4>
                <div class="bg-card-bg rounded-xl p-4">
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
                                id="sinucaBetAmount" 
                                class="input-field pl-12" 
                                placeholder="0,00"
                                min="1"
                                step="0.01"
                            >
                        </div>
                    </div>
                    ${Components.renderQuickValues('sinucaBetAmount')}
                </div>
            </div>

            <!-- Bet Summary -->
            <div id="sinucaBetSummary" style="display: none;"></div>

            <!-- Action Button -->
            <button id="btnConfirmSinucaBet" class="btn btn-warning" disabled onclick="SinucaPage.confirmBet()">
                <i class="fas fa-bolt"></i> CONFIRMAR APOSTA
            </button>
        `);
    },

    /**
     * Select bet option
     */
    selectBetOption(element, option) {
        // Update selection
        document.querySelectorAll('.bet-option div').forEach(opt => {
            opt.classList.remove('border-accent', 'bg-accent/10');
        });
        
        element.querySelector('div').classList.add('border-accent', 'bg-accent/10');

        // Store selection
        this.selectedBetOption = option;
        this.selectedOdds = option === 'par' ? 1.85 : 1.95;

        // Show amount section
        document.getElementById('betAmountSection').style.display = 'block';

        // Setup amount input listener
        const amountInput = document.getElementById('sinucaBetAmount');
        amountInput.addEventListener('input', (e) => {
            this.updateSinucaBetAmount(parseFloat(e.target.value) || 0);
        });

        Utils.vibrate(30);
    },

    /**
     * Update bet amount
     */
    updateSinucaBetAmount(amount) {
        this.betAmount = amount;
        const balance = Storage.getBalance();
        const validation = Utils.validateBetAmount(amount, balance);

        const btnConfirm = document.getElementById('btnConfirmSinucaBet');
        btnConfirm.disabled = !validation.valid || !this.selectedBetOption;

        if (amount > 0 && this.selectedBetOption) {
            this.updateSinucaBetSummary();
        }
    },

    /**
     * Update bet summary
     */
    updateSinucaBetSummary() {
        const summaryEl = document.getElementById('sinucaBetSummary');
        
        if (!this.selectedBetOption || this.betAmount <= 0) {
            summaryEl.style.display = 'none';
            return;
        }

        const potentialWin = Utils.calculatePotentialWin(this.betAmount, this.selectedOdds);

        summaryEl.style.display = 'block';
        summaryEl.innerHTML = `
            <div class="bg-secondary rounded-xl p-4 mb-4">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-400">Aposta:</span>
                    <span class="font-semibold">${this.selectedBetOption.toUpperCase()}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-400">Odd:</span>
                    <span class="font-semibold text-accent">${this.selectedOdds.toFixed(2)}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-400">Valor:</span>
                    <span class="font-semibold">${Utils.formatCurrency(this.betAmount, true)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Ganho potencial:</span>
                    <span class="font-semibold text-success">${Utils.formatCurrency(potentialWin, true)}</span>
                </div>
            </div>
        `;
    },

    /**
     * Confirm bet
     */
    async confirmBet() {
        if (!this.selectedBetOption || this.betAmount <= 0) {
            Components.showToast('Complete todos os campos', 'warning');
            return;
        }

        Components.closeModal();
        Components.showLoading(true);

        const betData = {
            matchId: 1,
            game: 'sinuca',
            option: this.selectedBetOption,
            odds: this.selectedOdds,
            amount: this.betAmount,
            potentialWin: Utils.calculatePotentialWin(this.betAmount, this.selectedOdds)
        };

        try {
            const result = await API.placeBet({
                ...betData,
                fighterName: `${betData.option.toUpperCase()} - Sinuca`,
                fighterId: betData.option
            });

            Components.showLoading(false);

            if (result.success) {
                Components.showModal(`
                    <div class="text-center py-4">
                        <div class="w-24 h-24 bg-success/20 rounded-full flex-center mx-auto mb-4">
                            <i class="fas fa-check text-5xl text-success"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">Aposta Realizada!</h3>
                        <p class="text-gray-400 mb-6">Sua aposta em sinuca foi registrada.</p>
                        
                        <div class="bg-secondary rounded-xl p-4 mb-6 text-left">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-400">Jogo:</span>
                                <span class="font-semibold">Sinuca - ${betData.option.toUpperCase()}</span>
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

                        <button class="btn btn-primary" onclick="closeModal();">
                            <i class="fas fa-check"></i> OK
                        </button>
                    </div>
                `);
                Utils.vibrate(100);
            } else {
                Components.showToast(result.error || 'Erro ao realizar aposta', 'error');
            }
        } catch (error) {
            Components.showLoading(false);
            Components.showToast('Erro ao processar aposta', 'error');
        }
    }
};
