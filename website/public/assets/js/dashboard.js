/**
 * =============================================================================
 * TIREDOFDOINTM - Dashboard JavaScript
 * =============================================================================
 */

(function() {
    'use strict';

    // Sidebar toggle for mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');

    sidebarToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            if (!sidebar?.contains(e.target) && !sidebarToggle?.contains(e.target)) {
                sidebar?.classList.remove('open');
            }
        }
    });

    // Initialize any charts or additional dashboard features here

})();
