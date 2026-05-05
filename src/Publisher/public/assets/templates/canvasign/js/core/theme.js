/* Theme toggle: persists in localStorage, fires "themechange" event */
(function () {
  const KEY = 'theme';
  const stored = localStorage.getItem(KEY) || 'dark';
  document.documentElement.setAttribute('data-bs-theme', stored);

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem(KEY, theme);
    document.querySelectorAll('[data-theme-icon]').forEach(el => {
      el.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    });
    window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
  }

  document.addEventListener('DOMContentLoaded', () => {
    applyTheme(localStorage.getItem(KEY) || 'dark');
    document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
      btn.addEventListener('click', () => {
        const cur = document.documentElement.getAttribute('data-bs-theme');
        applyTheme(cur === 'dark' ? 'light' : 'dark');
      });
    });
  });

  window.getTheme = () => document.documentElement.getAttribute('data-bs-theme');
})();
