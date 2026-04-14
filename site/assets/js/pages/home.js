/**
 * FORAPIX - Home Page
 * Página inicial do aplicativo
 */

const HomePage = {
    /**
     * Render home page
     */
    render() {
        const user = Storage.getUser();
        const balance = Storage.getBalance();
        const greeting = this.getGreeting();

        return `
            <div class="page-enter p-4">
                <!-- Welcome Card -->
                <div class="welcome-card bg-card-bg rounded-2xl p-5 mb-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">${greeting}</p>
                    <h2 class="text-xl font-bold mb-3">Bem-vindo(a), <span class="text-accent">${user.name.split(' ')[0].toLowerCase()}</span></h2>
                    
                    <p class="text-xs text-gray-500 uppercase tracking-wider">SALDO DISPONÍVEL</p>
                    <p class="text-2xl font-bold text-white mb-4">${Utils.formatCurrency(balance, true)}</p>
                    
                    <button class="w-full bg-accent hover:bg-accent-light text-white py-3 px-4 rounded-xl font-semibold flex items-center justify-center gap-2 transition-all" onclick="HomePage.shareInvite()">
                        <i class="fas fa-user-plus"></i>
                        Convide seus amigos
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-2">Copie o link e compartilhe</p>
                </div>

                <!-- Aposta Casada Banner -->
                <div class="mb-4">
                    <div class="game-card" onclick="App.navigateTo('sinuca')">
                        <img src="assets/images/apostacasada.png" alt="Aposta Casada" class="w-full h-auto rounded-2xl">
                    </div>
                </div>

                <!-- Games Section -->
                <p class="section-title mb-3">JOGOS</p>
                <div class="games-grid mb-6">
                    <div class="game-card" onclick="App.navigateTo('matches', { sportId: 1 })">
                        <img src="assets/images/casino.png" alt="Cassino">
                    </div>
                    <div class="game-card" onclick="App.navigateTo('games')">
                        <img src="assets/images/bingo.png" alt="Bingo">
                    </div>
                </div>

                <!-- Services Section -->
                <div class="section-header">
                    <span class="section-title">SERVIÇOS</span>
                </div>
                <div class="service-grid">
                    ${this.renderServices()}
                </div>
            </div>
        `;
    },

    /**
     * Render services grid
     */
    renderServices() {
        const services = [
            { name: 'Depósito', icon: 'fa-diamond', action: "App.navigateTo('deposit')" },
            { name: 'Suporte', icon: 'fa-headset', action: "HomePage.openSupport()" },
            { name: 'Resultados', icon: 'fa-trophy', action: "HomePage.showResults()" },
            { name: 'Palpites do Dia', icon: 'fa-star', action: "HomePage.showTips()" },
            { name: 'Sonhos', icon: 'fa-moon', action: "HomePage.showDreams()" },
            { name: 'Atrasados', icon: 'fa-clock', action: "HomePage.showDelayed()" },
            { name: 'Calculadora', icon: 'fa-calculator', action: "HomePage.showCalculator()" },
            { name: 'Tabela de Bichos', icon: 'fa-paw', action: "HomePage.showAnimalsTable()" }
        ];

        return services.map(service => Components.renderServiceItem(service)).join('');
    },

    /**
     * Initialize home page
     */
    init() {
        // Nothing to initialize for now
    },

    /**
     * Get greeting based on time of day
     */
    getGreeting() {
        const hour = new Date().getHours();
        if (hour >= 5 && hour < 12) return 'BOM DIA';
        if (hour >= 12 && hour < 18) return 'BOA TARDE';
        return 'BOA NOITE';
    },

    /**
     * Share invite link
     */
    async shareInvite() {
        const inviteLink = 'https://forapix.com/invite/ABC123';
        
        if (navigator.share) {
            try {
                await navigator.share({
                    title: 'ForaPix - Apostas Online',
                    text: 'Venha apostar comigo no ForaPix!',
                    url: inviteLink
                });
            } catch (err) {
                this.copyInviteLink(inviteLink);
            }
        } else {
            this.copyInviteLink(inviteLink);
        }
    },

    /**
     * Copy invite link to clipboard
     */
    async copyInviteLink(link) {
        try {
            await navigator.clipboard.writeText(link);
            Components.showToast('Link copiado!', 'success');
        } catch (err) {
            Components.showToast('Erro ao copiar link', 'error');
        }
    },

    /**
     * Open support
     */
    openSupport() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Suporte</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-center py-8">
                <div class="w-20 h-20 bg-accent/20 rounded-full flex-center mx-auto mb-4">
                    <i class="fas fa-headset text-4xl text-accent"></i>
                </div>
                <h4 class="text-lg font-semibold mb-2">Precisa de ajuda?</h4>
                <p class="text-gray-400 mb-6">Entre em contato com nosso suporte</p>
                <a href="https://wa.me/5511999999999" target="_blank" class="btn btn-success">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>
        `);
    },

    /**
     * Show results
     */
    showResults() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Resultados</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            ${Components.renderEmptyState('fa-trophy', 'Sem resultados', 'Nenhum resultado disponível no momento.')}
        `);
    },

    /**
     * Show tips of the day
     */
    showTips() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Palpites do Dia</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            ${Components.renderEmptyState('fa-star', 'Sem palpites', 'Nenhum palpite disponível hoje.')}
        `);
    },

    /**
     * Show dreams interpretation
     */
    showDreams() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Sonhos</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="input-group">
                <label>Digite seu sonho</label>
                <input type="text" class="input-field" placeholder="Ex: Sonhei com água...">
            </div>
            <button class="btn btn-primary">
                <i class="fas fa-search"></i> Interpretar
            </button>
        `);
    },

    /**
     * Show delayed numbers
     */
    showDelayed() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Números Atrasados</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            ${Components.renderEmptyState('fa-clock', 'Sem dados', 'Nenhum número atrasado no momento.')}
        `);
    },

    /**
     * Show calculator
     */
    showCalculator() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Calculadora</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="input-group">
                <label>Valor da Aposta</label>
                <input type="number" id="calcAmount" class="input-field" placeholder="0,00">
            </div>
            <div class="input-group">
                <label>Odd</label>
                <input type="number" id="calcOdds" class="input-field" placeholder="1.00" step="0.01">
            </div>
            <div class="bet-summary">
                <div class="bet-summary-row">
                    <span class="label">Ganho Potencial</span>
                    <span class="value highlight" id="calcResult">R$ 0,00</span>
                </div>
            </div>
            <button class="btn btn-primary" onclick="HomePage.calculate()">
                <i class="fas fa-calculator"></i> Calcular
            </button>
        `);
    },

    /**
     * Calculate potential win
     */
    calculate() {
        const amount = parseFloat(document.getElementById('calcAmount').value) || 0;
        const odds = parseFloat(document.getElementById('calcOdds').value) || 1;
        const result = Utils.calculatePotentialWin(amount, odds);
        document.getElementById('calcResult').textContent = Utils.formatCurrency(result, true);
    },

    /**
     * Show animals table (Jogo do Bicho)
     */
    showAnimalsTable() {
        const animals = [
            { num: '01-04', name: 'Avestruz' },
            { num: '05-08', name: 'Águia' },
            { num: '09-12', name: 'Burro' },
            { num: '13-16', name: 'Borboleta' },
            { num: '17-20', name: 'Cachorro' },
            { num: '21-24', name: 'Cabra' },
            { num: '25-28', name: 'Carneiro' },
            { num: '29-32', name: 'Camelo' },
            { num: '33-36', name: 'Cobra' },
            { num: '37-40', name: 'Coelho' },
            { num: '41-44', name: 'Cavalo' },
            { num: '45-48', name: 'Elefante' },
            { num: '49-52', name: 'Galo' },
            { num: '53-56', name: 'Gato' },
            { num: '57-60', name: 'Jacaré' },
            { num: '61-64', name: 'Leão' },
            { num: '65-68', name: 'Macaco' },
            { num: '69-72', name: 'Porco' },
            { num: '73-76', name: 'Pavão' },
            { num: '77-80', name: 'Peru' },
            { num: '81-84', name: 'Touro' },
            { num: '85-88', name: 'Tigre' },
            { num: '89-92', name: 'Urso' },
            { num: '93-96', name: 'Veado' },
            { num: '97-00', name: 'Vaca' }
        ];

        const animalsHtml = animals.map(a => `
            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                <span class="text-accent font-mono">${a.num}</span>
                <span>${a.name}</span>
            </div>
        `).join('');

        Components.showModal(`
            <div class="modal-header">
                <h3>Tabela de Bichos</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="max-h-80 overflow-y-auto">
                ${animalsHtml}
            </div>
        `);
    }
};
