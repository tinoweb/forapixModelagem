/**
 * FORAPIX - Wallet Page
 * Página de carteira/histórico financeiro
 */

const WalletPage = {
    /**
     * Render wallet page
     */
    render() {
        const balance = Storage.getBalance();
        const transactions = Storage.getTransactions();

        return `
            <div class="page-enter p-4">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-6">
                    <button onclick="App.goBack()" class="w-10 h-10 bg-card-bg rounded-full flex-center">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h2 class="text-xl font-bold">Carteira</h2>
                </div>

                <!-- Balance Card -->
                <div class="wallet-balance">
                    <p class="label">Saldo Disponível</p>
                    <p class="amount">${Utils.formatCurrency(balance, true)}</p>
                </div>

                <!-- Quick Actions -->
                <div class="wallet-actions">
                    <div class="wallet-action deposit" onclick="App.navigateTo('deposit')">
                        <div class="icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <p class="font-semibold">Depositar</p>
                    </div>
                    <div class="wallet-action withdraw" onclick="WalletPage.showWithdraw()">
                        <div class="icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <p class="font-semibold">Sacar</p>
                    </div>
                </div>

                <!-- Transactions -->
                <div class="section-header">
                    <span class="section-title">Histórico</span>
                    <span class="section-link" onclick="WalletPage.showAllTransactions()">Ver tudo</span>
                </div>

                <div id="transactionsList">
                    ${this.renderTransactions(transactions.slice(0, 5))}
                </div>
            </div>
        `;
    },

    /**
     * Render transactions list
     * @param {array} transactions - Transactions array
     */
    renderTransactions(transactions) {
        if (transactions.length === 0) {
            return Components.renderEmptyState(
                'fa-receipt',
                'Sem transações',
                'Você ainda não possui transações.'
            );
        }

        return transactions.map(t => Components.renderTransactionItem(t)).join('');
    },

    /**
     * Initialize wallet page
     */
    init() {
        // Nothing to initialize
    },

    /**
     * Show withdraw modal
     */
    showWithdraw() {
        const balance = Storage.getBalance();

        if (balance <= 0) {
            Components.showToast('Saldo insuficiente para saque', 'warning');
            return;
        }

        Components.showModal(`
            <div class="modal-header">
                <h3>Solicitar Saque</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="mb-4">
                <p class="text-sm text-gray-400 mb-2">Saldo disponível</p>
                <p class="text-2xl font-bold text-success">${Utils.formatCurrency(balance, true)}</p>
            </div>

            <div class="input-group">
                <label>Valor do Saque</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">R$</span>
                    <input 
                        type="number" 
                        id="withdrawAmountInput" 
                        class="input-field pl-12" 
                        placeholder="0,00"
                        min="10"
                        max="${balance}"
                        step="0.01"
                    >
                </div>
            </div>

            <div class="input-group">
                <label>Chave PIX</label>
                <input 
                    type="text" 
                    id="withdrawPixKey" 
                    class="input-field" 
                    placeholder="CPF, E-mail, Telefone ou Chave aleatória"
                >
            </div>

            <button class="btn btn-warning" onclick="WalletPage.processWithdraw()">
                <i class="fas fa-arrow-up"></i> Solicitar Saque
            </button>

            <p class="text-xs text-gray-500 text-center mt-4">
                <i class="fas fa-info-circle mr-1"></i>
                Saques são processados em até 24 horas
            </p>
        `);
    },

    /**
     * Process withdraw request
     */
    async processWithdraw() {
        const amount = parseFloat(document.getElementById('withdrawAmountInput').value) || 0;
        const pixKey = document.getElementById('withdrawPixKey').value.trim();
        const balance = Storage.getBalance();

        if (amount < 10) {
            Components.showToast('Valor mínimo: R$ 10,00', 'warning');
            return;
        }

        if (amount > balance) {
            Components.showToast('Saldo insuficiente', 'error');
            return;
        }

        if (!pixKey) {
            Components.showToast('Informe a chave PIX', 'warning');
            return;
        }

        Components.closeModal();
        Components.showLoading(true);

        try {
            const result = await API.withdraw(amount);

            Components.showLoading(false);

            if (result.success) {
                Components.showModal(`
                    <div class="text-center py-4">
                        <div class="w-24 h-24 bg-success/20 rounded-full flex-center mx-auto mb-4">
                            <i class="fas fa-check text-5xl text-success"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">Saque Solicitado!</h3>
                        <p class="text-gray-400 mb-4">Seu saque será processado em breve.</p>
                        
                        <div class="bg-secondary rounded-xl p-4 mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-400">Valor:</span>
                                <span class="font-semibold">${Utils.formatCurrency(amount, true)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Chave PIX:</span>
                                <span class="font-semibold">${Utils.truncate(pixKey, 20)}</span>
                            </div>
                        </div>

                        <button class="btn btn-primary" onclick="closeModal(); App.navigateTo('wallet');">
                            <i class="fas fa-wallet"></i> Voltar à Carteira
                        </button>
                    </div>
                `);

                Utils.vibrate(100);
            } else {
                Components.showToast(result.error || 'Erro ao processar saque', 'error');
            }
        } catch (error) {
            Components.showLoading(false);
            Components.showToast('Erro ao processar saque', 'error');
        }
    },

    /**
     * Show all transactions
     */
    showAllTransactions() {
        const transactions = Storage.getTransactions();

        Components.showModal(`
            <div class="modal-header">
                <h3>Todas as Transações</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                ${this.renderTransactions(transactions)}
            </div>
        `);
    }
};
