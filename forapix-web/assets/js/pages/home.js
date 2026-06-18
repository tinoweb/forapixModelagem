/**
 * ApostaCasada - Home Page
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
                    <h2 class="text-xl font-bold mb-3">Bem-vindo(a), <span id="homeUserName" class="user-name">${user ? user.name.split(' ')[0] : 'você'}</span></h2>

                    <p class="balance-label">Saldo Disponível</p>
                    <p id="homeBalance" class="text-2xl font-bold text-white mb-4">${Utils.formatCurrency(balance, true)}</p>

                    <button class="invite-btn" onclick="HomePage.shareInvite()">
                        <i class="fas fa-user-plus"></i>
                        Convide seus amigos
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-2">Copie o link e compartilhe</p>
                </div>

                <!-- Aposta Casada Banner -->
                <div class="mb-4">
                    <div class="game-card cursor-pointer" onclick="App.navigateTo('matches')">
                        <img src="assets/images/jrpix.png" alt="JRpix" class="w-full h-auto rounded-2xl shadow-[0_0_15px_rgba(16,185,129,0.3)]">
                    </div>
                </div>

                <!-- Services Section -->
                <p class="section-title mb-3">SERVIÇOS</p>
                <div class="service-grid mb-6">
                    ${this.renderServices()}
                </div>

                <!-- Aposta Casada Banner 2 (abaixo dos botões de serviços) -->
                <div class="mb-4">
                    <div class="game-card cursor-pointer" onclick="App.navigateTo('matches')">
                        <img src="assets/images/jrpix2.png" alt="JRpix" class="w-full h-auto rounded-2xl shadow-[0_0_15px_rgba(16,185,129,0.3)]">
                    </div>
                </div>
            </div>
        `;
    },

    renderServices() {
        const services = [
            { name: 'Depósito', icon: 'fa-dice-five', action: "App.navigateTo('deposit')" },
            { name: 'Saque', icon: 'fa-wallet', action: "App.navigateTo('wallet')" },
            { name: 'Suporte', icon: 'fa-headset', action: "HomePage.openSupport()" },
            { name: 'Resultados', icon: 'fa-trophy', action: "HomePage.showResults()" }
        ];

        return services.map(service => Components.renderServiceItem(service)).join('');
    },

    /**
     * Initialize home page
     */
    init() {
        this._refreshUserCard();
    },

    async _refreshUserCard() {
        if (!Storage.isLoggedIn()) return;
        try {
            const res = await API.request('/auth/profile');
            if (res && res.success && res.data) {
                const data = res.data;
                const stored = Storage.getUser() || {};
                Storage.setUser({ ...stored, ...data });
                const fresh = parseFloat(data.balance) || 0;
                Storage.setBalance(fresh);

                const nameEl    = document.getElementById('homeUserName');
                const balanceEl = document.getElementById('homeBalance');
                if (nameEl)    nameEl.textContent    = data.name.split(' ')[0];
                if (balanceEl) balanceEl.textContent = Utils.formatCurrency(fresh, true);

                // Atualiza header também
                if (typeof App !== 'undefined') App.updateBalance();
            }
        } catch (_) {}
    },

    /**
     * Carrega partidas ao vivo na seção "AO VIVO AGORA" da home
     */
    async loadLiveMatches() {
        try {
            const response = await API.getMatches({ status: 'live' });
            const payload = Array.isArray(response.data?.data)
                ? response.data.data
                : (Array.isArray(response.data) ? response.data : []);

            const section = document.getElementById('liveMatchesSection');
            const list    = document.getElementById('liveMatchesList');
            if (!section || !list) return;

            if (payload.length > 0) {
                section.style.display = '';
                list.innerHTML = payload.slice(0, 3).map(m => Components.renderMatchCard(m)).join('');
            } else {
                section.style.display = 'none';
            }
        } catch (_) {}
    },

    /**
     * Carrega próximas partidas agendadas na home
     */
    async loadFeaturedMatches() {
        try {
            const response = await API.getMatches({ status: 'scheduled' });
            const payload = Array.isArray(response.data?.data)
                ? response.data.data
                : (Array.isArray(response.data) ? response.data : []);

            const container = document.getElementById('featuredMatches');
            if (!container) return;

            if (payload.length > 0) {
                container.innerHTML = payload.slice(0, 3).map(m => Components.renderMatchCard(m)).join('');
            } else {
                container.innerHTML = `<p class="text-center text-sm text-gray-500 py-6">Nenhuma partida agendada no momento.</p>`;
            }
        } catch (error) {
            console.error('Erro ao carregar partidas agendadas:', error);
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
        const inviteLink = 'https://jrpix.com/invite/ABC123';
        
        if (navigator.share) {
            try {
                await navigator.share({
                    title: 'JRpix - Jogos e Apostas',
                    text: 'Venha apostar comigo na JRpix!',
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
    async openSupport() {
        const settings = await this._getSettings();
        const whatsappNumber = settings.whatsapp_number || '';
        const whatsappEnabled = settings.whatsapp_enabled !== false;
        const supportEmail = settings.support_email || 'suporte@jrpix.com';

        let whatsappButton = '';
        if (whatsappEnabled && whatsappNumber) {
            whatsappButton = `
                <button onclick="window.open('https://wa.me/${whatsappNumber}', '_blank')" class="w-full bg-green-600/20 hover:bg-green-600/30 border border-green-600/30 p-4 rounded-xl text-left transition">
                    <div class="flex items-center gap-3">
                        <i class="fab fa-whatsapp text-green-500 text-xl"></i>
                        <div>
                            <p class="font-semibold text-green-400">WhatsApp</p>
                            <p class="text-xs text-gray-400">Fale conosco agora</p>
                        </div>
                    </div>
                </button>
            `;
        }

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
                ${whatsappButton}

                <button class="w-full bg-secondary hover:bg-card-hover p-4 rounded-xl text-left transition">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-envelope text-accent"></i>
                        <div>
                            <p class="font-semibold">Email</p>
                            <p class="text-xs text-gray-400">${supportEmail}</p>
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
     * Get system settings
     */
    async _getSettings() {
        try {
            const res = await API.request('/settings');
            return res?.data || {};
        } catch (e) {
            console.error('Erro ao buscar configurações:', e);
            return {};
        }
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
