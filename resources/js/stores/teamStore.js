import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useTeamStore = defineStore('team', () => {
    // State: 6 Pokemon slots
    const team = ref([
        null, null, null, null, null, null
    ]);

    // Getters
    const teamCount = computed(() => team.value.filter(p => p !== null).length);
    const isFull = computed(() => teamCount.value === 6);
    const isEmpty = computed(() => teamCount.value === 0);

    // Actions
    function addPokemon(pokemon, slotIndex = null) {
        if (slotIndex !== null && slotIndex >= 0 && slotIndex < 6) {
            team.value[slotIndex] = pokemon;
        } else {
            // Find first empty slot
            const emptyIndex = team.value.findIndex(p => p === null);
            if (emptyIndex !== -1) {
                team.value[emptyIndex] = pokemon;
            }
        }
    }

    function removePokemon(slotIndex) {
        if (slotIndex >= 0 && slotIndex < 6) {
            team.value[slotIndex] = null;
        }
    }

    function clearTeam() {
        team.value = [null, null, null, null, null, null];
    }

    function swapPokemon(fromIndex, toIndex) {
        const temp = team.value[fromIndex];
        team.value[fromIndex] = team.value[toIndex];
        team.value[toIndex] = temp;
    }

    return {
        team,
        teamCount,
        isFull,
        isEmpty,
        addPokemon,
        removePokemon,
        clearTeam,
        swapPokemon,
    };
});