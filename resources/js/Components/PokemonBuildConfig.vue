<template>
    <div class="space-y-6">
        <h3 class="text-lg font-semibold text-zinc-950 dark:text-white">Build Configuration</h3>

        <!-- Moves Section -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Moves</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-for="slot in 4" :key="slot" class="relative">
                    <AutoComplete
                        v-model="moves[slot - 1]"
                        :suggestions="filteredMoves"
                        optionLabel="name"
                        placeholder="Select a move..."
                        dropdown
                        showClear
                        forceSelection
                        :loading="movesLoading"
                        @complete="searchMoves"
                        class="w-full"
                    >
                        <template #option="{ option }">
                            <div class="flex items-center justify-between w-full gap-2 py-1">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span
                                        class="inline-block w-16 flex-shrink-0 px-2 py-0.5 rounded text-xs font-medium text-white text-center"
                                        :class="getTypeClass(option.type?.name)"
                                    >
                                        {{ option.type?.name || '???' }}
                                    </span>
                                    <span class="font-medium truncate">{{ option.name }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 flex-shrink-0">
                                    <span
                                        v-if="option.usage != null"
                                        class="px-1.5 py-0.5 rounded font-mono bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300"
                                    >
                                        {{ formatUsage(option.usage) }}
                                    </span>
                                    <span v-if="option.power" class="flex items-center gap-1">
                                        <i class="pi pi-bolt text-yellow-500"></i>
                                        {{ option.power }}
                                    </span>
                                    <span v-if="option.accuracy" class="flex items-center gap-1">
                                        <i class="pi pi-bullseye text-blue-500"></i>
                                        {{ option.accuracy }}%
                                    </span>
                                    <span
                                        class="px-1.5 py-0.5 rounded text-xs"
                                        :class="getDamageClassStyle(option.damage_class)"
                                    >
                                        {{ formatDamageClass(option.damage_class) }}
                                    </span>
                                </div>
                            </div>
                        </template>
                        <template #chip="{ value }">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-block w-3 h-3 rounded-full"
                                    :class="getTypeClass(value.type?.name)"
                                ></span>
                                <span>{{ value.name }}</span>
                            </div>
                        </template>
                    </AutoComplete>
                </div>
            </div>
        </div>

        <!-- Ability Section -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Ability</label>
            <AutoComplete
                v-model="ability"
                :suggestions="filteredAbilities"
                optionLabel="name"
                placeholder="Select ability..."
                dropdown
                showClear
                forceSelection
                :loading="abilitiesLoading"
                @complete="searchAbilities"
                class="w-full md:w-1/2"
            >
                <template #option="{ option }">
                    <div class="py-1">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ option.name }}</span>
                                <span
                                    v-if="option.is_hidden"
                                    class="px-1.5 py-0.5 rounded text-xs bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300"
                                >
                                    Hidden
                                </span>
                            </div>
                            <span
                                v-if="option.usage != null"
                                class="px-1.5 py-0.5 rounded text-xs font-mono bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300"
                            >
                                {{ formatUsage(option.usage) }}
                            </span>
                        </div>
                        <p v-if="option.short_effect" class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 line-clamp-2">
                            {{ option.short_effect }}
                        </p>
                    </div>
                </template>
            </AutoComplete>
        </div>

        <!-- Item Section -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Held Item</label>
            <AutoComplete
                v-model="item"
                :suggestions="filteredItems"
                optionLabel="name"
                placeholder="Search items..."
                dropdown
                showClear
                forceSelection
                :loading="itemsLoading"
                @complete="searchItems"
                class="w-full md:w-1/2"
            >
                <template #option="{ option }">
                    <div class="flex items-center justify-between gap-3 py-1">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="option.sprite"
                                :src="option.sprite"
                                :alt="option.name"
                                class="w-6 h-6 object-contain"
                            />
                            <div class="w-6 h-6 bg-zinc-200 dark:bg-zinc-700 rounded" v-else></div>
                            <div>
                                <div class="font-medium">{{ option.name }}</div>
                                <p v-if="option.effect" class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-1">
                                    {{ option.effect }}
                                </p>
                            </div>
                        </div>
                        <span
                            v-if="option.usage != null"
                            class="px-1.5 py-0.5 rounded text-xs font-mono bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300 flex-shrink-0"
                        >
                            {{ formatUsage(option.usage) }}
                        </span>
                    </div>
                </template>
            </AutoComplete>
        </div>

        <!-- Tera Type Section -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Tera Type</label>
            <AutoComplete
                v-model="teraType"
                :suggestions="filteredTeraTypes"
                optionLabel="name"
                placeholder="Select tera type..."
                dropdown
                showClear
                forceSelection
                @complete="searchTeraTypes"
                class="w-full md:w-1/2"
            >
                <template #option="{ option }">
                    <div class="flex items-center gap-2 py-1">
                        <span
                            class="inline-block w-16 px-2 py-0.5 rounded text-xs font-medium text-white text-center"
                            :class="getTypeClass(option.name)"
                        >
                            {{ option.name }}
                        </span>
                    </div>
                </template>
            </AutoComplete>
        </div>

        <!-- EV/IV Spread Section -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <SpreadSelector
                :pokemon="pokemon"
                :format="format"
                :initial-spread="spread"
                @update:spread="onSpreadUpdate"
            />
        </div>

        <!-- Common Partners Section -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <CommonPartners
                :pokemon="pokemon"
                :format="format"
                @select="$emit('select-pokemon', $event)"
            />
        </div>

        <!-- Counters Section -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <PokemonCounters
                :pokemon="pokemon"
                :format="format"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import SpreadSelector from './SpreadSelector.vue';
