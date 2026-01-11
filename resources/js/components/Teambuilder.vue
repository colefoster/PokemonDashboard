<script setup>
import { ref } from 'vue';
import { useTeamStore } from '@/stores/teamStore';
import TeamSlot from './TeamSlot.vue';

const teamStore = useTeamStore();

// Example Pokemon data (in real app, fetch from API)
const examplePokemon = [
    { id: 25, name: 'Pikachu', sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png', types: ['electric'] },
    { id: 1, name: 'Bulbasaur', sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/1.png', types: ['grass', 'poison'] },
    { id: 6, name: 'Charizard', sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/6.png', types: ['fire', 'flying'] },
    { id: 143, name: 'Snorlax', sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/143.png', types: ['normal'] },
];

const showSelector = ref(false);
const selectedSlot = ref(null);

function openSelector(slotIndex) {
    selectedSlot.value = slotIndex;
    showSelector.value = true;
}

function selectPokemon(pokemon) {
    teamStore.addPokemon(pokemon, selectedSlot.value);
    showSelector.value = false;
    selectedSlot.value = null;
}
</script>

<template>
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Pokemon Team Builder
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Build your perfect team of 6 Pokemon
            </p>
        </div>

        <!-- Team Stats -->
        <div class="mb-6 flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Team: {{ teamStore.teamCount }} / 6 Pokemon
            </div>
            <button
                @click="teamStore.clearTeam"
                v-if="!teamStore.isEmpty"
                class="px-4 py-2 text-sm bg-red-500 text-white rounded hover:bg-red-600 transition"
            >
                Clear Team
            </button>
        </div>

        <!-- Team Slots Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
            <TeamSlot
                v-for="(pokemon, index) in teamStore.team"
                :key="index"
                :pokemon="pokemon"
                :slot-number="index + 1"
                @add="openSelector(index)"
                @remove="teamStore.removePokemon(index)"
            />
        </div>

        <!-- Pokemon Selector Modal -->
        <div
            v-if="showSelector"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click="showSelector = false"
        >
            <div
                class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4"
                @click.stop
            >
                <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                    Select a Pokemon
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <button
                        v-for="pokemon in examplePokemon"
                        :key="pokemon.id"
                        @click="selectPokemon(pokemon)"
                        class="flex flex-col items-center p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                    >
                        <img :src="pokemon.sprite" :alt="pokemon.name" class="w-24 h-24">
                        <span class="text-sm font-medium mt-2 text-gray-900 dark:text-white">
                            {{ pokemon.name }}
                        </span>
                    </button>
                </div>
                <button
                    @click="showSelector = false"
                    class="mt-4 w-full py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</template>