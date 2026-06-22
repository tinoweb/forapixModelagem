/**
 * ApostaCasada - Página de Carteira
 * Gerenciamento de saldo, transações, depósito e saque
 */

const WalletPage = {
    currentTab: 'balance',

    render(params = {}) {
        this.currentTab = params.tab || 'balance';
        const balance = Storage.getBalance();
        const withdrawable = parseFloat(Storage.getItem('forapix_withdrawable')) || 0;
        const locked = Math.max(0, balance - withdrawable);

        return `
            <div class="page-enter p-4">
                <!-- Card de saldo -->
                <div class="wallet-balance-card mb-6">
                    <p class="wallet-balance-label">Saldo Total</p>
                    <p class="wallet-balance-value">${Utils.formatCurrency(balance, true)}</p>
                    
                    <!-- Sub-saldos: disponível para saque e trancado -->
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-white/10 text-left">
                        <div class="flex-1 border-r border-white/10 pr-4">
                            <p style="font-size: 11px; color: rgba(255, 255, 255, 0.75); text-transform: uppercase; letter-spacing: 0.5px;">Disponível p/ Saque</p>
                            <p class="wallet-withdrawable-value" style="font-size: 18px; font-weight: 800; color: #ffffff; margin-top: 2px;">${Utils.formatCurrency(withdrawable, true)}</p>
                        </div>
                        <div class="flex-1 pl-4">
                            <p style="font-size: 11px; color: rgba(255, 255, 255, 0.75); text-transform: uppercase; letter-spacing: 0.5px;">Saldo Trancado</p>
                            <p class="wallet-locked-value" style="font-size: 18px; font-weight: 800; color: #ffffff; margin-top: 2px;">${Utils.formatCurrency(locked, true)}</p>
                        </div>
                    </div>

                    <div class="wallet-balance-actions mt-6">
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
                const fresh        = parseFloat(res.data.balance) || 0;
                const withdrawable = parseFloat(res.data.withdrawable_balance) || 0;
                const locked       = Math.max(0, fresh - withdrawable);
                Storage.setBalance(fresh);
                Storage.setItem('forapix_withdrawable', withdrawable);
                
                const elBalance = document.querySelector('.wallet-balance-value');
                if (elBalance) elBalance.textContent = Utils.formatCurrency(fresh, true);
                
                const elWithdrawable = document.querySelector('.wallet-withdrawable-value');
                if (elWithdrawable) elWithdrawable.textContent = Utils.formatCurrency(withdrawable, true);
                
                const elLocked = document.querySelector('.wallet-locked-value');
                if (elLocked) elLocked.textContent = Utils.formatCurrency(locked, true);
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
                    <p class="transaction-item-date">${Utils.formatDate(transaction.created_at || transaction.date)}</p>
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
                    <p class="transaction-item-date">${Utils.formatDate(bet.placedAt || bet.created_at || bet.date)}</p>
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

    async showWithdrawModal() {
        let balance      = parseFloat(Storage.getBalance()) || 0;
        let withdrawable = parseFloat(Storage.getItem('forapix_withdrawable')) || 0;
        try {
            const res = await API.getBalance();
            if (res && res.success && res.data) {
                balance      = parseFloat(res.data.balance) || 0;
                withdrawable = parseFloat(res.data.withdrawable_balance) || 0;
                Storage.setBalance(balance);
                Storage.setItem('forapix_withdrawable', withdrawable);
            }
        } catch (_) {}
        const canWithdraw  = withdrawable >= 10;

        Components.showModal(`
            <div class="modal-header">
                <h3>Sacar saldo</h3>
                <button class="modal-close" onclick="Components.closeModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="rounded-xl overflow-hidden mb-4" style="border:1px solid rgba(255,255,255,0.08)">
                <div class="flex justify-between items-center p-3" style="background:rgba(255,255,255,0.04)">
                    <span style="font-size:12px;color:#9ca3af">Saldo total</span>
                    <span style="font-weight:700">${Utils.formatCurrency(balance, true)}</span>
                </div>
                <div class="flex justify-between items-center p-3">
                    <div>
                        <span style="font-size:12px;color:#9ca3af">Saldo sacável</span>
                        <span style="display:block;font-size:10px;color:#6b7280">Apenas ganhos de apostas</span>
                    </div>
                    <span style="font-weight:700;color:${canWithdraw ? '#22c55e' : '#fbbf24'}">
                        ${Utils.formatCurrency(withdrawable, true)}
                    </span>
                </div>
            </div>

            ${!canWithdraw ? `
            <div style="background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.25);border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#fbbf24;line-height:1.6">
                <strong>⚠️ Saque não disponível</strong><br>
                Deposite, faça apostas e ganhe para liberar o saque.
                Mínimo de R$ 10,00 em ganhos necessário.
            </div>
            <button class="btn btn-secondary w-full" onclick="Components.closeModal()">Fechar</button>
            ` : `
            <div class="input-group mb-4">
                <label class="input-label">Valor do saque <span style="color:#6b7280;font-size:11px">(mín. R$ 10 · máx. ${Utils.formatCurrency(withdrawable, true)})</span></label>
                <input type="number" id="withdrawAmount" class="input-field" placeholder="10,00"
                    min="10" max="${withdrawable.toFixed(2)}" step="0.01">
            </div>
            <div class="input-group mb-4">
                <label class="input-label">Chave PIX</label>
                <input type="text" id="withdrawPixKey" class="input-field" placeholder="CPF, email, telefone ou chave aleatória">
            </div>
            <div class="input-group mb-6">
                <label class="input-label">CPF do titular da chave <span style="color:#6b7280;font-size:11px">(apenas números)</span></label>
                <input type="text" id="withdrawDocument" class="input-field" placeholder="00000000000" maxlength="14">
            </div>
            <div class="flex gap-3">
                <button class="btn btn-secondary flex-1" onclick="Components.closeModal()">Cancelar</button>
                <button class="btn btn-primary flex-1" onclick="WalletPage.processWithdraw()">
                    <i class="fas fa-paper-plane mr-2"></i>Confirmar saque
                </button>
            </div>
            `}
        `);
    },

    async processWithdraw() {
        const amount  = parseFloat(document.getElementById('withdrawAmount')?.value);
        const pixKey  = document.getElementById('withdrawPixKey')?.value;
        const cpfDoc  = (document.getElementById('withdrawDocument')?.value || '').replace(/\D/g, '');

        if (!amount || amount < 10) {
            Components.showToast('Valor mínimo de saque: R$ 10,00', 'warning');
            return;
        }
        if (!pixKey) {
            Components.showToast('Informe sua chave PIX', 'warning');
            return;
        }
        if (!cpfDoc || cpfDoc.length < 11) {
            Components.showToast('Informe o CPF do titular da chave PIX', 'warning');
            return;
        }

        try {
            const result = await API.withdraw(amount, pixKey, cpfDoc);
            Components.closeModal();

            if (result.success) {
                Components.showToast(result.message || 'Saque solicitado com sucesso!', 'success');
                await this._refreshBalanceCard();
                App.updateBalance();
                this.loadTransactions();
                this.switchTab('balance');
            } else {
                Components.showToast(result.error || result.message || 'Erro ao processar saque', 'error');
            }
        } catch (error) {
            Components.showToast('Erro ao processar saque', 'error');
        }
    }
};
