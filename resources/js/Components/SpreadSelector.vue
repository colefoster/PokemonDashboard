<template>
    <div class="space-y-4">
        <!-- Spread Autocomplete -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                EV Spread
                <span class="text-xs text-zinc-500 ml-2">{{ evTotal }}/510 EVs used</span>
            </label>
            <AutoComplete
                v-model="selectedSpread"
                :suggestions="filteredSpreads"
                optionLabel="spread_string"
                placeholder="Select a popular spread or customize below..."
                dropdown
                showClear
                :loading="spreadsLoading"
                @complete="searchSpreads"
                @item-select="onSpreadSelect"
                class="w-full"
            >
                <template #option="{ option }">
                    <div class="flex items-center justify-between w-full py-1 gap-4">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                                {{ option.nature }}
                            </span>
                            <span class="font-mono text-sm text-zinc-700 dark:text-zinc-300">
                                {{ formatEvString(option.evs) }}
                            </span>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-xs font-mono bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                            {{ formatUsage(option.usage) }}
                        </span>
                    </div>
                </template>
            </AutoComplete>
        </div>

        <!-- Nature Selection -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Nature</label>
            <Select
                v-model="nature"
                :options="natures"
                optionLabel="name"
                placeholder="Select nature..."
                class="w-full md:w-1/2"
            >
                <template #value="{ value }">
                    <div v-if="value" class="flex items-center gap-2">
                        <span>{{ value.name }}</span>
                        <span v-if="value.increased" class="text-xs text-green-600">+{{ value.increased }}</span>
                        <span v-if="value.decreased" class="text-xs text-red-600">-{{ value.decreased }}</span>
                    </div>
                    <span v-else>Select nature...</span>
                </template>
                <template #option="{ option }">
                    <div class="flex items-center justify-between w-full">
                        <span>{{ option.name }}</span>
                        <div class="flex items-center gap-2 text-xs">
                            <span v-if="option.increased" class="text-green-600 dark:text-green-400">+{{ option.increased }}</span>
                            <span v-if="option.decreased" class="text-red-600 dark:text-red-400">-{{ option.decreased }}</span>
                            <span v-if="!option.increased" class="text-zinc-500">Neutral</span>
                        </div>
                    </div>
                </template>
            </Select>
        </div>

        <!-- EV Sliders -->
        <div class="space-y-3">
            <div v-for="stat in stats" :key="stat.key" class="flex items-center gap-3">
                <div class="w-12 text-right">
                    <span
                        class="text-sm font-medium"
                        :class="getStatColor(stat.key)"
                    >
                        {{ stat.label }}
                    </span>
                </div>
                <div class="flex-1">
                    <Slider
                        v-model="evs[stat.key]"
                        :min="0"
                        :max="252"
                        :step="4"
                        class="w-full"
                        :pt="{
                            range: { class: getSliderRangeClass(stat.key) }
                        }"
                    />
                </div>
                <div class="w-12">
                    <InputNumber
                        v-model="evs[stat.key]"
                        :min="0"
                        :max="252"
                        :step="4"
                        inputClass="w-12 text-center text-sm p-1"
                        :pt="{ input: { class: 'w-full text-center' } }"
                    />
                </div>
            </div>
        </div>

        <!-- EV Total Bar -->
        <div class="mt-4">
            <div class="flex justify-between text-xs text-zinc-600 dark:text-zinc-400 mb-1">
                <span>EV Total</span>
                <span :class="evTotal > 510 ? 'text-red-500 font-bold' : ''">
                    {{ evTotal }} / 510
                </span>
            </div>
            <div class="h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                <div
                    class="h-full transition-all duration-200"
                    :class="evTotal > 510 ? 'bg-red-500' : 'bg-emerald-500'"
                    :style="{ width: Math.min((evTotal / 510) * 100, 100) + '%' }"
                ></div>
            </div>
        </div>

        <!-- IV Section -->
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-3">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">IVs</label>
                <Button
                    label="Max All"
                    size="small"
                    severity="secondary"
                    text
                    @click="maxAllIvs"
                />
            </div>
            <div class="grid grid-cols-6 gap-2">
                <div v-for="stat in stats" :key="stat.key" class="text-center">
                    <label class="block text-xs font-medium mb-1" :class="getStatColor(stat.key)">
                        {{ stat.label }}
                    </label>
                    <InputNumber
                        v-model="ivs[stat.key]"
                        :min="0"
                        :max="31"
                        inputClass="w-full text-center text-sm p-1"
                        :pt="{ input: { class: 'w-full text-center' } }"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import Select from 'primevue/select';
import Slider from 'primevue/slider';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';

