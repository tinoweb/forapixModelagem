/**
 * FORAPIX - Matches Page
 * Página de listagem de partidas/confrontos
 */

const MatchesPage = {
    currentSportId: 1,
    matches: [],

    /**
     * Render matches page
     * @param {object} params - Page parameters
     */
    render(params = {}) {
        this.currentSportId = params.sportId || 1;
        const sport = CONFIG.SPORTS[this.currentSportId];

        return `
            <div class="page-enter p-4">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-4">
                    <button onclick="App.goBack()" class="w-10 h-10 bg-card-bg rounded-full flex-center">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h2 class="text-xl font-bold">${sport?.name || 'Partidas'}</h2>
                </div>

                <!-- Sport Tabs -->
                ${Components.renderSportTabs(this.currentSportId)}

                <!-- Matches List -->
                <div id="matchesList" class="mt-4">
                    <div class="flex-center py-8">
                        <div class="w-8 h-8 border-4 border-accent border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Initialize matches page
     */
    async init() {
        // Add event listeners to sport tabs
        document.querySelectorAll('.sport-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const sportId = parseInt(e.currentTarget.dataset.sportId);
                this.changeSport(sportId);
            });
        });

        // Load matches
        await this.loadMatches();
    },

    /**
     * Change sport filter
     * @param {number} sportId - Sport ID
     */
    async changeSport(sportId) {
        this.currentSportId = sportId;

        // Update active tab
        document.querySelectorAll('.sport-tab').forEach(tab => {
            tab.classList.toggle('active', parseInt(tab.dataset.sportId) === sportId);
        });

        // Reload matches
        await this.loadMatches();
    },

    /**
     * Load matches from API
     */
    async loadMatches() {
        const listEl = document.getElementById('matchesList');
        
        // Show loading
        listEl.innerHTML = `
            <div class="flex-center py-8">
                <div class="w-8 h-8 border-4 border-accent border-t-transparent rounded-full animate-spin"></div>
            </div>
        `;

        try {
            const response = await API.getMatches(this.currentSportId);

            if (response.success && response.data) {
                this.matches = Utils.parseApiResponse(response.data);
                this.renderMatches();
            } else {
                this.renderError();
            }
        } catch (error) {
            console.error('Error loading matches:', error);
            this.renderError();
        }
    },

    /**
     * Render matches list
     */
    renderMatches() {
        const listEl = document.getElementById('matchesList');

        if (this.matches.length === 0) {
            listEl.innerHTML = Components.renderEmptyState(
                'fa-calendar-xmark',
                'Sem partidas',
                'Nenhuma partida disponível no momento.'
            );
            return;
        }

        listEl.innerHTML = this.matches.map(match => Components.renderMatchCard(match)).join('');
    },

    /**
     * Render error state
     */
    renderError() {
        const listEl = document.getElementById('matchesList');
        listEl.innerHTML = `
            <div class="empty-state">
                <div class="icon">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </div>
                <h3>Erro ao carregar</h3>
                <p>Não foi possível carregar as partidas.</p>
                <button class="btn btn-secondary mt-4" onclick="MatchesPage.loadMatches()">
                    <i class="fas fa-refresh"></i> Tentar novamente
                </button>
            </div>
        `;
    },

    /**
     * Get match by ID
     * @param {number} matchId - Match ID
     */
    getMatch(matchId) {
        return this.matches.find(m => m.id == matchId);
    }
};
