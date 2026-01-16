<template>
    <div class="w-full overflow-hidden">
        <fieldset class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
            <legend class="px-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Base Stats
            </legend>

            <div class="grid grid-cols-[2fr_1fr] items-center gap-4">
                <!-- Radar Chart -->
                <div class="relative h-64 w-full overflow-hidden">
                    <Chart
                        type="radar"
                        :data="chartData"
                        :options="chartOptions"
                        class="w-full h-full"
                    />
                </div>

                <!-- Stats Table -->
                <div>
                    <table class="w-full border-collapse">
                        <tbody>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">HP</td>
                                <td class="py-2 text-right text-zinc-900 dark:text-white">{{ stats.hp }}</td>
                            </tr>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">ATK</td>
                                <td class="py-2 text-right text-zinc-900 dark:text-white">{{ stats.attack }}</td>
                            </tr>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">DEF</td>
                                <td class="py-2 text-right text-zinc-900 dark:text-white">{{ stats.defense }}</td>
                            </tr>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">SPA</td>
                                <td class="py-2 text-right text-zinc-900 dark:text-white">{{ stats.specialAttack }}</td>
                            </tr>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">SPD</td>
                                <td class="py-2 text-right text-zinc-900 dark:text-white">{{ stats.specialDefense }}</td>
                            </tr>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">SPE</td>
                                <td class="py-2 text-right text-zinc-900 dark:text-white">{{ stats.speed }}</td>
                            </tr>
                            <tr class="border-t-2 border-zinc-300 dark:border-zinc-600">
                                <td class="py-2 font-bold text-zinc-900 dark:text-white">BST</td>
                                <td class="py-2 text-right font-bold text-zinc-900 dark:text-white">{{ totalBaseStat }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </fieldset>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        default: () => ({
            hp: 0,
            attack: 0,
            defense: 0,
            specialAttack: 0,
            specialDefense: 0,
            speed: 0
        })
    },
    primaryType: {
        type: String,
        default: null
    },
    pokemonName: {
        type: String,
        default: 'Pokemon'
    }
});

// Type color mapping
const typeColors = {
    normal: '#A8A77A',
    fire: '#EE8130',
    water: '#6390F0',
    electric: '#F7D02C',
    grass: '#7AC74C',
    ice: '#96D9D6',
    fighting: '#C22E28',
    poison: '#A33EA1',
    ground: '#E2BF65',
    flying: '#A98FF3',
    psychic: '#F95587',
    bug: '#A6B91A',
    rock: '#B6A136',
    ghost: '#735797',
    dragon: '#6F35FC',
    dark: '#705746',
    steel: '#B7B7CE',
    fairy: '#D685AD'
};

// Get type color or default blue
const typeColor = computed(() => {
    if (!props.primaryType) return '#3b82f6';
    return typeColors[props.primaryType.toLowerCase()] || '#3b82f6';
});

// Convert hex to RGB
const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 59, g: 130, b: 246 };
};

// Calculate total base stat
const totalBaseStat = computed(() => {
    return props.stats.hp +
        props.stats.attack +
        props.stats.defense +
        props.stats.specialAttack +
        props.stats.specialDefense +
        props.stats.speed;
});

// Chart data
const chartData = computed(() => {
    const rgb = hexToRgb(typeColor.value);
    const rgbString = `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;
    const rgbaString = `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.2)`;

    return {
        labels: ['HP', 'ATK', 'DEF', 'SPA', 'SPD', 'SPE'],
        datasets: [
            {
                label: props.pokemonName,
                data: [
                    props.stats.hp,
                    props.stats.attack,
                    props.stats.defense,
                    props.stats.specialAttack,
                    props.stats.specialDefense,
                    props.stats.speed
                ],
                backgroundColor: rgbaString,
                borderColor: rgbString,
                borderWidth: 2,
                pointBackgroundColor: rgbString,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: rgbString
            }
        ]
    };
});

// Chart options
const chartOptions = computed(() => {
    // Detect dark mode
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
    const labelColor = isDark ? '#ffffff' : '#000000';

    return {
        scales: {
            r: {
                beginAtZero: true,
                min: 0,
                max: 200,
                ticks: {
                    stepSize: 100,
                    display: false
                },
                grid: {
                    color: gridColor,
                    lineWidth: 1
                },
                angleLines: {
                    color: gridColor,
                    lineWidth: 1
                },
                pointLabels: {
                    color: labelColor,
                    font: {
                        size: 12,
                        weight: 500
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        },
        maintainAspectRatio: true
    };
});
</script>