/**
 * Vitest Setup File
 * 
 * This file runs before all tests to setup the test environment.
 */

// Mock global objects
global.console = {
    ...console,
    // Suppress console.log in tests (optional)
    // log: vi.fn(),
    // error: vi.fn(),
    // warn: vi.fn(),
};

// Mock window.Alpine if needed
global.Alpine = {
    data: vi.fn(),
    store: vi.fn(),
};

// Mock fetch if needed
global.fetch = vi.fn();

// Setup CSRF token mock
global.document = {
    ...global.document,
    querySelector: vi.fn((selector) => {
        if (selector === 'meta[name="csrf-token"]') {
            return {
                getAttribute: () => 'mock-csrf-token',
            };
        }
        return null;
    }),
};
