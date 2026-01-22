/**
 * =============================================================================
 * TIREDOFDOINTM - Gate System ("67")
 * =============================================================================
 * Simple password gate for soft launch access
 * =============================================================================
 */

(function() {
    'use strict';

    const GATE_URL = '/api/gate/verify';

    // DOM Elements
    let overlay, input;

    /**
     * Initialize gate
     */
    function init() {
        overlay = document.querySelector('.gate-overlay');
        input = document.querySelector('.gate-input');

        if (!overlay || !input) return;

        // Focus input when visible
        setTimeout(() => {
            input.focus();
        }, 1500); // After panel slide-up animation

        // Handle input
        input.addEventListener('input', handleInput);
        input.addEventListener('keydown', handleKeydown);

        // Prevent paste of more than expected length
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            input.value = paste.substring(0, 10).replace(/\D/g, '');
            checkPassword();
        });
    }

    /**
     * Handle input changes
     */
    function handleInput(e) {
        // Only allow numbers
        input.value = input.value.replace(/\D/g, '');

        // Clear error state on new input
        input.classList.remove('error');

        // Auto-submit when length matches expected
        if (input.value.length >= 2) {
            checkPassword();
        }
    }

    /**
     * Handle keydown
     */
    function handleKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            checkPassword();
        }
    }

    /**
     * Check password via API
     */
    async function checkPassword() {
        const password = input.value;

        if (!password) {
            showError();
            return;
        }

        try {
            const response = await fetch(GATE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ password }),
            });

            const data = await response.json();

            if (data.success) {
                showSuccess();
            } else {
                showError();
            }
        } catch (error) {
            console.error('Gate verification failed:', error);
            showError();
        }
    }

    /**
     * Show error state
     */
    function showError() {
        input.classList.add('error');
        input.value = '';
        input.focus();

        // Remove error class after animation
        setTimeout(() => {
            input.classList.remove('error');
        }, 500);
    }

    /**
     * Show success and remove gate
     */
    function showSuccess() {
        input.classList.add('success');
        overlay.classList.add('success');

        // Remove overlay after animation
        setTimeout(() => {
            overlay.remove();
        }, 500);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
