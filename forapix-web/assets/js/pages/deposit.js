/**
 * ApostaCasada - Página de Depósito
 * Integração real com VeoPag (PIX)
 */

const DepositPage = {
    selectedAmount: null,
    currentTransaction: null,   // { transaction_id, qrcode, amount, expires_at }
    pollingTimer: null,
    pollingCount: 0,
    MAX_POLLS: 120,             // 10 min (5s cada)

    render() {
        const balance = Storage.getBalance();

        return `
            <div class="page-enter p-4">
                <!-- Header com saldo -->
                <div class="bg-gradient-to-br from-[#1a1d3a] to-[#151a35] rounded-2xl p-5 mb-6 border border-white/5">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Saldo atual</p>
                    <p class="text-3xl font-bold text-white mb-1">${Utils.formatCurrency(balance, true)}</p>
                    <p class="text-xs text-gray-500">Disponível para apostas</p>
                </div>

                <!-- Formulário de depósito -->
                <div class="bg-card-bg rounded-2xl p-5 mb-6 border border-white/5">
                    <h3 class="text-lg font-bold text-white mb-4">Valor do depósito</h3>
                    
                    <!-- Valores rápidos -->
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        ${Config.BETTING.QUICK_VALUES.map(value => `
                            <button class="quick-amount-btn" data-amount="${value}" onclick="DepositPage.selectAmount(${value})">
                                <span class="text-lg font-bold text-white">${Utils.formatCurrency(value)}</span>
                            </button>
                        `).join('')}
                    </div>

                    <!-- Valor personalizado -->
                    <div class="input-group mb-4">
                        <label class="input-label">Outro valor</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-semibold">R$</span>
                            <input type="number" id="customAmount" class="input-field pl-12" placeholder="0,00" min="10" step="0.01" oninput="DepositPage.onCustomAmountInput()">
                        </div>
                    </div>

                    <!-- Valor selecionado -->
                    <div id="selectedAmountDisplay" class="bg-success/10 border border-success/30 rounded-xl p-4 mb-4 hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Valor a depositar:</span>
                            <span id="selectedAmountValue" class="text-success font-bold text-lg"></span>
                        </div>
                    </div>

                    <!-- Botão continuar -->
                    <button id="continueBtn" class="btn btn-warning w-full" onclick="DepositPage.showPixDetails()" disabled>
                        <i class="fas fa-arrow-right"></i> Continuar
                    </button>
                </div>

                <!-- PIX gerado (preenchido dinamicamente) -->
                <div id="pixDetails" class="hidden"></div>

                <!-- Depósitos recentes -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-white mb-4">Depósitos Recentes</h3>
                    ${this.renderRecentDeposits()}
                </div>
            </div>
        `;
    },

    init() {
        this.bindEvents();
    },

    bindEvents() {
        // Eventos já estão configurados nos onclicks
    },

    selectAmount(amount) {
        this.selectedAmount = amount;
        
        // Atualizar UI
        document.querySelectorAll('.quick-amount-btn').forEach(btn => {
            btn.classList.toggle('selected', parseFloat(btn.dataset.amount) === amount);
        });
        
        // Limpar campo personalizado
        const customInput = document.getElementById('customAmount');
        if (customInput) {
            customInput.value = '';
        }

        this.updateSelectedAmountDisplay();
    },

    onCustomAmountInput() {
        const customInput = document.getElementById('customAmount');
        const value = parseFloat(customInput.value) || 0;
        
        if (value >= 1) {
            this.selectedAmount = value;
            document.querySelectorAll('.quick-amount-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
        } else {
            this.selectedAmount = null;
        }

        this.updateSelectedAmountDisplay();
    },

    updateSelectedAmountDisplay() {
        const display = document.getElementById('selectedAmountDisplay');
        const valueLabel = document.getElementById('selectedAmountValue');
        const continueBtn = document.getElementById('continueBtn');

        if (this.selectedAmount && this.selectedAmount > 0) {
            display.classList.remove('hidden');
            valueLabel.textContent = Utils.formatCurrency(this.selectedAmount, true);
            continueBtn.disabled = false;
            continueBtn.classList.remove('opacity-50');
        } else {
            display.classList.add('hidden');
            continueBtn.disabled = true;
            continueBtn.classList.add('opacity-50');
        }
    },

    async showPixDetails() {
        if (!this.selectedAmount || this.selectedAmount <= 0) {
            Components.showToast('Selecione um valor válido', 'warning');
            return;
        }

        if (!Storage.isLoggedIn()) {
            Components.showToast('Faça login para depositar.', 'warning');
            App.navigateTo('menu');
            return;
        }

        const btn = document.getElementById('continueBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando PIX...';

        try {
            const result = await API.deposit(this.selectedAmount);

            if (!result.success) {
                Components.showToast(result.message || 'Erro ao gerar PIX', 'error');
                return;
            }

            this.currentTransaction = result.data;
            this.pollingCount = 0;
            this._renderPixPanel(result.data);
            this._startPolling(result.data.transaction_id);

        } catch (e) {
            Components.showToast(e.message || 'Erro ao gerar PIX', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-arrow-right"></i> Continuar';
        }
    },

    _renderPixPanel(data) {
        const pixDetails = document.getElementById('pixDetails');
        const amount = Utils.formatCurrency(data.amount, true);
        const expires = new Date(data.expires_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

        pixDetails.className = 'bg-card-bg rounded-2xl p-5 border border-white/5 mb-6';
        pixDetails.innerHTML = `
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Pague via PIX</h3>
                <span class="text-xs text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full">
                    <i class="fas fa-clock"></i> Expira às ${expires}
                </span>
            </div>

            <!-- QR Code -->
            <div class="bg-white rounded-2xl p-4 mb-4 flex items-center justify-center">
                <div id="qrCodeCanvas" class="w-48 h-48 flex items-center justify-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                </div>
            </div>

            <!-- Valor -->
            <div class="bg-success/10 border border-success/20 rounded-xl p-3 mb-4 text-center">
                <p class="text-xs text-gray-400 mb-1">Valor a pagar</p>
                <p class="text-2xl font-bold text-success">${amount}</p>
                ${data.fee > 0 ? `<p class="text-xs text-gray-500">+ R$ ${data.fee.toFixed(2)} de taxa</p>` : ''}
            </div>

            <!-- Copia e Cola -->
            <div class="mb-4">
                <label class="input-label">PIX Copia e Cola</label>
                <div class="flex gap-2">
                    <input type="text" id="pixCodeInput" class="input-field flex-1 text-xs" value="${data.qrcode}" readonly>
                    <button class="btn btn-secondary" onclick="DepositPage.copyPixCode()">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <!-- Status do pagamento -->
            <div id="paymentStatus" class="bg-secondary rounded-xl p-4 mb-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-yellow-400/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-spinner fa-spin text-yellow-400 text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-yellow-300">Aguardando pagamento...</p>
                    <p class="text-xs text-gray-400">Confirmaremos automaticamente em segundos.</p>
                </div>
            </div>

            <button class="btn btn-secondary w-full" onclick="DepositPage.cancelDeposit()">
                <i class="fas fa-times"></i> Cancelar
            </button>
        `;

        pixDetails.scrollIntoView({ behavior: 'smooth' });
        this._generateQrCode(data.qrcode);
    },

    _generateQrCode(emv) {
        const container = document.getElementById('qrCodeCanvas');
        if (!container) return;

        if (typeof QRCode !== 'undefined') {
            container.innerHTML = '';
            new QRCode(container, {
                text: emv,
                width: 180,
                height: 180,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });
        } else {
            container.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(emv)}" alt="QR Code PIX" class="w-44 h-44 rounded">`;
        }
    },

    _startPolling(transactionId) {
        this._stopPolling();
        this.pollingTimer = setInterval(() => this._pollStatus(transactionId), 5000);
    },

    _stopPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
    },

    async _pollStatus(transactionId) {
        this.pollingCount++;

        if (this.pollingCount > this.MAX_POLLS) {
            this._stopPolling();
            this._updatePaymentStatus('expired');
            return;
        }

        try {
            const res = await API.getDepositStatus(transactionId);
            if (res.success && res.data.status === 'completed') {
                this._stopPolling();
                Storage.setBalance(res.data.balance);
                App.updateAuthUI();
                this._updatePaymentStatus('completed', res.data.balance);
            }
        } catch (_) {}
    },

    _updatePaymentStatus(status, newBalance = null) {
        const el = document.getElementById('paymentStatus');
        if (!el) return;

        if (status === 'completed') {
            el.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-success/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-check text-success text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-success">Pagamento confirmado!</p>
                    <p class="text-xs text-gray-400">Novo saldo: ${newBalance !== null ? Utils.formatCurrency(newBalance, true) : '...'}</p>
                </div>
            `;
            setTimeout(() => this.showDepositConfirmation(), 1500);

        } else if (status === 'expired') {
            el.innerHTML = `
                <div class="w-8 h-8 rounded-full bg-red-400/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-times text-red-400 text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-red-400">PIX expirado.</p>
                    <p class="text-xs text-gray-400">Gere um novo QR Code para tentar novamente.</p>
                </div>
            `;
        }
    },

    async copyPixCode() {
        const input = document.getElementById('pixCodeInput');
        const code  = input ? input.value : (this.currentTransaction?.qrcode ?? '');
        const ok    = await Utils.copyToClipboard(code);
        Components.showToast(ok ? '✅ Código PIX copiado!' : 'Erro ao copiar', ok ? 'success' : 'error');
    },

    showDepositConfirmation() {
        this._stopPolling();
        const amount = this.currentTransaction?.amount ?? this.selectedAmount;
        Components.showModal(`
            <div class="text-center">
                <div class="w-20 h-20 bg-success/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-4xl text-success"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Depósito confirmado!</h3>
                <p class="text-gray-400 mb-4">Valor: ${Utils.formatCurrency(amount, true)}</p>
                <p class="text-xs text-gray-500 mb-6">Um e-mail de confirmação foi enviado para você.</p>
                <button class="btn btn-primary w-full" onclick="Components.closeModal(); App.navigateTo('wallet');">
                    <i class="fas fa-wallet"></i> Ver carteira
                </button>
            </div>
        `);
    },

    cancelDeposit() {
        this._stopPolling();
        this.currentTransaction = null;
        this.selectedAmount     = null;

        const pixDetails = document.getElementById('pixDetails');
        if (pixDetails) pixDetails.className = 'hidden';

        document.querySelectorAll('.quick-amount-btn').forEach(b => b.classList.remove('selected'));
        const ci = document.getElementById('customAmount');
        if (ci) ci.value = '';
        this.updateSelectedAmountDisplay();
    },

    renderRecentDeposits() {
        const transactions = Storage.getTransactions()
            .filter(t => t.type === 'deposit')
            .slice(0, 5);

        if (transactions.length === 0) {
            return Components.renderEmptyState(
                'fa-receipt',
                'Nenhum depósito recente',
                'Seus depósitos aparecerão aqui'
            );
        }

        return transactions.map(t => Components.renderTransactionItem(t)).join('');
    }
};
