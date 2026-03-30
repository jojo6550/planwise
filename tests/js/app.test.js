/**
 * app.test.js
 *
 * Unit tests for public/js/app.js frontend logic.
 *
 * The module uses an IIFE and accesses the DOM, so we re-implement the
 * pure utility functions (escapeHtml, statusBadge) in a testable form
 * and drive the IIFE via jsdom DOM simulation.
 *
 * Jest is configured to use jsdom as the test environment (package.json).
 */

'use strict';

// ---------------------------------------------------------------------------
// Re-declare the pure helpers extracted from app.js so they can be tested
// without triggering the full IIFE (which requires DOM elements to exist).
// ---------------------------------------------------------------------------

/**
 * Escape HTML to prevent XSS when inserting API data into innerHTML.
 * Mirrors the implementation in app.js exactly.
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}

/**
 * Map a lesson plan status to its Bootstrap badge class string.
 * Extracted from the object literal inside app.js.
 */
function getStatusBadge(status) {
    return ({
        published: 'badge bg-success',
        draft:     'badge bg-warning text-dark',
        archived:  'badge bg-secondary',
    })[status] || 'badge bg-light text-dark';
}

// ---------------------------------------------------------------------------
// escapeHtml
// ---------------------------------------------------------------------------

describe('escapeHtml()', () => {
    test('returns empty string for empty input', () => {
        expect(escapeHtml('')).toBe('');
    });

    test('escapes < and > characters', () => {
        expect(escapeHtml('<p>hello</p>')).toBe('&lt;p&gt;hello&lt;/p&gt;');
    });

    test('escapes a full XSS payload', () => {
        const result = escapeHtml('<script>alert(1)</script>');
        expect(result).toBe('&lt;script&gt;alert(1)&lt;/script&gt;');
        expect(result).not.toContain('<script>');
    });

    test('leaves double-quote characters as-is (text nodes do not encode quotes)', () => {
        // document.createTextNode only encodes &, <, > — not "
        expect(escapeHtml('"hello"')).toBe('"hello"');
    });

    test('escapes ampersand characters', () => {
        expect(escapeHtml('a & b')).toBe('a &amp; b');
    });

    test('leaves plain text unchanged', () => {
        expect(escapeHtml('Hello World')).toBe('Hello World');
    });

    test('coerces null to string without throwing', () => {
        expect(() => escapeHtml(null)).not.toThrow();
        expect(escapeHtml(null)).toBe('null');
    });

    test('coerces undefined to string without throwing', () => {
        expect(() => escapeHtml(undefined)).not.toThrow();
        expect(escapeHtml(undefined)).toBe('undefined');
    });

    test('coerces numbers to string', () => {
        expect(escapeHtml(42)).toBe('42');
    });

    test('does not double-encode already-safe text', () => {
        expect(escapeHtml('safe text')).toBe('safe text');
    });
});

// ---------------------------------------------------------------------------
// getStatusBadge()
// ---------------------------------------------------------------------------

describe('getStatusBadge()', () => {
    test('returns bg-success badge for published', () => {
        expect(getStatusBadge('published')).toBe('badge bg-success');
    });

    test('returns bg-warning text-dark badge for draft', () => {
        expect(getStatusBadge('draft')).toBe('badge bg-warning text-dark');
    });

    test('returns bg-secondary badge for archived', () => {
        expect(getStatusBadge('archived')).toBe('badge bg-secondary');
    });

    test('returns bg-light text-dark badge for unknown status', () => {
        expect(getStatusBadge('unknown')).toBe('badge bg-light text-dark');
    });

    test('returns bg-light text-dark badge for empty string', () => {
        expect(getStatusBadge('')).toBe('badge bg-light text-dark');
    });

    test('returns bg-light text-dark badge for undefined', () => {
        expect(getStatusBadge(undefined)).toBe('badge bg-light text-dark');
    });
});

// ---------------------------------------------------------------------------
// Debounce / IIFE integration — simulated DOM test
// ---------------------------------------------------------------------------

describe('Live search IIFE (debounce behaviour)', () => {
    beforeEach(() => {
        // Provide the DOM elements the IIFE requires
        document.body.innerHTML = `
            <input id="lessonPlanSearchInput" type="text" />
            <table id="lessonPlansTable">
                <tbody></tbody>
            </table>
        `;

        // Stub fetch so we control the response
        global.fetch = jest.fn().mockResolvedValue({
            json: jest.fn().mockResolvedValue({
                success: true,
                data: [
                    {
                        lesson_id:  1,
                        title:      'Algebra Basics',
                        subject:    'Mathematics',
                        grade_level: '8',
                        status:     'published',
                        updated_at: '2026-01-15 10:00:00',
                    },
                ],
            }),
        });

        // Load app.js AFTER the DOM is ready (re-evaluate each test)
        jest.resetModules();
        require('../../public/js/app.js');
    });

    afterEach(() => {
        jest.clearAllMocks();
        jest.useRealTimers();
    });

    test('fetch is not called before debounce delay elapses', () => {
        jest.useFakeTimers();
        const input = document.getElementById('lessonPlanSearchInput');
        input.value = 'algebra';
        input.dispatchEvent(new Event('input'));
        // No time elapsed — fetch should NOT have been called yet
        expect(fetch).not.toHaveBeenCalled();
        jest.useRealTimers();
    });

    test('fetch is called once after 300ms debounce', async () => {
        jest.useFakeTimers();
        const input = document.getElementById('lessonPlanSearchInput');

        // Simulate rapid typing
        for (const ch of ['a', 'l', 'g']) {
            input.value += ch;
            input.dispatchEvent(new Event('input'));
        }

        // Advance timer past the 300ms debounce window
        jest.advanceTimersByTime(350);

        // Allow all microtasks / promises to settle
        await Promise.resolve();

        expect(fetch).toHaveBeenCalledTimes(1);
        expect(fetch.mock.calls[0][0]).toContain('searchLessonPlans');
        jest.useRealTimers();
    });

    test('fetch URL contains encoded search term', async () => {
        jest.useFakeTimers();
        const input = document.getElementById('lessonPlanSearchInput');
        input.value = 'science & math';
        input.dispatchEvent(new Event('input'));
        jest.advanceTimersByTime(350);
        await Promise.resolve();

        const calledUrl = fetch.mock.calls[0][0];
        expect(calledUrl).toContain(encodeURIComponent('science & math'));
        jest.useRealTimers();
    });
});
