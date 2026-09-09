/**
 * AulaCode - JavaScript base (Fase 1)
 */
(function () {
    'use strict';

    function initSidebar() {
        var toggle = document.getElementById('menuToggle');
        var closeBtn = document.getElementById('sidebarClose');
        var sidebar = document.getElementById('sidebar');

        if (!sidebar) {
            return;
        }

        function openSidebar() {
            sidebar.classList.add('is-open');
        }

        function closeSidebar() {
            sidebar.classList.remove('is-open');
        }

        if (toggle) {
            toggle.addEventListener('click', openSidebar);
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initSidebar);
})();
