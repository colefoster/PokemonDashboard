<script setup>
import {ref, computed, watch} from 'vue';
import ScrollPanel from 'primevue/scrollpanel';
import Divider from 'primevue/divider';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Select from 'primevue/select';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import {useTeambuilderStore} from '../stores/useTeambuilderStore';
import PokemonSelector from './PokemonSelector.vue';
import PokemonBuildConfig from './PokemonBuildConfig.vue';

const teambuilderStore = useTeambuilderStore();

// Format options
const formatOptions = [
    {label: 'All Formats', value: 'all'},
    {label: 'Gen 9 OU', value: 'gen9ou'},
    {label: 'Gen 9 UU', value: 'gen9uu'},
    {label: 'Gen 9 RU', value: 'gen9ru'},
    {label: 'Gen 9 NU', value: 'gen9nu'},
    {label: 'Gen 9 Ubers', value: 'gen9ubers'},
    {label: 'Gen 9 AG', value: 'gen9ag'},
    {label: 'Gen 9 Doubles OU', value: 'gen9doublesou'},
];

const formatLabels = Object.fromEntries(
    formatOptions.map(opt => [opt.value, opt.label])
);

// UI State
const selectedSlotIndex = ref(null);
const isAddingPokemon = ref(false);
const renameDialogVisible = ref(false);
const newTeamName = ref('');
const teamToRename = ref(null);

// Filter and group teams by format from store
const teamsByFormat = computed(() => {
    const selectedFormat = teambuilderStore.currentFormat;
    const allTeams = teambuilderStore.teams;
    const filtered = selectedFormat === 'all'
        ? allTeams
        : allTeams.filter(team => team.format === selectedFormat);

    const grouped = {};
    filtered.forEach(team => {
        if (!grouped[team.format]) {
            grouped[team.format] = [];
        }
        grouped[team.format].push(team);
    });
    return grouped;
});

// Get current team members with full Pokemon data
const currentTeamMembers = computed(() => {
    if (!teambuilderStore.currentTeam) return [];
    return teambuilderStore.team;
});

// Currently selected member for editing
const selectedMember = computed(() => {
    if (selectedSlotIndex.value === null || isAddingPokemon.value) return null;
    return currentTeamMembers.value[selectedSlotIndex.value] || null;
});

// Get Pokemon data for selected member
const selectedMemberPokemon = computed(() => {
    if (!selectedMember.value) return null;
    return teambuilderStore.getPokemonFromCache(selectedMember.value.pokemonId);
});

// Team actions
const selectTeam = (team) => {
    teambuilderStore.selectTeam(team.id);
    selectedSlotIndex.value = null;
    isAddingPokemon.value = false;
};

const addNewTeam = () => {
    const format = teambuilderStore.currentFormat === 'all' ? 'gen9ou' : teambuilderStore.currentFormat;
    teambuilderStore.createTeam('New Team', format);
    selectedSlotIndex.value = null;
    isAddingPokemon.value = false;
};

const openRenameDialog = (team) => {
    teamToRename.value = team;
    newTeamName.value = team.name;
    renameDialogVisible.value = true;
};

const confirmRename = () => {
    if (teamToRename.value && newTeamName.value.trim()) {
        teambuilderStore.renameTeam(teamToRename.value.id, newTeamName.value.trim());
    }
    renameDialogVisible.value = false;
    teamToRename.value = null;
};

const deleteCurrentTeam = () => {
    if (teambuilderStore.currentTeamId) {
        teambuilderStore.deleteTeam(teambuilderStore.currentTeamId);
    }
};

// Slot actions
const selectSlot = (index) => {
    const member = currentTeamMembers.value[index];
    if (member) {
        // Slot has a Pokemon - edit it
        selectedSlotIndex.value = index;
        isAddingPokemon.value = false;
    } else {
        // Empty slot - add Pokemon
        selectedSlotIndex.value = index;
        isAddingPokemon.value = true;
    }
};

