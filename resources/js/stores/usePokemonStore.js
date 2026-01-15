import {defineStore} from 'pinia';
import {ref, computed} from 'vue';

export const usePokemonStore = defineStore('pokemon', () => {
        // State
        const pokemon = ref([]);
        const selectedPokemon = ref(null);
        const selectedPokemonData = ref(null);
        const selectedPokemonSets = ref(null);
        const currentFormat = ref('gen9ou');
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
        const formattedFormat = computed(() => {
            // "gen9ou" -> "Gen 9 OU"
            const match = currentFormat.value.match(/^(gen)(\d+)(.*)$/i);
            if (match) {
                const tier = match[3].toUpperCase() || '';
                return `Gen ${match[2]} ${tier}`.trim();
            }
            return currentFormat.value;
        });

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

        async function fetchPokemonNamesInFormat(format = 'gen9ou') {
            loading.value = true;
            error.value = null;
            currentFormat.value = format;
            const startTime = performance.now();

            try {
                const response = await fetch(`/api/formats/${format}/names`);

                if (!response.ok) {
                    throw new Error('Failed to fetch Pokemon names');
                }

                const data = await response.json();
                const endTime = performance.now();
                loadTime.value = ((endTime - startTime) / 1000).toFixed(2);

                pokemon.value = data;
            } catch (err) {
                error.value = err.message;
                console.error('Error fetching Pokemon names:', err);
            } finally {
                loading.value = false;
            }
        }

        async function fetchCombinedByName(name, format = null) {
            if (!name) return null;

            loading.value = true;
            error.value = null;

            const targetFormat = format || currentFormat.value;

            try {
                const response = await fetch(
                    `/api/formats/${targetFormat}/combined/search?q=${encodeURIComponent(name)}`
                );

                if (!response.ok) {
                    throw new Error('Failed to fetch Pokemon data');
                }

                const data = await response.json();

                // Find exact match or first result
                const match = data.find(item =>
                    item.name.toLowerCase() === name.toLowerCase()
                ) || data[0];

                if (match) {
                    selectedPokemon.value = name;
                    selectedPokemonData.value = match.pokemon;
                    selectedPokemonSets.value = match.sets;
                    return match;
                }

                return null;
            } catch (err) {
                error.value = err.message;
                console.error('Error fetching Pokemon data:', err);
                return null;
            } finally {
                loading.value = false;
            }
        }

        async function fetchSetsByFormat(format = 'gen9ou') {
            loading.value = true;
            error.value = null;

            try {
                const response = await fetch(`/api/formats/${format}/sets`);

                if (!response.ok) {
                    throw new Error('Failed to fetch sets');
                }

                const data = await response.json();
                return data;
            } catch (err) {
                error.value = err.message;
                console.error('Error fetching sets:', err);
                return null;
            } finally {
                loading.value = false;
            }
        }

        function clearSelection() {
            selectedPokemon.value = null;
            selectedPokemonData.value = null;
            selectedPokemonSets.value = null;
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

// Load Pokemon names on store initialization
        fetchPokemonNamesInFormat();

        return {
            // State
            pokemon,
            selectedPokemon,
            selectedPokemonData,
            selectedPokemonSets,
            currentFormat,
            loading,
            error,
            loadTime,
            pagination,
            team,
            maxTeamSize,

            // Getters
            hasFullTeam,
            teamCount,
            formattedFormat,

            // Actions
            fetchPokemon,
            fetchPokemonById,
            fetchPokemonNamesInFormat,
            fetchCombinedByName,
            fetchSetsByFormat,
            searchPokemon,
            addToTeam,
            removeFromTeam,
            clearTeam,
            clearSelection,
            isInTeam,
        };
    })
;
