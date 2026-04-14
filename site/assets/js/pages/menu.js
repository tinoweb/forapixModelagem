/**
 * FORAPIX - Menu Page
 * Página de menu/configurações
 */

const MenuPage = {
    /**
     * Render menu page
     */
    render() {
        const user = Storage.getUser();

        return `
            <div class="page-enter p-4">
                <!-- User Profile -->
                <div class="bg-card-bg rounded-2xl p-4 mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-accent rounded-full flex-center">
                            <i class="fas fa-user text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-lg">${user.name}</h3>
                            <p class="text-sm text-gray-400">${user.email}</p>
                        </div>
                        <button class="w-10 h-10 bg-secondary rounded-full flex-center" onclick="MenuPage.editProfile()">
                            <i class="fas fa-pen text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Menu Items -->
                <div class="space-y-2">
                    ${this.renderMenuItems()}
                </div>

                <!-- App Info -->
                <div class="mt-8 text-center">
                    <p class="text-xs text-gray-500">
                        ${CONFIG.APP.NAME} v${CONFIG.APP.VERSION}
                    </p>
                    <p class="text-xs text-gray-600 mt-1">
                        © 2024 Todos os direitos reservados
                    </p>
                </div>
            </div>
        `;
    },

    /**
     * Render menu items
     */
    renderMenuItems() {
        const items = [
            { icon: 'fa-ticket', label: 'Minhas Apostas', action: 'MenuPage.showBets()' },
            { icon: 'fa-history', label: 'Histórico', action: "App.navigateTo('wallet')" },
            { icon: 'fa-bell', label: 'Notificações', action: 'MenuPage.showNotifications()' },
            { icon: 'fa-shield-alt', label: 'Segurança', action: 'MenuPage.showSecurity()' },
            { icon: 'fa-question-circle', label: 'Ajuda', action: 'MenuPage.showHelp()' },
            { icon: 'fa-file-alt', label: 'Termos de Uso', action: 'MenuPage.showTerms()' },
            { icon: 'fa-lock', label: 'Política de Privacidade', action: 'MenuPage.showPrivacy()' },
            { icon: 'fa-sign-out-alt', label: 'Sair', action: 'MenuPage.logout()', danger: true }
        ];

        return items.map(item => `
            <div class="card cursor-pointer ${item.danger ? 'hover:bg-danger/10' : ''}" onclick="${item.action}">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-secondary rounded-xl flex-center">
                        <i class="fas ${item.icon} ${item.danger ? 'text-danger' : 'text-accent'}"></i>
                    </div>
                    <span class="flex-1 ${item.danger ? 'text-danger' : ''}">${item.label}</span>
                    <i class="fas fa-chevron-right text-gray-500 text-sm"></i>
                </div>
            </div>
        `).join('');
    },

    /**
     * Initialize menu page
     */
    init() {
        // Nothing to initialize
    },

    /**
     * Edit profile
     */
    editProfile() {
        const user = Storage.getUser();

        Components.showModal(`
            <div class="modal-header">
                <h3>Editar Perfil</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="input-group">
                <label>Nome</label>
                <input type="text" id="editName" class="input-field" value="${user.name}">
            </div>

            <div class="input-group">
                <label>E-mail</label>
                <input type="email" id="editEmail" class="input-field" value="${user.email}">
            </div>

            <button class="btn btn-primary" onclick="MenuPage.saveProfile()">
                <i class="fas fa-save"></i> Salvar
            </button>
        `);
    },

    /**
     * Save profile changes
     */
    saveProfile() {
        const name = document.getElementById('editName').value.trim();
        const email = document.getElementById('editEmail').value.trim();

        if (!name || !email) {
            Components.showToast('Preencha todos os campos', 'warning');
            return;
        }

        const user = Storage.getUser();
        user.name = name;
        user.email = email;
        Storage.set(CONFIG.STORAGE.USER, user);

        Components.closeModal();
        Components.showToast('Perfil atualizado!', 'success');
        App.navigateTo('menu');
    },

    /**
     * Show user bets
     */
    showBets() {
        const bets = Storage.getBets();

        Components.showModal(`
            <div class="modal-header">
                <h3>Minhas Apostas</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                ${bets.length === 0 
                    ? Components.renderEmptyState('fa-ticket', 'Sem apostas', 'Você ainda não fez nenhuma aposta.')
                    : bets.map(bet => this.renderBetItem(bet)).join('')
                }
            </div>
        `);
    },

    /**
     * Render bet item
     * @param {object} bet - Bet data
     */
    renderBetItem(bet) {
        const statusColors = {
            pending: 'text-warning',
            won: 'text-success',
            lost: 'text-danger'
        };

        const statusLabels = {
            pending: 'Pendente',
            won: 'Ganhou',
            lost: 'Perdeu'
        };

        return `
            <div class="bg-secondary rounded-xl p-4 mb-3">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="font-semibold">${bet.fighterName}</p>
                        <p class="text-xs text-gray-400">${Utils.formatDate(bet.createdAt, 'full')}</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-gray-700 ${statusColors[bet.status]}">
                        ${statusLabels[bet.status]}
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Valor: ${Utils.formatCurrency(bet.amount, true)}</span>
                    <span class="text-accent">Odd: ${bet.odds.toFixed(2)}</span>
                </div>
            </div>
        `;
    },

    /**
     * Show notifications settings
     */
    showNotifications() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Notificações</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-secondary rounded-xl">
                    <div>
                        <p class="font-semibold">Resultados</p>
                        <p class="text-xs text-gray-400">Receber notificações de resultados</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-secondary rounded-xl">
                    <div>
                        <p class="font-semibold">Promoções</p>
                        <p class="text-xs text-gray-400">Receber ofertas e promoções</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
                    </label>
                </div>
            </div>
        `);
    },

    /**
     * Show security settings
     */
    showSecurity() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Segurança</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="space-y-3">
                <div class="card cursor-pointer" onclick="MenuPage.changePassword()">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-secondary rounded-xl flex-center">
                            <i class="fas fa-key text-accent"></i>
                        </div>
                        <span class="flex-1">Alterar Senha</span>
                        <i class="fas fa-chevron-right text-gray-500 text-sm"></i>
                    </div>
                </div>

                <div class="card cursor-pointer">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-secondary rounded-xl flex-center">
                            <i class="fas fa-mobile-alt text-accent"></i>
                        </div>
                        <span class="flex-1">Verificação em 2 etapas</span>
                        <i class="fas fa-chevron-right text-gray-500 text-sm"></i>
                    </div>
                </div>
            </div>
        `);
    },

    /**
     * Change password
     */
    changePassword() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Alterar Senha</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="input-group">
                <label>Senha Atual</label>
                <input type="password" class="input-field" placeholder="••••••••">
            </div>

            <div class="input-group">
                <label>Nova Senha</label>
                <input type="password" class="input-field" placeholder="••••••••">
            </div>

            <div class="input-group">
                <label>Confirmar Nova Senha</label>
                <input type="password" class="input-field" placeholder="••••••••">
            </div>

            <button class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar
            </button>
        `);
    },

    /**
     * Show help
     */
    showHelp() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Ajuda</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="space-y-3">
                <div class="card">
                    <h4 class="font-semibold mb-2">Como fazer uma aposta?</h4>
                    <p class="text-sm text-gray-400">Selecione um jogo, escolha o lutador e informe o valor da aposta.</p>
                </div>

                <div class="card">
                    <h4 class="font-semibold mb-2">Como depositar?</h4>
                    <p class="text-sm text-gray-400">Clique em Depositar, informe o valor e pague via PIX.</p>
                </div>

                <div class="card">
                    <h4 class="font-semibold mb-2">Como sacar?</h4>
                    <p class="text-sm text-gray-400">Acesse sua Carteira, clique em Sacar e informe sua chave PIX.</p>
                </div>
            </div>

            <button class="btn btn-success mt-4" onclick="window.open('https://wa.me/5511999999999', '_blank')">
                <i class="fab fa-whatsapp"></i> Falar com Suporte
            </button>
        `);
    },

    /**
     * Show terms of use
     */
    showTerms() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Termos de Uso</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto text-sm text-gray-400">
                <p class="mb-4">Ao utilizar o ${CONFIG.APP.NAME}, você concorda com os seguintes termos:</p>
                
                <h4 class="font-semibold text-white mb-2">1. Uso do Serviço</h4>
                <p class="mb-4">O serviço é destinado apenas para maiores de 18 anos...</p>
                
                <h4 class="font-semibold text-white mb-2">2. Responsabilidades</h4>
                <p class="mb-4">O usuário é responsável por suas apostas...</p>
                
                <h4 class="font-semibold text-white mb-2">3. Pagamentos</h4>
                <p class="mb-4">Todos os pagamentos são processados via PIX...</p>
            </div>
        `);
    },

    /**
     * Show privacy policy
     */
    showPrivacy() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Política de Privacidade</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto text-sm text-gray-400">
                <p class="mb-4">Sua privacidade é importante para nós.</p>
                
                <h4 class="font-semibold text-white mb-2">Dados Coletados</h4>
                <p class="mb-4">Coletamos apenas os dados necessários para o funcionamento do serviço...</p>
                
                <h4 class="font-semibold text-white mb-2">Uso dos Dados</h4>
                <p class="mb-4">Seus dados são utilizados apenas para processar apostas e pagamentos...</p>
                
                <h4 class="font-semibold text-white mb-2">Segurança</h4>
                <p class="mb-4">Utilizamos criptografia de ponta a ponta...</p>
            </div>
        `);
    },

    /**
     * Logout
     */
    logout() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Sair</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="text-center py-4">
                <div class="w-20 h-20 bg-danger/20 rounded-full flex-center mx-auto mb-4">
                    <i class="fas fa-sign-out-alt text-4xl text-danger"></i>
                </div>
                <p class="text-gray-400 mb-6">Tem certeza que deseja sair?</p>

                <div class="flex gap-3">
                    <button class="btn btn-secondary flex-1" onclick="closeModal()">
                        Cancelar
                    </button>
                    <button class="btn btn-danger flex-1" onclick="MenuPage.confirmLogout()">
                        Sair
                    </button>
                </div>
            </div>
        `);
    },

    /**
     * Confirm logout
     */
    confirmLogout() {
        Storage.clear();
        Components.closeModal();
        Components.showToast('Você saiu da conta', 'success');
        
        // Reinitialize app
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
};
