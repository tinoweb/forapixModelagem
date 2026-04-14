/**
 * FORAPIX - Games Page
 * Página de jogos disponíveis
 */

const GamesPage = {
    /**
     * Render games page
     */
    render() {
        return `
            <div class="page-enter p-4">
                <h2 class="text-xl font-bold mb-4">Jogos</h2>
                
                <!-- Sport Tabs -->
                ${Components.renderSportTabs(1)}

                <!-- Games Categories -->
                <div class="section-header mt-4">
                    <span class="section-title">CATEGORIAS</span>
                </div>

                <div class="space-y-3">
                    ${this.renderCategories()}
                </div>
            </div>
        `;
    },

    /**
     * Render game categories
     */
    renderCategories() {
        const categories = [
            { 
                id: 'head-to-head', 
                name: 'Head to Head', 
                description: 'Apostas em confrontos diretos',
                icon: 'fa-hand-fist',
                sportId: 1
            },
            { 
                id: 'cassino', 
                name: 'Cassino', 
                description: 'Jogos de cassino online',
                icon: 'fa-dice',
                sportId: null
            },
            { 
                id: 'bingo', 
                name: 'Bingo', 
                description: 'Jogos de bingo',
                icon: 'fa-circle-dot',
                sportId: null
            },
            { 
                id: 'slots', 
                name: 'Slots', 
                description: 'Máquinas caça-níqueis',
                icon: 'fa-slot-machine',
                sportId: null
            }
        ];

        return categories.map(cat => `
            <div class="card cursor-pointer" onclick="GamesPage.selectCategory('${cat.id}', ${cat.sportId})">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-secondary rounded-xl flex-center">
                        <i class="fas ${cat.icon} text-2xl text-accent"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg">${cat.name}</h3>
                        <p class="text-sm text-gray-400">${cat.description}</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-500"></i>
                </div>
            </div>
        `).join('');
    },

    /**
     * Initialize games page
     */
    init() {
        // Add event listeners to sport tabs
        document.querySelectorAll('.sport-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const sportId = parseInt(e.currentTarget.dataset.sportId);
                this.selectSport(sportId);
            });
        });
    },

    /**
     * Select sport
     * @param {number} sportId - Sport ID
     */
    selectSport(sportId) {
        // Update active tab
        document.querySelectorAll('.sport-tab').forEach(tab => {
            tab.classList.toggle('active', parseInt(tab.dataset.sportId) === sportId);
        });

        // Navigate to matches
        App.navigateTo('matches', { sportId });
    },

    /**
     * Select category
     * @param {string} categoryId - Category ID
     * @param {number} sportId - Sport ID (if applicable)
     */
    selectCategory(categoryId, sportId) {
        if (categoryId === 'head-to-head' && sportId) {
            App.navigateTo('matches', { sportId });
        } else {
            Components.showToast('Em breve!', 'warning');
        }
    }
};
