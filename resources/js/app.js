import './bootstrap';

const THEME_STORAGE_KEY = 'theme-preference';

const getStoredTheme = () => {
    try {
        return localStorage.getItem(THEME_STORAGE_KEY);
    } catch (error) {
        console.error('Unable to read theme preference from localStorage:', error);
        return null;
    }
};

const storeTheme = (value) => {
    try {
        localStorage.setItem(THEME_STORAGE_KEY, value);
    } catch (error) {
        console.error('Unable to persist theme preference to localStorage:', error);
    }
};

const applyTheme = (theme) => {
    const classList = document.documentElement.classList;
    if (theme === 'dark') {
        classList.add('dark');
    } else {
        classList.remove('dark');
    }
};

const syncToggleButton = (button, theme) => {
    if (!button) {
        return;
    }

    const iconLight = button.querySelector('[data-theme-icon="light"]');
    const iconDark = button.querySelector('[data-theme-icon="dark"]');

    if (iconLight) {
        iconLight.classList.toggle('hidden', theme === 'dark');
    }

    if (iconDark) {
        iconDark.classList.toggle('hidden', theme !== 'dark');
    }

    button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    button.setAttribute('title', theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
};

document.addEventListener('DOMContentLoaded', () => {
    const themeToggles = document.querySelectorAll('[data-theme-toggle]');
    if (!themeToggles.length) {
        return;
    }

    const mediaQuery = window.matchMedia?.('(prefers-color-scheme: dark)');
    const storedTheme = getStoredTheme();
    const initialTheme = storedTheme ?? (mediaQuery?.matches ? 'dark' : 'light');

    applyTheme(initialTheme);
    themeToggles.forEach((button) => syncToggleButton(button, initialTheme));

    const handleMediaChange = (event) => {
        const preference = getStoredTheme();
        if (preference) {
            return;
        }

        const nextTheme = event.matches ? 'dark' : 'light';
        applyTheme(nextTheme);
        themeToggles.forEach((button) => syncToggleButton(button, nextTheme));
    };

    if (mediaQuery?.addEventListener) {
        mediaQuery.addEventListener('change', handleMediaChange);
    } else if (mediaQuery?.addListener) {
        mediaQuery.addListener(handleMediaChange);
    }

    themeToggles.forEach((button) => {
        button.addEventListener('click', () => {
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

            applyTheme(nextTheme);
            storeTheme(nextTheme);
            themeToggles.forEach((toggle) => syncToggleButton(toggle, nextTheme));
        });
    });
});
