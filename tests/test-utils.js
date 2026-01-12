// Test utilities and helpers for Pokemon Team Builder tests
import { createPinia, setActivePinia } from 'pinia';

/**
 * Creates and activates a fresh Pinia instance for testing
 * Use this in beforeEach hooks to ensure isolated store state
 */
export function setupPinia() {
    const pinia = createPinia();
    setActivePinia(pinia);
    return pinia;
}

/**
 * Mock Pokemon data for testing
 */
export const mockPokemon = {
    pikachu: {
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
    },
    bulbasaur: {
        id: 1,
        name: 'Bulbasaur',
        sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/1.png',
        types: ['grass', 'poison'],
        stats: {
            hp: 45,
            attack: 49,
            defense: 49,
            specialAttack: 65,
            specialDefense: 65,
            speed: 45
        }
    },
    charizard: {
        id: 6,
        name: 'Charizard',
        sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/6.png',
        types: ['fire', 'flying'],
        stats: {
            hp: 78,
            attack: 84,
            defense: 78,
            specialAttack: 109,
            specialDefense: 85,
            speed: 100
        }
    },
    squirtle: {
        id: 7,
        name: 'Squirtle',
        sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/7.png',
        types: ['water'],
        stats: {
            hp: 44,
            attack: 48,
            defense: 65,
            specialAttack: 50,
            specialDefense: 64,
            speed: 43
        }
    },
    snorlax: {
        id: 143,
        name: 'Snorlax',
        sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/143.png',
        types: ['normal'],
        stats: {
            hp: 160,
            attack: 110,
            defense: 65,
            specialAttack: 65,
            specialDefense: 110,
            speed: 30
        }
    },
    mewtwo: {
        id: 150,
        name: 'Mewtwo',
        sprite: 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/150.png',
        types: ['psychic'],
        stats: {
            hp: 106,
            attack: 110,
            defense: 90,
            specialAttack: 154,
            specialDefense: 90,
            speed: 130
        }
    }
};

/**
 * Creates a minimal Pokemon object for testing
 * @param {number} id - Pokemon ID
 * @param {string} name - Pokemon name
 * @param {string[]} types - Pokemon types
 * @returns {Object} Minimal Pokemon object
 */
export function createMockPokemon(id, name, types = ['normal']) {
    return {
        id,
        name,
        sprite: `https://example.com/${id}.png`,
        types
    };
}

/**
 * Creates an array of mock Pokemon for testing lists
 * @param {number} count - Number of Pokemon to create
 * @returns {Array} Array of mock Pokemon
 */
export function createMockPokemonList(count) {
    return Array.from({ length: count }, (_, i) =>
        createMockPokemon(i + 1, `Pokemon ${i + 1}`, ['normal'])
    );
}

/**
 * Gets an array of all mock Pokemon
 * @returns {Array} Array of all predefined mock Pokemon
 */
export function getAllMockPokemon() {
    return Object.values(mockPokemon);
}

/**
 * Waits for Vue to process reactive updates
 * Useful when testing async state changes
 */
export async function flushPromises() {
    return new Promise(resolve => setTimeout(resolve, 0));
}

/**
 * Helper to find element by data-test attribute
 * @param {Wrapper} wrapper - Vue Test Utils wrapper
 * @param {string} testId - Value of data-test attribute
 * @returns {Wrapper} Element wrapper
 */
export function findByTestId(wrapper, testId) {
    return wrapper.find(`[data-test="${testId}"]`);
}

/**
 * Helper to find all elements by data-test attribute
 * @param {Wrapper} wrapper - Vue Test Utils wrapper
 * @param {string} testId - Value of data-test attribute
 * @returns {Array} Array of element wrappers
 */
export function findAllByTestId(wrapper, testId) {
    return wrapper.findAll(`[data-test="${testId}"]`);
}

/**
 * Fills a team store with Pokemon
 * @param {Object} store - Team store instance
 * @param {number} count - Number of Pokemon to add (1-6)
 */
export function fillTeam(store, count = 6) {
    const pokemon = getAllMockPokemon();
    for (let i = 0; i < Math.min(count, 6); i++) {
        store.addPokemon(pokemon[i], i);
    }
}

/**
 * Asserts that a team has expected number of Pokemon
 * @param {Object} store - Team store instance
 * @param {number} expectedCount - Expected number of Pokemon
 */
export function expectTeamCount(store, expectedCount) {
    const actualCount = store.team.filter(p => p !== null).length;
    if (actualCount !== expectedCount) {
        throw new Error(`Expected team to have ${expectedCount} Pokemon, but has ${actualCount}`);
    }
}

/**
 * Checks if an element has a specific CSS class
 * @param {Wrapper} wrapper - Vue Test Utils wrapper
 * @param {string} className - CSS class name to check
 * @returns {boolean} True if element has the class
 */
export function hasClass(wrapper, className) {
    return wrapper.classes().includes(className);
}

/**
 * Simulates a user typing in an input field
 * @param {Wrapper} inputWrapper - Wrapper of input element
 * @param {string} text - Text to type
 */
export async function typeInInput(inputWrapper, text) {
    await inputWrapper.setValue(text);
    await inputWrapper.trigger('input');
}

/**
 * Simulates clicking an element multiple times
 * @param {Wrapper} wrapper - Element wrapper
 * @param {number} times - Number of clicks
 */
export async function clickMultipleTimes(wrapper, times) {
    for (let i = 0; i < times; i++) {
        await wrapper.trigger('click');
    }
}

/**
 * Gets text content from all elements matching selector
 * @param {Wrapper} wrapper - Vue Test Utils wrapper
 * @param {string} selector - CSS selector
 * @returns {Array} Array of text content strings
 */
export function getTextFromAll(wrapper, selector) {
    return wrapper.findAll(selector).map(el => el.text());
}

/**
 * Checks if wrapper contains specific text
 * @param {Wrapper} wrapper - Vue Test Utils wrapper
 * @param {string} text - Text to search for
 * @returns {boolean} True if text is found
 */
export function containsText(wrapper, text) {
    return wrapper.text().includes(text);
}

/**
 * Mock fetch response for API testing
 * @param {any} data - Data to return from fetch
 * @param {number} status - HTTP status code
 * @returns {Promise} Mock fetch promise
 */
export function mockFetchResponse(data, status = 200) {
    return Promise.resolve({
        ok: status >= 200 && status < 300,
        status,
        json: () => Promise.resolve(data)
    });
}

/**
 * Creates a mock PokeAPI Pokemon response
 * @param {number} id - Pokemon ID
 * @param {string} name - Pokemon name
 * @returns {Object} Mock PokeAPI response
 */
export function createMockApiPokemon(id, name) {
    return {
        id,
        name,
        sprites: {
            front_default: `https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/${id}.png`
        },
        types: [
            {
                slot: 1,
                type: {
                    name: 'normal'
                }
            }
        ],
        stats: [
            { stat: { name: 'hp' }, base_stat: 50 },
            { stat: { name: 'attack' }, base_stat: 50 },
            { stat: { name: 'defense' }, base_stat: 50 },
            { stat: { name: 'special-attack' }, base_stat: 50 },
            { stat: { name: 'special-defense' }, base_stat: 50 },
            { stat: { name: 'speed' }, base_stat: 50 }
        ]
    };
}
