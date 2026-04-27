/**
 * ForaPix - Página de partida (Sinuca / Jogos)
 * Layout padronizado conforme site de referência
 */

const SinucaPage = {
    matchId: null,
    currentMatch: null,
    betOptions: [],
    selectedBet: null,
    betAmount: 0,

    render(params = {}) {
        this.matchId = params.matchId || params.id || this.matchId;
        this.betOptions = [];
        this.selectedBet = null;
        this.betAmount = 0;
        return `<div class="page-enter" id="sinucaPage">${this.renderLoading()}</div>`;
    },

    init() {
        this.loadMatch();
    },

    async loadMatch() {
        const container = document.getElementById('sinucaPage');
        if (!this.matchId) { container.innerHTML = this.renderError('Partida não encontrada.'); return; }
        try {
            const response = await API.getMatch(this.matchId);
            if (!response.success || !response.data) throw new Error(response.message || 'Partida não encontrada.');
            this.currentMatch = response.data;
            this.betOptions = this.buildBetOptions(response.data);
            container.innerHTML = this.renderContent();
        } catch (error) {
            console.error('Erro ao carregar partida', error);
            container.innerHTML = this.renderError(error.message || 'Erro ao carregar partida.');
        }
    },

    renderLoading() {
        return `<div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <div class="w-12 h-12 border-4 border-accent border-t-transparent rounded-full animate-spin mb-4"></div>
            <p>Carregando partida...</p></div>`;
    },

    renderError(message) {
        return `<div class="p-6 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-red-500/10 text-red-300 flex items-center justify-center mx-auto">
                <i class="fas fa-triangle-exclamation text-2xl"></i></div>
            <p class="text-gray-300">${message}</p>
            <button class="btn btn-primary w-full" onclick="App.goBack()"><i class="fas fa-arrow-left"></i> Voltar</button></div>`;
    },

    renderContent() {
        const match = this.currentMatch;
        return `
            <div class="match-page">
                ${this.renderHero(match)}
                ${this.renderOddsBar(match)}
                <div class="match-page-body">
                    ${this.renderMatchDetails(match)}
                    ${this.renderApostarBtn(match)}
                    ${this.renderMyBets()}
                    ${this.renderStream(match)}
                </div>
            </div>`;
    },

    /* ── HERO ────────────────────────────────────────────────── */
    renderHero(match) {
        const p1  = match.first_player  || {};
        const p2  = match.second_player || {};
        const img = this.getMatchImage(match);
        const canBet    = this.canBet(match);
        const sport     = match.game?.sport?.name || 'Sinuca';
        const modality  = match.game?.name || '';
        const s1 = match.first_player_score  ?? 0;
        const s2 = match.second_player_score ?? 0;
        const statusLabel = canBet ? 'Apostas estão abertas' : 'Apostas bloqueadas até empate';

        return `
        <div class="match-hero" style="background-image:url('${img}')">
            <div class="match-hero-overlay">
                <!-- badges topo -->
                <div class="match-hero-top">
                    <span class="mh-badge-sport">${sport.toUpperCase()}</span>
                    <span class="mh-badge-status"><i class="far fa-clock"></i> ${statusLabel}</span>
                </div>

                <!-- modalidade (centro) -->
                <div class="mh-modality">${modality}</div>

                <!-- jogadores + placar -->
                <div class="mh-players">
                    <div class="mh-player">
                        <div class="mh-avatar">
                            <img src="${Utils.getPlayerPhoto(p1)}" alt="${p1.name || ''}"
                                 onerror="this.src='assets/images/jogador1.png'">
                        </div>
                        <span class="mh-player-name">${this.breakName(p1.name || 'Jogador 1')}</span>
                    </div>

                    <div class="mh-score-area">
                        <span class="mh-score-num">${s1}</span>
                        <span class="mh-vs">vs</span>
                        <span class="mh-score-num">${s2}</span>
                    </div>

                    <div class="mh-player mh-player--right">
                        <div class="mh-avatar">
                            <img src="${Utils.getPlayerPhoto(p2)}" alt="${p2.name || ''}"
                                 onerror="this.src='assets/images/jogador2.png'">
                        </div>
                        <span class="mh-player-name">${this.breakName(p2.name || 'Jogador 2')}</span>
                    </div>
                </div>

                <!-- detalhe do jogo abaixo do placar -->
                <div class="mh-game-detail">${match.title || modality}</div>
            </div>
        </div>`;
    },

    /* ── BARRA DE ODDS ───────────────────────────────────────── */
    renderOddsBar(match) {
        const o1 = parseFloat(match.first_player_odds)  || 0;
        const o2 = parseFloat(match.second_player_odds) || 0;
        if (!o1 || !o2) return '';

        const i1 = 1 / o1, i2 = 1 / o2, tot = i1 + i2;
        const p1 = Math.round((i1 / tot) * 100);
        const p2 = 100 - p1;
        const p1src = Utils.getPlayerPhoto(match.first_player  || {});
        const p2src = Utils.getPlayerPhoto(match.second_player || {});

        return `
        <div class="odds-bar-row">
            <div class="odds-bar-side">
                <img src="${p1src}" onerror="this.src='assets/images/jogador1.png'" class="odds-bar-avatar">
                <span class="odds-bar-pct">${p1}%</span>
            </div>
            <div class="odds-bar-track">
                <div class="odds-bar-seg odds-bar-seg--p1" style="width:${p1}%"></div>
                <div class="odds-bar-seg odds-bar-seg--p2" style="width:${p2}%"></div>
            </div>
            <div class="odds-bar-side odds-bar-side--right">
                <span class="odds-bar-pct">${p2}%</span>
                <img src="${p2src}" onerror="this.src='assets/images/jogador2.png'" class="odds-bar-avatar">
            </div>
        </div>`;
    },

    /* ── DETALHES DA PARTIDA ─────────────────────────────────── */
    renderMatchDetails(match) {
        const canBet   = this.canBet(match);
        const sport    = match.game?.sport?.name || 'Sinuca';
        const modality = match.game?.name        || '--';
        const detail   = match.title             || '--';
        const info     = match.description       || '';
        const deadline = this.formatDate(match.betting_deadline, 'datetime');

        return `
        <div class="md-card">
            <h3 class="md-title">Detalhes da partida</h3>

            <div class="md-row">
                <div class="md-icon"><i class="fas fa-gamepad"></i></div>
                <span class="md-label">ESPORTE</span>
                <span class="md-value">${sport}</span>
            </div>

            <div class="md-row">
                <div class="md-icon"><i class="fas fa-circle-dot"></i></div>
                <span class="md-label">MODALIDADE</span>
                <span class="md-value">${modality}</span>
            </div>

            <div class="md-row">
                <div class="md-icon"><i class="fas fa-circle-info"></i></div>
                <span class="md-label">DETALHES DO JOGO</span>
                <span class="md-value">${detail}</span>
            </div>

            ${info ? `
            <div class="md-row">
                <div class="md-icon"><i class="fas fa-circle-info"></i></div>
                <span class="md-label">INFORMAÇÕES</span>
                <span class="md-value">${info}</span>
            </div>` : ''}

            <div class="md-row">
                <div class="md-icon"><i class="fas fa-calendar-days"></i></div>
                <span class="md-label">PRAZO PARA APOSTAS</span>
                <span class="md-value">
                    <span class="md-status-badge ${canBet ? 'md-status-badge--open' : 'md-status-badge--closed'}">
                        ${canBet ? 'Aberta' : 'Encerrada'}
                    </span>
                    <span class="md-deadline">${deadline}</span>
                </span>
            </div>
        </div>`;
    },

    /* ── BOTÃO APOSTAR ───────────────────────────────────────── */
    renderApostarBtn(match) {
        const canBet = this.canBet(match);
        return `
        <div class="px-4 pb-2">
            <button class="apostar-btn ${canBet ? '' : 'apostar-btn--disabled'}"
                    onclick="SinucaPage.showBettingPanel()"
                    ${canBet ? '' : 'disabled'}>
                ${canBet
                    ? `<i class="fas fa-check-circle"></i> APOSTAR`
                    : `<i class="fas fa-lock"></i> Apostas encerradas`}
            </button>
        </div>`;
    },

    /* ── MINHAS APOSTAS ──────────────────────────────────────── */
    renderMyBets() {
        return `
        <div class="match-section-card">
            <div class="match-section-header">
                <span>Minhas apostas</span>
                <button class="match-section-refresh" onclick="SinucaPage.refreshMyBets()">
                    <i class="fas fa-rotate-right"></i>
                </button>
            </div>
            <div id="myBetsList" class="match-section-empty">Você ainda não apostou</div>
        </div>`;
    },

    /* ── STREAM ──────────────────────────────────────────────── */
    renderStream(match) {
        if (match.metadata?.stream_url) {
            return `
            <div class="match-stream-card">
                <div class="aspect-video">
                    <iframe src="${match.metadata.stream_url}" frameborder="0" allowfullscreen class="w-full h-full rounded-xl"></iframe>
                </div>
            </div>`;
        }
        return `
        <div class="match-stream-card match-stream-empty">
            <i class="fas fa-video-slash text-3xl text-gray-600"></i>
            <p class="text-gray-500 text-sm mt-2">Sem transmissão disponível</p>
        </div>`;
    },

    /* ── PAINEL DE APOSTA (slide-up) ─────────────────────────── */
    showBettingPanel() {
        if (!this.currentMatch || !this.canBet(this.currentMatch)) {
            Components.showToast('Apostas encerradas para esta partida.', 'warning'); return;
        }

        this.selectedBet = null;
        this.betAmount   = 0;
        const match   = this.currentMatch;
        const balance = Storage.getBalance();
        const quickValues = [5, 10, 25, 50, 100, 200];

        const playerOptions = this.betOptions
            .filter(o => ['first_player','second_player','draw'].includes(o.type))
            .map(o => {
                const player = o.type === 'first_player' ? match.first_player
                             : o.type === 'second_player' ? match.second_player : null;
                const photo  = player ? Utils.getPlayerPhoto(player) : '';
                const pFallback = o.type === 'first_player' ? 'assets/images/jogador1.png' : 'assets/images/jogador2.png';
                return `
                <label class="bp-competitor" onclick="SinucaPage.selectBetOption('${o.type}')">
                    ${photo ? `<img src="${photo}" onerror="this.src='${pFallback}'" class="bp-comp-avatar">` : `<div class="bp-comp-avatar bp-comp-avatar--icon"><i class="fas fa-handshake"></i></div>`}
                    <span class="bp-comp-name">${o.label}</span>
                    <span class="bp-comp-odds">${o.odds.toFixed(2)}x</span>
                    <div class="bp-radio" id="radio_${o.type}"></div>
                </label>`;
            }).join('');

        Components.showModal(`
        <div class="bp-panel">
            <div class="bp-header">
                <span class="bp-title">NOVA APOSTA</span>
                <button class="bp-close" onclick="Components.closeModal()"><i class="fas fa-times"></i></button>
            </div>

            <div class="bp-section-label"><span class="bp-step">1</span> ESCOLHA O COMPETIDOR</div>
            <div class="bp-competitors" id="bpCompetitors">${playerOptions}</div>

            <div class="bp-section-label"><span class="bp-step">2</span> DEFINA O VALOR</div>
            <div class="bp-amount-row">
                <button class="bp-amount-btn" onclick="SinucaPage.adjustAmount(-10)">−</button>
                <div class="bp-amount-display">
                    <span class="bp-amount-currency">R$</span>
                    <input type="number" id="betAmountInput" class="bp-amount-input" value="0" min="1"
                           oninput="SinucaPage.onAmountChange(this.value)">
                </div>
                <button class="bp-amount-btn" onclick="SinucaPage.adjustAmount(10)">+</button>
            </div>
            <div class="bp-quick-row">
                <span class="bp-quick-label">ADICIONAR:</span>
                ${quickValues.map(v=>`<button class="bp-quick-val" onclick="SinucaPage.addAmount(${v})">R$ ${v},00</button>`).join('')}
                <button class="bp-quick-clear" onclick="SinucaPage.clearAmount()">LIMPAR</button>
            </div>

            <div id="bpPotential" class="bp-potential hidden">
                Ganho potencial: <strong id="bpPotentialVal"></strong>
            </div>

            <button id="bpConfirmBtn" class="bp-confirm-btn" onclick="SinucaPage.confirmBet()" disabled>
                <i class="fas fa-check-circle"></i> CONFIRMAR APOSTA
            </button>

            <div class="bp-balance">Saldo: <strong>R$ ${Utils.formatCurrency(balance)}</strong></div>
        </div>`);
    },

    selectBetOption(type) {
        document.querySelectorAll('.bp-radio').forEach(r => r.classList.remove('bp-radio--active'));
        document.querySelectorAll('.bp-competitor').forEach(l => l.classList.remove('bp-competitor--active'));
        const radio = document.getElementById(`radio_${type}`);
        if (radio) {
            radio.classList.add('bp-radio--active');
            radio.closest('.bp-competitor')?.classList.add('bp-competitor--active');
        }
        this.selectedBet = this.betOptions.find(o => o.type === type) || null;
        this.validateBetForm();
        this.updatePotential();
    },

    adjustAmount(delta) {
        const input = document.getElementById('betAmountInput');
        if (!input) return;
        let val = parseFloat(input.value) || 0;
        val = Math.max(0, val + delta);
        input.value = val;
        this.betAmount = val;
        this.updatePotential();
        this.validateBetForm();
    },

    addAmount(value) {
        const input = document.getElementById('betAmountInput');
        if (!input) return;
        let val = (parseFloat(input.value) || 0) + value;
        input.value = val;
        this.betAmount = val;
        this.updatePotential();
        this.validateBetForm();
    },

    clearAmount() {
        const input = document.getElementById('betAmountInput');
        if (!input) return;
        input.value = 0;
        this.betAmount = 0;
        this.updatePotential();
        this.validateBetForm();
    },

    onAmountChange(val) {
        this.betAmount = parseFloat(val) || 0;
        this.updatePotential();
        this.validateBetForm();
    },

    updatePotential() {
        const el  = document.getElementById('bpPotential');
        const val = document.getElementById('bpPotentialVal');
        if (!el || !val) return;
        if (this.betAmount > 0 && this.selectedBet) {
            const win = this.betAmount * this.selectedBet.odds;
            val.textContent = `R$ ${Utils.formatCurrency(win)}`;
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    },

    validateBetForm() {
        const btn = document.getElementById('bpConfirmBtn');
        if (!btn) return;
        const balance    = Storage.getBalance();
        const validation = Utils.validateBetAmount(this.betAmount, balance);
        const valid      = validation.valid && !!this.selectedBet;
        btn.disabled     = !valid;
        btn.classList.toggle('bp-confirm-btn--disabled', !valid);
    },

    async confirmBet() {
        const validation = Utils.validateBetAmount(this.betAmount, Storage.getBalance());
        if (!validation.valid) { Components.showToast(validation.error, 'error'); return; }
        if (!this.selectedBet) { Components.showToast('Selecione um competidor.', 'warning'); return; }

        try {
            const betData = {
                matchId: this.currentMatch.id,
                option: this.selectedBet.type,
                amount: this.betAmount,
                odds: this.selectedBet.odds,
                potentialWin: this.betAmount * this.selectedBet.odds,
                fighterName: `${this.selectedBet.label} — ${this.currentMatch.first_player?.name || ''} vs ${this.currentMatch.second_player?.name || ''}`
            };
            const result = await API.placeBet(betData);
            Components.closeModal();
            if (result.success) {
                Components.showToast('Aposta realizada com sucesso!', 'success');
                App.updateBalance();
                this.showBetConfirmation(betData);
            } else {
                Components.showToast(result.error || 'Erro ao realizar aposta', 'error');
            }
        } catch (e) {
            Components.showToast('Erro ao processar aposta.', 'error');
        }
    },

    refreshMyBets() {
        const el = document.getElementById('myBetsList');
        if (el) el.innerHTML = '<p class="match-section-empty">Você ainda não apostou</p>';
    },

    showBetConfirmation(betData) {
        Components.showModal(`
        <div class="text-center py-4">
            <div class="w-20 h-20 bg-success/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-4xl text-success"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">Aposta confirmada!</h3>
            <p class="text-gray-400 mb-6">Sua aposta foi registrada com sucesso.</p>
        </div>
        ${Components.renderBetSummary(betData)}
        <div class="mt-6">
            <button class="btn btn-primary w-full" onclick="Components.closeModal()"><i class="fas fa-check"></i> OK</button>
        </div>`);
    },

    /* ── HELPERS ─────────────────────────────────────────────── */
    buildBetOptions(match) {
        const opts = [];
        if (match.first_player_odds)  opts.push({ type:'first_player',  label: match.first_player?.name  || 'Jogador 1', odds: parseFloat(match.first_player_odds) });
        if (match.second_player_odds) opts.push({ type:'second_player', label: match.second_player?.name || 'Jogador 2', odds: parseFloat(match.second_player_odds) });
        if (match.draw_odds)          opts.push({ type:'draw',          label: 'Empate',  odds: parseFloat(match.draw_odds) });
        if (match.par_odds)           opts.push({ type:'par',           label: 'Par',     odds: parseFloat(match.par_odds) });
        if (match.impar_odds)         opts.push({ type:'impar',         label: 'Ímpar',   odds: parseFloat(match.impar_odds) });
        return opts;
    },

    canBet(match) {
        if (typeof match.can_bet !== 'undefined') return !!match.can_bet;
        if (!match.betting_deadline) return false;
        return match.status !== 'finished' && new Date(match.betting_deadline) > new Date();
    },

    getMatchImage(match) {
        const meta = match.metadata?.banner_image || match.metadata?.banner;
        if (meta) return Utils.resolveImage(meta, 'assets/images/sinuca-game.png');
        if (match.game?.image) return Utils.resolveImage(match.game.image, 'assets/images/sinuca-game.png');
        const isSinuca = match.game?.sport?.slug === 'sinuca' || (match.game?.slug || '').includes('sinuca');
        return isSinuca ? 'assets/images/sinuca-game.png' : 'assets/images/sinuca-placeholder.svg';
    },

    breakName(name) {
        const parts = (name || '').trim().split(' ');
        if (parts.length <= 2) return parts.join('<br>');
        return parts[0] + '<br>' + parts.slice(1).join(' ');
    },

    formatDate(date, mode = 'short') {
        if (!date) return '--';
        if (mode === 'datetime') {
            const d = new Date(date);
            return `${d.getDate().toString().padStart(2,'0')}/${(d.getMonth()+1).toString().padStart(2,'0')}/${d.getFullYear()} ${d.getHours().toString().padStart(2,'0')}:${d.getMinutes().toString().padStart(2,'0')}:${d.getSeconds().toString().padStart(2,'0')}`;
        }
        return Utils.formatDate(date, mode === 'time' ? 'time' : 'short');
    }
};
