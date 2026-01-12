# Pokemon Team Builder - TDD Test Suite

This test suite provides comprehensive coverage for building a Pokemon team builder feature using Vue 3 + Pinia with Test-Driven Development (TDD).

## Overview

The test suite is organized to support incremental development of:
- 6 team slots for selecting unique Pokemon
- Pokemon data display with sprites and types
- Team summary component with type coverage
- Full team management (add, remove, clear, swap)

## Test Structure

```
tests/
├── setup.js                           # Global test configuration
├── unit/
│   ├── stores/
│   │   └── teamStore.spec.js         # Pinia store tests (71 tests)
│   └── components/
│       ├── TeamSlot.spec.js          # Team slot component (48 tests)
│       ├── PokemonCard.spec.js       # Pokemon display card (42 tests)
│       ├── PokemonSelector.spec.js   # Pokemon selection modal (57 tests)
│       └── TeamSummary.spec.js       # Team overview (56 tests)
└── integration/
    └── Teambuilder.spec.js           # Full integration (50+ tests)
```

**Total: 320+ tests covering all functionality**

## Getting Started

### 1. Install Dependencies

```bash
npm install
```

This will install:
- `vitest` - Fast unit test framework
- `@vue/test-utils` - Vue component testing utilities
- `@pinia/testing` - Pinia store testing helpers
- `happy-dom` - Fast DOM implementation
- `@vitest/ui` - Optional UI for test visualization

### 2. Run Tests

```bash
# Run all tests
npm test

# Run tests in watch mode (recommended for TDD)
npm test -- --watch

# Run tests with UI
npm run test:ui

# Run tests with coverage
npm run test:coverage

# Run specific test file
npm test tests/unit/stores/teamStore.spec.js

# Run tests matching a pattern
npm test -- --grep "should add Pokemon"
```

## TDD Workflow

### Phase 1: Store Foundation (Start Here)

The Pinia store is the core of the team builder. Start by making these tests pass:

```bash
npm test tests/unit/stores/teamStore.spec.js
```

**Components to implement:**
- `resources/js/stores/teamStore.js` (already exists, verify all tests pass)

**Key functionality:**
1. ✅ Initialize with 6 empty slots
2. ✅ Add Pokemon to specific slot or first empty slot
3. ✅ Remove Pokemon from slot
4. ✅ Clear entire team
5. ✅ Swap Pokemon between slots
6. ✅ Computed properties: `teamCount`, `isEmpty`, `isFull`

### Phase 2: TeamSlot Component

Build the individual team slot component:

```bash
npm test tests/unit/components/TeamSlot.spec.js
```

**Component to implement:**
- `resources/js/components/TeamSlot.vue` (already exists, verify tests pass)

**Key functionality:**
1. Display empty state with "Add Pokemon" button
2. Display filled state with Pokemon sprite, name, and types
3. Emit `add` event when clicking empty slot
4. Emit `remove` event when clicking remove button
5. Reactive updates when props change

**Props:**
- `pokemon` (Object | null) - The Pokemon data or null for empty
- `slotNumber` (Number) - Slot number 1-6

**Events:**
- `add` - Emitted when user clicks "Add Pokemon"
- `remove` - Emitted when user clicks "Remove"

### Phase 3: PokemonCard Component (New)

Create a reusable card for displaying Pokemon:

```bash
npm test tests/unit/components/PokemonCard.spec.js
```

**Component to create:**
- `resources/js/components/PokemonCard.vue`

