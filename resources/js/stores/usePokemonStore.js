import {defineStore} from 'pinia';
import {ref, computed} from 'vue';

export const usePokemonStore = defineStore('pokemon', () => {
        // State
        const pokemon = ref([]);
        const selectedPokemon = ref(null);
        const loading = ref(false);
        const error = ref(null);
        const loadTime = ref(null);
        const pagination = ref({
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0
        });

        // Team state
        const team = ref([]);
        const maxTeamSize = 6;

        // Getters
        const hasFullTeam = computed(() => team.value.length >= maxTeamSize);
        const teamCount = computed(() => team.value.length);

        // Actions
        async function fetchPokemon(search = '') {
            loading.value = true;
            error.value = null;
            const startTime = performance.now();

            try {
                const params = new URLSearchParams();

                if (search) {
                    params.append('search', search);
                }

                const response = await fetch(`/api/pokemon?${params}`);

                if (!response.ok) {
                    throw new Error('Failed to fetch Pokemon');
                }

                const data = await response.json();
                const endTime = performance.now();
                loadTime.value = ((endTime - startTime) / 1000).toFixed(2); // Convert to seconds

                // If response is an array, it's all Pokemon
                if (Array.isArray(data)) {
                    pokemon.value = data;
                    pagination.value = {
                        current_page: 1,
                        last_page: 1,
                        per_page: data.length,
                        total: data.length
                    };
                } else {
                    // Paginated response
                    pokemon.value = data.data;
                    pagination.value = {
                        current_page: data.current_page,
                        last_page: data.last_page,
                        per_page: data.per_page,
                        total: data.total
                    };
                }
            } catch (err) {
                error.value = err.message;
                console.error('Error fetching Pokemon:', err);
            } finally {
                loading.value = false;
            }
        }

        async function fetchPokemonByFormat(format = 'gen9ou') {
            loading.value = true;
            error.value = null;
            const startTime = performance.now();

            try {
                const params = new URLSearchParams();

                if (format) {
                    params.append('format', format);
                }

                const response = await fetch(`/api/pokemon/format/${format}`);

                console.log(response);
                if (!response.ok) {
                    throw new Error('Failed to fetch Pokemon');
                }

                const data = await response.json();
                const endTime = performance.now();
                loadTime.value = ((endTime - startTime) / 1000).toFixed(2); // Convert to seconds

                // If response is an array, it's all Pokemon
                pokemon.value = data;

            } catch (err) {
                error.value = err.message;
                console.error('Error fetching Pokemon:', err);
            } finally {
                loading.value = false;
            }
        }


        async function fetchPokemonById(apiId) {
            loading.value = true;
            error.value = null;

            try {
                const response = await fetch(`/api/pokemon/${apiId}`);

                if (!response.ok) {
                    throw new Error('Failed to fetch Pokemon details');
                }

                const data = await response.json();
                selectedPokemon.value = data;
                return data;
            } catch (err) {
                error.value = err.message;
                console.error('Error fetching Pokemon:', err);
                return null;
            } finally {
                loading.value = false;
            }
        }

        async function searchPokemon(query) {
            if (!query) {
                return [];
            }

            loading.value = true;
            error.value = null;

            try {
                const response = await fetch(`/api/pokemon/search?q=${encodeURIComponent(query)}`);

                if (!response.ok) {
                    throw new Error('Failed to search Pokemon');
                }

                const data = await response.json();
                return data;
            } catch (err) {
                error.value = err.message;
                console.error('Error searching Pokemon:', err);
                return [];
            } finally {
                loading.value = false;
            }
        }

// Team management
        function addToTeam(pokemonData) {
            if (team.value.length >= maxTeamSize) {
                error.value = 'Team is full! Maximum 6 Pokemon allowed.';
                return false;
            }

            // Check if Pokemon is already in team
            if (team.value.find(p => p.api_id === pokemonData.api_id)) {
                error.value = 'This Pokemon is already in your team!';
                return false;
            }

            team.value.push(pokemonData);
            saveTeamToStorage();
            return true;
        }

        function removeFromTeam(apiId) {
            const index = team.value.findIndex(p => p.api_id === apiId);
            if (index !== -1) {
                team.value.splice(index, 1);
                saveTeamToStorage();
            }
        }

        function clearTeam() {
            team.value = [];
            saveTeamToStorage();
        }

        function isInTeam(apiId) {
            return team.value.some(p => p.api_id === apiId);
        }

// Local storage persistence
        function saveTeamToStorage() {
            try {
                localStorage.setItem('pokemon_team', JSON.stringify(team.value));
            } catch (err) {
                console.error('Failed to save team to storage:', err);
            }
        }

        function loadTeamFromStorage() {
            try {
                const saved = localStorage.getItem('pokemon_team');
                if (saved) {
                    team.value = JSON.parse(saved);
                }
            } catch (err) {
                console.error('Failed to load team from storage:', err);
            }
        }

// Load team on store initialization
        loadTeamFromStorage();

        return {
            // State
            pokemon,
            selectedPokemon,
            loading,
            error,
            loadTime,
            pagination,
            team,
            maxTeamSize,

            // Getters
            hasFullTeam,
            teamCount,

            // Actions
            fetchPokemon,
            fetchPokemonById,
            searchPokemon,
            addToTeam,
            removeFromTeam,
            clearTeam,
            isInTeam,
            loadTeamFromStorage,

            fetchPokemonByFormat,
        };
    })
;
