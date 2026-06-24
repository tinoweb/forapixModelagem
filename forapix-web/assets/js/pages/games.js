/**
 * JrPix - Página de Jogos
 * Listagem de jogos disponíveis com filtros por esporte
 */

const GamesPage = {
    selectedSport: null,
    games: [],
    sports: Config.SPORTS,

    render(params = {}) {
        this.selectedSport = params.sport_id || params.sportId || null;
        
        return `
            <div class="page-enter p-4">
                <!-- Filtros de esporte -->
                <div class="mb-6">
                    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                        <button class="sport-filter-btn ${!this.selectedSport ? 'active' : ''}" data-sport="all" onclick="GamesPage.filterBySport('all')">
                            <i class="fas fa-th-large"></i>
                            <span>Todos</span>
                        </button>
                        ${this.sports.map(sport => `
                            <button class="sport-filter-btn ${this.selectedSport === sport.id ? 'active' : ''}" data-sport="${sport.id}" onclick="GamesPage.filterBySport(${sport.id})">
                                <i class="fas ${sport.icon}"></i>
                                <span>${sport.name}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>

                <!-- Lista de jogos -->
                <div id="gamesList">
                    ${this.renderSkeletons()}
                </div>
            </div>
        `;
    },

    init() {
        this.loadGames();
    },

    async loadGames() {
        const container = document.getElementById('gamesList');
        if (!container) return;

        container.innerHTML = this.renderSkeletons();

        try {
            const filters = {};
            if (this.selectedSport) {
                filters.sport_id = this.selectedSport;
            }

            const response = await API.getGames(filters);
            this.games = Array.isArray(response.data?.data)
                ? response.data.data
                : (Array.isArray(response.data) ? response.data : []);

            if (!this.games.length) {
                container.innerHTML = this.renderEmptyState();
                return;
            }

            container.innerHTML = this.renderGamesList();
        } catch (error) {
            console.error('Erro ao carregar jogos:', error);
            container.innerHTML = this.renderEmptyState();
        }
    },

    filterBySport(sportId) {
        this.selectedSport = sportId === 'all' ? null : sportId;
        
        // Atualizar UI dos botões
        document.querySelectorAll('.sport-filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.sport === String(sportId));
        });

        this.loadGames();
    },

    renderGamesList() {
        const filteredGames = this.selectedSport
            ? this.games.filter(game => game.sport_id === this.selectedSport || game.sport?.id === this.selectedSport)
            : this.games;

        if (!filteredGames.length) {
            return this.renderEmptyState();
        }

        return `
            <div class="games-list">
                ${filteredGames.map(game => this.renderGameCard(game)).join('')}
            </div>
        `;
    },

    renderGameCard(game) {
        const image = Utils.resolveImage(game.image, 'assets/images/sinuca-placeholder.svg');
        const sportIcon = this.getSportIcon(game.sport_id || game.sport?.id);
        const sportName = game.sport?.name || this.getSportName(game.sport_id);
        const matchCount = game.match_count || game.matches_count || 0;

        return `
            <div class="game-item-card" onclick="App.navigateTo('matches', { game_id: ${game.id}, gameId: ${game.id} })">
                <div class="game-item-image">
                    <img src="${image}" alt="${game.name}" onerror="this.src='assets/images/sinuca-placeholder.svg'">
                    <div class="game-item-overlay"></div>
                    <div class="game-item-badge">
                        <i class="fas ${sportIcon}"></i>
                        <span>${sportName}</span>
                    </div>
                </div>
                <div class="game-item-content">
                    <h3 class="game-item-title">${game.name}</h3>
                    <div class="game-item-meta">
                        <span class="game-item-matches">
                            <i class="fas fa-trophy"></i>
                            ${matchCount} ${matchCount === 1 ? 'partida' : 'partidas'}
                        </span>
                        <span class="game-item-status ${game.is_active ? 'active' : 'inactive'}">
                            <span class="w-2 h-2 rounded-full ${game.is_active ? 'bg-green-400' : 'bg-gray-500'}"></span>
                            ${game.is_active ? 'Ativo' : 'Inativo'}
                        </span>
                    </div>
                </div>
            </div>
        `;
    },

    renderSkeletons() {
        return `
            <div class="games-skeleton">
                ${Array.from({ length: 6 }).map(() => `
                    <div class="skeleton-game"></div>
                `).join('')}
            </div>
        `;
    },

    renderEmptyState() {
        return `
            <div class="text-center py-16">
                <div class="w-20 h-20 bg-secondary rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-gamepad text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Nenhum jogo encontrado</h3>
                <p class="text-gray-400 text-sm mb-4">Tente selecionar outro esporte ou volte mais tarde.</p>
                <button class="btn btn-secondary" onclick="GamesPage.filterBySport('all')">
                    <i class="fas fa-th-large"></i> Ver todos
                </button>
            </div>
        `;
    },

    getSportIcon(sportId) {
        const sport = this.sports.find(s => s.id === sportId);
        return sport ? sport.icon : 'fa-gamepad';
    },

    getSportName(sportId) {
        const sport = this.sports.find(s => s.id === sportId);
        return sport ? sport.name : 'Jogo';
    }
};
