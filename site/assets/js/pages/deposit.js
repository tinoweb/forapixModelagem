/**
 * FORAPIX - Deposit Page
 * Página de depósito/adição de saldo
 */

const DepositPage = {
    depositAmount: 0,

    /**
     * Render deposit page
     */
    render() {
        const balance = Storage.getBalance();

        return `
            <div class="page-enter p-4">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-6">
                    <button onclick="App.goBack()" class="w-10 h-10 bg-card-bg rounded-full flex-center">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h2 class="text-xl font-bold">Depositar</h2>
                </div>

                <!-- Current Balance -->
                <div class="wallet-balance mb-6">
                    <p class="label">Saldo Atual</p>
                    <p class="amount">${Utils.formatCurrency(balance, true)}</p>
                </div>

                <!-- Deposit Form -->
                <div class="bg-card-bg rounded-2xl p-4 mb-6">
                    <h3 class="text-sm font-semibold text-gray-400 mb-4 uppercase">Valor do Depósito</h3>
                    
                    <div class="input-group">
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg">R$</span>
                            <input 
                                type="number" 
                                id="depositAmountInput" 
                                class="input-field pl-14 text-2xl font-bold text-center" 
                                placeholder="0,00"
                                min="10"
                                step="0.01"
                            >
                        </div>
                    </div>

                    <!-- Quick Values -->
                    <div class="grid grid-cols-3 gap-2 mt-4">
                        <button class="quick-value-btn" onclick="DepositPage.setAmount(20)">R$ 20</button>
                        <button class="quick-value-btn" onclick="DepositPage.setAmount(50)">R$ 50</button>
                        <button class="quick-value-btn" onclick="DepositPage.setAmount(100)">R$ 100</button>
                        <button class="quick-value-btn" onclick="DepositPage.setAmount(200)">R$ 200</button>
                        <button class="quick-value-btn" onclick="DepositPage.setAmount(500)">R$ 500</button>
                        <button class="quick-value-btn" onclick="DepositPage.setAmount(1000)">R$ 1000</button>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-card-bg rounded-2xl p-4 mb-6">
                    <h3 class="text-sm font-semibold text-gray-400 mb-4 uppercase">Método de Pagamento</h3>
                    
                    <div class="payment-method selected flex items-center gap-4 p-4 bg-secondary rounded-xl border-2 border-accent cursor-pointer">
                        <div class="w-12 h-12 bg-success/20 rounded-xl flex-center">
                            <i class="fas fa-qrcode text-2xl text-success"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold">PIX</p>
                            <p class="text-sm text-gray-400">Transferência instantânea</p>
                        </div>
                        <i class="fas fa-check-circle text-accent text-xl"></i>
                    </div>
                </div>

                <!-- Deposit Button -->
                <button id="btnDeposit" class="btn btn-success" disabled onclick="DepositPage.processDeposit()">
                    <i class="fas fa-plus"></i> DEPOSITAR
                </button>

                <!-- Info -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Transação segura e criptografada
                    </p>
                </div>
            </div>
        `;
    },

    /**
     * Initialize deposit page
     */
    init() {
        const amountInput = document.getElementById('depositAmountInput');
        if (amountInput) {
            amountInput.addEventListener('input', (e) => {
                this.depositAmount = parseFloat(e.target.value) || 0;
                this.updateButton();
            });
        }
    },

    /**
     * Set deposit amount
     * @param {number} amount - Amount to set
     */
    setAmount(amount) {
        const input = document.getElementById('depositAmountInput');
        input.value = amount;
        this.depositAmount = amount;
        this.updateButton();
    },

    /**
     * Update deposit button state
     */
    updateButton() {
        const btn = document.getElementById('btnDeposit');
        btn.disabled = this.depositAmount < 10;
    },

    /**
     * Process deposit
     */
    async processDeposit() {
        if (this.depositAmount < 10) {
            Components.showToast('Valor mínimo: R$ 10,00', 'warning');
            return;
        }

        // Show PIX QR Code modal
        this.showPixModal();
    },

    /**
     * Show PIX payment modal
     */
    showPixModal() {
        const pixCode = this.generatePixCode();

        Components.showModal(`
            <div class="modal-header">
                <h3>Pagamento PIX</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="text-center">
                <!-- QR Code Placeholder -->
                <div class="bg-white p-4 rounded-xl inline-block mb-4">
                    <div class="w-48 h-48 bg-gray-200 flex-center">
                        <i class="fas fa-qrcode text-6xl text-gray-800"></i>
                    </div>
                </div>

                <p class="text-lg font-bold mb-2">${Utils.formatCurrency(this.depositAmount, true)}</p>
                <p class="text-sm text-gray-400 mb-4">Escaneie o QR Code ou copie o código</p>

                <!-- PIX Code -->
                <div class="bg-secondary rounded-xl p-3 mb-4">
                    <p class="text-xs text-gray-400 mb-1">Código PIX Copia e Cola</p>
                    <p class="text-xs font-mono break-all text-gray-300" id="pixCodeText">${pixCode}</p>
                </div>

                <button class="btn btn-secondary mb-3" onclick="DepositPage.copyPixCode()">
                    <i class="fas fa-copy"></i> Copiar Código
                </button>

                <div class="text-xs text-gray-500 mb-4">
                    <i class="fas fa-clock mr-1"></i>
                    O pagamento expira em 30 minutos
                </div>

                <!-- Simulate Payment (for demo) -->
                <button class="btn btn-success" onclick="DepositPage.simulatePayment()">
                    <i class="fas fa-check"></i> Simular Pagamento
                </button>
            </div>
        `);
    },

    /**
     * Generate fake PIX code
     */
    generatePixCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '00020126580014BR.GOV.BCB.PIX0136';
        for (let i = 0; i < 36; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return code;
    },

    /**
     * Copy PIX code to clipboard
     */
    async copyPixCode() {
        const code = document.getElementById('pixCodeText').textContent;
        
        try {
            await navigator.clipboard.writeText(code);
            Components.showToast('Código copiado!', 'success');
        } catch (error) {
            Components.showToast('Erro ao copiar', 'error');
        }
    },

    /**
     * Simulate payment (for demo purposes)
     */
    async simulatePayment() {
        Components.closeModal();
        Components.showLoading(true);

        try {
            const result = await API.deposit(this.depositAmount);

            Components.showLoading(false);

            if (result.success) {
                this.showSuccessModal();
                Utils.vibrate(100);
            } else {
                Components.showToast(result.error || 'Erro ao processar depósito', 'error');
            }
        } catch (error) {
            Components.showLoading(false);
            Components.showToast('Erro ao processar depósito', 'error');
        }
    },

    /**
     * Show success modal
     */
    showSuccessModal() {
        const newBalance = Storage.getBalance();

        Components.showModal(`
            <div class="text-center py-4">
                <div class="w-24 h-24 bg-success/20 rounded-full flex-center mx-auto mb-4">
                    <i class="fas fa-check text-5xl text-success"></i>
                </div>
                <h3 class="text-2xl font-bold mb-2">Depósito Confirmado!</h3>
                <p class="text-gray-400 mb-4">Seu saldo foi atualizado.</p>
                
                <div class="bg-secondary rounded-xl p-4 mb-6">
                    <p class="text-sm text-gray-400">Novo Saldo</p>
                    <p class="text-3xl font-bold text-success">${Utils.formatCurrency(newBalance, true)}</p>
                </div>

                <button class="btn btn-primary" onclick="closeModal(); App.navigateTo('home');">
                    <i class="fas fa-gamepad"></i> Começar a Jogar
                </button>
            </div>
        `);

        // Reset amount
        this.depositAmount = 0;
    }
};
