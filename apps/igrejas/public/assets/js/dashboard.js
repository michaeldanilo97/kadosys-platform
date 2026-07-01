(function () {
  'use strict';

  var THEME_STORAGE_KEY = 'kadosys_igrejas_theme';

  document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initSidebar();
  });

  function initTheme() {
    var body = document.body;
    var toggleBtn = document.querySelector('[data-theme-toggle]');
    var storedTheme = null;

    try {
      storedTheme = window.localStorage.getItem(THEME_STORAGE_KEY);
    } catch (error) {
      storedTheme = null;
    }

    var theme = storedTheme || 'light';
    applyTheme(body, theme);

    if (!toggleBtn) {
      return;
    }

    toggleBtn.addEventListener('click', function () {
      var current = body.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      var next = current === 'dark' ? 'light' : 'dark';

      applyTheme(body, next);

      try {
        window.localStorage.setItem(THEME_STORAGE_KEY, next);
      } catch (error) {
        // Armazenamento indisponivel (modo privado, etc.); ignora.
      }
    });
  }

  function applyTheme(body, theme) {
    if (theme === 'dark') {
      body.setAttribute('data-theme', 'dark');
    } else {
      body.removeAttribute('data-theme');
    }

    var icon = document.querySelector('[data-theme-icon]');
    if (icon) {
      icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }
  }

  function initSidebar() {
    var sidebar = document.querySelector('[data-dash-sidebar]');
    var overlay = document.querySelector('[data-sidebar-overlay]');
    var openBtn = document.querySelector('[data-sidebar-open]');

    if (!sidebar || !overlay || !openBtn) {
      return;
    }

    function open() {
      sidebar.classList.add('show');
      overlay.classList.add('show');
    }

    function close() {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    }

    openBtn.addEventListener('click', open);
    overlay.addEventListener('click', close);
  }
})();
