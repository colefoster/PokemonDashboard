<template>


        <!-- Pokemon Details Section (Always Visible) -->
            <div class="px-6 py-8">
                <!-- Pokemon Search -->
                <div class="mb-6">
                    <div class="flex gap-2 mb-2">
                        <ToggleButton
                            v-model="teambuilderStore.sortByUsage"
                            onLabel="Sort by Usage"
                            offLabel="Sort A-Z"
                            onIcon="pi pi-chart-bar"
                            offIcon="pi pi-sort-alpha-down"
                            size="small"
                        />
                    </div>
                    <AutoComplete
                        v-model="searchTerm"
                        :suggestions="filteredPokemon"
                        :placeholder="`Search ${teambuilderStore.formattedFormat} Pokemon...`"
                        optionLabel="name"
                        dropdown
                        showClear
                        forceSelection
                        :loading="teambuilderStore.loading"
                        @complete="search"
                        @item-select="onPokemonSelect"
                        @clear="clearSelection"
                        size="large"
                        class="w-full"
                    >
                        <template #option="{ option }">
                            <div class="flex justify-between items-center w-full gap-4">
                                <span>{{ option.name }}</span>
                                <span v-if="option.usage != null" class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">
                                    {{ formatUsage(option.usage) }}
                                </span>
                            </div>
                        </template>
                    </AutoComplete>
                </div>

                <!-- Selected Pokemon Content -->
                <div v-if="teambuilderStore.selectedPokemonData">
                    <!-- Header with sprite and name -->
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex flex-col items-center">
                            <div class="relative w-32 h-32 flex items-center justify-center bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                                <img
                                    v-if="spriteUrl"
                                    :src="spriteUrl"
                                    :alt="teambuilderStore.selectedPokemon"
                                    class="max-w-full max-h-full object-contain"
                                    :class="{ 'pixelated': selectedSpriteStyle?.value === 'default' || selectedSpriteStyle?.value?.startsWith('gen') }"
                                />
                                <div v-else class="text-zinc-400 text-sm">No sprite</div>
                            </div>
                            <Select
                                v-model="selectedSpriteStyle"
                                :options="spriteStyleOptions"
                                optionLabel="label"
                                placeholder="Sprite Style"
                                class="mt-2 w-full"
                                size="small"
                            />
                            <div class="flex gap-2 mt-2">
                                <ToggleButton
                                    v-model="spriteShiny"
                                    onLabel="Shiny"
                                    offLabel="Normal"
                                    onIcon="pi pi-star-fill"
                                    offIcon="pi pi-star"
                                    size="small"
                                />
                            </div>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-zinc-950 dark:text-white">
                                {{ teambuilderStore.selectedPokemon }}
                            </h2>
                            <div class="flex gap-2 mt-1">
                                <span
                                    v-for="type in teambuilderStore.selectedPokemonData.types"
                                    :key="type.id"
                                    class="px-2 py-1 rounded text-xs font-medium text-white"
                                    :class="getTypeClass(type.name)"
                                >
                                    {{ formatName(type.name) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Sets -->
                    <div v-if="teambuilderStore.selectedPokemonSets">
                        <h3 class="text-lg font-semibold text-zinc-950 dark:text-white mb-3">Smogon Sets</h3>
                        <div class="space-y-4">
                            <div
                                v-for="(setData, setName) in teambuilderStore.selectedPokemonSets"
                                :key="setName"
                                class="bg-zinc-100 dark:bg-zinc-800 rounded-lg p-4 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors border-2"
                                :class="selectedSetName === setName ? 'border-primary-500' : 'border-transparent'"
                                @click="loadSet(setName, setData)"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-zinc-950 dark:text-white">{{ setName }}</h4>
                                    <span class="text-xs text-primary-600 dark:text-primary-400 font-medium">
                                        {{ selectedSetName === setName ? 'Selected' : 'Click to use' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div v-if="setData.ability">
                                        <span class="text-zinc-500 dark:text-zinc-400">Ability:</span>
                                        <span class="text-zinc-950 dark:text-white ml-1">
                                            {{ Array.isArray(setData.ability) ? setData.ability[0] : setData.ability }}
                                        </span>
                                    </div>
                                    <div v-if="setData.item">
                                        <span class="text-zinc-500 dark:text-zinc-400">Item:</span>
                                        <span class="text-zinc-950 dark:text-white ml-1">
                                            {{ Array.isArray(setData.item) ? setData.item[0] : setData.item }}
                                        </span>
                                    </div>
                                    <div v-if="setData.nature">
                                        <span class="text-zinc-500 dark:text-zinc-400">Nature:</span>
                                        <span class="text-zinc-950 dark:text-white ml-1">
                                            {{ Array.isArray(setData.nature) ? setData.nature[0] : setData.nature }}
                                        </span>
                                    </div>
                                    <div v-if="setData.teraType || setData.teratypes">
                                        <span class="text-zinc-500 dark:text-zinc-400">Tera Type:</span>
                                        <span class="text-zinc-950 dark:text-white ml-1">
                                            {{ formatTeraTypes(setData.teraType || setData.teratypes) }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="setData.moves" class="mt-2">
                                    <span class="text-zinc-500 dark:text-zinc-400 text-sm">Moves:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <span
                                            v-for="(move, idx) in formatMoves(setData.moves).slice(0, 4)"
                                            :key="idx"
                                            class="bg-zinc-200 dark:bg-zinc-700 px-2 py-1 rounded text-xs text-zinc-950 dark:text-white"
                                        >
                                            {{ move }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Build Configuration -->
                    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <PokemonBuildConfig
                            :pokemon="teambuilderStore.selectedPokemonData"
                            :format="teambuilderStore.currentFormat"
                            :initial-config="selectedSetConfig"
                            @update:config="onBuildConfigUpdate"
                        />
                    </div>

                    <div class="mt-6 flex gap-2">
                        <Button label="Add to Team" @click="handleAddToTeam" :disabled="teambuilderStore.hasFullTeam"/>
                        <Button label="Clear" severity="secondary" @click="clearSelection"/>
                    </div>
                </div>

                <!-- Empty state when no Pokemon selected -->
                <div v-else-if="!teambuilderStore.loading" class="text-center py-8">
                    <p class="text-zinc-500 dark:text-zinc-400">
                        Search for a Pokemon above to view details and add to your team.
                    </p>
                </div>

                <!-- Loading state -->
                <div v-else class="text-center py-8">
                    <p class="text-zinc-600 dark:text-zinc-400">Loading Pokemon data...</p>
                </div>
        </div>
</template>

<script setup>
import {ref, computed, watch} from 'vue';
import {useTeambuilderStore} from '../stores/useTeambuilderStore';

import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import Select from 'primevue/select';
import ToggleButton from 'primevue/togglebutton';
import PokemonBuildConfig from './PokemonBuildConfig.vue';

const emit = defineEmits(['pokemon-added']);

const teambuilderStore = useTeambuilderStore();
const filteredPokemon = ref([]);
const searchTerm = ref('');
const buildConfig = ref(null);
const selectedSetName = ref(null);
const selectedSetConfig = ref(null);


// Sprite options
const spriteStyleOptions = [
    {label: 'Default', value: 'default'},
    {label: 'Official Artwork', value: 'official-artwork'},
    {label: 'Pokemon HOME', value: 'home'},
    {label: 'Dream World', value: 'dream-world'},
    {label: 'Showdown', value: 'showdown'},
    {label: 'Gen 1 (Red/Blue)', value: 'gen-i-red-blue'},
    {label: 'Gen 2 (Crystal)', value: 'gen-ii-crystal'},
    {label: 'Gen 3 (Emerald)', value: 'gen-iii-emerald'},
    {label: 'Gen 4 (Platinum)', value: 'gen-iv-platinum'},
    {label: 'Gen 5 (Black/White)', value: 'gen-v-black-white'},
];

const selectedSpriteStyle = ref(spriteStyleOptions[0]);
const spriteShiny = ref(false);
const spriteUrl = ref(null);

// Fetch sprite when Pokemon or style changes
const fetchSprite = async () => {
    if (!teambuilderStore.selectedPokemonData?.api_id) {
        spriteUrl.value = null;
        return;
    }

    const pokemonId = teambuilderStore.selectedPokemonData.api_id;
    const style = selectedSpriteStyle.value?.value || 'default';

    let url = `/api/sprites/pokemon/${pokemonId}?`;
    const params = new URLSearchParams();

    if (spriteShiny.value) {
        params.append('shiny', 'true');
    }

    // Handle generation-specific sprites
    if (style.startsWith('gen-')) {
        const parts = style.split('-');
        const gen = parts[1]; // i, ii, iii, etc.
        const game = parts.slice(2).join('-'); // red-blue, crystal, etc.
        params.append('generation', gen);
        params.append('game', game);
    } else if (style !== 'default') {
        params.append('style', style);
    }

    try {
        const response = await fetch(url + params.toString());
        const data = await response.json();
        spriteUrl.value = data.url;
    } catch (error) {
        console.error('Failed to fetch sprite:', error);
        // Fallback to stored sprite
        spriteUrl.value = teambuilderStore.selectedPokemonData.sprites?.front_default;
    }
};

// Watch for changes to trigger sprite fetch
watch(
    () => teambuilderStore.selectedPokemonData,
    () => fetchSprite(),
    {immediate: true}
);

watch(selectedSpriteStyle, () => fetchSprite());
watch(spriteShiny, () => fetchSprite());


// Transform stats array to object for radar chart
const transformedStats = computed(() => {
    if (!teambuilderStore.selectedPokemonData?.stats) {
        return {
            hp: 0,
            attack: 0,
            defense: 0,
            specialAttack: 0,
            specialDefense: 0,
            speed: 0
        };
    }

    const stats = teambuilderStore.selectedPokemonData.stats;
    const statMap = {};

    stats.forEach(stat => {
        statMap[stat.stat_name] = stat.base_stat;
    });

    return {
        hp: statMap['hp'] || 0,
        attack: statMap['attack'] || 0,
        defense: statMap['defense'] || 0,
        specialAttack: statMap['special-attack'] || 0,
        specialDefense: statMap['special-defense'] || 0,
        speed: statMap['speed'] || 0
    };
});

// Get primary type for chart coloring
const primaryType = computed(() => {
    if (!teambuilderStore.selectedPokemonData?.types?.length) return null;
    // Types are sorted by slot, first one is primary
    return teambuilderStore.selectedPokemonData.types[0]?.name || null;
});

const search = (event) => {
    const query = event.query.toLowerCase();
    filteredPokemon.value = teambuilderStore.sortedAvailablePokemon.filter(pokemon =>
        pokemon.name.toLowerCase().includes(query)
    );
};

const onPokemonSelect = async (event) => {
    const selectedName = event.value?.name || event.value;
    selectedSetName.value = null;
    selectedSetConfig.value = null;
    await teambuilderStore.fetchCombinedByName(selectedName);
};

const formatUsage = (usage) => {
    if (usage == null) return '';
    return (usage * 100).toFixed(2) + '%';
};

const clearSelection = () => {
    searchTerm.value = '';
    spriteUrl.value = null;
    buildConfig.value = null;
    selectedSetName.value = null;
    selectedSetConfig.value = null;
    teambuilderStore.clearSelection();
};

const onBuildConfigUpdate = (config) => {
    buildConfig.value = config;
};

// Load a Smogon set into the build config
const loadSet = (setName, setData) => {
    selectedSetName.value = setName;

    // Convert Smogon set format to build config format
    // Take first option if arrays are present (for multiple options)
    const getFirst = (val) => Array.isArray(val) ? val[0] : val;

    // Extract moves - Smogon format can be [[move1], [move2, alt]] or [move1, move2]
    const moves = [];
    if (setData.moves) {
        for (let i = 0; i < 4; i++) {
            if (setData.moves[i]) {
                const move = Array.isArray(setData.moves[i]) ? setData.moves[i][0] : setData.moves[i];
                moves.push(move);
            } else {
                moves.push(null);
            }
        }
    }

    // Parse EVs if present (format: "252 Atk / 4 Def / 252 Spe" or object)
    let evs = {hp: 0, atk: 0, def: 0, spa: 0, spd: 0, spe: 0};
    if (setData.evs) {
        if (typeof setData.evs === 'object' && !Array.isArray(setData.evs)) {
            evs = {...evs, ...setData.evs};
        }
    }

    // Parse IVs if present
    let ivs = {hp: 31, atk: 31, def: 31, spa: 31, spd: 31, spe: 31};
    if (setData.ivs) {
        if (typeof setData.ivs === 'object' && !Array.isArray(setData.ivs)) {
            ivs = {...ivs, ...setData.ivs};
        }
    }

    selectedSetConfig.value = {
        moves: moves,
        ability: getFirst(setData.ability),
        item: getFirst(setData.item),
        teraType: getFirst(setData.teraType || setData.teratypes),
        nature: getFirst(setData.nature),
        evs: evs,
        ivs: ivs
    };
};

const handleAddToTeam = () => {
    if (!teambuilderStore.selectedPokemonData) return;

    // Combine Pokemon data with build configuration
    const pokemonWithConfig = {
        ...teambuilderStore.selectedPokemonData,
        build: buildConfig.value
    };

    const success = teambuilderStore.addToTeam(pokemonWithConfig);
    if (success) {
        emit('pokemon-added', pokemonWithConfig);
        clearSelection();
    }
};

const formatName = (name) => {
    return name.split('-').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

const formatStatName = (statName) => {
    const statMap = {
        'hp': 'HP',
        'attack': 'Atk',
        'defense': 'Def',
        'special-attack': 'SpA',
        'special-defense': 'SpD',
        'speed': 'Spe'
    };
    return statMap[statName] || statName;
};

const formatMoves = (moves) => {
    if (!moves) return [];
    // Moves can be an array of arrays (move slots with options)
    return moves.flat().filter((v, i, a) => a.indexOf(v) === i);
};

const formatTeraTypes = (types) => {
    if (!types) return '';
    if (Array.isArray(types)) return types.join(' / ');
    return types;
};

const getTypeClass = (type) => {
    const typeColors = {
        normal: 'bg-gray-400',
        fire: 'bg-red-500',
        water: 'bg-blue-500',
        electric: 'bg-yellow-400',
        grass: 'bg-green-500',
        ice: 'bg-cyan-300',
        fighting: 'bg-red-700',
        poison: 'bg-purple-500',
        ground: 'bg-amber-600',
        flying: 'bg-indigo-300',
        psychic: 'bg-pink-500',
        bug: 'bg-lime-500',
        rock: 'bg-amber-700',
        ghost: 'bg-purple-700',
        dragon: 'bg-indigo-600',
        dark: 'bg-gray-700',
        steel: 'bg-gray-400',
        fairy: 'bg-pink-300',
    };
    return typeColors[type.toLowerCase()] || 'bg-gray-500';
};
</script>

<style scoped>
.pixelated {
    image-rendering: pixelated;
    image-rendering: -moz-crisp-edges;
    image-rendering: crisp-edges;
}
</style>
