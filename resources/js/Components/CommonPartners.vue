<template>
    <div>
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Common Teammates</h4>
            <span v-if="loading" class="text-xs text-zinc-500">
                <i class="pi pi-spin pi-spinner"></i>
            </span>
        </div>

        <div v-if="teammates.length > 0" class="flex flex-wrap gap-2">
            <div
                v-for="teammate in teammates"
                :key="teammate.name"
                class="flex items-center gap-2 px-3 py-2 bg-zinc-100 dark:bg-zinc-800 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors cursor-pointer"
                @click="$emit('select', teammate)"
            >
                <img
                    v-if="teammate.sprite"
                    :src="teammate.sprite"
                    :alt="teammate.name"
                    class="w-8 h-8 object-contain pixelated"
                    @error="handleImageError($event, teammate)"
                />
                <div v-else class="w-8 h-8 bg-zinc-300 dark:bg-zinc-600 rounded flex items-center justify-center text-xs text-zinc-500">?</div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ teammate.name }}</span>
                    <div class="flex items-center gap-1">
                        <span
                            v-for="type in teammate.types?.slice(0, 2)"
                            :key="type"
                            class="inline-block w-2 h-2 rounded-full"
                            :class="getTypeClass(type)"
                        ></span>
                        <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400">
                            {{ formatUsage(teammate.usage) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="!loading" class="text-sm text-zinc-500 dark:text-zinc-400 italic">
            No teammate data available
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    pokemon: {
        type: Object,
        required: true
    },
    format: {
        type: String,
        default: 'gen9ou'
    }
});

defineEmits(['select']);

const teammates = ref([]);
const loading = ref(false);

const formatUsage = (usage) => {
    if (usage == null) return '';
    return (usage * 100).toFixed(1) + '%';
};

const handleImageError = (event, pokemon) => {
    // Hide the broken image
    event.target.style.display = 'none';
};

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

const fetchTeammates = async () => {
    if (!props.pokemon?.name) return;

    loading.value = true;
    try {
        const pokemonName = props.pokemon.name;
        const response = await fetch(
            `/api/formats/${props.format}/pokemon/${encodeURIComponent(pokemonName)}/teammates?limit=8`
        );
        teammates.value = await response.json();
    } catch (error) {
        console.error('Failed to fetch teammates:', error);
        teammates.value = [];
    } finally {
        loading.value = false;
    }
};

watch(() => props.pokemon, fetchTeammates, { immediate: true });
watch(() => props.format, fetchTeammates);
</script>

<style scoped>
.pixelated {
    image-rendering: pixelated;
    image-rendering: -moz-crisp-edges;
    image-rendering: crisp-edges;
}
</style>
