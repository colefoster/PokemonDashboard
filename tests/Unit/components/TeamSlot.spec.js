import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import TeamSlot from '@/components/TeamSlot.vue';

describe('TeamSlot Component', () => {
    describe('Empty Slot Rendering', () => {
        it('should render empty slot when no Pokemon provided', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 1
                }
            });

            expect(wrapper.find('.text-4xl').text()).toBe('+');
            expect(wrapper.find('button').exists()).toBe(true);
        });

        it('should display correct slot number', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 3
                }
            });

            expect(wrapper.text()).toContain('Slot 3');
        });

        it('should show "Add Pokemon" button text', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 1
                }
            });

            expect(wrapper.text()).toContain('Add Pokemon');
        });

        it('should have dashed border when empty', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 1
                }
            });

            expect(wrapper.classes()).toContain('border-dashed');
        });

        it('should not show Pokemon sprite when empty', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 1
                }
            });

            expect(wrapper.find('img').exists()).toBe(false);
        });
    });

    describe('Filled Slot Rendering', () => {
        const pikachu = {
            id: 25,
            name: 'Pikachu',
            sprite: 'https://example.com/25.png',
            types: ['electric']
        };

        it('should render Pokemon sprite', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            const img = wrapper.find('img');
            expect(img.exists()).toBe(true);
            expect(img.attributes('src')).toBe(pikachu.sprite);
            expect(img.attributes('alt')).toBe(pikachu.name);
        });

        it('should display Pokemon name', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            expect(wrapper.find('h3').text()).toBe('Pikachu');
        });

        it('should display Pokemon types', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            const types = wrapper.findAll('span').filter(span =>
                span.text() === 'electric'
            );
            expect(types).toHaveLength(1);
        });

        it('should display multiple types', () => {
            const bulbasaur = {
                id: 1,
                name: 'Bulbasaur',
                sprite: 'https://example.com/1.png',
                types: ['grass', 'poison']
            };

            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: bulbasaur,
                    slotNumber: 1
                }
            });

            expect(wrapper.text()).toContain('grass');
            expect(wrapper.text()).toContain('poison');
        });

        it('should show Remove button when filled', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            expect(wrapper.text()).toContain('Remove');
        });

        it('should have solid border when filled', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            expect(wrapper.classes()).toContain('border-solid');
        });

        it('should not show "Add Pokemon" button when filled', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            const buttons = wrapper.findAll('button');
            const addButton = buttons.find(btn => btn.text() === 'Add Pokemon');
            expect(addButton).toBeUndefined();
        });

        it('should not show slot number when filled', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            expect(wrapper.text()).not.toContain('Slot 1');
        });
    });

    describe('Event Emissions', () => {
        const pikachu = {
            id: 25,
            name: 'Pikachu',
            sprite: 'https://example.com/25.png',
            types: ['electric']
        };

        it('should emit "add" event when Add button clicked', async () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 1
                }
            });

            await wrapper.find('button').trigger('click');

            expect(wrapper.emitted('add')).toBeTruthy();
            expect(wrapper.emitted('add')).toHaveLength(1);
        });

        it('should emit "remove" event when Remove button clicked', async () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            const removeButton = wrapper.findAll('button').find(
                btn => btn.text() === 'Remove'
            );
            await removeButton.trigger('click');

            expect(wrapper.emitted('remove')).toBeTruthy();
            expect(wrapper.emitted('remove')).toHaveLength(1);
        });

        it('should not emit "remove" event from empty slot', async () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 1
                }
            });

            await wrapper.find('button').trigger('click');

            expect(wrapper.emitted('remove')).toBeFalsy();
        });
    });

    describe('Props Validation', () => {
        it('should accept null pokemon prop', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 1
                }
            });

            expect(wrapper.props('pokemon')).toBeNull();
        });

        it('should accept pokemon object prop', () => {
            const pikachu = {
                id: 25,
                name: 'Pikachu',
                sprite: 'https://example.com/25.png',
                types: ['electric']
            };

            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            expect(wrapper.props('pokemon')).toEqual(pikachu);
        });

        it('should accept slotNumber prop', () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 5
                }
            });

            expect(wrapper.props('slotNumber')).toBe(5);
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

            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon,
                    slotNumber: 1
                }
            });

            expect(wrapper.find('h3').text()).toBe('MissingNo');
            const typeSpans = wrapper.findAll('span').filter(span =>
                span.classes().includes('bg-gray-200')
            );
            expect(typeSpans).toHaveLength(0);
        });

        it('should handle Pokemon with single type', () => {
            const pokemon = {
                id: 1,
                name: 'Pikachu',
                sprite: 'https://example.com/25.png',
                types: ['electric']
            };

            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon,
                    slotNumber: 1
                }
            });

            expect(wrapper.text()).toContain('electric');
        });

        it('should handle Pokemon with long name', () => {
            const pokemon = {
                id: 1,
                name: 'Fletchinder-Mega-Evolution-X',
                sprite: 'https://example.com/1.png',
                types: ['fire', 'flying']
            };

            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon,
                    slotNumber: 1
                }
            });

            expect(wrapper.text()).toContain('Fletchinder-Mega-Evolution-X');
        });

        it('should handle missing sprite URL', () => {
            const pokemon = {
                id: 1,
                name: 'Pikachu',
                sprite: '',
                types: ['electric']
            };

            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon,
                    slotNumber: 1
                }
            });

            const img = wrapper.find('img');
            expect(img.attributes('src')).toBe('');
        });

        it('should render slot numbers 1-6', () => {
            for (let i = 1; i <= 6; i++) {
                const wrapper = mount(TeamSlot, {
                    props: {
                        pokemon: null,
                        slotNumber: i
                    }
                });

                expect(wrapper.text()).toContain(`Slot ${i}`);
            }
        });
    });

    describe('Reactivity', () => {
        it('should update display when pokemon prop changes from null to filled', async () => {
            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: null,
                    slotNumber: 1
                }
            });

            expect(wrapper.text()).toContain('Add Pokemon');

            const pikachu = {
                id: 25,
                name: 'Pikachu',
                sprite: 'https://example.com/25.png',
                types: ['electric']
            };

            await wrapper.setProps({ pokemon: pikachu });

            expect(wrapper.text()).toContain('Pikachu');
            expect(wrapper.text()).not.toContain('Add Pokemon');
        });

        it('should update display when pokemon prop changes from filled to null', async () => {
            const pikachu = {
                id: 25,
                name: 'Pikachu',
                sprite: 'https://example.com/25.png',
                types: ['electric']
            };

            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            expect(wrapper.text()).toContain('Pikachu');

            await wrapper.setProps({ pokemon: null });

            expect(wrapper.text()).toContain('Add Pokemon');
            expect(wrapper.text()).not.toContain('Pikachu');
        });

        it('should update when pokemon prop changes between different Pokemon', async () => {
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

            const wrapper = mount(TeamSlot, {
                props: {
                    pokemon: pikachu,
                    slotNumber: 1
                }
            });

            expect(wrapper.text()).toContain('Pikachu');

            await wrapper.setProps({ pokemon: bulbasaur });

            expect(wrapper.text()).toContain('Bulbasaur');
            expect(wrapper.text()).not.toContain('Pikachu');
        });
    });
});