const startAddingPokemon = () => {
    // Find first empty slot
    const emptyIndex = currentTeamMembers.value.length;
    if (emptyIndex < 6) {
        selectedSlotIndex.value = emptyIndex;
        isAddingPokemon.value = true;
    }
};

const cancelAddingPokemon = () => {
    isAddingPokemon.value = false;
    selectedSlotIndex.value = null;
    teambuilderStore.clearSelection();
};

// Handle Pokemon added from selector
const onPokemonAdded = (pokemonData) => {
    isAddingPokemon.value = false;
    // Select the newly added Pokemon for editing
    const newIndex = currentTeamMembers.value.length - 1;
    selectedSlotIndex.value = newIndex;
};

// Handle build config updates
const onBuildConfigUpdate = (config) => {
    if (!selectedMember.value) return;

    teambuilderStore.updateTeamMember(selectedMember.value.id, {
        moves: config.moves?.map(m => m?.name || m) || [null, null, null, null],
        ability: config.ability?.name || config.ability,
        item: config.item?.name || config.item,
        teraType: config.teraType?.name || config.teraType,
        evs: config.evs || {hp: 0, atk: 0, def: 0, spa: 0, spd: 0, spe: 0},
        ivs: config.ivs || {hp: 31, atk: 31, def: 31, spa: 31, spd: 31, spe: 31},
        nature: config.nature?.name || config.nature
    });
};

// Remove Pokemon from team
const removePokemon = (memberId) => {
    teambuilderStore.removeFromTeam(memberId);
    selectedSlotIndex.value = null;
};

// Get sprite URL for a Pokemon
const getSpriteUrl = (pokemon) => {
    if (!pokemon) return null;
    return pokemon.sprite || pokemon.sprites?.front_default;
};

// Type color class helper
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

// Reset selection when team changes
watch(() => teambuilderStore.currentTeamId, () => {
    selectedSlotIndex.value = null;
    isAddingPokemon.value = false;
});
</script>