const props = defineProps({
    pokemon: {
        type: Object,
        required: true
    },
    format: {
        type: String,
        default: 'gen9ou'
    },
    initialSpread: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:spread']);

// Stats configuration
const stats = [
    { key: 'hp', label: 'HP' },
    { key: 'atk', label: 'Atk' },
    { key: 'def', label: 'Def' },
    { key: 'spa', label: 'SpA' },
    { key: 'spd', label: 'SpD' },
    { key: 'spe', label: 'Spe' },
];

// State
const selectedSpread = ref(null);
const filteredSpreads = ref([]);
const spreadsLoading = ref(false);
const allSpreads = ref([]);

const evs = ref({
    hp: 0,
    atk: 0,
    def: 0,
    spa: 0,
    spd: 0,
    spe: 0,
});

const ivs = ref({
    hp: 31,
    atk: 31,
    def: 31,
    spa: 31,
    spd: 31,
    spe: 31,
});

const nature = ref(null);
const natures = ref([]);

// Computed
const evTotal = computed(() => {
    return Object.values(evs.value).reduce((sum, val) => sum + (val || 0), 0);
});

// Methods
const formatUsage = (usage) => {
    if (usage == null) return '';
    return (usage * 100).toFixed(1) + '%';
};

const formatEvString = (evObj) => {
    return `${evObj.hp}/${evObj.atk}/${evObj.def}/${evObj.spa}/${evObj.spd}/${evObj.spe}`;
};

const getStatColor = (statKey) => {
    const colors = {
        hp: 'text-red-500',
        atk: 'text-orange-500',
        def: 'text-yellow-500',
        spa: 'text-blue-500',
        spd: 'text-green-500',
        spe: 'text-pink-500',
    };
    return colors[statKey] || 'text-zinc-500';
};

const getSliderRangeClass = (statKey) => {
    const colors = {
        hp: '!bg-red-500',
        atk: '!bg-orange-500',
        def: '!bg-yellow-500',
        spa: '!bg-blue-500',
        spd: '!bg-green-500',
        spe: '!bg-pink-500',
    };
    return colors[statKey] || '!bg-zinc-500';
};

const searchSpreads = async (event) => {
    const query = event.query?.toLowerCase() || '';

    if (allSpreads.value.length === 0) {
        await fetchSpreads();
    }

    if (query) {
        filteredSpreads.value = allSpreads.value.filter(spread =>
            spread.spread_string.toLowerCase().includes(query) ||
            spread.nature.toLowerCase().includes(query)
        );
    } else {
        filteredSpreads.value = allSpreads.value;
    }
};

const fetchSpreads = async () => {
    if (!props.pokemon?.name) return;

    spreadsLoading.value = true;
    try {
        const pokemonName = props.pokemon.name;
        const response = await fetch(
            `/api/formats/${props.format}/pokemon/${encodeURIComponent(pokemonName)}/spreads?limit=20`
        );
        allSpreads.value = await response.json();
        filteredSpreads.value = allSpreads.value;
    } catch (error) {
        console.error('Failed to fetch spreads:', error);
        allSpreads.value = [];
        filteredSpreads.value = [];
    } finally {
        spreadsLoading.value = false;
    }
};

const onSpreadSelect = (event) => {
    const spread = event.value;
    if (!spread) return;

    // Update EVs
    evs.value = { ...spread.evs };

    // Update nature
    const foundNature = natures.value.find(n => n.name === spread.nature);
    if (foundNature) {
        nature.value = foundNature;
    }
};

const maxAllIvs = () => {
    ivs.value = {
        hp: 31,
        atk: 31,
        def: 31,
        spa: 31,
        spd: 31,
        spe: 31,
    };
};

// Load natures on mount
onMounted(async () => {
    try {
        const response = await fetch('/api/natures');
        natures.value = await response.json();
    } catch (error) {
        console.error('Failed to fetch natures:', error);
    }
});

// Reset when Pokemon changes
watch(() => props.pokemon, () => {
    selectedSpread.value = null;
    evs.value = { hp: 0, atk: 0, def: 0, spa: 0, spd: 0, spe: 0 };
    ivs.value = { hp: 31, atk: 31, def: 31, spa: 31, spd: 31, spe: 31 };
    nature.value = null;
    allSpreads.value = [];
    filteredSpreads.value = [];
}, { immediate: true });

// Load initial spread when provided (from parent/preset)
watch(() => props.initialSpread, (newSpread) => {
    if (!newSpread) return;

    if (newSpread.evs) {
        evs.value = { ...evs.value, ...newSpread.evs };
    }
    if (newSpread.ivs) {
        ivs.value = { ...ivs.value, ...newSpread.ivs };
    }
    if (newSpread.nature) {
        // Find the nature object from our natures list or create one
        if (typeof newSpread.nature === 'string') {
            const found = natures.value.find(n => n.name.toLowerCase() === newSpread.nature.toLowerCase());
            nature.value = found || { name: newSpread.nature };
        } else {
            nature.value = newSpread.nature;
        }
    }
}, { immediate: true });

// Emit updates
watch([evs, ivs, nature], () => {
    emit('update:spread', {
        evs: evs.value,
        ivs: ivs.value,
        nature: nature.value,
    });
}, { deep: true });
</script>
