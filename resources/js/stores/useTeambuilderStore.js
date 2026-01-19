import {defineStore} from 'pinia';
import {ref, computed} from 'vue';

export const useTeambuilderStore = defineStore('teambuilder', () => {
    // State
    const availablePokemon = ref([]); // Array of {name, usage} objects
    const selectedPokemon = ref(null);
    const selectedPokemonData = ref(null);
    const selectedPokemonSets = ref(null);
    const currentFormat = ref('gen9ou');
    const loading = ref(false);
    const error = ref(null);
    const loadTime = ref(null);
    const sortByUsage = ref(true); // Default to sorting by usage

    // Pokemon data cache - stores full Pokemon data by api_id to avoid duplication
    const pokemonCache = ref({});

    // Teams state - array of team objects
    const teams = ref([]);
    const currentTeamId = ref(null);
    const maxTeamSize = 6;

    // Legacy single team support (computed from current team)
    const team = computed(() => {
        const currentTeam = teams.value.find(t => t.id === currentTeamId.value);
        if (!currentTeam) return [];
        return currentTeam.members.map(member => ({
            ...pokemonCache.value[member.pokemonId],
            ...member
        }));
    });


    // Getters
    const currentTeam = computed(() => teams.value.find(t => t.id === currentTeamId.value) || null);
    const hasFullTeam = computed(() => team.value.length >= maxTeamSize);
    const teamCount = computed(() => team.value.length);
    const teamsInFormat = computed(() => {
        if (currentFormat.value === 'all') return teams.value;
        return teams.value.filter(t => t.format === currentFormat.value);
    });
    const sortedAvailablePokemon = computed(() => {
        if (!availablePokemon.value.length) return [];

        const sorted = [...availablePokemon.value];
        if (sortByUsage.value) {
            sorted.sort((a, b) => (b.usage ?? 0) - (a.usage ?? 0));
        } else {
            sorted.sort((a, b) => a.name.localeCompare(b.name));
        }
        return sorted;
    });
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
    async function setFormat(format) {
        if (format === currentFormat.value) return;

        currentFormat.value = format;
        clearSelection();
        await fetchPokemonNamesInFormat(format);
    }

    async function fetchPokemonNamesInFormat(format = null) {
        const targetFormat = format || currentFormat.value;
        loading.value = true;
        error.value = null;
        const startTime = performance.now();

        try {
            // Fetch names with usage data
            const response = await fetch(`/api/formats/${targetFormat}/names/usage?sort=usage`);

            if (!response.ok) {
                throw new Error('Failed to fetch Pokemon names');
            }

            const data = await response.json();
            const endTime = performance.now();
            loadTime.value = ((endTime - startTime) / 1000).toFixed(2);

            availablePokemon.value = data;
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching Pokemon names:', err);
        } finally {
            loading.value = false;
        }
    }

    function setSortByUsage(value) {
        sortByUsage.value = value;
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

    async function fetchSetsByFormat(format = null) {
        const targetFormat = format || currentFormat.value;
        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/formats/${targetFormat}/sets`);

            if (!response.ok) {
                throw new Error('Failed to fetch sets');
            }

            return await response.json();
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

    // Team CRUD operations
    function createTeam(name, format = null) {
        const teamFormat = format || currentFormat.value;
        const newTeam = {
            id: generateId(),
            name: name || `New Team`,
            format: teamFormat,
            members: [],
            createdAt: Date.now(),
            updatedAt: Date.now()
        };
        teams.value.push(newTeam);
        currentTeamId.value = newTeam.id;
        saveAllToStorage();
        return newTeam;
    }

    function deleteTeam(teamId) {
        const index = teams.value.findIndex(t => t.id === teamId);
        if (index !== -1) {
            teams.value.splice(index, 1);
            if (currentTeamId.value === teamId) {
                currentTeamId.value = teams.value[0]?.id || null;
            }
            cleanupPokemonCache();
            saveAllToStorage();
        }
    }

    function renameTeam(teamId, newName) {
        const team = teams.value.find(t => t.id === teamId);
        if (team) {
            team.name = newName;
            team.updatedAt = Date.now();
            saveAllToStorage();
        }
    }

    function selectTeam(teamId) {
        currentTeamId.value = teamId;
    }

    function duplicateTeam(teamId) {
        const original = teams.value.find(t => t.id === teamId);
        if (!original) return null;

        const newTeam = {
            ...original,
            id: generateId(),
            name: `${original.name} (Copy)`,
            members: original.members.map(m => ({...m, id: generateId()})),
            createdAt: Date.now(),
            updatedAt: Date.now()
        };
        teams.value.push(newTeam);
        saveAllToStorage();
        return newTeam;
    }

    // Team member management
    function addToTeam(pokemonData, teamId = null) {
        const targetTeamId = teamId || currentTeamId.value;
        const targetTeam = teams.value.find(t => t.id === targetTeamId);

        if (!targetTeam) {
            error.value = 'No team selected!';
            return false;
        }

        if (targetTeam.members.length >= maxTeamSize) {
            error.value = 'Team is full! Maximum 6 Pokemon allowed.';
            return false;
        }

        // Cache the Pokemon data (avoids duplication across teams)
        cachePokemon(pokemonData);

        // Create team member with customizable fields
        const member = createTeamMember(pokemonData.api_id);

        // Apply build configuration if provided
        if (pokemonData.build) {
            const build = pokemonData.build;
            if (build.moves) member.moves = build.moves.map(m => m?.name || m || null);
            if (build.ability) member.ability = build.ability?.name || build.ability;
            if (build.item) member.item = build.item?.name || build.item;
            if (build.teraType) member.teraType = build.teraType?.name || build.teraType;
            if (build.nature) member.nature = build.nature?.name || build.nature;
            if (build.evs) member.evs = {...member.evs, ...build.evs};
            if (build.ivs) member.ivs = {...member.ivs, ...build.ivs};
        }

        targetTeam.members.push(member);
        targetTeam.updatedAt = Date.now();
        saveAllToStorage();
        return true;
    }

    function removeFromTeam(memberId, teamId = null) {
        const targetTeamId = teamId || currentTeamId.value;
        const targetTeam = teams.value.find(t => t.id === targetTeamId);

        if (!targetTeam) return;

        const index = targetTeam.members.findIndex(m => m.id === memberId);
        if (index !== -1) {
            targetTeam.members.splice(index, 1);
            targetTeam.updatedAt = Date.now();
            cleanupPokemonCache();
            saveAllToStorage();
        }
    }

    function updateTeamMember(memberId, updates, teamId = null) {
        const targetTeamId = teamId || currentTeamId.value;
        const targetTeam = teams.value.find(t => t.id === targetTeamId);

        if (!targetTeam) return;

        const member = targetTeam.members.find(m => m.id === memberId);
        if (member) {
            Object.assign(member, updates);
            targetTeam.updatedAt = Date.now();
            saveAllToStorage();
        }
    }

    function reorderTeamMembers(newOrder, teamId = null) {
        const targetTeamId = teamId || currentTeamId.value;
        const targetTeam = teams.value.find(t => t.id === targetTeamId);

        if (!targetTeam) return;

        targetTeam.members = newOrder.map(id => targetTeam.members.find(m => m.id === id)).filter(Boolean);
        targetTeam.updatedAt = Date.now();
        saveAllToStorage();
    }

    function clearTeam(teamId = null) {
        const targetTeamId = teamId || currentTeamId.value;
        const targetTeam = teams.value.find(t => t.id === targetTeamId);

        if (targetTeam) {
            targetTeam.members = [];
            targetTeam.updatedAt = Date.now();
            cleanupPokemonCache();
            saveAllToStorage();
        }
    }

    function isInTeam(apiId, teamId = null) {
        const targetTeamId = teamId || currentTeamId.value;
        const targetTeam = teams.value.find(t => t.id === targetTeamId);
        if (!targetTeam) return false;
        return targetTeam.members.some(m => m.pokemonId === apiId);
    }

    // Pokemon cache management
    function cachePokemon(pokemonData) {
        if (!pokemonCache.value[pokemonData.api_id]) {
            pokemonCache.value[pokemonData.api_id] = {
                api_id: pokemonData.api_id,
                name: pokemonData.name,
                sprite: pokemonData.sprite,
                types: pokemonData.types,
                stats: pokemonData.stats,
                abilities: pokemonData.abilities,
                moves: pokemonData.moves,
                weight: pokemonData.weight,
                height: pokemonData.height
            };
        }
    }

    function getPokemonFromCache(apiId) {
        return pokemonCache.value[apiId] || null;
    }

    function cleanupPokemonCache() {
        // Get all Pokemon IDs currently in use across all teams
        const usedIds = new Set();
        teams.value.forEach(team => {
            team.members.forEach(member => usedIds.add(member.pokemonId));
        });

        // Remove unused Pokemon from cache
        Object.keys(pokemonCache.value).forEach(id => {
            if (!usedIds.has(parseInt(id))) {
                delete pokemonCache.value[id];
            }
        });
    }

    // Helper to create a new team member with all customizable fields
    function createTeamMember(pokemonId) {
        return {
            id: generateId(),
            pokemonId: pokemonId,
            nickname: '',
            level: 100,
            gender: null,
            shiny: false,
            ability: null,
            item: null,
            nature: null,
            teraType: null,
            moves: [null, null, null, null],
            evs: {hp: 0, atk: 0, def: 0, spa: 0, spd: 0, spe: 0},
            ivs: {hp: 31, atk: 31, def: 31, spa: 31, spd: 31, spe: 31},
            happiness: 255
        };
    }

    // Utility
    function generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
    }

    // Get full member data (cached Pokemon + customizations)
    function getFullMemberData(memberId, teamId = null) {
        const targetTeamId = teamId || currentTeamId.value;
        const targetTeam = teams.value.find(t => t.id === targetTeamId);
        if (!targetTeam) return null;

        const member = targetTeam.members.find(m => m.id === memberId);
        if (!member) return null;

        const pokemon = pokemonCache.value[member.pokemonId];
        return pokemon ? {...pokemon, ...member} : null;
    }

    // Storage
    function saveAllToStorage() {
        try {
            localStorage.setItem('teambuilder_teams', JSON.stringify(teams.value));
            localStorage.setItem('teambuilder_pokemonCache', JSON.stringify(pokemonCache.value));
            localStorage.setItem('teambuilder_currentTeamId', currentTeamId.value);
        } catch (err) {
            console.error('Error saving to storage:', err);
        }
    }

    function loadAllFromStorage() {
        try {
            const savedTeams = localStorage.getItem('teambuilder_teams');
            const savedCache = localStorage.getItem('teambuilder_pokemonCache');
            const savedCurrentTeamId = localStorage.getItem('teambuilder_currentTeamId');

            if (savedTeams) teams.value = JSON.parse(savedTeams);
            if (savedCache) pokemonCache.value = JSON.parse(savedCache);
            if (savedCurrentTeamId) currentTeamId.value = savedCurrentTeamId;
        } catch (err) {
            console.error('Error loading from storage:', err);
        }
    }

    // Create example teams for new users
    function createExampleTeams() {
        if (teams.value.length > 0) return; // Don't overwrite existing teams

        // Example OU Team
        const ouTeam = {
            id: generateId(),
            name: 'OU Rain Team',
            format: 'gen9ou',
            members: [],
            createdAt: Date.now(),
            updatedAt: Date.now()
        };

        // Example UU Team
        const uuTeam = {
            id: generateId(),
            name: 'UU Balance',
            format: 'gen9uu',
            members: [],
            createdAt: Date.now() - 1000,
            updatedAt: Date.now() - 1000
        };

        // Example Doubles Team
        const doublesTeam = {
            id: generateId(),
            name: 'Doubles Sun',
            format: 'gen9doublesou',
            members: [],
            createdAt: Date.now() - 2000,
            updatedAt: Date.now() - 2000
        };

        teams.value = [ouTeam, uuTeam, doublesTeam];
        currentTeamId.value = ouTeam.id;
        saveAllToStorage();
    }

    // Initialize on store creation
    loadAllFromStorage();
    createExampleTeams();
    fetchPokemonNamesInFormat();

    return {
        // State
        availablePokemon,
        selectedPokemon,
        selectedPokemonData,
        selectedPokemonSets,
        currentFormat,
        loading,
        error,
        loadTime,
        team,
        teams,
        currentTeamId,
        pokemonCache,
        maxTeamSize,
        sortByUsage,

        // Getters
        currentTeam,
        hasFullTeam,
        teamCount,
        teamsInFormat,
        formattedFormat,
        sortedAvailablePokemon,

        // Actions - Format
        setFormat,
        fetchPokemonNamesInFormat,
        fetchCombinedByName,
        fetchSetsByFormat,
        clearSelection,
        setSortByUsage,

        // Actions - Team CRUD
        createTeam,
        deleteTeam,
        renameTeam,
        selectTeam,
        duplicateTeam,

        // Actions - Team Members
        addToTeam,
        removeFromTeam,
        updateTeamMember,
        reorderTeamMembers,
        clearTeam,
        isInTeam,
        getFullMemberData,

        // Actions - Pokemon Cache
        cachePokemon,
        getPokemonFromCache,

        // Actions - Storage
        saveAllToStorage,
        loadAllFromStorage,
    };
});
