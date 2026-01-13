<template>
  <Head title="Team Builder" />

  <div class="min-h-screen bg-zinc-950">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-zinc-900 shadow-xl rounded-xl overflow-hidden ring-1 ring-zinc-950/5 dark:ring-white/10">
        <div class="px-6 py-8">
          <h1 class="text-4xl font-bold text-zinc-950 dark:text-white mb-2">
            Pokémon Team Builder
          </h1>
          <p class="text-zinc-600 dark:text-zinc-400 mb-6">
            Build and analyze your perfect Pokémon team
          </p>

          <!-- Pokemon Selection Demo -->
          <div class="mb-8 p-4 bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
              Add Pokémon to Team
            </label>
            <div class="flex gap-3">
              <select
                v-model="selectedPokemonId"
                class="flex-1 px-4 py-2 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                :disabled="pokemonStore.loading"
              >
                <option value="">
                  {{ pokemonStore.loading ? 'Loading Pokemon...' : 'Select a Pokemon' }}
                </option>
                <option
                  v-for="pokemon in pokemonStore.pokemon"
                  :key="pokemon.id"
                  :value="pokemon.api_id"
                >
                  #{{ pokemon.api_id }} - {{ pokemon.name }}
                </option>
              </select>
              <button
                @click="handleAddToTeam"
                :disabled="!selectedPokemonId || pokemonStore.loading || pokemonStore.hasFullTeam"
                class="px-6 py-2 bg-sky-600 hover:bg-sky-700 disabled:bg-zinc-600 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors"
              >
                Add
              </button>
            </div>
            <div class="mt-2 flex items-center justify-between">
              <span class="text-xs text-zinc-500 dark:text-zinc-600">
                {{ pokemonStore.loading ? 'Loading...' : `${pokemonStore.pokemon.length} Pokemon available` }}
              </span>
              <span v-if="pokemonStore.loadTime && !pokemonStore.loading" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                ⚡ Loaded in {{ pokemonStore.loadTime }}s
              </span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Team slots -->
            <div
              v-for="(pokemon, index) in pokemonStore.team"
              :key="pokemon.api_id"
              class="border-2 border-sky-500 dark:border-sky-600 rounded-xl p-4 text-center bg-zinc-50 dark:bg-zinc-950 relative group"
            >
              <button
                @click="pokemonStore.removeFromTeam(pokemon.api_id)"
                class="absolute top-2 right-2 p-1 bg-red-600 hover:bg-red-700 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                title="Remove from team"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
              <img
                v-if="pokemon.sprite_front_default"
                :src="pokemon.sprite_front_default"
                :alt="pokemon.name"
                class="w-24 h-24 mx-auto mb-2"
              />
              <p class="text-sm text-zinc-700 dark:text-zinc-300 font-medium">{{ pokemon.name }}</p>
              <p class="text-xs text-zinc-500 dark:text-zinc-600 mt-1">#{{ pokemon.api_id }}</p>
            </div>

            <!-- Empty slots -->
            <div
              v-for="slot in (6 - pokemonStore.team.length)"
              :key="`empty-${slot}`"
              class="border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl p-6 text-center bg-zinc-50 dark:bg-zinc-950"
            >
              <div class="text-zinc-400 dark:text-zinc-600 mb-2">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
              </div>
              <p class="text-sm text-zinc-700 dark:text-zinc-400 font-medium">Empty Slot</p>
              <p class="text-xs text-zinc-500 dark:text-zinc-600 mt-1">Select a Pokémon above</p>
            </div>
          </div>

          <div class="mt-8 p-4 bg-sky-50 dark:bg-sky-950/50 border border-sky-200 dark:border-sky-900 rounded-xl">
            <div class="flex items-center justify-between">
              <p class="text-sm text-sky-900 dark:text-sky-400">
                <strong class="font-semibold">Team Count:</strong> {{ pokemonStore.teamCount }}/{{ pokemonStore.maxTeamSize }}
              </p>
              <button
                v-if="pokemonStore.team.length > 0"
                @click="pokemonStore.clearTeam"
                class="text-xs px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded transition-colors"
              >
                Clear Team
              </button>
            </div>
            <p v-if="pokemonStore.error" class="text-xs text-red-600 dark:text-red-400 mt-2">
              {{ pokemonStore.error }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { usePokemonStore } from '../stores/usePokemonStore';

const pokemonStore = usePokemonStore();
const selectedPokemonId = ref('');

onMounted(async () => {
  // Load any saved team from localStorage
  pokemonStore.loadTeamFromStorage();

  // Fetch initial Pokemon list
  await pokemonStore.fetchPokemon();
});

const handleAddToTeam = async () => {
  if (!selectedPokemonId.value) return;

  // Find the pokemon in the current list
  const pokemon = pokemonStore.pokemon.find(p => p.api_id === parseInt(selectedPokemonId.value));

  if (pokemon) {
    const success = pokemonStore.addToTeam(pokemon);
    if (success) {
      selectedPokemonId.value = ''; // Reset selection
    }
  }
};
</script>
