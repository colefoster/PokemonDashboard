// Global test setup for Vitest
import { afterEach } from 'vitest';

// Cleanup after each test - Vitest handles this automatically with happy-dom
afterEach(() => {
    // Clear any mounted Vue instances
    document.body.innerHTML = '';
});
