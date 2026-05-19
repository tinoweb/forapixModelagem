/**
 * ApostaCasada - Página de Carteira
 * Gerenciamento de saldo, transações, depósito e saque
 */

const WalletPage = {
    currentTab: 'balance',

    render(params = {}) {
        this.currentTab = params.tab || 'balance';
        const balance = Storage.getBalance();

        return `
            <div class="page-enter p-4">
                <!-- Card de saldo -->
                <div class="wallet-balance-card mb-6">
                    <p class="wallet-balance-label">Saldo disponível</p>
                    <p class="wallet-balance-value">${Utils.formatCurrency(balance, true)}</p>
                    <div class="wallet-balance-actions">
                        <button class="wallet-action-btn deposit" onclick="App.navigateTo('deposit')">
                            <i class="fas fa-plus"></i> Depositar
                        </button>
                        <button class="wallet-action-btn withdraw" onclick="WalletPage.showWithdrawModal()">
                            <i class="fas fa-arrow-up"></i> Sacar
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="profile-tabs mb-6">
                    <button class="profile-tab ${this.currentTab === 'balance' ? 'active' : ''}" data-tab="balance" onclick="WalletPage.switchTab('balance')">
                        <i class="fas fa-wallet"></i>
                        <span>Extrato</span>
                    </button>
                    <button class="profile-tab ${this.currentTab === 'bets' ? 'active' : ''}" data-tab="bets" onclick="WalletPage.switchTab('bets')">
                        <i class="fas fa-dice"></i>
                        <span>Apostas</span>
                    </button>
                </div>

                <!-- Conteúdo -->
                <div id="walletContent">
                    ${this.renderCurrentTab()}
                </div>
            </div>
        `;
    },

    init() {
        this.loadTransactions();
        this._refreshBalanceCard();
    },

    async _refreshBalanceCard() {
        try {
            const res = await API.getBalance();
            if (res && res.success && res.data) {
                const fresh = parseFloat(res.data.balance) || 0;
                Storage.setBalance(fresh);
                const el = document.querySelector('.wallet-balance-value');
                if (el) el.textContent = Utils.formatCurrency(fresh, true);
            }
        } catch (_) {}
    },

    switchTab(tab) {
        this.currentTab = tab;

        document.querySelectorAll('.profile-tab').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });

        const content = document.getElementById('walletContent');
        if (content) {
            content.innerHTML = this.renderCurrentTab();
        }

        if (tab === 'balance') {
            this.loadTransactions();
        } else {
            this.loadBets();
        }
    },

    renderCurrentTab() {
        switch (this.currentTab) {
            case 'balance':
                return this.renderBalanceTab();
            case 'bets':
                return this.renderBetsTab();
            default:
                return this.renderBalanceTab();
        }
    },

    renderBalanceTab() {
        const transactions = Storage.getTransactions();

        if (!transactions.length) {
            return Components.renderEmptyState(
                'fa-receipt',
                'Nenhuma transação',
                'Suas transações aparecerão aqui.'
            );
        }

        return `
            <div class="transactions-list">
                ${transactions.slice(0, 20).map(t => this.renderTransactionItem(t)).join('')}
            </div>
        `;
    },

    renderBetsTab() {
        const bets = Storage.getBets();

        if (!bets.length) {
            return Components.renderEmptyState(
                'fa-dice',
                'Nenhuma aposta',
                'Suas apostas aparecerão aqui.'
            );
        }

        return `
            <div class="transactions-list">
                ${bets.slice(0, 20).map(bet => this.renderBetItem(bet)).join('')}
            </div>
        `;
    },

    renderTransactionItem(transaction) {
        const isCredit = transaction.amount > 0;
        const icon = this.getTransactionIcon(transaction.type);
        const color = isCredit ? 'text-success' : 'text-danger';
        const statusLabel = this.getStatusLabel(transaction.status);

        return `
            <div class="transaction-item">
                <div class="transaction-item-icon ${isCredit ? 'credit' : 'debit'}">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="transaction-item-content">
                    <p class="transaction-item-desc">${transaction.description}</p>
                    <p class="transaction-item-date">${Utils.formatDate(transaction.date)}</p>
                </div>
                <div class="transaction-item-amount">
                    <p class="${color} font-bold">${isCredit ? '+' : ''}${Utils.formatCurrency(Math.abs(transaction.amount), true)}</p>
                    <p class="transaction-item-status">${statusLabel}</p>
                </div>
            </div>
        `;
    },

    renderBetItem(bet) {
        const statusColors = {
            pending: 'text-warning',
            won: 'text-success',
            lost: 'text-danger',
            cancelled: 'text-secondary'
        };
        const statusLabels = {
            pending: 'Pendente',
            won: 'Ganhou',
            lost: 'Perdeu',
            cancelled: 'Cancelada'
        };
        const color = statusColors[bet.status] || 'text-secondary';
        const label = statusLabels[bet.status] || bet.status;

        return `
            <div class="transaction-item">
                <div class="transaction-item-icon debit">
                    <i class="fas fa-dice"></i>
                </div>
                <div class="transaction-item-content">
                    <p class="transaction-item-desc">${bet.fighterName || bet.option || 'Aposta'}</p>
                    <p class="transaction-item-date">${Utils.formatDate(bet.placedAt || bet.date)}</p>
                </div>
                <div class="transaction-item-amount">
                    <p class="font-bold">-${Utils.formatCurrency(bet.amount, true)}</p>
                    <p class="${color} font-semibold text-xs">${label}</p>
                </div>
            </div>
        `;
    },

    getTransactionIcon(type) {
        const icons = {
            deposit: 'fa-arrow-down',
            withdraw: 'fa-arrow-up',
            bet: 'fa-dice',
            win: 'fa-trophy',
            refund: 'fa-rotate-left'
        };
        return icons[type] || 'fa-exchange-alt';
    },

    getStatusLabel(status) {
        const labels = {
            completed: 'Concluída',
            pending: 'Pendente',
            failed: 'Falhou',
            cancelled: 'Cancelada'
        };
        return labels[status] || status;
    },

    async loadTransactions() {
        try {
            const response = await API.getTransactions();
            const transactions = Array.isArray(response.data?.data)
                ? response.data.data
                : (Array.isArray(response.data) ? response.data : Storage.getTransactions());

            const content = document.getElementById('walletContent');
            if (content && this.currentTab === 'balance') {
                if (!transactions.length) {
                    content.innerHTML = Components.renderEmptyState('fa-receipt', 'Nenhuma transação', 'Suas transações aparecerão aqui.');
                    return;
                }
                content.innerHTML = `
                    <div class="transactions-list">
                        ${transactions.slice(0, 20).map(t => this.renderTransactionItem(t)).join('')}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Erro ao carregar transações:', error);
        }
    },

    async loadBets() {
        try {
            const response = await API.getBets();
            const bets = Array.isArray(response.data?.data)
                ? response.data.data
                : (Array.isArray(response.data) ? response.data : Storage.getBets());

            const content = document.getElementById('walletContent');
            if (content && this.currentTab === 'bets') {
                if (!bets.length) {
                    content.innerHTML = Components.renderEmptyState('fa-dice', 'Nenhuma aposta', 'Suas apostas aparecerão aqui.');
                    return;
                }
                content.innerHTML = `
                    <div class="transactions-list">
                        ${bets.slice(0, 20).map(bet => this.renderBetItem(bet)).join('')}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Erro ao carregar apostas:', error);
        }
    },

    showWithdrawModal() {
        const balance = parseFloat(Storage.getBalance()) || 0;

        Components.showModal(`
            <div class="modal-header">
                <h3>Sacar saldo</h3>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="bg-secondary rounded-xl p-3 mb-4 flex justify-between text-sm">
                <span class="text-gray-400">Saldo disponível</span>
                <span class="text-white font-semibold">${Utils.formatCurrency(balance, true)}</span>
            </div>
            <div class="input-group mb-4">
                <label class="input-label">Valor do saque</label>
                <input type="number" id="withdrawAmount" class="input-field" placeholder="50,00" min="10" step="0.01">
            </div>
            <div class="input-group mb-6">
                <label class="input-label">Chave PIX</label>
                <input type="text" id="withdrawPixKey" class="input-field" placeholder="CPF, email, telefone ou chave aleatória">
            </div>
            <div class="flex gap-3">
                <button class="btn btn-secondary flex-1" onclick="closeModal()">Cancelar</button>
                <button class="btn btn-primary flex-1" onclick="WalletPage.processWithdraw()">Confirmar saque</button>
            </div>
        `);
    },

    async processWithdraw() {
        const amount = parseFloat(document.getElementById('withdrawAmount')?.value);
        const pixKey = document.getElementById('withdrawPixKey')?.value;

        if (!amount || amount < 10) {
            Components.showToast('Valor mínimo de saque: R$ 10,00', 'warning');
            return;
        }
        if (!pixKey) {
            Components.showToast('Informe sua chave PIX', 'warning');
            return;
        }

        try {
            const result = await API.withdraw(amount, pixKey);
            Components.closeModal();

            if (result.success) {
                Components.showToast('Saque solicitado com sucesso!', 'success');
                App.updateBalance();
                this.switchTab('balance');
            } else {
                Components.showToast(result.error || 'Erro ao processar saque', 'error');
            }
        } catch (error) {
            Components.showToast('Erro ao processar saque', 'error');
        }
    }
};
