/**
 * ForaPix - Home Page
 * Página inicial baseada na interface de referência
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
                <div class="welcome-card p-5 mb-4">
                    <p class="greeting mb-1">${greeting}</p>
                    <h2 class="text-xl font-bold mb-3">Bem-vindo(a), <span class="user-name">${user.name.split(' ')[0].toLowerCase()}</span></h2>

                    <p class="balance-label">Saldo Disponível</p>
                    <p class="text-2xl font-bold text-white mb-4">${Utils.formatCurrency(balance, true)}</p>

                    <button class="invite-btn" onclick="HomePage.shareInvite()">
                        <i class="fas fa-user-plus"></i>
                        Convide seus amigos
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-2">Copie o link e compartilhe</p>
                </div>

                <!-- Aposta Casada Banner -->
                <div class="mb-4">
                    <div class="game-card cursor-pointer" onclick="App.navigateTo('matches')">
                        <img src="assets/images/apostacasada.png" alt="Aposta Casada" class="w-full h-auto rounded-2xl shadow-[0_0_15px_rgba(124,58,237,0.3)]">
                    </div>
                </div>

                <!-- Games Section -->
                <p class="section-title mb-3">JOGOS</p>
                <div class="games-grid mb-6">
                    <div class="game-card cursor-pointer" onclick="App.navigateTo('matches', { gameType: 'casino' })">
                        <img src="assets/images/casino.png" alt="Cassino" class="w-full h-full object-cover rounded-2xl">
                    </div>
                    <div class="game-card cursor-pointer" onclick="App.navigateTo('games')">
                        <img src="assets/images/bingo.png" alt="Bingo" class="w-full h-full object-cover rounded-2xl">
                    </div>
                </div>

                <!-- Services Section -->
                <p class="section-title mb-3">SERVIÇOS</p>
                <div class="service-grid mb-6">
                    ${this.renderServices()}
                </div>

                <!-- Recent Matches -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <p class="section-title">PARTIDAS EM DESTAQUE</p>
                        <button class="text-accent text-sm font-semibold" onclick="App.navigateTo('matches')">Ver todas</button>
                    </div>
                    <div id="featuredMatches">
                        ${this.renderFeaturedMatches()}
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Render services
     */
    renderServices() {
        const services = [
            { name: 'Depósito', icon: 'fa-dice-five', action: "App.navigateTo('deposit')" },
            { name: 'Suporte', icon: 'fa-headset', action: "HomePage.openSupport()" },
            { name: 'Resultados', icon: 'fa-trophy', action: "HomePage.showResults()" },
            { name: 'Palpites do Dia', icon: 'fa-star', action: "Components.showToast('Em breve!', 'info')" },
            { name: 'Sonhos', icon: 'fa-moon', action: "Components.showToast('Em breve!', 'info')" },
            { name: 'Atrasados', icon: 'fa-clock', action: "Components.showToast('Em breve!', 'info')" },
            { name: 'Calculadora', icon: 'fa-calculator', action: "Components.showToast('Em breve!', 'info')" },
            { name: 'Tabela de Bichos', icon: 'fa-paw', action: "Components.showToast('Em breve!', 'info')" }
        ];

        return services.map(service => Components.renderServiceItem(service)).join('');
    },

    /**
     * Render featured matches
     */
    renderFeaturedMatches() {
        // Mock data for featured matches
        const matches = [
            {
                id: 1,
                sport: 'UFC',
                fighter1: 'Jon Jones',
                fighter2: 'Stipe Miocic',
                odds1: '1.85',
                odds2: '2.10',
                date: new Date(Date.now() + 24 * 60 * 60 * 1000) // Tomorrow
            },
            {
                id: 2,
                sport: 'SINUCA',
                fighter1: 'Maycon de Teixeira',
                fighter2: 'Fábio Cabeludo',
                odds1: '1.95',
                odds2: '1.85',
                date: new Date()
            }
        ];

        if (matches.length === 0) {
            return Components.renderEmptyState(
                'fa-calendar',
                'Nenhuma partida em destaque',
                'Novas partidas serão exibidas aqui em breve.'
            );
        }

        return matches.map(match => Components.renderMatchCard(match)).join('');
    },

    /**
     * Initialize home page
     */
    init() {
        this.loadFeaturedMatches();
    },

    /**
     * Load featured matches from API
     */
    async loadFeaturedMatches() {
        try {
            const response = await API.getUpcomingMatches();
            if (response.success && response.data.length > 0) {
                const container = document.getElementById('featuredMatches');
                if (container) {
                    container.innerHTML = response.data.slice(0, 3).map(match => 
                        Components.renderMatchCard(match)
                    ).join('');
                }
            }
        } catch (error) {
            console.error('Error loading featured matches:', error);
        }
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
                Components.showToast('Link compartilhado!', 'success');
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
        const success = await Utils.copyToClipboard(link);
        if (success) {
            Components.showToast('Link copiado!', 'success');
        } else {
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

            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-accent/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-4xl text-accent"></i>
                </div>
                <h4 class="text-lg font-semibold mb-2">Central de Ajuda</h4>
                <p class="text-sm text-gray-400">Como podemos te ajudar hoje?</p>
            </div>

            <div class="space-y-3 mb-6">
                <button class="w-full bg-secondary hover:bg-card-hover p-4 rounded-xl text-left transition">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-comments text-accent"></i>
                        <div>
                            <p class="font-semibold">Chat ao Vivo</p>
                            <p class="text-xs text-gray-400">Fale conosco agora</p>
                        </div>
                    </div>
                </button>
                
                <button class="w-full bg-secondary hover:bg-card-hover p-4 rounded-xl text-left transition">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-envelope text-accent"></i>
                        <div>
                            <p class="font-semibold">Email</p>
                            <p class="text-xs text-gray-400">suporte@forapix.com</p>
                        </div>
                    </div>
                </button>
                
                <button class="w-full bg-secondary hover:bg-card-hover p-4 rounded-xl text-left transition">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-question-circle text-accent"></i>
                        <div>
                            <p class="font-semibold">FAQ</p>
                            <p class="text-xs text-gray-400">Perguntas frequentes</p>
                        </div>
                    </div>
                </button>
            </div>

            <button class="btn btn-primary w-full" onclick="closeModal()">
                <i class="fas fa-check"></i> OK
            </button>
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

            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-warning/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trophy text-4xl text-warning"></i>
                </div>
                <h4 class="text-lg font-semibold mb-2">Resultados Recentes</h4>
                <p class="text-sm text-gray-400">Confira os últimos resultados</p>
            </div>

            <div class="space-y-3 mb-6">
                <div class="bg-secondary rounded-xl p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold">UFC 300</span>
                        <span class="text-xs text-gray-400">Ontem</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Jon Jones vs Stipe Miocic</span>
                        <span class="text-sm text-success font-semibold">Jon Jones</span>
                    </div>
                </div>
                
                <div class="bg-secondary rounded-xl p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold">Sinuca Pro</span>
                        <span class="text-xs text-gray-400">2 dias atrás</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Maycon vs Fábio</span>
                        <span class="text-sm text-success font-semibold">Par (8)</span>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary w-full" onclick="closeModal()">
                <i class="fas fa-check"></i> OK
            </button>
        `);
    }
};
