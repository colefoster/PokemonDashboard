import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import PokemonCard from '@/components/PokemonCard.vue';

describe('PokemonCard Component', () => {
    const pikachu = {
        id: 25,
        name: 'Pikachu',
        sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png',
        types: ['electric'],
        stats: {
            hp: 35,
            attack: 55,
            defense: 40,
            specialAttack: 50,
            specialDefense: 50,
            speed: 90
        }
    };

    describe('Basic Rendering', () => {
        it('should render Pokemon sprite', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            const img = wrapper.find('img');
            expect(img.exists()).toBe(true);
            expect(img.attributes('src')).toBe(pikachu.sprite);
        });

        it('should render Pokemon name', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            expect(wrapper.text()).toContain('Pikachu');
        });

        it('should render Pokemon ID', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            expect(wrapper.text()).toContain('#25');
        });

        it('should render Pokemon types', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            expect(wrapper.text()).toContain('electric');
        });

        it('should render multiple types', () => {
            const bulbasaur = {
                id: 1,
                name: 'Bulbasaur',
                sprite: 'https://example.com/1.png',
                types: ['grass', 'poison']
            };

            const wrapper = mount(PokemonCard, {
                props: { pokemon: bulbasaur }
            });

            expect(wrapper.text()).toContain('grass');
            expect(wrapper.text()).toContain('poison');
        });
    });

    describe('Props Handling', () => {
        it('should accept pokemon prop', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            expect(wrapper.props('pokemon')).toEqual(pikachu);
        });

        it('should accept clickable prop', () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    clickable: true
                }
            });

            expect(wrapper.props('clickable')).toBe(true);
        });

        it('should default clickable to false', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            expect(wrapper.props('clickable')).toBe(false);
        });

        it('should accept showStats prop', () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    showStats: true
                }
            });

            expect(wrapper.props('showStats')).toBe(true);
        });

        it('should default showStats to false', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            expect(wrapper.props('showStats')).toBe(false);
        });
    });

    describe('Stats Display', () => {
        it('should not show stats by default', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            expect(wrapper.text()).not.toContain('HP');
            expect(wrapper.text()).not.toContain('Attack');
        });

        it('should show stats when showStats is true', () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    showStats: true
                }
            });

            expect(wrapper.text()).toContain('HP');
            expect(wrapper.text()).toContain('35');
        });

        it('should display all stats when enabled', () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    showStats: true
                }
            });

            expect(wrapper.text()).toContain('HP');
            expect(wrapper.text()).toContain('Attack');
            expect(wrapper.text()).toContain('Defense');
            expect(wrapper.text()).toContain('Speed');
        });

        it('should handle Pokemon without stats', () => {
            const pokemon = {
                id: 1,
                name: 'Bulbasaur',
                sprite: 'https://example.com/1.png',
                types: ['grass']
            };

            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon,
                    showStats: true
                }
            });

            // Should still render without errors
            expect(wrapper.text()).toContain('Bulbasaur');
        });
    });

    describe('Click Behavior', () => {
        it('should emit click event when clickable and clicked', async () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    clickable: true
                }
            });

            await wrapper.trigger('click');

            expect(wrapper.emitted('click')).toBeTruthy();
            expect(wrapper.emitted('click')).toHaveLength(1);
        });

        it('should emit pokemon data with click event', async () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    clickable: true
                }
            });

            await wrapper.trigger('click');

            expect(wrapper.emitted('click')[0]).toEqual([pikachu]);
        });

        it('should not emit click event when not clickable', async () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    clickable: false
                }
            });

            await wrapper.trigger('click');

            expect(wrapper.emitted('click')).toBeFalsy();
        });

        it('should have hover styles when clickable', () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    clickable: true
                }
            });

            expect(wrapper.classes()).toContain('cursor-pointer');
        });

        it('should not have cursor-pointer when not clickable', () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    clickable: false
                }
            });

            expect(wrapper.classes()).not.toContain('cursor-pointer');
        });
    });

    describe('Edge Cases', () => {
        it('should handle Pokemon with no types', () => {
            const pokemon = {
                id: 1,
                name: 'MissingNo',
                sprite: 'https://example.com/missing.png',
                types: []
            };

            const wrapper = mount(PokemonCard, {
                props: { pokemon }
            });

            expect(wrapper.text()).toContain('MissingNo');
        });

        it('should handle Pokemon with single type', () => {
            const pokemon = {
                id: 1,
                name: 'Pikachu',
                sprite: 'https://example.com/25.png',
                types: ['electric']
            };

            const wrapper = mount(PokemonCard, {
                props: { pokemon }
            });

            expect(wrapper.text()).toContain('electric');
        });

        it('should handle Pokemon with 3-digit ID', () => {
            const mewtwo = {
                id: 150,
                name: 'Mewtwo',
                sprite: 'https://example.com/150.png',
                types: ['psychic']
            };

            const wrapper = mount(PokemonCard, {
                props: { pokemon: mewtwo }
            });

            expect(wrapper.text()).toContain('#150');
        });

        it('should handle Pokemon with 4-digit ID', () => {
            const pokemon = {
                id: 1025,
                name: 'Future Pokemon',
                sprite: 'https://example.com/1025.png',
                types: ['normal']
            };

            const wrapper = mount(PokemonCard, {
                props: { pokemon }
            });

            expect(wrapper.text()).toContain('#1025');
        });

        it('should handle long Pokemon names', () => {
            const pokemon = {
                id: 1,
                name: 'Mega-Charizard-X',
                sprite: 'https://example.com/1.png',
                types: ['fire', 'dragon']
            };

            const wrapper = mount(PokemonCard, {
                props: { pokemon }
            });

            expect(wrapper.text()).toContain('Mega-Charizard-X');
        });

        it('should handle missing sprite gracefully', () => {
            const pokemon = {
                id: 1,
                name: 'Bulbasaur',
                sprite: null,
                types: ['grass']
            };

            const wrapper = mount(PokemonCard, {
                props: { pokemon }
            });

            expect(wrapper.text()).toContain('Bulbasaur');
        });

        it('should format Pokemon ID with leading hash', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            const idText = wrapper.text().match(/#\d+/);
            expect(idText).toBeTruthy();
            expect(idText[0]).toBe('#25');
        });
    });

    describe('Type Display', () => {
        it('should display types in order', () => {
            const pokemon = {
                id: 1,
                name: 'Bulbasaur',
                sprite: 'https://example.com/1.png',
                types: ['grass', 'poison']
            };

            const wrapper = mount(PokemonCard, {
                props: { pokemon }
            });

            const typeElements = wrapper.findAll('[data-test="pokemon-type"]');
            expect(typeElements[0].text()).toBe('grass');
            expect(typeElements[1].text()).toBe('poison');
        });

        it('should apply type-specific styling', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            const typeElement = wrapper.find('[data-test="pokemon-type"]');
            expect(typeElement.exists()).toBe(true);
        });
    });

    describe('Accessibility', () => {
        it('should have alt text for sprite', () => {
            const wrapper = mount(PokemonCard, {
                props: { pokemon: pikachu }
            });

            const img = wrapper.find('img');
            expect(img.attributes('alt')).toBe('Pikachu');
        });

        it('should be keyboard accessible when clickable', () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    clickable: true
                }
            });

            expect(wrapper.attributes('tabindex')).toBe('0');
        });

        it('should not have tabindex when not clickable', () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    clickable: false
                }
            });

            expect(wrapper.attributes('tabindex')).toBeUndefined();
        });

        it('should handle keyboard enter when clickable', async () => {
            const wrapper = mount(PokemonCard, {
                props: {
                    pokemon: pikachu,
                    clickable: true
                }
            });

            await wrapper.trigger('keydown.enter');

            expect(wrapper.emitted('click')).toBeTruthy();
        });
    });
});
