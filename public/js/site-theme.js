(() => {
    const storageKey = 'ecocycle-theme';
    const root = document.documentElement;

    function applyTheme(theme) {
        root.dataset.theme = theme;
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.setAttribute('aria-pressed', String(theme === 'dark'));
        });
    }

    try {
        applyTheme(localStorage.getItem(storageKey) === 'dark' ? 'dark' : 'light');
    } catch {
        applyTheme('light');
    }

    document.addEventListener('DOMContentLoaded', () => applyTheme(root.dataset.theme));
    document.addEventListener('click', event => {
        if (!event.target.closest('[data-theme-toggle]')) return;

        const theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        applyTheme(theme);

        try {
            localStorage.setItem(storageKey, theme);
        } catch {
            // Keep the chosen theme for this page when storage is unavailable.
        }
    });

    window.addEventListener('storage', event => {
        if (event.key === storageKey) applyTheme(event.newValue === 'dark' ? 'dark' : 'light');
    });
})();
