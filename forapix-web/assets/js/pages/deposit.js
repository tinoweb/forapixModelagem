/**
 * ForaPix - Página de Depósito
 * Formulário funcional para depósito via PIX
 */

const DepositPage = {
    selectedAmount: null,
    pixKey: 'forapix@pix.com.br',
    qrCode: '00020126580014br.gov.bcb.pix0136forapix@pix.com.br5204000053039865405100.005802BR5925ForaPix Apostas Online6009Sao Paulo62070503***6304E8A2',

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
                            <input type="number" id="customAmount" class="input-field pl-12" placeholder="0,00" min="1" step="0.01" oninput="DepositPage.onCustomAmountInput()">
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

                <!-- Informações PIX -->
                <div id="pixDetails" class="bg-card-bg rounded-2xl p-5 border border-white/5 hidden">
                    <h3 class="text-lg font-bold text-white mb-4">Escaneie o QR Code</h3>
                    
                    <!-- QR Code -->
                    <div class="bg-white rounded-2xl p-6 mb-4 flex items-center justify-center">
                        <div class="w-48 h-48 bg-gray-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-qrcode text-8xl text-gray-300"></i>
                        </div>
                    </div>

                    <!-- Chave PIX -->
                    <div class="mb-4">
                        <label class="input-label">Chave PIX</label>
                        <div class="flex gap-2">
                            <input type="text" class="input-field flex-1" value="${this.pixKey}" readonly>
                            <button class="btn btn-secondary" onclick="DepositPage.copyPixKey()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Código PIX -->
                    <div class="mb-4">
                        <label class="input-label">Código PIX (copiar e colar)</label>
                        <div class="flex gap-2">
                            <input type="text" class="input-field flex-1 text-xs" value="${this.qrCode}" readonly>
                            <button class="btn btn-secondary" onclick="DepositPage.copyPixCode()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Informações -->
                    <div class="bg-secondary rounded-xl p-4 mb-4">
                        <h4 class="text-sm font-bold text-white mb-2">Instruções:</h4>
                        <ul class="text-xs text-gray-400 space-y-2">
                            <li>1. Abra o app do seu banco</li>
                            <li>2. Escolha a opção PIX</li>
                            <li>3. Escaneie o QR Code ou copie a chave</li>
                            <li>4. Confirme o valor e faça o pagamento</li>
                            <li>5. O saldo será creditado automaticamente</li>
                        </ul>
                    </div>

                    <!-- Botão confirmar -->
                    <button class="btn btn-success w-full mb-3" onclick="DepositPage.confirmDeposit()">
                        <i class="fas fa-check"></i> Já fiz o pagamento
                    </button>
                    <button class="btn btn-secondary w-full" onclick="DepositPage.cancelDeposit()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>

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
        
        if (value > 0) {
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

    showPixDetails() {
        if (!this.selectedAmount || this.selectedAmount <= 0) {
            Components.showToast('Selecione um valor válido', 'warning');
            return;
        }

        const pixDetails = document.getElementById('pixDetails');
        if (pixDetails) {
            pixDetails.classList.remove('hidden');
            pixDetails.scrollIntoView({ behavior: 'smooth' });
        }
    },

    async copyPixKey() {
        const success = await Utils.copyToClipboard(this.pixKey);
        if (success) {
            Components.showToast('Chave PIX copiada!', 'success');
        } else {
            Components.showToast('Erro ao copiar chave', 'error');
        }
    },

    async copyPixCode() {
        const success = await Utils.copyToClipboard(this.qrCode);
        if (success) {
            Components.showToast('Código PIX copiado!', 'success');
        } else {
            Components.showToast('Erro ao copiar código', 'error');
        }
    },

    async confirmDeposit() {
        if (!this.selectedAmount || this.selectedAmount <= 0) {
            Components.showToast('Valor inválido', 'error');
            return;
        }

        try {
            Components.showLoading(true);

            // Simular processamento
            await new Promise(resolve => setTimeout(resolve, 2000));

            // Registrar depósito
            const result = await API.deposit(this.selectedAmount);

            if (result.success) {
                Components.showLoading(false);
                Components.showToast('Depósito confirmado com sucesso!', 'success');
                App.updateBalance();
                
                // Mostrar confirmação
                this.showDepositConfirmation();
            } else {
                Components.showLoading(false);
                Components.showToast(result.error || 'Erro ao processar depósito', 'error');
            }
        } catch (error) {
            Components.showLoading(false);
            Components.showToast('Erro ao processar depósito', 'error');
        }
    },

    showDepositConfirmation() {
        Components.showModal(`
            <div class="text-center">
                <div class="w-20 h-20 bg-success/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-4xl text-success"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Depósito confirmado!</h3>
                <p class="text-gray-400 mb-4">Valor depositado: ${Utils.formatCurrency(this.selectedAmount, true)}</p>
                <p class="text-sm text-gray-500 mb-6">O saldo já está disponível na sua conta.</p>
                <button class="btn btn-primary w-full" onclick="closeModal(); App.navigateTo('wallet');">
                    <i class="fas fa-wallet"></i> Ver carteira
                </button>
            </div>
        `);
    },

    cancelDeposit() {
        this.selectedAmount = null;
        const pixDetails = document.getElementById('pixDetails');
        if (pixDetails) {
            pixDetails.classList.add('hidden');
        }
        
        document.querySelectorAll('.quick-amount-btn').forEach(btn => {
            btn.classList.remove('selected');
        });
        
        const customInput = document.getElementById('customAmount');
        if (customInput) {
            customInput.value = '';
        }

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
