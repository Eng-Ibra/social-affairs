(function () {
    'use strict';

    // Theme (light/dark) toggle, persisted per-browser.
    var root = document.documentElement;
    var THEME_KEY = 'sas_theme';

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        try { localStorage.setItem(THEME_KEY, theme); } catch (e) {}
        var icon = document.getElementById('themeIcon');
        if (icon) {
            icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var stored = null;
        try { stored = localStorage.getItem(THEME_KEY); } catch (e) {}
        if (stored) applyTheme(stored);

        var toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var current = root.getAttribute('data-theme') || 'light';
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        }

        var sidebarToggle = document.getElementById('sidebarToggle');
        var sidebar = document.getElementById('appSidebar');
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
            });
            document.addEventListener('click', function (e) {
                if (window.innerWidth < 992 && sidebar.classList.contains('show') &&
                    !sidebar.contains(e.target) && e.target !== sidebarToggle && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            });
        }

        // Confirmation dialogs for destructive actions.
        document.querySelectorAll('[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    e.preventDefault();
                }
            });
        });

        // Auto-dismiss alerts.
        document.querySelectorAll('.alert[data-autohide]').forEach(function (alertEl) {
            setTimeout(function () {
                var alert = bootstrap.Alert.getOrCreateInstance(alertEl);
                alert.close();
            }, 5000);
        });

        // Notification bell: mark read via AJAX.
        document.querySelectorAll('.notif-mark-read').forEach(function (el) {
            el.addEventListener('click', function (evt) {
                var id = el.getAttribute('data-id');
                fetch(el.getAttribute('data-url'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: '_token=' + encodeURIComponent(window.SAS_CSRF || '')
                });
            });
        });
    });
})();