import CommonPartners from './CommonPartners.vue';
import PokemonCounters from './PokemonCounters.vue';

const props = defineProps({
    pokemon: {
        type: Object,
        required: true
    },
    format: {
        type: String,
        default: 'gen9ou'
    },
    initialConfig: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:config', 'select-pokemon']);

// State for selections
const moves = ref([null, null, null, null]);
const ability = ref(null);
const item = ref(null);
const teraType = ref(null);
const spread = ref(null);

// Suggestions lists
const filteredMoves = ref([]);
const filteredAbilities = ref([]);
const filteredItems = ref([]);
const filteredTeraTypes = ref([]);

// Loading states
const movesLoading = ref(false);
const abilitiesLoading = ref(false);
const itemsLoading = ref(false);

// Cache for static data
const allTypes = ref([]);

// Format usage percentage
const formatUsage = (usage) => {
    if (usage == null) return '';
    return (usage * 100).toFixed(1) + '%';
};

// Fetch moves for the selected Pokemon with usage stats
const searchMoves = async (event) => {
    const query = event.query || '';
    movesLoading.value = true;

    try {
        const pokemonName = props.pokemon.name || props.pokemon.api_id;
        const params = new URLSearchParams({ q: query, limit: '50' });
        const response = await fetch(`/api/formats/${props.format}/pokemon/${encodeURIComponent(pokemonName)}/moves?${params}`);
        const data = await response.json();
        filteredMoves.value = data;
    } catch (error) {
        console.error('Failed to fetch moves:', error);
        filteredMoves.value = [];
    } finally {
        movesLoading.value = false;
    }
};

// Fetch abilities for the Pokemon with usage stats
const searchAbilities = async (event) => {
    const query = event.query?.toLowerCase() || '';
    abilitiesLoading.value = true;

    try {
        const pokemonName = props.pokemon.name || props.pokemon.api_id;
        const response = await fetch(`/api/formats/${props.format}/pokemon/${encodeURIComponent(pokemonName)}/abilities`);
        const data = await response.json();

        // Filter by query
        filteredAbilities.value = data.filter(ability =>
            ability.name.toLowerCase().includes(query)
        );
    } catch (error) {
        console.error('Failed to fetch abilities:', error);
        filteredAbilities.value = [];
    } finally {
        abilitiesLoading.value = false;
    }
};

// Fetch items with usage stats for this Pokemon
const searchItems = async (event) => {
    const query = event.query || '';
    itemsLoading.value = true;

    try {
        const pokemonName = props.pokemon.name || props.pokemon.api_id;
        const params = new URLSearchParams({ q: query, limit: '50' });
        const response = await fetch(`/api/formats/${props.format}/pokemon/${encodeURIComponent(pokemonName)}/items?${params}`);
        const data = await response.json();
        filteredItems.value = data;
    } catch (error) {
        console.error('Failed to fetch items:', error);
        filteredItems.value = [];
    } finally {
        itemsLoading.value = false;
    }
};

// Filter tera types
const searchTeraTypes = (event) => {
    const query = event.query?.toLowerCase() || '';
    filteredTeraTypes.value = allTypes.value.filter(type =>
        type.name.toLowerCase().includes(query)
    );
};

// Handle spread updates from SpreadSelector
const onSpreadUpdate = (newSpread) => {
    spread.value = newSpread;
};

// Load static data on mount
onMounted(async () => {
    // Load types
    try {
        const typesResponse = await fetch('/api/types');
        allTypes.value = await typesResponse.json();
        filteredTeraTypes.value = allTypes.value;
    } catch (error) {
        console.error('Failed to fetch types:', error);
    }
});

// Reset selections when Pokemon changes
watch(() => props.pokemon, () => {
    moves.value = [null, null, null, null];
    ability.value = null;
    item.value = null;
    teraType.value = null;
    spread.value = null;
}, { immediate: true });

// Load initial config when a set is selected
watch(() => props.initialConfig, async (config) => {
    if (!config) return;

    // Load moves - convert strings to objects with name property
    if (config.moves) {
        const newMoves = [];
        for (let i = 0; i < 4; i++) {
            if (config.moves[i]) {
                newMoves[i] = { name: config.moves[i] };
            } else {
                newMoves[i] = null;
            }
        }
        moves.value = newMoves;
    }

    // Load ability
    if (config.ability) {
        ability.value = { name: config.ability };
    }

    // Load item
    if (config.item) {
        item.value = { name: config.item };
    }

    // Load tera type
    if (config.teraType) {
        teraType.value = { name: config.teraType };
    }

    // Load spread (EVs, IVs, nature) - emit to SpreadSelector
    if (config.evs || config.ivs || config.nature) {
        spread.value = {
            evs: config.evs || {hp: 0, atk: 0, def: 0, spa: 0, spd: 0, spe: 0},
            ivs: config.ivs || {hp: 31, atk: 31, def: 31, spa: 31, spd: 31, spe: 31},
            nature: config.nature ? { name: config.nature } : null
        };
    }
}, { immediate: true });

// Emit config updates
watch([moves, ability, item, teraType, spread], () => {
    emit('update:config', {
        moves: moves.value.filter(m => m !== null),
        ability: ability.value,
        item: item.value,
        teraType: teraType.value,
        evs: spread.value?.evs,
        ivs: spread.value?.ivs,
        nature: spread.value?.nature,
    });
}, { deep: true });

// Type colors for styling
const getTypeClass = (typeName) => {
    if (!typeName) return 'bg-gray-500';

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

    return typeColors[typeName.toLowerCase()] || 'bg-gray-500';
};

const getDamageClassStyle = (damageClass) => {
    if (!damageClass) return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';

    const styles = {
        physical: 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
        special: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        status: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    };

    return styles[damageClass.toLowerCase()] || styles.status;
};

const formatDamageClass = (damageClass) => {
    if (!damageClass) return '?';
    const abbrev = {
        physical: 'Phys',
        special: 'Spec',
        status: 'Stat',
    };
    return abbrev[damageClass.toLowerCase()] || damageClass;
};
</script>