<template>
    <div class="flex h-screen">
        <!-- Left Sidebar - Team List -->
        <div class="w-72 bg-surface-50 dark:bg-surface-900 border-r border-surface-200 dark:border-surface-700 flex flex-col">
            <div class="p-4 border-b border-surface-200 dark:border-surface-700">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-surface-900 dark:text-surface-0">Teams</h2>
                </div>
                <Button
                    label="New Team"
                    icon="pi pi-plus"
                    class="w-full"
                    severity="primary"
                    @click="addNewTeam"
                />
            </div>

            <ScrollPanel class="flex-1" style="width: 100%;">
                <div class="p-2">
                    <div v-if="Object.keys(teamsByFormat).length === 0" class="p-4 text-center text-surface-500">
                        No teams yet. Create one to get started!
                    </div>
                    <template v-for="(formatTeams, format) in teamsByFormat" :key="format">
                        <div class="px-2 py-2 mt-2 first:mt-0">
                            <span class="text-xs font-bold uppercase tracking-wider text-surface-500 dark:text-surface-400">
                                {{ formatLabels[format] || format }}
                            </span>
                        </div>

                        <div
                            v-for="team in formatTeams"
                            :key="team.id"
                            @click="selectTeam(team)"
                            @dblclick="openRenameDialog(team)"
                            class="p-3 mb-2 rounded-lg cursor-pointer transition-colors duration-200 border-2 relative"
                            :class="[
                                teambuilderStore.currentTeamId === team.id
                                    ? 'bg-primary-100 dark:bg-primary-900 border-primary-500 dark:border-primary-400 ring-2 ring-primary-500/20'
                                    : 'bg-surface-0 dark:bg-surface-800 hover:bg-surface-100 dark:hover:bg-surface-700 border-surface-200 dark:border-surface-700'
                            ]"
                        >
                            <!-- Editing indicator -->
                            <div
                                v-if="teambuilderStore.currentTeamId === team.id"
                                class="absolute -top-2 -right-2 px-2 py-0.5 bg-primary-500 text-white text-[10px] font-bold uppercase rounded-full shadow-sm"
                            >
                                Editing
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="font-medium text-surface-900 dark:text-surface-0 truncate">
                                    {{ team.name }}
                                </div>
                                <Badge :value="`${team.members.length}/6`" />
                            </div>
                            <!-- Mini team preview -->
                            <div v-if="team.members.length > 0" class="flex gap-1 mt-2">
                                <img
                                    v-for="member in team.members"
                                    :key="member.id"
                                    :src="getSpriteUrl(teambuilderStore.getPokemonFromCache(member.pokemonId))"
                                    class="w-6 h-6 object-contain"
                                    :alt="teambuilderStore.getPokemonFromCache(member.pokemonId)?.name"
                                />
                            </div>
                            <div v-else class="text-xs text-surface-400 mt-2 italic">
                                Empty team
                            </div>
                        </div>

                        <Divider class="my-2" />
                    </template>
                </div>
            </ScrollPanel>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-900">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span v-if="teambuilderStore.currentTeam" class="text-sm font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wide">
                                <i class="pi pi-pencil mr-1"></i>Editing
                            </span>
                        </div>
                        <h1 class="text-2xl font-bold text-zinc-950 dark:text-white">
                            {{ teambuilderStore.currentTeam?.name || 'Team Builder' }}
                        </h1>
                        <span v-if="teambuilderStore.currentTeam" class="text-sm text-surface-500">
                            {{ formatLabels[teambuilderStore.currentTeam.format] }} · {{ teambuilderStore.currentTeam.members.length }}/6 Pokemon
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <Select
                            v-model="teambuilderStore.currentFormat"
                            :options="formatOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filter Format"
                            class="w-48"
                        />
                        <Button
                            v-if="teambuilderStore.currentTeam"
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            @click="deleteCurrentTeam"
                            v-tooltip="'Delete Team'"
                        />
                    </div>
                </div>
            </div>

            <!-- Team Content -->
            <div v-if="teambuilderStore.currentTeam" class="flex-1 flex overflow-hidden">
                <!-- Team Grid -->
                <div class="w-80 p-4 border-r border-surface-200 dark:border-surface-700 overflow-y-auto">
                    <h3 class="text-sm font-semibold text-surface-700 dark:text-surface-300 mb-3">Team Roster</h3>
                    <div class="grid grid-cols-1 gap-3">
                        <template v-for="index in 6" :key="index">
                            <div
                                @click="selectSlot(index - 1)"
                                class="relative p-3 rounded-lg cursor-pointer transition-all duration-200 border-2 min-h-28"
                                :class="[
                                    selectedSlotIndex === index - 1
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30'
                                        : 'border-surface-200 dark:border-surface-700 hover:border-surface-400 dark:hover:border-surface-500 bg-surface-0 dark:bg-surface-800'
                                ]"
                            >
                                <template v-if="currentTeamMembers[index - 1]">
                                    <div class="flex flex-col items-center">
                                        <img
                                            :src="getSpriteUrl(teambuilderStore.getPokemonFromCache(currentTeamMembers[index - 1].pokemonId))"
                                            class="w-16 h-16 object-contain"
                                            :alt="teambuilderStore.getPokemonFromCache(currentTeamMembers[index - 1].pokemonId)?.name"
                                        />
                                        <span class="text-xs font-medium text-center truncate w-full mt-1">
                                            {{ currentTeamMembers[index - 1].nickname || teambuilderStore.getPokemonFromCache(currentTeamMembers[index - 1].pokemonId)?.name }}
                                        </span>
                                        <!-- Type badges -->
                                        <div class="flex gap-1 mt-1">
                                            <span
                                                v-for="type in teambuilderStore.getPokemonFromCache(currentTeamMembers[index - 1].pokemonId)?.types?.slice(0, 2)"
                                                :key="type.id || type.name"
                                                class="px-1 py-0.5 rounded text-[10px] font-medium text-white"
                                                :class="getTypeClass(type.name)"
                                            >
                                                {{ type.name }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Remove button -->
                                    <button
                                        @click.stop="removePokemon(currentTeamMembers[index - 1].id)"
                                        class="absolute top-1 right-1 w-5 h-5 flex items-center justify-center rounded-full bg-red-500 text-white opacity-0 hover:opacity-100 transition-opacity text-xs"
                                    >
                                        <i class="pi pi-times text-[10px]"></i>
                                    </button>
                                </template>
                                <template v-else>
                                    <div class="flex flex-col items-center justify-center h-full text-surface-400">
                                        <i class="pi pi-plus text-2xl mb-1"></i>
                                        <span class="text-xs">Add Pokemon</span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Pokemon Editor Panel -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Adding Pokemon Mode -->
                    <div v-if="isAddingPokemon" class="h-full">
                        <div class="p-4 border-b border-surface-200 dark:border-surface-700 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-0">
                                Add Pokemon to Slot {{ selectedSlotIndex + 1 }}
                            </h3>
                            <Button
                                icon="pi pi-times"
                                severity="secondary"
                                text
                                rounded
                                @click="cancelAddingPokemon"
                            />
                        </div>
                        <PokemonSelector @pokemon-added="onPokemonAdded" />
                    </div>

                    <!-- Editing Pokemon Mode -->
                    <div v-else-if="selectedMember && selectedMemberPokemon" class="p-6">
                        <div class="flex items-center gap-4 mb-6">
                            <img
                                :src="getSpriteUrl(selectedMemberPokemon)"
                                class="w-24 h-24 object-contain bg-surface-100 dark:bg-surface-800 rounded-lg"
                                :alt="selectedMemberPokemon.name"
                            />
                            <div>
                                <h2 class="text-2xl font-bold text-zinc-950 dark:text-white">
                                    {{ selectedMember.nickname || selectedMemberPokemon.name }}
                                </h2>
                                <div class="flex gap-2 mt-1">
                                    <span
                                        v-for="type in selectedMemberPokemon.types"
                                        :key="type.id || type.name"
                                        class="px-2 py-1 rounded text-xs font-medium text-white"
                                        :class="getTypeClass(type.name)"
                                    >
                                        {{ type.name }}
                                    </span>
                                </div>
                                <!-- Quick stats -->
                                <div class="flex gap-2 mt-2 text-xs text-surface-500">
                                    <span v-if="selectedMember.ability">Ability: {{ selectedMember.ability }}</span>
                                    <span v-if="selectedMember.item">@ {{ selectedMember.item }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Build Configuration -->
                        <PokemonBuildConfig
                            :pokemon="selectedMemberPokemon"
                            :format="teambuilderStore.currentTeam.format"
                            @update:config="onBuildConfigUpdate"
                        />

                        <div class="mt-6 pt-6 border-t border-surface-200 dark:border-surface-700">
                            <Button
                                label="Remove from Team"
                                icon="pi pi-trash"
                                severity="danger"
                                outlined
                                @click="removePokemon(selectedMember.id)"
                            />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="flex flex-col items-center justify-center h-full text-surface-500">
                        <i class="pi pi-arrow-left text-4xl mb-4"></i>
                        <p>Select a slot to add or edit a Pokemon</p>
                    </div>
                </div>
            </div>

            <!-- No Team Selected -->
            <div v-else class="flex-1 flex flex-col items-center justify-center text-surface-500">
                <i class="pi pi-users text-6xl mb-4"></i>
                <p class="text-lg">Select a team from the sidebar or create a new one</p>
                <Button
                    label="Create New Team"
                    icon="pi pi-plus"
                    class="mt-4"
                    @click="addNewTeam"
                />
            </div>
        </div>
    </div>

    <!-- Rename Dialog -->
    <Dialog
        v-model:visible="renameDialogVisible"
        header="Rename Team"
        :modal="true"
        :style="{ width: '25rem' }"
    >
        <div class="flex flex-col gap-4">
            <InputText
                v-model="newTeamName"
                placeholder="Team name"
                class="w-full"
                @keyup.enter="confirmRename"
            />
        </div>
        <template #footer>
            <Button label="Cancel" severity="secondary" @click="renameDialogVisible = false" />
            <Button label="Save" @click="confirmRename" />
        </template>
    </Dialog>
</template>
