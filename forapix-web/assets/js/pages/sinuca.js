/**
 * JrPix - Página de partida (Sinuca / Jogos)
 * Layout padronizado conforme site de referência
 */

const SinucaPage = {
    matchId: null,
    currentMatch: null,
    betOptions: [],
    selectedBet: null,
    betAmount: 0,

    pollingTimer: null,

    render(params = {}) {
        this.matchId = params.matchId || params.id || this.matchId;
        this.betOptions = [];
        this.selectedBet = null;
        this.betAmount = 0;
        this._stopPolling();
        return `<div class="page-enter" id="sinucaPage">${this.renderLoading()}</div>`;
    },

    init() {
        this.loadMatch();
        this._startPolling();
    },

    _startPolling() {
        this._stopPolling();
        this.pollingTimer = setInterval(() => this._pollMatchStatus(), 4000);
    },

    _stopPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
    },

    async _pollMatchStatus() {
        if (App.currentPage !== 'sinuca' || !document.getElementById('sinucaPage') || !this.matchId) {
            this._stopPolling();
            return;
        }

        try {
            const response = await API.getMatch(this.matchId);
            if (response.success && response.data) {
                const newMatch = response.data;
                const oldMatch = this.currentMatch;

                if (oldMatch) {
                    // Alert if betting_locked status changed
                    if (oldMatch.betting_locked !== newMatch.betting_locked) {
                        if (newMatch.betting_locked) {
                            Components.showToast('⚠️ As apostas para esta partida foram TRANCADAS!', 'warning');
                        } else {
                            Components.showToast('🟢 As apostas para esta partida foram LIBERADAS!', 'success');
                        }
                    }
                    
                    // Alert if live_betting_open status changed (if not locked)
                    if (oldMatch.live_betting_open !== newMatch.live_betting_open && !newMatch.betting_locked) {
                        if (newMatch.live_betting_open) {
                            Components.showToast('⚡ Apostas ao vivo ABERTAS!', 'success');
                        } else {
                            Components.showToast('🔒 Apostas ao vivo fechadas.', 'info');
                        }
                    }

                    // Alert if score changed
                    if (oldMatch.first_player_score !== newMatch.first_player_score || oldMatch.second_player_score !== newMatch.second_player_score) {
                        Components.showToast(`📊 Placar atualizado: ${newMatch.first_player_score} x ${newMatch.second_player_score}`, 'info');
                    }
                    
                    // Alert if match status changed
                    if (oldMatch.status !== newMatch.status) {
                        const statusLabels = { live: 'ao vivo', finished: 'finalizada', cancelled: 'cancelada' };
                        const lbl = statusLabels[newMatch.status] || newMatch.status;
                        Components.showToast(`📢 Partida está agora ${lbl}!`, 'info');
                    }
                }

                this.currentMatch = newMatch;
                this.betOptions = this.buildBetOptions(newMatch);
                
                // Re-render dynamically without closing open modals
                const container = document.getElementById('sinucaPage');
                if (container) {
                    container.innerHTML = this.renderContent();
                    this.loadMyBetsQuietly();
                }
            }
        } catch (error) {
            console.error('Erro no polling do jogo:', error);
        }
    },

    async loadMyBetsQuietly() {
        const el = document.getElementById('myBetsList');
        if (!el || !this.matchId) return;
        try {
            const res = await API.getMyBetsForMatch(this.matchId);
            const bets = res?.data?.data || res?.data || [];
            if (!bets.length) {
                el.innerHTML = '<div class="match-section-empty">Você ainda não apostou nesta partida</div>';
                return;
            }
            el.innerHTML = bets.map(b => this.renderBetItem(b)).join('');
        } catch (e) {}
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
            this.loadMyBets();
        } catch (error) {
            console.error('Erro ao carregar partida', error);
            container.innerHTML = this.renderError(error.message || 'Erro ao carregar partida.');
        }
    },

    buildBetOptions(match) {
        const p1 = match.first_player || {};
        const p2 = match.second_player || {};
        const stats = match.bet_stats || {};
        
        // Calcular odds baseadas no pool de apostas
        const fpTotal = stats.first_player?.total || 0;
        const spTotal = stats.second_player?.total || 0;
        const totalPool = fpTotal + spTotal;
        
        // Se não há apostas, odds iguais (2.0 que resulta em 1.80 após a taxa de 10%)
        let fpOdds = 2.0, spOdds = 2.0;
        if (totalPool > 0) {
            fpOdds = totalPool / fpTotal;
            spOdds = totalPool / spTotal;
        }
        
        // Aplicar taxa da casa (10%)
        fpOdds = fpOdds * 0.9;
        spOdds = spOdds * 0.9;
        
        // Mínimo de 1.01
        fpOdds = Math.max(1.01, fpOdds);
        spOdds = Math.max(1.01, spOdds);
        
        return [
            {
                type: 'first_player',
                label: p1.name || 'Jogador 1',
                player: p1,
                odds: fpOdds
            },
            {
                type: 'second_player',
                label: p2.name || 'Jogador 2',
                player: p2,
                odds: spOdds
            }
        ];
    },

    canBet(match) {
        if (!match) return false;
        if (match.betting_locked) return false;
        // Se estiver ao vivo, verifica live_betting_open
        if (match.status === 'live') {
            return match.live_betting_open === true;
        }
        // Se estiver finalizada, não pode apostar
        if (match.status === 'finished') return false;
        // Verifica se ainda está dentro do prazo
        if (match.betting_deadline) {
            return new Date(match.betting_deadline) > new Date();
        }
        // Por padrão, permite apostar se não estiver finalizada
        return match.status !== 'finished';
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
                ${this.renderBetsBar(match)}
                ${match.betting_locked ? `
                    <div class="bg-yellow-500/10 border-y border-yellow-500/20 text-yellow-400 text-xs px-4 py-3 text-center flex items-center justify-center gap-2 animate-pulse">
                        <i class="fas fa-lock"></i> <span><strong>Apostas Trancadas:</strong> Novos palpites estão suspensos temporariamente nesta partida.</span>
                    </div>
                ` : ''}
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
        const isLive    = match.status === 'live';
        const sport     = match.game?.sport?.name || 'Sinuca';
        const modality  = match.game?.name || '';
        const title     = match.title || '';
        const s1 = match.first_player_score  ?? 0;
        const s2 = match.second_player_score ?? 0;
        const isLiveBetting = match.status === 'live' && match.live_betting_open;
        const statusLabel = isLiveBetting ? '⚡ Apostas ao vivo' : canBet ? 'Apostas abertas' : 'Apostas encerradas';

        const p1Name = this.breakName(p1.name || 'Jogador 1');
        const p2Name = this.breakName(p2.name || 'Jogador 2');

        return `
        <div class="mh-hero" style="background-image:url('${img}')">
            <div class="mh-overlay">

                <!-- topo: esporte + status -->
                <div class="mh-top">
                    <span class="mh-sport-badge">${sport.toUpperCase()}</span>
                    <span class="mh-status-badge">
                        ${isLive ? '<span class="live-dot"></span>' : '<i class="far fa-clock"></i>'}
                        ${statusLabel}
                    </span>
                </div>

                <!-- modalidade centralizada -->
                ${modality ? `<div class="mh-modality-pill">${modality}</div>` : ''}

                <!-- jogadores + placar -->
                <div class="mh-players-row">
                    <div class="mh-player-col">
                        <div class="mh-av mh-av--p1">
                            <img src="${Utils.getPlayerPhoto(p1)}" alt="${p1Name}" onerror="this.src='assets/images/jogador1.png'">
                        </div>
                        <span class="mh-name">${p1Name}</span>
                    </div>

                    <div class="mh-score-col">
                        <span class="mh-num">${s1}</span>
                        <div class="mh-vs-circle">vs</div>
                        <span class="mh-num">${s2}</span>
                    </div>

                    <div class="mh-player-col">
                        <div class="mh-av mh-av--p2">
                            <img src="${Utils.getPlayerPhoto(p2)}" alt="${p2Name}" onerror="this.src='assets/images/jogador2.png'">
                        </div>
                        <span class="mh-name">${p2Name}</span>
                    </div>
                </div>

                <!-- título abaixo -->
                ${title && title !== modality ? `<div class="mh-title-pill">${title.toUpperCase()}</div>` : ''}
            </div>
        </div>`;
    },

    /* ── BARRA DE APOSTAS CASADAS (pool real) ───────────────── */
    renderBetsBar(match) {
        const s     = match.bet_stats || {};
        const fp    = s.first_player  || { total: 0, matched: 0, unmatched: 0, count: 0 };
        const sp    = s.second_player || { total: 0, matched: 0, unmatched: 0, count: 0 };
        const pool  = s.total_matched_pool || 0;
        const p1    = match.first_player  || {};
        const p2    = match.second_player || {};

        const totalAll = fp.total + sp.total;
        const pFp = totalAll > 0 ? Math.round((fp.total / totalAll) * 100) : 50;
        const pSp = 100 - pFp;

        const fmtR = v => 'R$ ' + parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        return `
        <div class="mh-bets-bar">
            <div class="flex justify-between text-xs mb-1 px-1">
                <span class="text-white font-semibold">${(p1.name || 'J1').split(' ')[0]}</span>
                <span class="text-gray-400 text-[10px]">⚔️ Pool casado: ${fmtR(pool)}</span>
                <span class="text-white font-semibold">${(p2.name || 'J2').split(' ')[0]}</span>
            </div>
            <div class="mh-bets-track" style="border-radius:8px;overflow:hidden;display:flex;height:10px;gap:1px">
                <div style="width:${pFp}%;background:#34d399;transition:width .4s"></div>
                <div style="width:${pSp}%;background:#f59e0b;transition:width .4s"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-400 mt-1 px-1">
                <span>
                    <span class="text-emerald-400 font-semibold">${fmtR(fp.total)}</span>
                    <span class="text-gray-600 ml-1">(${fmtR(fp.matched)} casado)</span>
                </span>
                <span>
                    <span class="text-gray-600 mr-1">(${fmtR(sp.matched)} casado)</span>
                    <span class="text-yellow-400 font-semibold">${fmtR(sp.total)}</span>
                </span>
            </div>
        </div>`;
    },

    /* ── BARRA DE ODDS ───────────────────────────────────────── */
    renderOddsBar(_match) { return ''; },

    _renderOddsBarLegacy(match) {
        const o1 = 0, o2 = 0;
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
        const matchStart = this.formatDate(match.match_start, 'datetime');
        const matchEnd   = this.formatDate(match.match_end, 'datetime');

        return `
        <div class="md-card">
            <h3 class="md-title">Detalhes da partida</h3>

            <div class="md-item">
                <div class="md-item-header">
                    <div class="md-item-icon"><i class="fas fa-gamepad"></i></div>
                    <span class="md-item-label">ESPORTE</span>
                </div>
                <span class="md-item-value">${sport}</span>
            </div>

            <div class="md-item">
                <div class="md-item-header">
                    <div class="md-item-icon"><i class="fas fa-circle-dot"></i></div>
                    <span class="md-item-label">MODALIDADE</span>
                </div>
                <span class="md-item-value">${modality}</span>
            </div>

            <div class="md-item">
                <div class="md-item-header">
                    <div class="md-item-icon"><i class="fas fa-circle-info"></i></div>
                    <span class="md-item-label">DETALHES DO JOGO</span>
                </div>
                <span class="md-item-value">${detail}</span>
            </div>

            ${info ? `
            <div class="md-item">
                <div class="md-item-header">
                    <div class="md-item-icon"><i class="fas fa-circle-info"></i></div>
                    <span class="md-item-label">INFORMAÇÕES</span>
                </div>
                <span class="md-item-value">${info}</span>
            </div>` : ''}

            <div class="md-item">
                <div class="md-item-header">
                    <div class="md-item-icon"><i class="fas fa-calendar-days"></i></div>
                    <span class="md-item-label">HORA DE INÍCIO DO JOGO</span>
                </div>
                <span class="md-item-value">${matchStart}</span>
            </div>

            <div class="md-item">
                <div class="md-item-header">
                    <div class="md-item-icon"><i class="fas fa-clock"></i></div>
                    <span class="md-item-label">HORA DE TÉRMINO DO JOGO</span>
                </div>
                <span class="md-item-value">${matchEnd}</span>
            </div>
        </div>`;
    },

    /* ── BOTÃO APOSTAR ───────────────────────────────────────── */
    renderApostarBtn(match) {
        const canBet       = this.canBet(match);
        const isLiveBetting = match.status === 'live' && match.live_betting_open;
        const btnClass  = canBet ? (isLiveBetting ? 'apostar-btn apostar-btn--live' : '') : 'apostar-btn--disabled';
        
        let btnLabel;
        if (match.betting_locked) {
            btnLabel = `<i class="fas fa-lock text-yellow-400"></i> Apostas trancadas`;
        } else if (isLiveBetting) {
            btnLabel = `<i class="fas fa-bolt"></i> APOSTAR AO VIVO`;
        } else if (canBet) {
            btnLabel = `<i class="fas fa-check-circle"></i> APOSTAR`;
        } else {
            btnLabel = `<i class="fas fa-lock"></i> Apostas encerradas`;
        }

        return `
        <div class="px-4 pb-2">
            ${isLiveBetting ? '<p class="text-center text-xs text-green-400 mb-2 animate-pulse">⚡ JANELA DE APOSTAS AO VIVO ABERTA</p>' : ''}
            ${match.betting_locked ? '<p class="text-center text-xs text-yellow-400 mb-2 font-bold"><i class="fas fa-lock"></i> APOSTAS TRANCADAS TEMPORARIAMENTE</p>' : ''}
            <button class="apostar-btn ${btnClass}"
                    onclick="SinucaPage.showBettingPanel()"
                    ${canBet ? '' : 'disabled'}>
                ${btnLabel}
            </button>
        </div>`;
    },

    /* ── MINHAS APOSTAS ──────────────────────────────────────── */
    renderMyBets() {
        const user = Storage.getUser();
        if (!user) return '';
        return `
        <div class="match-section-card">
            <div class="match-section-header">
                <span>Minhas apostas</span>
                <button class="match-section-refresh" onclick="SinucaPage.loadMyBets()">
                    <i class="fas fa-rotate-right"></i>
                </button>
            </div>
            <div id="myBetsList"><div class="match-section-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div></div>
        </div>`;
    },

    async loadMyBets() {
        const el = document.getElementById('myBetsList');
        if (!el || !this.matchId) return;
        el.innerHTML = '<div class="match-section-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';
        try {
            const res = await API.getMyBetsForMatch(this.matchId);
            const bets = res?.data?.data || res?.data || [];
            if (!bets.length) {
                el.innerHTML = '<div class="match-section-empty">Você ainda não apostou nesta partida</div>';
                return;
            }
            el.innerHTML = bets.map(b => this.renderBetItem(b)).join('');
        } catch (e) {
            el.innerHTML = '<div class="match-section-empty">Erro ao carregar apostas</div>';
        }
    },

    renderBetItem(bet) {
        const m  = this.currentMatch || bet.match || {};
        const p1 = (m.first_player?.name || m.firstPlayer?.name || bet.match?.first_player?.name || 'Jogador 1').split(' ')[0];
        const p2 = (m.second_player?.name || m.secondPlayer?.name || bet.match?.second_player?.name || 'Jogador 2').split(' ')[0];
        const typeLabels = { first_player: p1, second_player: p2, draw: 'Empate', par: 'Par', impar: 'Ímpar' };

        const betType = bet.bet_type || bet.option || '';
        const label   = typeLabels[betType] || betType || '--';
        const code    = bet.bet_id || bet.id || '--';

        const total    = parseFloat(bet.amount ?? bet.betAmount ?? 0);
        const matched  = parseFloat(bet.matched_amount ?? 0);
        const pending  = total - matched;

        // Badge e breakdown por situação
        let badgeHtml, breakdownHtml = '';

        if (bet.status === 'won') {
            const resultAmt = Utils.formatCurrency(bet.result_amount ?? 0);
            badgeHtml = `<span class="mbi-badge mbi-won">Ganhou</span>`;
            breakdownHtml = `<span class="mbi-matched">Ganho: <strong>R$ ${resultAmt}</strong></span>`;
        } else if (bet.status === 'lost') {
            badgeHtml = `<span class="mbi-badge mbi-lost">Perdeu</span>`;
        } else if (bet.status === 'cancelled' || bet.status === 'refunded') {
            badgeHtml = `<span class="mbi-badge mbi-cancelled">Cancelada</span>`;
        } else {
            // pending — mostra breakdown casado/pendente
            if (matched >= total && total > 0) {
                badgeHtml = `<span class="mbi-badge mbi-confirmed">✓ Confirmada</span>`;
                breakdownHtml = `<span class="mbi-matched">Casado: <strong>R$ ${Utils.formatCurrency(matched)}</strong></span>`;
            } else if (matched > 0) {
                badgeHtml = `<span class="mbi-badge mbi-partial">Parcial</span>`;
                breakdownHtml = `
                    <span class="mbi-matched">✓ Casado: <strong>R$ ${Utils.formatCurrency(matched)}</strong></span>
                    <span class="mbi-unmatched">⏳ Pendente: <strong>R$ ${Utils.formatCurrency(pending)}</strong></span>`;
            } else {
                badgeHtml = `<span class="mbi-badge mbi-pending">Pendente</span>`;
                breakdownHtml = `<span class="mbi-unmatched">⏳ Aguardando casamento</span>`;
            }
        }

        return `
        <div class="mbi-row">
            <div class="mbi-top">
                <span class="mbi-label">${label}</span>
                ${badgeHtml}
            </div>
            <div class="mbi-details">
                <span>Total: <strong>R$ ${Utils.formatCurrency(total)}</strong></span>
                ${breakdownHtml}
            </div>
            <div class="mbi-code">Cód: ${code}</div>
        </div>`;
    },

    refreshMyBets() { this.loadMyBets(); },

    /* ── STREAM ──────────────────────────────────────────────── */
    toEmbedUrl(url) {
        if (!url) return null;
        try {
            const u = new URL(url);
            // youtube.com/watch?v=ID
            if ((u.hostname === 'www.youtube.com' || u.hostname === 'youtube.com') && u.searchParams.get('v')) {
                const id = u.searchParams.get('v');
                return `https://www.youtube.com/embed/${id}?rel=0&modestbranding=1`;
            }
            // youtu.be/ID
            if (u.hostname === 'youtu.be') {
                const id = u.pathname.replace('/', '');
                return `https://www.youtube.com/embed/${id}?rel=0&modestbranding=1`;
            }
            // youtube.com/shorts/ID
            if (u.pathname.startsWith('/shorts/')) {
                const id = u.pathname.replace('/shorts/', '');
                return `https://www.youtube.com/embed/${id}?rel=0&modestbranding=1`;
            }
            // youtube.com/live/ID
            if (u.pathname.startsWith('/live/')) {
                const id = u.pathname.replace('/live/', '').split('?')[0];
                return `https://www.youtube.com/embed/${id}?rel=0&modestbranding=1`;
            }
            // já é embed ou outra plataforma — usa direto
            return url;
        } catch (e) {
            return url;
        }
    },

    renderStream(match) {
        const raw = match.metadata?.stream_url;
        if (raw) {
            const embedUrl = this.toEmbedUrl(raw);
            return `
            <div class="match-stream-card">
                <div class="aspect-video">
                    <iframe src="${embedUrl}" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen class="w-full h-full rounded-xl"></iframe>
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

        const stats = match.bet_stats || {};
        const playerOptions = this.betOptions.map(o => {
            const side  = stats[o.type] || { total: 0, matched: 0 };
            const photo = Utils.getPlayerPhoto(o.player);
            const pFall = o.type === 'first_player' ? 'assets/images/jogador1.png' : 'assets/images/jogador2.png';
            const fmtR  = v => 'R$ ' + parseFloat(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
            return `
                <label class="bp-competitor" onclick="SinucaPage.selectBetOption('${o.type}')">
                    ${photo
                        ? `<img src="${photo}" onerror="this.src='${pFall}'" class="bp-comp-avatar">`
                        : `<div class="bp-comp-avatar bp-comp-avatar--icon"><i class="fas fa-user"></i></div>`}
                    <div style="flex:1">
                        <span class="bp-comp-name">${o.label}</span>
                        <span style="display:block;font-size:10px;color:#9ca3af">${fmtR(side.matched)} casado / ${fmtR(side.total)} total</span>
                    </div>
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
                    <input type="number" id="betAmountInput" class="bp-amount-input" value="0" min="10"
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
                Ganho estimado: <strong id="bpPotentialVal"></strong>
                <span style="font-size:10px;color:#6b7280;display:block;margin-top:2px">*baseado no pool atual &mdash; 10% taxa da casa</span>
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
        if (!this.selectedBet) { Components.showToast('Selecione um competidor.', 'warning'); return; }
        if (this.betAmount <= 0)  { Components.showToast('Informe um valor válido para apostar.', 'warning'); return; }

        // Pré-validação de saldo no frontend
        const balance = Storage.getBalance();
        if (this.betAmount > balance) {
            const faltam = this.betAmount - balance;
            Components.showToast(
                `Não foi possível realizar esta ação: Saldo insuficiente para apostar (Valor R$ ${Utils.formatCurrency(this.betAmount)}, faltam R$ ${Utils.formatCurrency(faltam)})`,
                'error'
            );
            return;
        }

        const btn = document.getElementById('bpConfirmBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...'; }

        try {
            const betData = {
                matchId: this.currentMatch.hash_id || this.currentMatch.id,
                option: this.selectedBet.type,
                amount: this.betAmount,
                odds: this.selectedBet.odds,
                potentialWin: this.betAmount * this.selectedBet.odds,
                fighterName: `${this.selectedBet.label} — ${(this.currentMatch.first_player?.name || '').split(' ')[0]} vs ${(this.currentMatch.second_player?.name || '').split(' ')[0]}`
            };
            const result = await API.placeBet(betData);
            Components.closeModal();
            if (result.success) {
                Components.showToast('Aposta realizada com sucesso!', 'success');
                App.updateBalance();
                this.showBetConfirmation(betData);
                this.loadMyBets();
            } else {
                const msg = result.message || result.error || 'Erro ao realizar aposta';
                Components.showToast(msg, 'error');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle"></i> CONFIRMAR'; }
            }
        } catch (e) {
            const msg = e.message || 'Erro ao processar aposta.';
            Components.showToast(msg, 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle"></i> CONFIRMAR'; }
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

    getMatchImage(match) {
        const meta = match.metadata?.banner_image || match.metadata?.banner;
        if (meta) return Utils.resolveImage(meta, 'assets/images/sinuca-game.png');
        if (match.game?.image) return Utils.resolveImage(match.game.image, 'assets/images/sinuca-game.png');
        const isSinuca = match.game?.sport?.slug === 'sinuca' || (match.game?.slug || '').includes('sinuca');
        return isSinuca ? 'assets/images/sinuca-game.png' : 'assets/images/sinuca-placeholder.svg';
    },

    breakName(name) {
        const parts = (name || '').trim().split(' ');
        return parts[0] || name;
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