**Key functionality:**
1. Display Pokemon sprite with alt text
2. Display Pokemon name and ID (#25)
3. Display type badges
4. Optional: Show stats when `showStats` prop is true
5. Optional: Make clickable with hover effects when `clickable` prop is true
6. Emit click event with Pokemon data
7. Keyboard accessible (Tab, Enter)

**Props:**
- `pokemon` (Object, required) - Pokemon data
- `clickable` (Boolean, default: false) - Make card clickable
- `showStats` (Boolean, default: false) - Show stats

**Events:**
- `click` - Emitted when clickable and card is clicked, includes Pokemon data

**Example Structure:**
```vue
<template>
  <div
    :class="{ 'cursor-pointer hover:shadow-lg': clickable }"
    :tabindex="clickable ? 0 : undefined"
    @click="handleClick"
    @keydown.enter="handleClick"
  >
    <img :src="pokemon.sprite" :alt="pokemon.name" />
    <h3>{{ pokemon.name }}</h3>
    <span class="text-xs text-gray-500">#{{ pokemon.id }}</span>
    <div class="flex gap-1">
      <span
        v-for="type in pokemon.types"
        :key="type"
        data-test="pokemon-type"
        class="px-2 py-1 rounded text-xs"
      >
        {{ type }}
      </span>
    </div>
    <!-- Stats section if showStats -->
  </div>
</template>

<script setup>
const props = defineProps({
  pokemon: { type: Object, required: true },
  clickable: { type: Boolean, default: false },
  showStats: { type: Boolean, default: false }
});

const emit = defineEmits(['click']);

function handleClick() {
  if (props.clickable) {
    emit('click', props.pokemon);
  }
}
</script>
```

### Phase 4: PokemonSelector Component (New)

Create the modal for selecting Pokemon:

```bash
npm test tests/unit/components/PokemonSelector.spec.js
```

**Component to create:**
- `resources/js/components/PokemonSelector.vue`

**Key functionality:**
1. Display modal with backdrop
2. Show list of available Pokemon using PokemonCard
3. Filter out already selected Pokemon (by ID)
4. Search/filter by name (case-insensitive)
5. Filter by type
6. Emit `select` event with Pokemon data
7. Emit `cancel` event when closed
8. Close on backdrop click or Escape key
9. Focus search input on mount
10. Show empty states ("No Pokemon available", "No Pokemon found")

**Props:**
- `availablePokemon` (Array, required) - List of all Pokemon
- `selectedPokemonIds` (Array, default: []) - IDs of Pokemon already on team

**Events:**
- `select` - Emitted with Pokemon object when user selects
- `cancel` - Emitted when user closes without selecting

**Example Structure:**
```vue
<template>
  <div
    data-test="modal-backdrop"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click="$emit('cancel')"
    @keydown.esc="$emit('cancel')"
    role="dialog"
    aria-modal="true"
  >
    <div
      data-test="modal-content"
      class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto"
      @click.stop
    >
      <h2 class="text-2xl font-bold mb-4">Select a Pokemon</h2>

      <input
        ref="searchInputRef"
        data-test="search-input"
        v-model="searchQuery"
        type="text"
        placeholder="Search by name..."
        aria-label="Search Pokemon"
        class="w-full px-4 py-2 border rounded mb-4"
      />

      <!-- Type filters -->
      <div class="mb-4 flex flex-wrap gap-2">
        <button
          data-test="type-filter-all"
          @click="selectedType = 'all'"
          :class="{ 'bg-blue-500 text-white': selectedType === 'all' }"
          class="px-3 py-1 rounded border"
        >
          All Types
        </button>
        <button
          v-for="type in availableTypes"
          :key="type"
          :data-test="`type-filter-${type}`"
          @click="selectedType = type"
          :class="{ 'bg-blue-500 text-white': selectedType === type }"
          class="px-3 py-1 rounded border"
        >
          {{ type }}
        </button>
      </div>

      <!-- Pokemon grid -->
      <div v-if="filteredPokemon.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <PokemonCard
          v-for="pokemon in filteredPokemon"
          :key="pokemon.id"
          :data-test="`pokemon-card-${pokemon.id}`"
          data-test="pokemon-card"
          :pokemon="pokemon"
          :clickable="true"
          @click="$emit('select', pokemon)"
        />
      </div>

      <!-- Empty states -->
      <div v-else-if="availablePokemon.length === 0" class="text-center py-8 text-gray-500">
        No Pokemon available
      </div>
      <div v-else class="text-center py-8 text-gray-500">
        No Pokemon found
      </div>

      <button
        data-test="cancel-button"
        @click="$emit('cancel')"
        class="mt-4 w-full py-2 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300"
      >
        Cancel
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import PokemonCard from './PokemonCard.vue';

const props = defineProps({
  availablePokemon: { type: Array, required: true },
  selectedPokemonIds: { type: Array, default: () => [] }
});

const emit = defineEmits(['select', 'cancel']);

const searchQuery = ref('');
const selectedType = ref('all');
const searchInputRef = ref(null);

const filteredPokemon = computed(() => {
  let result = props.availablePokemon;

  // Filter out selected Pokemon
  if (props.selectedPokemonIds && props.selectedPokemonIds.length > 0) {
    result = result.filter(p => !props.selectedPokemonIds.includes(p.id));
  }

  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(p =>
      p.name.toLowerCase().includes(query)
    );
  }

  // Filter by type
  if (selectedType.value !== 'all') {
    result = result.filter(p =>
      p.types && p.types.includes(selectedType.value)
    );
  }

  return result;
});

const availableTypes = computed(() => {
  const types = new Set();
  props.availablePokemon.forEach(pokemon => {
    if (pokemon.types) {
      pokemon.types.forEach(type => types.add(type));
    }
  });
  return Array.from(types).sort();
});

onMounted(() => {
  searchInputRef.value?.focus();
});
</script>
```

### Phase 5: TeamSummary Component (New)

Create a summary view of the team:

```bash
npm test tests/unit/components/TeamSummary.spec.js
```

**Component to create:**
- `resources/js/components/TeamSummary.vue`

**Key functionality:**
1. Connect to teamStore
2. Display team count (e.g., "Team: 3 / 6")
3. Show type coverage with count per type
4. Display all Pokemon in team with sprites
5. Show empty state when no Pokemon
6. Show "Team Complete" indicator when full
7. Clear team button (only when not empty)
8. Real-time updates when store changes

**No props needed** - Reads directly from `useTeamStore()`

**Events:**
- `clear` - Emitted when clear button clicked

**Example Structure:**
```vue
<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
    <h2 class="text-2xl font-bold mb-4">Team Summary</h2>

    <!-- Empty state -->
    <div v-if="teamStore.isEmpty" class="text-center py-8 text-gray-500">
      Your team is empty. Add some Pokemon to get started!
    </div>

    <!-- Team content -->
    <div v-else>
      <!-- Team count and status -->
      <div
        data-test="team-status"
        aria-label="Team status"
        aria-live="polite"
        class="mb-4"
        :class="{ 'team-full': teamStore.isFull }"
      >
        <p class="text-lg font-semibold">
          Team: {{ teamStore.teamCount }} / 6
          <span v-if="teamStore.isFull" class="ml-2 text-green-600">✓ Team Complete</span>
        </p>
      </div>

      <!-- Team statistics -->
      <div class="mb-4">
        <h3 class="text-lg font-semibold mb-2">Team Statistics</h3>
        <p>Pokemon: {{ teamStore.teamCount }}</p>
        <p>Types: {{ uniqueTypes.length }}</p>
      </div>

      <!-- Type coverage -->
      <div class="mb-4">
        <h3 class="text-lg font-semibold mb-2">Type Coverage</h3>
        <div v-if="uniqueTypes.length > 0" data-test="types-list" class="flex flex-wrap gap-2">
          <span
            v-for="[type, count] in typesCoverage"
            :key="type"
            data-test="type-badge"
            :data-test="`type-badge-${type}`"
            class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700 text-sm"
          >
            {{ type }} <span class="font-bold">({{ count }})</span>
          </span>
        </div>
      </div>

      <!-- Pokemon overview -->
      <div class="mb-4">
        <h3 class="text-lg font-semibold mb-2">Team Overview</h3>
        <div class="grid grid-cols-6 gap-2">
          <img
            v-for="(pokemon, index) in teamStore.team"
            :key="index"
            v-if="pokemon"
            :src="pokemon.sprite"
            :alt="pokemon.name"
            data-test="pokemon-sprite"
            class="w-full h-auto"
          />
          <div
            v-for="(pokemon, index) in teamStore.team"
            :key="`empty-${index}`"
            v-if="!pokemon"
            data-test="empty-slot"
            class="aspect-square border-2 border-dashed border-gray-300 rounded flex items-center justify-center"
          >
            <span class="text-gray-400 text-xs">Empty</span>
          </div>
        </div>
      </div>

      <!-- Clear button -->
      <button
        v-if="!teamStore.isEmpty"
        data-test="clear-button"
        @click="$emit('clear')"
        class="w-full py-2 bg-red-500 text-white rounded hover:bg-red-600 transition"
      >
        Clear Team
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTeamStore } from '@/stores/teamStore';

const teamStore = useTeamStore();
const emit = defineEmits(['clear']);

const uniqueTypes = computed(() => {
  const types = new Set();
  teamStore.team.forEach(pokemon => {
    if (pokemon?.types) {
      pokemon.types.forEach(type => types.add(type));
    }
  });
  return Array.from(types);
});

const typesCoverage = computed(() => {
  const counts = {};
  teamStore.team.forEach(pokemon => {
    if (pokemon?.types) {
      pokemon.types.forEach(type => {
        counts[type] = (counts[type] || 0) + 1;
      });
    }
  });
  return Object.entries(counts).sort((a, b) => b[1] - a[1]);
});
</script>

<style scoped>
.team-full {
  color: #10b981;
}
</style>
```

### Phase 6: Teambuilder Integration

Tie it all together:

```bash
npm test tests/integration/Teambuilder.spec.js
```

**Component to update:**
- `resources/js/components/Teambuilder.vue` (already exists, needs updates)

**Refactor to:**
1. Extract PokemonSelector into separate component
2. Add TeamSummary component
3. Use PokemonCard in selector
4. Connect all components via teamStore
5. Handle modal open/close state
6. Track which slot is being filled
7. Fetch available Pokemon from API or props

**Updated Structure:**
```vue
<template>
  <div class="max-w-6xl mx-auto p-4">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
        Pokemon Team Builder
      </h1>
      <p class="text-gray-600 dark:text-gray-400">
        Build your perfect team of 6 Pokemon
      </p>
    </div>

    <!-- Team count and clear button -->
    <div class="mb-6 flex items-center justify-between">
      <div data-test="team-status" class="text-sm text-gray-600 dark:text-gray-400" aria-live="polite">
        Team: {{ teamStore.teamCount }} / 6 Pokemon
      </div>
      <button
        v-if="!teamStore.isEmpty"
        data-test="clear-team-button"
        @click="teamStore.clearTeam"
        class="px-4 py-2 text-sm bg-red-500 text-white rounded hover:bg-red-600 transition"
      >
        Clear Team
      </button>
    </div>

    <!-- Team slots grid -->
    <div
      data-test="team-grid"
      aria-label="Pokemon team slots"
      class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8"
    >
      <TeamSlot
        v-for="(pokemon, index) in teamStore.team"
        :key="index"
        data-test="team-slot"
        :pokemon="pokemon"
        :slot-number="index + 1"
        @add="openSelector(index)"
        @remove="teamStore.removePokemon(index)"
      />
    </div>

    <!-- Team summary -->
    <TeamSummary
      data-test="team-summary"
      @clear="teamStore.clearTeam"
    />

    <!-- Pokemon selector modal -->
    <PokemonSelector
      v-if="showSelector"
      data-test="pokemon-selector"
      :available-pokemon="availablePokemon"
      :selected-pokemon-ids="selectedPokemonIds"
      @select="selectPokemon"
      @cancel="closeSelector"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useTeamStore } from '@/stores/teamStore';
import TeamSlot from './TeamSlot.vue';
import TeamSummary from './TeamSummary.vue';
import PokemonSelector from './PokemonSelector.vue';

const teamStore = useTeamStore();

const props = defineProps({
  availablePokemon: { type: Array, default: () => [] }
});

const showSelector = ref(false);
const selectedSlot = ref(null);

const selectedPokemonIds = computed(() =>
  teamStore.team.filter(p => p !== null).map(p => p.id)
);

function openSelector(slotIndex) {
  selectedSlot.value = slotIndex;
  showSelector.value = true;
}

function selectPokemon(pokemon) {
  teamStore.addPokemon(pokemon, selectedSlot.value);
  closeSelector();
}

function closeSelector() {
  showSelector.value = false;
  selectedSlot.value = null;
}
</script>
```

## Component Checklist

Use this checklist to track your progress:

### Phase 1: Store Foundation ✅
- [x] teamStore initializes correctly
- [x] Can add Pokemon to slots
- [x] Can remove Pokemon from slots
- [x] Can clear entire team
- [x] Can swap Pokemon
- [x] Computed properties work (teamCount, isEmpty, isFull)

### Phase 2: TeamSlot Component ✅
- [x] Shows empty state with "Add" button
- [x] Shows filled state with Pokemon data
- [x] Emits add/remove events
- [x] Updates reactively

### Phase 3: PokemonCard Component (NEW)
- [ ] Displays Pokemon sprite, name, ID
- [ ] Shows type badges
- [ ] Optional stats display
- [ ] Clickable with keyboard support
- [ ] Proper accessibility

### Phase 4: PokemonSelector Component (NEW)
- [ ] Modal with backdrop
- [ ] Lists available Pokemon
- [ ] Filters out selected Pokemon
- [ ] Search by name
- [ ] Filter by type
- [ ] Emits select/cancel events
- [ ] Keyboard accessible (Escape to close)

### Phase 5: TeamSummary Component (NEW)
- [ ] Shows team count
- [ ] Displays type coverage
- [ ] Lists all Pokemon
- [ ] Clear team button
- [ ] Empty state
- [ ] Real-time updates

### Phase 6: Integration
- [ ] All components work together
- [ ] Modal opens/closes correctly
- [ ] Pokemon selection updates team
- [ ] Team summary reflects changes
- [ ] No duplicate Pokemon on team
- [ ] Full team behavior

## Tips for Success

1. **Run tests in watch mode**: `npm test -- --watch`
   - Tests auto-run when you save files
   - Instant feedback loop

2. **Focus on one test at a time**: Use `.only`
   ```js
   it.only('should add Pokemon', () => {
     // This test will run alone
   });
   ```

3. **Skip tests temporarily**: Use `.skip`
   ```js
   it.skip('should do something complex', () => {
     // Will be skipped
   });
   ```

4. **Check coverage**: `npm run test:coverage`
   - Identifies untested code
   - Aim for >80% coverage

5. **Use the UI**: `npm run test:ui`
   - Visual test runner
   - Easier to debug failures

6. **Read error messages carefully**:
   - Failed assertion: "expected X to be Y"
   - Component error: Check your template/script
   - Import error: Check file paths

## Common Issues & Solutions

### Issue: "Cannot find module '@/components/...'"

**Solution**: Check that `vitest.config.js` has the correct alias:
```js
resolve: {
  alias: {
    '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
  },
}
```

### Issue: "wrapper.find(...) is null"

**Solution**:
- Component hasn't rendered yet
- Selector is wrong (check `data-test` attribute)
- Use `await nextTick()` if waiting for reactive updates

### Issue: "Expected X to be Y but got undefined"

**Solution**:
- Component not receiving props
- Computed property not working
- Check store connection

### Issue: Tests pass individually but fail together

**Solution**:
- Store state isn't being reset between tests
- Check `beforeEach` hooks are resetting state
- Each test should be independent

## Next Steps

After all tests pass:

1. **Connect to Real API**:
   - Fetch Pokemon from `/api/pokemon` endpoint
   - Add loading states
   - Handle errors

2. **Add Advanced Features** (write tests first!):
   - Drag and drop to reorder team
   - Save/load teams from backend
   - Pokemon nicknames
   - Share team URL

3. **Styling**:
   - Type-specific colors
   - Animations
   - Responsive design
   - Dark mode

4. **Performance**:
   - Virtual scrolling for large lists
   - Lazy load images
   - Debounce search input

## Resources

- [Vitest Documentation](https://vitest.dev/)
- [Vue Test Utils](https://test-utils.vuejs.org/)
- [Pinia Testing](https://pinia.vuejs.org/cookbook/testing.html)
- [Testing Library Principles](https://testing-library.com/docs/guiding-principles/)

---

**Happy Testing! 🧪**

Remember: Tests are documentation. Write tests that explain what your code should do.
