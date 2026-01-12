import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useTeamStore } from '@/stores/teamStore';

describe('TeamStore', () => {
    let store;

    beforeEach(() => {
        // Create a new pinia instance for each test
        setActivePinia(createPinia());
        store = useTeamStore();
    });

    describe('Initialization', () => {
        it('should initialize with 6 empty slots', () => {
            expect(store.team).toHaveLength(6);
            expect(store.team.every(slot => slot === null)).toBe(true);
        });

        it('should have teamCount of 0', () => {
            expect(store.teamCount).toBe(0);
        });

        it('should be empty initially', () => {
            expect(store.isEmpty).toBe(true);
        });

        it('should not be full initially', () => {
            expect(store.isFull).toBe(false);
        });
    });

    describe('Adding Pokemon', () => {
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

        it('should add Pokemon to specific slot', () => {
            store.addPokemon(pikachu, 0);

            expect(store.team[0]).toEqual(pikachu);
            expect(store.teamCount).toBe(1);
        });

        it('should add Pokemon to first empty slot when no index specified', () => {
            store.addPokemon(pikachu);

            expect(store.team[0]).toEqual(pikachu);
            expect(store.teamCount).toBe(1);
        });

        it('should add Pokemon to first empty slot after filled slots', () => {
            store.addPokemon(pikachu, 0);
            store.addPokemon(bulbasaur);

            expect(store.team[0]).toEqual(pikachu);
            expect(store.team[1]).toEqual(bulbasaur);
            expect(store.teamCount).toBe(2);
        });

        it('should replace Pokemon in slot if slot already filled', () => {
            store.addPokemon(pikachu, 0);
            store.addPokemon(bulbasaur, 0);

            expect(store.team[0]).toEqual(bulbasaur);
            expect(store.teamCount).toBe(1);
        });

        it('should add Pokemon to last slot', () => {
            store.addPokemon(pikachu, 5);

            expect(store.team[5]).toEqual(pikachu);
            expect(store.teamCount).toBe(1);
        });

        it('should not add Pokemon if team is full and no slot specified', () => {
            // Fill all slots
            for (let i = 0; i < 6; i++) {
                store.addPokemon({ ...pikachu, id: i }, i);
            }

            const charizard = { id: 6, name: 'Charizard' };
            const initialTeam = [...store.team];

            store.addPokemon(charizard);

            expect(store.team).toEqual(initialTeam);
            expect(store.isFull).toBe(true);
        });

        it('should add to first empty slot when index is negative', () => {
            store.addPokemon(pikachu, -1);

            // Should fall back to finding first empty slot
            expect(store.team[0]).toEqual(pikachu);
            expect(store.teamCount).toBe(1);
        });

        it('should add to first empty slot when index is greater than 5', () => {
            store.addPokemon(pikachu, 6);

            // Should fall back to finding first empty slot
            expect(store.team[0]).toEqual(pikachu);
            expect(store.teamCount).toBe(1);
        });

        it('should update teamCount after adding Pokemon', () => {
            expect(store.teamCount).toBe(0);

            store.addPokemon(pikachu, 0);
            expect(store.teamCount).toBe(1);

            store.addPokemon(bulbasaur, 1);
            expect(store.teamCount).toBe(2);
        });

        it('should update isEmpty after adding Pokemon', () => {
            expect(store.isEmpty).toBe(true);

            store.addPokemon(pikachu, 0);
            expect(store.isEmpty).toBe(false);
        });

        it('should update isFull when all slots filled', () => {
            expect(store.isFull).toBe(false);

            for (let i = 0; i < 6; i++) {
                store.addPokemon({ ...pikachu, id: i }, i);
            }

            expect(store.isFull).toBe(true);
        });
    });

    describe('Removing Pokemon', () => {
        const pikachu = {
            id: 25,
            name: 'Pikachu',
            sprite: 'https://example.com/25.png',
            types: ['electric']
        };

        beforeEach(() => {
            store.addPokemon(pikachu, 0);
        });

        it('should remove Pokemon from slot', () => {
            store.removePokemon(0);

            expect(store.team[0]).toBeNull();
            expect(store.teamCount).toBe(0);
        });

        it('should update teamCount after removing Pokemon', () => {
            expect(store.teamCount).toBe(1);

            store.removePokemon(0);
            expect(store.teamCount).toBe(0);
        });

        it('should update isEmpty after removing last Pokemon', () => {
            expect(store.isEmpty).toBe(false);

            store.removePokemon(0);
            expect(store.isEmpty).toBe(true);
        });

        it('should update isFull after removing Pokemon from full team', () => {
            // Fill team
            for (let i = 1; i < 6; i++) {
                store.addPokemon({ ...pikachu, id: i }, i);
            }
            expect(store.isFull).toBe(true);

            store.removePokemon(0);
            expect(store.isFull).toBe(false);
        });

        it('should ignore invalid slot indices (negative)', () => {
            const initialTeam = [...store.team];
            store.removePokemon(-1);

            expect(store.team).toEqual(initialTeam);
        });

        it('should ignore invalid slot indices (greater than 5)', () => {
            const initialTeam = [...store.team];
            store.removePokemon(6);

            expect(store.team).toEqual(initialTeam);
        });

        it('should handle removing from empty slot', () => {
            store.removePokemon(1); // Slot 1 is empty

            expect(store.team[1]).toBeNull();
            expect(store.teamCount).toBe(1); // Still have pikachu in slot 0
        });
    });

    describe('Clearing Team', () => {
        const pikachu = {
            id: 25,
            name: 'Pikachu',
            sprite: 'https://example.com/25.png',
            types: ['electric']
        };

        it('should clear all Pokemon from team', () => {
            // Add some Pokemon
            store.addPokemon(pikachu, 0);
            store.addPokemon({ ...pikachu, id: 1 }, 1);
            store.addPokemon({ ...pikachu, id: 2 }, 2);

            store.clearTeam();

            expect(store.team.every(slot => slot === null)).toBe(true);
            expect(store.teamCount).toBe(0);
            expect(store.isEmpty).toBe(true);
        });

        it('should handle clearing empty team', () => {
            store.clearTeam();

            expect(store.team.every(slot => slot === null)).toBe(true);
            expect(store.teamCount).toBe(0);
        });
    });

    describe('Swapping Pokemon', () => {
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

        beforeEach(() => {
            store.addPokemon(pikachu, 0);
            store.addPokemon(bulbasaur, 1);
        });

        it('should swap two Pokemon', () => {
            store.swapPokemon(0, 1);

            expect(store.team[0]).toEqual(bulbasaur);
            expect(store.team[1]).toEqual(pikachu);
        });

        it('should swap Pokemon with empty slot', () => {
            store.swapPokemon(0, 2); // Slot 2 is empty

            expect(store.team[0]).toBeNull();
            expect(store.team[2]).toEqual(pikachu);
        });

        it('should swap two empty slots', () => {
            store.swapPokemon(3, 4);

            expect(store.team[3]).toBeNull();
            expect(store.team[4]).toBeNull();
        });

        it('should maintain teamCount after swap', () => {
            const initialCount = store.teamCount;
            store.swapPokemon(0, 1);

            expect(store.teamCount).toBe(initialCount);
        });

        it('should handle swapping same slot', () => {
            store.swapPokemon(0, 0);

            expect(store.team[0]).toEqual(pikachu);
        });
    });

    describe('Computed Properties', () => {
        const pikachu = {
            id: 25,
            name: 'Pikachu',
            sprite: 'https://example.com/25.png',
            types: ['electric']
        };

        it('teamCount should reflect number of non-null slots', () => {
            expect(store.teamCount).toBe(0);

            store.addPokemon(pikachu, 0);
            expect(store.teamCount).toBe(1);

            store.addPokemon({ ...pikachu, id: 1 }, 2);
            expect(store.teamCount).toBe(2);

            store.removePokemon(0);
            expect(store.teamCount).toBe(1);
        });

        it('isEmpty should be true only when no Pokemon', () => {
            expect(store.isEmpty).toBe(true);

            store.addPokemon(pikachu, 0);
            expect(store.isEmpty).toBe(false);

            store.removePokemon(0);
            expect(store.isEmpty).toBe(true);
        });

        it('isFull should be true only when all 6 slots filled', () => {
            expect(store.isFull).toBe(false);

            for (let i = 0; i < 5; i++) {
                store.addPokemon({ ...pikachu, id: i }, i);
                expect(store.isFull).toBe(false);
            }

            store.addPokemon({ ...pikachu, id: 5 }, 5);
            expect(store.isFull).toBe(true);
        });
    });

    describe('Edge Cases', () => {
        const pikachu = {
            id: 25,
            name: 'Pikachu',
            sprite: 'https://example.com/25.png',
            types: ['electric']
        };

        it('should handle adding same Pokemon to multiple slots', () => {
            store.addPokemon(pikachu, 0);
            store.addPokemon(pikachu, 1);

            expect(store.team[0]).toEqual(pikachu);
            expect(store.team[1]).toEqual(pikachu);
            expect(store.teamCount).toBe(2);
        });

        it('should handle Pokemon with minimal data', () => {
            const minimalPokemon = { id: 1 };
            store.addPokemon(minimalPokemon, 0);

            expect(store.team[0]).toEqual(minimalPokemon);
        });

        it('should handle Pokemon with extra properties', () => {
            const detailedPokemon = {
                ...pikachu,
                stats: { hp: 35, attack: 55 },
                abilities: ['Static'],
                moves: ['Thunder Shock']
            };

            store.addPokemon(detailedPokemon, 0);
            expect(store.team[0]).toEqual(detailedPokemon);
        });
    });
});
