import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import TeamSummary from '@/components/TeamSummary.vue';
import { useTeamStore } from '@/stores/teamStore';

describe('TeamSummary Component', () => {
    let store;

    beforeEach(() => {
        setActivePinia(createPinia());
        store = useTeamStore();
    });

    const pikachu = {
        id: 25,
        name: 'Pikachu',
        sprite: 'https://example.com/25.png',
        types: ['electric']
    };

    const bulbasaur = {
        id: 1,
        name: 'Bulbasaur',
        sprite: 'https://example.com/1.png',
        types: ['grass', 'poison']
    };

    const charizard = {
        id: 6,
        name: 'Charizard',
        sprite: 'https://example.com/6.png',
        types: ['fire', 'flying']
    };

    describe('Basic Rendering', () => {
        it('should render component', () => {
            const wrapper = mount(TeamSummary);

            expect(wrapper.exists()).toBe(true);
        });

        it('should display team count', () => {
            const wrapper = mount(TeamSummary);

            expect(wrapper.text()).toContain('Team: 0 / 6');
        });

        it('should update team count when Pokemon added', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            expect(wrapper.text()).toContain('Team: 1 / 6');
        });

        it('should show full team count', () => {
            const wrapper = mount(TeamSummary);

            for (let i = 0; i < 6; i++) {
                store.addPokemon({ ...pikachu, id: i }, i);
            }

            expect(wrapper.text()).toContain('Team: 6 / 6');
        });
    });

    describe('Clear Button', () => {
        it('should not show clear button when team is empty', () => {
            const wrapper = mount(TeamSummary);

            const clearButton = wrapper.find('[data-test="clear-button"]');
            expect(clearButton.exists()).toBe(false);
        });

        it('should show clear button when team has Pokemon', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            const clearButton = wrapper.find('[data-test="clear-button"]');
            expect(clearButton.exists()).toBe(true);
        });

        it('should emit clear event when clicked', async () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            const clearButton = wrapper.find('[data-test="clear-button"]');
            await clearButton.trigger('click');

            expect(wrapper.emitted('clear')).toBeTruthy();
            expect(wrapper.emitted('clear')).toHaveLength(1);
        });

        it('should hide clear button after team cleared', async () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);
            store.clearTeam();

            const clearButton = wrapper.find('[data-test="clear-button"]');
            expect(clearButton.exists()).toBe(false);
        });

        it('should have proper styling', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            const clearButton = wrapper.find('[data-test="clear-button"]');
            expect(clearButton.text()).toBe('Clear Team');
        });
    });

    describe('Type Coverage Display', () => {
        it('should display type coverage section', () => {
            const wrapper = mount(TeamSummary);

            expect(wrapper.text()).toContain('Type Coverage');
        });

        it('should show no types when team is empty', () => {
            const wrapper = mount(TeamSummary);

            const typesList = wrapper.find('[data-test="types-list"]');
            expect(typesList.exists()).toBe(false);
        });

        it('should show unique types from team', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0); // electric

            expect(wrapper.text()).toContain('electric');
        });

        it('should show multiple unique types', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0); // electric
            store.addPokemon(bulbasaur, 1); // grass, poison

            expect(wrapper.text()).toContain('electric');
            expect(wrapper.text()).toContain('grass');
            expect(wrapper.text()).toContain('poison');
        });

        it('should not duplicate types', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0); // electric
            const raichu = { ...pikachu, id: 26, name: 'Raichu' }; // also electric
            store.addPokemon(raichu, 1);

            const typeBadges = wrapper.findAll('[data-test="type-badge"]');
            const electricBadges = typeBadges.filter(badge => badge.text() === 'electric');
            expect(electricBadges).toHaveLength(1);
        });

        it('should count types correctly', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0); // 1 type
            store.addPokemon(bulbasaur, 1); // 2 types
            store.addPokemon(charizard, 2); // 2 types

            const typeBadges = wrapper.findAll('[data-test="type-badge"]');
            // electric, grass, poison, fire, flying = 5 unique types
            expect(typeBadges).toHaveLength(5);
        });

        it('should show type count for each type', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0); // electric
            const raichu = { ...pikachu, id: 26, name: 'Raichu' }; // also electric
            store.addPokemon(raichu, 1);

            const electricBadge = wrapper.find('[data-test="type-badge-electric"]');
            expect(electricBadge.text()).toContain('2');
        });
    });

    describe('Team Overview', () => {
        it('should show all Pokemon in team', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);
            store.addPokemon(bulbasaur, 1);

            expect(wrapper.text()).toContain('Pikachu');
            expect(wrapper.text()).toContain('Bulbasaur');
        });

        it('should display Pokemon sprites', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            const sprites = wrapper.findAll('[data-test="pokemon-sprite"]');
            expect(sprites).toHaveLength(1);
            expect(sprites[0].attributes('src')).toBe(pikachu.sprite);
        });

        it('should show empty slots', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            const emptySlots = wrapper.findAll('[data-test="empty-slot"]');
            expect(emptySlots).toHaveLength(5);
        });

        it('should not show empty slots when team is full', () => {
            const wrapper = mount(TeamSummary);

            for (let i = 0; i < 6; i++) {
                store.addPokemon({ ...pikachu, id: i, name: `Pokemon ${i}` }, i);
            }

            const emptySlots = wrapper.findAll('[data-test="empty-slot"]');
            expect(emptySlots).toHaveLength(0);
        });
    });

    describe('Team Statistics', () => {
        it('should display team statistics section', () => {
            const wrapper = mount(TeamSummary);

            expect(wrapper.text()).toContain('Team Statistics');
        });

        it('should show number of Pokemon', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);
            store.addPokemon(bulbasaur, 1);

            expect(wrapper.text()).toContain('Pokemon: 2');
        });

        it('should show number of unique types', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0); // electric
            store.addPokemon(bulbasaur, 1); // grass, poison

            expect(wrapper.text()).toContain('Types: 3');
        });

        it('should indicate when team is full', () => {
            const wrapper = mount(TeamSummary);

            for (let i = 0; i < 6; i++) {
                store.addPokemon({ ...pikachu, id: i }, i);
            }

            expect(wrapper.text()).toContain('Team Complete');
        });

        it('should not show "Team Complete" when not full', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            expect(wrapper.text()).not.toContain('Team Complete');
        });
    });

    describe('Empty State', () => {
        it('should show empty state message when no Pokemon', () => {
            const wrapper = mount(TeamSummary);

            expect(wrapper.text()).toContain('Your team is empty');
        });

        it('should not show empty state when Pokemon added', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            expect(wrapper.text()).not.toContain('Your team is empty');
        });

        it('should show empty state after clearing team', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);
            store.clearTeam();

            expect(wrapper.text()).toContain('Your team is empty');
        });
    });

    describe('Reactivity', () => {
        it('should update when Pokemon added to store', async () => {
            const wrapper = mount(TeamSummary);

            expect(wrapper.text()).toContain('Team: 0 / 6');

            await store.addPokemon(pikachu, 0);

            expect(wrapper.text()).toContain('Team: 1 / 6');
        });

        it('should update when Pokemon removed from store', async () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);
            expect(wrapper.text()).toContain('Team: 1 / 6');

            await store.removePokemon(0);

            expect(wrapper.text()).toContain('Team: 0 / 6');
        });

        it('should update type coverage when team changes', async () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);
            expect(wrapper.text()).toContain('electric');

            await store.addPokemon(bulbasaur, 1);

            expect(wrapper.text()).toContain('electric');
            expect(wrapper.text()).toContain('grass');
            expect(wrapper.text()).toContain('poison');
        });
    });

    describe('Edge Cases', () => {
        it('should handle Pokemon with no types', () => {
            const wrapper = mount(TeamSummary);

            const pokemon = {
                id: 1,
                name: 'MissingNo',
                sprite: 'https://example.com/missing.png',
                types: []
            };

            store.addPokemon(pokemon, 0);

            expect(wrapper.text()).toContain('MissingNo');
            expect(wrapper.text()).toContain('Types: 0');
        });

        it('should handle Pokemon with null types', () => {
            const wrapper = mount(TeamSummary);

            const pokemon = {
                id: 1,
                name: 'MissingNo',
                sprite: 'https://example.com/missing.png',
                types: null
            };

            store.addPokemon(pokemon, 0);

            // Should not crash
            expect(wrapper.exists()).toBe(true);
        });

        it('should handle rapid team changes', async () => {
            const wrapper = mount(TeamSummary);

            for (let i = 0; i < 6; i++) {
                await store.addPokemon({ ...pikachu, id: i }, i);
            }

            expect(wrapper.text()).toContain('Team: 6 / 6');

            await store.clearTeam();

            expect(wrapper.text()).toContain('Team: 0 / 6');
        });
    });

    describe('Accessibility', () => {
        it('should have proper heading structure', () => {
            const wrapper = mount(TeamSummary);

            const headings = wrapper.findAll('h2, h3');
            expect(headings.length).toBeGreaterThan(0);
        });

        it('should have ARIA labels for team status', () => {
            const wrapper = mount(TeamSummary);

            const teamStatus = wrapper.find('[data-test="team-status"]');
            expect(teamStatus.attributes('aria-label')).toBeDefined();
        });

        it('should announce team changes to screen readers', () => {
            const wrapper = mount(TeamSummary);

            const teamStatus = wrapper.find('[data-test="team-status"]');
            expect(teamStatus.attributes('aria-live')).toBe('polite');
        });
    });

    describe('Visual States', () => {
        it('should have different styling when team is full', () => {
            const wrapper = mount(TeamSummary);

            for (let i = 0; i < 6; i++) {
                store.addPokemon({ ...pikachu, id: i }, i);
            }

            const teamStatus = wrapper.find('[data-test="team-status"]');
            expect(teamStatus.classes()).toContain('team-full');
        });

        it('should highlight clear button on hover', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            const clearButton = wrapper.find('[data-test="clear-button"]');
            expect(clearButton.classes()).toContain('hover:bg-red-600');
        });

        it('should show type badges with type-specific colors', () => {
            const wrapper = mount(TeamSummary);

            store.addPokemon(pikachu, 0);

            const electricBadge = wrapper.find('[data-test="type-badge-electric"]');
            expect(electricBadge.exists()).toBe(true);
        });
    });
});
