<template>
    <Head title="Team Builder"/>

    <div class="min-h-screen bg-zinc-950">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div
                class="bg-white dark:bg-zinc-900 shadow-xl rounded-xl overflow-hidden ring-1 ring-zinc-950/5 dark:ring-white/10">
                <div class="px-6 py-8">
                    <h1 class="text-4xl font-bold text-zinc-950 dark:text-white mb-2">
                        Team Builder
                    </h1>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                        {{ pokemonStore.formattedFormat }}
                    </p>

                    <AutoComplete
                        v-model="searchTerm"
                        :suggestions="filteredPokemon"
                        :placeholder="`Search ${pokemonStore.formattedFormat} Pokemon...`"
                        dropdown
                        showClear
                        forceSelection
                        :loading="pokemonStore.loading"
                        @complete="search"
                        @item-select="onPokemonSelect"
                        @clear="clearSelection"
                        size="large"
                    />
                </div>
            </div>

            <!-- Selected Pokemon Details -->
            <div
                v-if="pokemonStore.selectedPokemonData"
                class="bg-white dark:bg-zinc-900 shadow-xl rounded-xl overflow-hidden ring-1 ring-zinc-950/5 dark:ring-white/10 mt-4">
                <div class="px-6 py-8">
                    <!-- Header with sprite and name -->
                    <div class="flex items-center gap-4 mb-6">
                        <img
                            v-if="pokemonStore.selectedPokemonData.sprites?.front_default"
                            :src="pokemonStore.selectedPokemonData.sprites.front_default"
                            :alt="pokemonStore.selectedPokemon"
                            class="w-24 h-24"
                        />
                        <div>
                            <h2 class="text-2xl font-bold text-zinc-950 dark:text-white">
                                {{ pokemonStore.selectedPokemon }}
                            </h2>
                            <div class="flex gap-2 mt-1">
                                <span
                                    v-for="type in pokemonStore.selectedPokemonData.types"
                                    :key="type.id"
                                    class="px-2 py-1 rounded text-xs font-medium text-white"
                                    :class="getTypeClass(type.name)"
                                >
                                    {{ formatName(type.name) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-zinc-950 dark:text-white mb-3">Base Stats</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <div
                                v-for="stat in pokemonStore.selectedPokemonData.stats"
                                :key="stat.stat_name"
                                class="bg-zinc-100 dark:bg-zinc-800 rounded-lg p-3"
                            >
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 uppercase">
                                    {{ formatStatName(stat.stat_name) }}
                                </div>
                                <div class="text-xl font-bold text-zinc-950 dark:text-white">
                                    {{ stat.base_stat }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sets -->
                    <div v-if="pokemonStore.selectedPokemonSets">
                        <h3 class="text-lg font-semibold text-zinc-950 dark:text-white mb-3">Smogon Sets</h3>
                        <div class="space-y-4">
                            <div
                                v-for="(setData, setName) in pokemonStore.selectedPokemonSets"
                                :key="setName"
                                class="bg-zinc-100 dark:bg-zinc-800 rounded-lg p-4"
                            >
                                <h4 class="font-medium text-zinc-950 dark:text-white mb-2">{{ setName }}</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div v-if="setData.ability">
                                        <span class="text-zinc-500 dark:text-zinc-400">Ability:</span>
                                        <span class="text-zinc-950 dark:text-white ml-1">
                                            {{ Array.isArray(setData.ability) ? setData.ability.join(' / ') : setData.ability }}
                                        </span>
                                    </div>
                                    <div v-if="setData.item">
                                        <span class="text-zinc-500 dark:text-zinc-400">Item:</span>
                                        <span class="text-zinc-950 dark:text-white ml-1">
                                            {{ Array.isArray(setData.item) ? setData.item.join(' / ') : setData.item }}
                                        </span>
                                    </div>
                                    <div v-if="setData.nature">
                                        <span class="text-zinc-500 dark:text-zinc-400">Nature:</span>
                                        <span class="text-zinc-950 dark:text-white ml-1">
                                            {{ Array.isArray(setData.nature) ? setData.nature.join(' / ') : setData.nature }}
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
                                            v-for="(move, idx) in formatMoves(setData.moves)"
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

                    <div class="mt-6 flex gap-2">
                        <Button label="Add to Team" @click="handleAddToTeam" :disabled="pokemonStore.hasFullTeam" />
                        <Button label="Clear" severity="secondary" @click="clearSelection" />
                    </div>
                </div>
            </div>

            <!-- Loading state -->
            <div
                v-else-if="pokemonStore.loading && searchTerm"
                class="bg-white dark:bg-zinc-900 shadow-xl rounded-xl overflow-hidden ring-1 ring-zinc-950/5 dark:ring-white/10 mt-4">
                <div class="px-6 py-8 text-center">
                    <p class="text-zinc-600 dark:text-zinc-400">Loading Pokemon data...</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref} from 'vue';
import {Head} from '@inertiajs/vue3';
import {usePokemonStore} from '../stores/usePokemonStore';

import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';

const pokemonStore = usePokemonStore();
const filteredPokemon = ref([]);
const searchTerm = ref('');

const search = (event) => {
    const query = event.query.toLowerCase();
    filteredPokemon.value = pokemonStore.pokemon.filter(name =>
        name.toLowerCase().includes(query)
    );
};

const onPokemonSelect = async (event) => {
    const selectedName = event.value;
    await pokemonStore.fetchCombinedByName(selectedName);
};

const clearSelection = () => {
    searchTerm.value = '';
    pokemonStore.clearSelection();
};

const handleAddToTeam = () => {
    if (!pokemonStore.selectedPokemonData) return;

    const success = pokemonStore.addToTeam(pokemonStore.selectedPokemonData);
    if (success) {
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