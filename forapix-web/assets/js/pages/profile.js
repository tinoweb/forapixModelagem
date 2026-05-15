/**
 * ForaPix - Página de Perfil e Configurações
 * Gerenciamento de perfil do usuário e configurações do app
 */

const ProfilePage = {
    currentTab: 'profile',
    editingProfile: false,
    authTab: 'login',

    render(params = {}) {
        this.currentTab = params.tab || 'profile';
        this.editingProfile = false;

        if (!Storage.isLoggedIn()) {
            return this.renderAuthPage();
        }

        return `
            <div class="page-enter p-4">
                <!-- Tabs de navegação -->
                <div class="profile-tabs mb-6">
                    <button class="profile-tab ${this.currentTab === 'profile' ? 'active' : ''}" data-tab="profile" onclick="ProfilePage.switchTab('profile')">
                        <i class="fas fa-user"></i>
                        <span>Perfil</span>
                    </button>
                    <button class="profile-tab ${this.currentTab === 'settings' ? 'active' : ''}" data-tab="settings" onclick="ProfilePage.switchTab('settings')">
                        <i class="fas fa-cog"></i>
                        <span>Configurações</span>
                    </button>
                    <button class="profile-tab ${this.currentTab === 'security' ? 'active' : ''}" data-tab="security" onclick="ProfilePage.switchTab('security')">
                        <i class="fas fa-shield-alt"></i>
                        <span>Segurança</span>
                    </button>
                </div>

                <!-- Conteúdo das tabs -->
                <div id="profileContent">
                    ${this.renderCurrentTab()}
                </div>
            </div>
        `;
    },

    init() {
        this.bindEvents();
        if (Storage.isLoggedIn()) this._refreshProfileData();
    },

    async _refreshProfileData() {
        try {
            const res = await API.request('/auth/profile');
            if (res && res.success && res.data) {
                const fresh = res.data;
                const stored = Storage.getUser() || {};
                Storage.setUser({ ...stored, ...fresh });
                Storage.setBalance(parseFloat(fresh.balance) || 0);
                // Re-renderiza aba de perfil se estiver ativa
                const profileTab = document.getElementById('profileTabContent');
                if (profileTab) profileTab.innerHTML = this.renderProfileTab();
                // Atualiza saldo do header
                App.updateBalance && App.updateBalance();
            }
        } catch (_) {}
    },

    bindEvents() {
        // Eventos já estão configurados nos onclicks
    },

    switchTab(tab) {
        this.currentTab = tab;
        this.editingProfile = false;

        // Atualizar UI das tabs
        document.querySelectorAll('.profile-tab').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });

        // Atualizar conteúdo
        const content = document.getElementById('profileContent');
        if (content) {
            content.innerHTML = this.renderCurrentTab();
        }
    },

    renderCurrentTab() {
        switch (this.currentTab) {
            case 'profile':
                return this.renderProfileTab();
            case 'settings':
                return this.renderSettingsTab();
            case 'security':
                return this.renderSecurityTab();
            default:
                return this.renderProfileTab();
        }
    },

    /* ── AUTH (não logado) ─────────────────────────────────── */
    renderAuthPage() {
        return `
        <div class="page-enter p-4 max-w-sm mx-auto">
            <div class="text-center mb-8 mt-4">
                <div class="w-16 h-16 rounded-2xl bg-accent/20 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-leaf text-3xl text-accent"></i>
                </div>
                <h2 class="text-2xl font-bold">ForaPix</h2>
                <p class="text-gray-400 text-sm">Apostas esportivas ao vivo</p>
            </div>

            <div class="flex gap-2 mb-6">
                <button id="authTabLogin" onclick="ProfilePage.switchAuthTab('login')" class="flex-1 py-2 rounded-xl text-sm font-semibold transition ${this.authTab === 'login' ? 'bg-accent text-black' : 'bg-white/5 text-gray-400'}">
                    Entrar
                </button>
                <button id="authTabRegister" onclick="ProfilePage.switchAuthTab('register')" class="flex-1 py-2 rounded-xl text-sm font-semibold transition ${this.authTab === 'register' ? 'bg-accent text-black' : 'bg-white/5 text-gray-400'}">
                    Criar conta
                </button>
            </div>

            <div id="authFormContent">
                ${this.authTab === 'login' ? this.renderLoginForm() : this.renderRegisterForm()}
            </div>
        </div>`;
    },

    switchAuthTab(tab) {
        this.authTab = tab;
        const el = document.getElementById('authFormContent');
        if (el) el.innerHTML = tab === 'login' ? this.renderLoginForm() : this.renderRegisterForm();
        const tl = document.getElementById('authTabLogin');
        const tr = document.getElementById('authTabRegister');
        if (tl) { tl.className = `flex-1 py-2 rounded-xl text-sm font-semibold transition ${tab==='login'?'bg-accent text-black':'bg-white/5 text-gray-400'}`; }
        if (tr) { tr.className = `flex-1 py-2 rounded-xl text-sm font-semibold transition ${tab==='register'?'bg-accent text-black':'bg-white/5 text-gray-400'}`; }
    },

    renderLoginForm() {
        return `
        <div class="space-y-4">
            <div>
                <label class="input-label">Email</label>
                <input type="email" id="loginEmail" class="input-field" placeholder="seu@email.com" autocomplete="email">
            </div>
            <div>
                <label class="input-label">Senha</label>
                <input type="password" id="loginPassword" class="input-field" placeholder="Sua senha" autocomplete="current-password">
            </div>
            <button onclick="ProfilePage.doLogin()" class="btn btn-primary w-full" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
            <p id="loginError" class="text-red-400 text-sm text-center hidden"></p>
        </div>`;
    },

    renderRegisterForm() {
        return `
        <div class="space-y-4">
            <div>
                <label class="input-label">Nome completo</label>
                <input type="text" id="regName" class="input-field" placeholder="Seu nome">
            </div>
            <div>
                <label class="input-label">Email</label>
                <input type="email" id="regEmail" class="input-field" placeholder="seu@email.com" autocomplete="email">
            </div>
            <div>
                <label class="input-label">Senha</label>
                <input type="password" id="regPassword" class="input-field" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
            </div>
            <button onclick="ProfilePage.doRegister()" class="btn btn-primary w-full" id="regBtn">
                <i class="fas fa-user-plus"></i> Criar conta
            </button>
            <p id="regError" class="text-red-400 text-sm text-center hidden"></p>
        </div>`;
    },

    async doLogin() {
        const email    = document.getElementById('loginEmail')?.value?.trim();
        const password = document.getElementById('loginPassword')?.value;
        const errEl    = document.getElementById('loginError');
        const btn      = document.getElementById('loginBtn');
        if (!email || !password) { this._showAuthError(errEl, 'Preencha email e senha.'); return; }
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
        try {
            const res = await API.login({ email, password });
            if (res.success) {
                App.handleLogin(res.data);
                App.navigateTo('home');
            } else {
                this._showAuthError(errEl, res.message || 'Credenciais inválidas');
            }
        } catch (e) {
            this._showAuthError(errEl, e.message || 'Erro ao conectar');
        } finally {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Entrar';
        }
    },

    async doRegister() {
        const name     = document.getElementById('regName')?.value?.trim();
        const email    = document.getElementById('regEmail')?.value?.trim();
        const password = document.getElementById('regPassword')?.value;
        const errEl    = document.getElementById('regError');
        const btn      = document.getElementById('regBtn');
        if (!name || !email || !password) { this._showAuthError(errEl, 'Preencha todos os campos.'); return; }
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
        try {
            const res = await API.register({ name, email, password, password_confirmation: password });
            if (res.success) {
                App.handleLogin(res.data);
                App.navigateTo('home');
            } else {
                this._showAuthError(errEl, res.message || 'Erro ao criar conta');
            }
        } catch (e) {
            this._showAuthError(errEl, e.message || 'Erro ao conectar');
        } finally {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-user-plus"></i> Criar conta';
        }
    },

    _showAuthError(el, msg) {
        if (!el) return;
        el.textContent = msg;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 5000);
    },

    /* ── PERFIL LOGADO ──────────────────────────────────────── */
    renderProfileTab() {
        const user = Storage.getUser();

        if (this.editingProfile) {
            return this.renderEditProfileForm(user);
        }

        return `
            <!-- Card de perfil -->
            <div class="profile-card mb-6">
                <div class="profile-avatar">
                    <span class="profile-avatar-text">${Utils.getInitials(user.name)}</span>
                </div>
                <h2 class="profile-name">${user.name}</h2>
                <p class="profile-email">${user.email}</p>
                <button class="btn btn-secondary mt-4" onclick="ProfilePage.toggleEditProfile()">
                    <i class="fas fa-edit"></i> Editar perfil
                </button>
            </div>

            <!-- Informações da conta -->
            <div class="settings-section mb-6">
                <h3 class="settings-section-title">Informações da conta</h3>
                <div class="settings-item">
                    <div class="settings-item-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Email</p>
                        <p class="settings-item-value">${user.email}</p>
                    </div>
                </div>
                <div class="settings-item">
                    <div class="settings-item-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Membro desde</p>
                        <p class="settings-item-value">${user.created_at ? Utils.formatDate(user.created_at) : 'N/A'}</p>
                    </div>
                </div>
                <div class="settings-item">
                    <div class="settings-item-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Saldo</p>
                        <p id="profileBalance" class="settings-item-value text-success font-bold">${Utils.formatCurrency(Storage.getBalance(), true)}</p>
                    </div>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="settings-section">
                <h3 class="settings-section-title">Estatísticas</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <p class="stat-value">${user.total_bets ?? Storage.getBets().length}</p>
                        <p class="stat-label">Apostas</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-value">${user.total_deposited != null ? Utils.formatCurrency(user.total_deposited) : Storage.getTransactions().length}</p>
                        <p class="stat-label">Depositado</p>
                    </div>
                </div>
            </div>
        `;
    },

    renderEditProfileForm(user) {
        return `
            <div class="settings-section">
                <h3 class="settings-section-title mb-4">Editar perfil</h3>
                <div class="input-group mb-4">
                    <label class="input-label">Nome</label>
                    <input type="text" id="profileName" class="input-field" value="${user.name}" placeholder="Seu nome">
                </div>
                <div class="input-group mb-4">
                    <label class="input-label">Email</label>
                    <input type="email" id="profileEmail" class="input-field" value="${user.email}" placeholder="seu@email.com">
                </div>
                <div class="input-group mb-6">
                    <label class="input-label">Telefone</label>
                    <input type="tel" id="profilePhone" class="input-field" placeholder="(00) 00000-0000">
                </div>
                <div class="flex gap-3">
                    <button class="btn btn-secondary flex-1" onclick="ProfilePage.toggleEditProfile()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button class="btn btn-primary flex-1" onclick="ProfilePage.saveProfile()">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </div>
        `;
    },

    renderSettingsTab() {
        const settings = Storage.getSettings();
        const theme = settings.theme || 'dark';
        const notifications = settings.notifications !== false;

        return `
            <div class="settings-section mb-6">
                <h3 class="settings-section-title">Aparência</h3>
                <div class="settings-item">
                    <div class="settings-item-icon">
                        <i class="fas fa-moon"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Tema</p>
                        <p class="settings-item-value">${theme === 'dark' ? 'Escuro' : 'Claro'}</p>
                    </div>
                    <button class="settings-item-action" onclick="ProfilePage.toggleTheme()">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="settings-section mb-6">
                <h3 class="settings-section-title">Notificações</h3>
                <div class="settings-item">
                    <div class="settings-item-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Notificações</p>
                        <p class="settings-item-value">${notifications ? 'Ativadas' : 'Desativadas'}</p>
                    </div>
                    <button class="settings-item-action" onclick="ProfilePage.toggleNotifications()">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="settings-section mb-6">
                <h3 class="settings-section-title">Idioma</h3>
                <div class="settings-item">
                    <div class="settings-item-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Idioma</p>
                        <p class="settings-item-value">Português (Brasil)</p>
                    </div>
                    <button class="settings-item-action">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="settings-section">
                <h3 class="settings-section-title">Sobre</h3>
                <div class="settings-item">
                    <div class="settings-item-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Versão</p>
                        <p class="settings-item-value">${Config.APP.VERSION}</p>
                    </div>
                </div>
            </div>
        `;
    },

    renderSecurityTab() {
        return `
            <div class="settings-section mb-6">
                <h3 class="settings-section-title">Segurança da conta</h3>
                <div class="settings-item" onclick="ProfilePage.showChangePassword()">
                    <div class="settings-item-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Alterar senha</p>
                        <p class="settings-item-value text-sm text-gray-400">Atualize sua senha regularmente</p>
                    </div>
                    <button class="settings-item-action">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="settings-item">
                    <div class="settings-item-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Autenticação 2FA</p>
                        <p class="settings-item-value text-sm text-gray-400">Proteção extra para sua conta</p>
                    </div>
                    <button class="settings-item-action">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="settings-section mb-6">
                <h3 class="settings-section-title">Privacidade</h3>
                <div class="settings-item">
                    <div class="settings-item-icon">
                        <i class="fas fa-eye-slash"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label">Perfil privado</p>
                        <p class="settings-item-value text-sm text-gray-400">Ocultar suas atividades</p>
                    </div>
                    <button class="settings-item-action">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="settings-section mb-6">
                <button onclick="App.logout()" class="btn w-full" style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.25);">
                    <i class="fas fa-sign-out-alt"></i> Sair da conta
                </button>
            </div>

            <div class="settings-section">
                <h3 class="settings-section-title">Zona de perigo</h3>
                <div class="settings-item" onclick="ProfilePage.showDeleteAccount()">
                    <div class="settings-item-icon text-danger">
                        <i class="fas fa-trash"></i>
                    </div>
                    <div class="settings-item-content">
                        <p class="settings-item-label text-danger">Excluir conta</p>
                        <p class="settings-item-value text-sm text-gray-400">Esta ação é irreversível</p>
                    </div>
                    <button class="settings-item-action">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        `;
    },

    toggleEditProfile() {
        this.editingProfile = !this.editingProfile;
        const content = document.getElementById('profileContent');
        if (content) {
            content.innerHTML = this.renderCurrentTab();
        }
    },

    saveProfile() {
        const name = document.getElementById('profileName').value;
        const email = document.getElementById('profileEmail').value;
        const phone = document.getElementById('profilePhone').value;

        if (!name || !email) {
            Components.showToast('Preencha todos os campos obrigatórios', 'warning');
            return;
        }

        const user = Storage.getUser();
        user.name = name;
        user.email = email;
        if (phone) user.phone = phone;

        Storage.setUser(user);
        this.editingProfile = false;

        const content = document.getElementById('profileContent');
        if (content) {
            content.innerHTML = this.renderCurrentTab();
        }

        Components.showToast('Perfil atualizado com sucesso!', 'success');
    },

    toggleTheme() {
        const settings = Storage.getSettings();
        settings.theme = settings.theme === 'dark' ? 'light' : 'dark';
        Storage.setSettings(settings);

        const content = document.getElementById('profileContent');
        if (content) {
            content.innerHTML = this.renderCurrentTab();
        }

        Components.showToast(`Tema ${settings.theme === 'dark' ? 'escuro' : 'claro'} ativado!`, 'success');
    },

    toggleNotifications() {
        const settings = Storage.getSettings();
        settings.notifications = !settings.notifications;
        Storage.setSettings(settings);

        const content = document.getElementById('profileContent');
        if (content) {
            content.innerHTML = this.renderCurrentTab();
        }

        Components.showToast(`Notificações ${settings.notifications ? 'ativadas' : 'desativadas'}!`, 'success');
    },

    showChangePassword() {
        Components.showModal(`
            <div class="modal-header">
                <h3>Alterar senha</h3>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="input-group mb-4">
                <label class="input-label">Senha atual</label>
                <input type="password" class="input-field" placeholder="••••••••">
            </div>
            <div class="input-group mb-4">
                <label class="input-label">Nova senha</label>
                <input type="password" class="input-field" placeholder="••••••••">
            </div>
            <div class="input-group mb-6">
                <label class="input-label">Confirmar nova senha</label>
                <input type="password" class="input-field" placeholder="••••••••">
            </div>
            <div class="flex gap-3">
                <button class="btn btn-secondary flex-1" onclick="closeModal()">Cancelar</button>
                <button class="btn btn-primary flex-1" onclick="ProfilePage.changePassword()">Alterar</button>
            </div>
        `);
    },

    changePassword() {
        Components.closeModal();
        Components.showToast('Senha alterada com sucesso!', 'success');
    },

    showDeleteAccount() {
        Components.showModal(`
            <div class="modal-header">
                <h3 class="text-danger">Excluir conta</h3>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-danger/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-3xl text-danger"></i>
                </div>
                <p class="text-gray-300 mb-2">Tem certeza que deseja excluir sua conta?</p>
                <p class="text-sm text-gray-500">Esta ação é irreversível e todos os seus dados serão perdidos.</p>
            </div>
            <div class="input-group mb-4">
                <label class="input-label">Digite "EXCLUIR" para confirmar</label>
                <input type="text" id="deleteConfirm" class="input-field" placeholder="EXCLUIR">
            </div>
            <div class="flex gap-3">
                <button class="btn btn-secondary flex-1" onclick="closeModal()">Cancelar</button>
                <button class="btn btn-danger flex-1" onclick="ProfilePage.deleteAccount()">Excluir conta</button>
            </div>
        `);
    },

    deleteAccount() {
        const confirmText = document.getElementById('deleteConfirm').value;
        if (confirmText !== 'EXCLUIR') {
            Components.showToast('Digite "EXCLUIR" para confirmar', 'warning');
            return;
        }

        Components.closeModal();
        Storage.logout();
        Components.showToast('Conta excluída com sucesso', 'success');
        App.navigateTo('home');
    }
};
