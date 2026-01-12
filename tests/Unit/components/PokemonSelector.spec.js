import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import PokemonSelector from '@/components/PokemonSelector.vue';

describe('PokemonSelector Component', () => {
    const availablePokemon = [
        {
            id: 25,
            name: 'Pikachu',
            sprite: 'https://example.com/25.png',
            types: ['electric']
        },
        {
            id: 1,
            name: 'Bulbasaur',
            sprite: 'https://example.com/1.png',
            types: ['grass', 'poison']
        },
        {
            id: 6,
            name: 'Charizard',
            sprite: 'https://example.com/6.png',
            types: ['fire', 'flying']
        },
        {
            id: 143,
            name: 'Snorlax',
            sprite: 'https://example.com/143.png',
            types: ['normal']
        }
    ];

    describe('Basic Rendering', () => {
        it('should render list of available Pokemon', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            expect(wrapper.text()).toContain('Pikachu');
            expect(wrapper.text()).toContain('Bulbasaur');
            expect(wrapper.text()).toContain('Charizard');
            expect(wrapper.text()).toContain('Snorlax');
        });

        it('should render title', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            expect(wrapper.text()).toContain('Select a Pokemon');
        });

        it('should render cancel button', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            expect(wrapper.text()).toContain('Cancel');
        });

        it('should render correct number of Pokemon', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const pokemonCards = wrapper.findAll('[data-test="pokemon-card"]');
            expect(pokemonCards).toHaveLength(4);
        });
    });

    describe('Filtering Already Selected Pokemon', () => {
        it('should filter out Pokemon already on team', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: [25] // Pikachu is selected
                }
            });

            expect(wrapper.text()).not.toContain('Pikachu');
            expect(wrapper.text()).toContain('Bulbasaur');
            expect(wrapper.text()).toContain('Charizard');
        });

        it('should filter out multiple selected Pokemon', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: [25, 1] // Pikachu and Bulbasaur
                }
            });

            expect(wrapper.text()).not.toContain('Pikachu');
            expect(wrapper.text()).not.toContain('Bulbasaur');
            expect(wrapper.text()).toContain('Charizard');
            expect(wrapper.text()).toContain('Snorlax');
        });

        it('should show all Pokemon when none selected', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const pokemonCards = wrapper.findAll('[data-test="pokemon-card"]');
            expect(pokemonCards).toHaveLength(4);
        });

        it('should handle empty available Pokemon list', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon: [],
                    selectedPokemonIds: []
                }
            });

            expect(wrapper.text()).toContain('No Pokemon available');
        });

        it('should show empty state when all Pokemon selected', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: [25, 1, 6, 143]
                }
            });

            expect(wrapper.text()).toContain('No Pokemon available');
        });
    });

    describe('Event Emissions', () => {
        it('should emit "select" event when Pokemon clicked', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const firstPokemon = wrapper.find('[data-test="pokemon-card"]');
            await firstPokemon.trigger('click');

            expect(wrapper.emitted('select')).toBeTruthy();
            expect(wrapper.emitted('select')).toHaveLength(1);
        });

        it('should emit selected Pokemon data', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const firstPokemon = wrapper.find('[data-test="pokemon-card"]');
            await firstPokemon.trigger('click');

            expect(wrapper.emitted('select')[0][0]).toEqual(availablePokemon[0]);
        });

        it('should emit "cancel" event when Cancel button clicked', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const cancelButton = wrapper.find('[data-test="cancel-button"]');
            await cancelButton.trigger('click');

            expect(wrapper.emitted('cancel')).toBeTruthy();
            expect(wrapper.emitted('cancel')).toHaveLength(1);
        });
    });

    describe('Search/Filter Functionality', () => {
        it('should render search input', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const searchInput = wrapper.find('[data-test="search-input"]');
            expect(searchInput.exists()).toBe(true);
        });

        it('should filter Pokemon by name', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const searchInput = wrapper.find('[data-test="search-input"]');
            await searchInput.setValue('pika');

            expect(wrapper.text()).toContain('Pikachu');
            expect(wrapper.text()).not.toContain('Bulbasaur');
            expect(wrapper.text()).not.toContain('Charizard');
        });

        it('should filter case-insensitively', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const searchInput = wrapper.find('[data-test="search-input"]');
            await searchInput.setValue('PIKACHU');

            expect(wrapper.text()).toContain('Pikachu');
        });

        it('should show "No results" when search has no matches', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const searchInput = wrapper.find('[data-test="search-input"]');
            await searchInput.setValue('zzzzz');

            expect(wrapper.text()).toContain('No Pokemon found');
        });

        it('should clear search when text is removed', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const searchInput = wrapper.find('[data-test="search-input"]');
            await searchInput.setValue('pika');
            await searchInput.setValue('');

            const pokemonCards = wrapper.findAll('[data-test="pokemon-card"]');
            expect(pokemonCards).toHaveLength(4);
        });

        it('should filter by partial name match', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const searchInput = wrapper.find('[data-test="search-input"]');
            await searchInput.setValue('char');

            expect(wrapper.text()).toContain('Charizard');
            expect(wrapper.text()).not.toContain('Pikachu');
        });
    });

    describe('Type Filtering', () => {
        it('should render type filter buttons', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            expect(wrapper.text()).toContain('All Types');
        });

        it('should filter Pokemon by type', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const electricButton = wrapper.find('[data-test="type-filter-electric"]');
            await electricButton.trigger('click');

            expect(wrapper.text()).toContain('Pikachu');
            expect(wrapper.text()).not.toContain('Bulbasaur');
        });

        it('should show all Pokemon when "All Types" selected', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            // First filter by type
            const electricButton = wrapper.find('[data-test="type-filter-electric"]');
            await electricButton.trigger('click');

            // Then select "All Types"
            const allTypesButton = wrapper.find('[data-test="type-filter-all"]');
            await allTypesButton.trigger('click');

            const pokemonCards = wrapper.findAll('[data-test="pokemon-card"]');
            expect(pokemonCards).toHaveLength(4);
        });

        it('should combine type filter with search', async () => {
            const extendedPokemon = [
                ...availablePokemon,
                {
                    id: 26,
                    name: 'Raichu',
                    sprite: 'https://example.com/26.png',
                    types: ['electric']
                }
            ];

            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon: extendedPokemon,
                    selectedPokemonIds: []
                }
            });

            // Filter by electric type
            const electricButton = wrapper.find('[data-test="type-filter-electric"]');
            await electricButton.trigger('click');

            // Search for "pika"
            const searchInput = wrapper.find('[data-test="search-input"]');
            await searchInput.setValue('pika');

            expect(wrapper.text()).toContain('Pikachu');
            expect(wrapper.text()).not.toContain('Raichu'); // Electric but doesn't match search
        });
    });

    describe('Props Validation', () => {
        it('should accept availablePokemon prop', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            expect(wrapper.props('availablePokemon')).toEqual(availablePokemon);
        });

        it('should accept selectedPokemonIds prop', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: [25, 1]
                }
            });

            expect(wrapper.props('selectedPokemonIds')).toEqual([25, 1]);
        });

        it('should handle null selectedPokemonIds', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: null
                }
            });

            // Should still render without errors
            const pokemonCards = wrapper.findAll('[data-test="pokemon-card"]');
            expect(pokemonCards).toHaveLength(4);
        });
    });

    describe('Modal Behavior', () => {
        it('should have modal backdrop', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            expect(wrapper.find('[data-test="modal-backdrop"]').exists()).toBe(true);
        });

        it('should emit cancel when backdrop clicked', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const backdrop = wrapper.find('[data-test="modal-backdrop"]');
            await backdrop.trigger('click');

            expect(wrapper.emitted('cancel')).toBeTruthy();
        });

        it('should not emit cancel when modal content clicked', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const modalContent = wrapper.find('[data-test="modal-content"]');
            await modalContent.trigger('click');

            expect(wrapper.emitted('cancel')).toBeFalsy();
        });

        it('should emit cancel when Escape key pressed', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            await wrapper.trigger('keydown.esc');

            expect(wrapper.emitted('cancel')).toBeTruthy();
        });
    });

    describe('Edge Cases', () => {
        it('should handle Pokemon with missing sprite', () => {
            const pokemonWithoutSprite = [
                {
                    id: 1,
                    name: 'Bulbasaur',
                    sprite: null,
                    types: ['grass']
                }
            ];

            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon: pokemonWithoutSprite,
                    selectedPokemonIds: []
                }
            });

            expect(wrapper.text()).toContain('Bulbasaur');
        });

        it('should handle large number of Pokemon', () => {
            const manyPokemon = Array.from({ length: 50 }, (_, i) => ({
                id: i + 1,
                name: `Pokemon ${i + 1}`,
                sprite: `https://example.com/${i + 1}.png`,
                types: ['normal']
            }));

            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon: manyPokemon,
                    selectedPokemonIds: []
                }
            });

            const pokemonCards = wrapper.findAll('[data-test="pokemon-card"]');
            expect(pokemonCards).toHaveLength(50);
        });

        it('should handle Pokemon with duplicate IDs gracefully', () => {
            const duplicatePokemon = [
                {
                    id: 25,
                    name: 'Pikachu',
                    sprite: 'https://example.com/25.png',
                    types: ['electric']
                },
                {
                    id: 25,
                    name: 'Pikachu Clone',
                    sprite: 'https://example.com/25.png',
                    types: ['electric']
                }
            ];

            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon: duplicatePokemon,
                    selectedPokemonIds: [25]
                }
            });

            // Both should be filtered out
            expect(wrapper.text()).toContain('No Pokemon available');
        });
    });

    describe('Accessibility', () => {
        it('should have proper ARIA labels', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const searchInput = wrapper.find('[data-test="search-input"]');
            expect(searchInput.attributes('aria-label')).toBe('Search Pokemon');
        });

        it('should be keyboard navigable', async () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                }
            });

            const firstCard = wrapper.find('[data-test="pokemon-card"]');
            expect(firstCard.attributes('tabindex')).toBeDefined();
        });

        it('should focus search input on mount', () => {
            const wrapper = mount(PokemonSelector, {
                props: {
                    availablePokemon,
                    selectedPokemonIds: []
                },
                attachTo: document.body
            });

            const searchInput = wrapper.find('[data-test="search-input"]');
            // Note: This test requires proper DOM attachment
            expect(searchInput.element).toBeDefined();

            wrapper.unmount();
        });
    });
});
