<script setup>
const props = defineProps({
    pokemon: Object,
    slotNumber: Number,
});

const emit = defineEmits(['add', 'remove']);
</script>

<template>
    <div
        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center transition hover:border-gray-400 dark:hover:border-gray-500"
        :class="{ 'border-solid bg-gray-50 dark:bg-gray-800': pokemon }"
    >
        <!-- Empty Slot -->
        <div v-if="!pokemon" class="flex flex-col items-center justify-center h-40">
            <div class="text-4xl text-gray-400 mb-2">+</div>
            <button
                @click="emit('add')"
                class="text-sm text-blue-500 hover:text-blue-600 font-medium"
            >
                Add Pokemon
            </button>
            <span class="text-xs text-gray-500 mt-1">Slot {{ slotNumber }}</span>
        </div>

        <!-- Filled Slot -->
        <div v-else class="flex flex-col items-center">
            <img :src="pokemon.sprite" :alt="pokemon.name" class="w-24 h-24 mb-2">
            <h3 class="font-semibold text-gray-900 dark:text-white">{{ pokemon.name }}</h3>
            <div class="flex gap-1 mt-1 mb-3">
                <span
                    v-for="type in pokemon.types"
                    :key="type"
                    class="text-xs px-2 py-1 rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                >
                    {{ type }}
                </span>
            </div>
            <button
                @click="emit('remove')"
                class="text-xs text-red-500 hover:text-red-600"
            >
                Remove
            </button>
        </div>
    </div>
</template>